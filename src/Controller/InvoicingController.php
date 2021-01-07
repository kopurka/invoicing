<?php

namespace App\Controller;

use App\Form\InitialDataFormType;
use App\Repository\CurrencyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Entity\Currency;
use App\Entity\Customer;
use App\Entity\Document;

class InvoicingController extends AbstractController
{
    
    private $main_currency = null;
    private $output_currency = null; 
    private $errors; 
    
    /**
     * @Route("/", name="homepage")
     */
    public function index(Request $request, CurrencyRepository $currencyRepository): Response
    {
        $form = $this->createForm(InitialDataFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            ///init in memory database tables
            $this->initDatabase();

            //set exchange rates
            $exchange_rates = $form->get('exchange_rates')->getData();
            if (!$this->isJSON($exchange_rates)) {
                return $this->throwError('Not valid exchange rates provided.');              
            }
            $this->setCurrencies($exchange_rates);           

            //set the output currency
            $output_currency = $form->get('output_currency')->getData();
            if (strlen($output_currency)!=3) {
                return $this->throwError('Not found output currency.');   
            }
            $this->setOutputCurrency($output_currency);

            //set the documents/invoices data
            $csv_file = $form->get('csv')->getData();
            if (!$csv_file) {
                return $this->throwError('Please upload a valid CSV file');
            }
            $csv = Reader::createFromPath($csv_file, 'r')->setHeaderOffset(0);
            if (count($csv)>0 && $this->validCSVFormat($csv->getHeader())) {
                $this->setData($csv);
            } else {
                return $this->throwError('Not valid CSV format data provided or empty data set.'); 
            }

            $results = [];
            if ($this->output_currency && $this->main_currency) {
                //get customers for result
                $entityManager = $this->getDoctrine()->getManager();
                $customerRepository = $entityManager->getRepository(Customer::class);
                $vat = $form->get('vat')->getData();
                $customer_name = $form->get('customer_name')->getData();
                if ($vat) {
                    //only vat
                    $customers = [$customerRepository->findOneByVAT($vat)];
                } elseif ($customer_name) {
                    //only customer name
                    $customers = [$customerRepository->findOneByName($customer_name)];
                } else {
                    //no search criteria
                    $customers = $customerRepository->findAll();
                } 
                //iterate all customers for due calculation
                if ($customers) {
                    foreach($customers AS $customer) {
                        $errors = [];
                        $results[] = ["name" => $customer->getName(), "vat" => $customer->getVat(), "due" => $customer->getDue($this->output_currency,$errors)];
                        if ($errors) {
                            foreach($errors as $error) {
                                $this->errors[] = $error;
                            }
                        }
                    }
                }
            } else {
                $this->errors[] = 'Bad currency data provided.';
            }          

            return $this->render('invoicing/result.html.twig', [
                'results' => $results,
                'output_currency' => $this->output_currency,
                'errors' => $this->errors
            ]);
        }

        return $this->render('invoicing/index.html.twig', [
            'data_form' => $form->createView(),
        ]);
    }

    private function setCurrencies(string $data): bool
    {
        $entityManager = $this->getDoctrine()->getManager();
        $currencyRepository = $entityManager->getRepository(Currency::class);
        $input_currency_data = json_decode($data,true);

        foreach ($input_currency_data as $currency_data) {
            if (!$currencyRepository->findOneByCode($currency_data["code"])) {              
                $currency = new Currency($currency_data["code"],$currency_data["rate"]);
                $entityManager->persist($currency);
                $entityManager->flush();
                if ($currency_data["rate"] == 1) $this->main_currency = $currency;
            } else {
                $this->errors[] = 'Duplicate entry for currency ('.$currency_data["code"].') in currency table.';
            }
        }

        return true;
    }

    private function setOutputCurrency(string $currency): bool
    {
        $entityManager = $this->getDoctrine()->getManager();
        $currency_code = strtoupper($currency);
        $output_currency = $entityManager->getRepository(Currency::class)->findOneByCode($currency_code);

        if ($output_currency) {
            //currency found in exchange rates provided
            $this->output_currency = $output_currency;
            return true;
        } else {
            //not found 
            $this->errors[] = 'Not found output currency ('.$currency.') in currency table.';
            return false;
        }
    }

    private function setData(Reader $csv): bool 
    {
        $entityManager = $this->getDoctrine()->getManager();
        $customerRepositroy = $entityManager->getRepository(Customer::class);
        $documentRepository = $entityManager->getRepository(Document::class);
        $currencyRepository = $entityManager->getRepository(Currency::class);

        foreach($csv as $record) {
            ///iterrate all records, first check for duplicate document number            
            if (!$documentRepository->findOneByNumber($record["Document number"])) {
                //check if currency persist
                $document_currency = $currencyRepository->findOneByCode(strtoupper($record["Currency"]));                
                if ($document_currency) {
                    //check if parent document persist
                    if ($record["Parent document"]) {
                        $parent_document = $documentRepository->findOneByNumber($record["Parent document"]);
                        if (!$parent_document) {
                            $this->errors[] = 'Not found parent number '.$record["Parent document"].' for document number ('.$record["Document number"].'). Skip entry.';
                            continue;
                        }
                    }
                    //adds customer, skip duplicates by vat
                    $customer = $customerRepositroy->findOneByVAT($record["Vat number"]);
                    if (!$customer) {
                        $customer = new Customer($record["Customer"],$record["Vat number"]);
                        $entityManager->persist($customer);
                        $entityManager->flush(); 
                    }

                    //creates document
                    $document = new Document(intval($record["Document number"]),intval($record["Type"]),floatval($record["Total"]));
                    $document->setCustomer($customer);
                    $document->setCurrency($document_currency);
                    if ($record["Parent document"]) {
                        $document->setParent($parent_document);
                    }
                    $entityManager->persist($document);
                    $entityManager->flush(); 
                    //adds document to customer
                    if ($record["Parent document"]) {
                        $parent_document->addRelatedDocument($document);
                        $entityManager->persist($parent_document);
                    }
                    $customer->addDocument($document);
                    $entityManager->persist($customer);
                    $entityManager->flush();                     
                } else {
                    $this->errors[] = 'Unknown currency '.$record["Currency"].' for document number '.$record["Document number"].'. Skip entry.';
                }
            } else {
                $this->errors[] = 'Duplicate entry for document number ('.$record["Document number"].'). Skip entry.';
            }           
        }

        return true;
    }

    private function initDatabase():bool 
    {
        $entityManager = $this->getDoctrine()->getManager();
        $conn = $entityManager->getConnection();
        $sql = 'CREATE TABLE currency (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(3) NOT NULL UNIQUE, rate DOUBLE PRECISION NOT NULL)';
        $conn->executeQuery($sql);
        $sql = 'CREATE TABLE customer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, vat VARCHAR(20) NOT NULL UNIQUE)';
        $conn->executeQuery($sql);
        $sql = 'CREATE TABLE document (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, currency_id INTEGER NOT NULL, parent_id INTEGER DEFAULT NULL, number BIGINT NOT NULL UNIQUE, type SMALLINT NOT NULL, total DOUBLE PRECISION NOT NULL)';
        $conn->executeQuery($sql);
        $sql = 'CREATE INDEX IDX_D8698A769395C3F3 ON document (customer_id)';
        $conn->executeQuery($sql);
        $sql = 'CREATE INDEX IDX_D8698A7638248176 ON document (currency_id)';
        $conn->executeQuery($sql);
        $sql = 'CREATE INDEX IDX_D8698A76727ACA70 ON document (parent_id)';
        $conn->executeQuery($sql);
        return true;
    }

    private function isJSON($string):bool
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }    

    private function throwError(string $error): Response
    {
        $this->errors[] = $error;
        return $this->render('invoicing/result.html.twig', [
             'results' => [],
             'output_currency' => '',
             'errors' => $this->errors
         ]);         
    }

    private function validCSVFormat(array $header): bool
    {
        return count($header)==7 && in_array("Customer",$header) && in_array("Vat number",$header) && in_array("Document number",$header) 
            && in_array("Type",$header) && in_array("Parent document",$header)
            && in_array("Currency",$header) && in_array("Total",$header);
    }
}
