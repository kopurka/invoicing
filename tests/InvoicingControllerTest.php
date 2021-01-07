<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InvoicingControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Hello! Lets sum some invoice documents!');        
    }

    public function testPostingEmptyForm()
    {     
        $client = static::createClient();
        $client->request('POST', '/');
        $client->submitForm('Submit', [
           'initial_data_form[exchange_rates]' => '',
            'initial_data_form[output_currency]' => '',
            'initial_data_form[customer_name]' => '',
            'initial_data_form[vat]' => '',
            'initial_data_form[csv]' => dirname(__DIR__).'/test.csv',
        ]); 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('div:contains("Not valid exchange rates provided.")'); 
    }

    public function testPostingFormWithoutOutputCurrency()
    {     
        $client = static::createClient();
        $client->request('POST', '/');
        $client->submitForm('Submit', [
           'initial_data_form[exchange_rates]' => '[{"code":"EUR","rate":1},{"code":"USD","rate":1.3},{"code":"GBP","rate":0.75}]',
            'initial_data_form[output_currency]' => '',
            'initial_data_form[customer_name]' => '',
            'initial_data_form[vat]' => '',
            'initial_data_form[csv]' => dirname(__DIR__).'/test.csv',
        ]); 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('div:contains("Not found output currency")'); 
    } 
    
    public function testPostingFormNotCSVFile()
    {     
        $client = static::createClient();
        $client->request('POST', '/');
        $client->submitForm('Submit', [
           'initial_data_form[exchange_rates]' => '[{"code":"EUR","rate":1},{"code":"USD","rate":1.3},{"code":"GBP","rate":0.75}]',
            'initial_data_form[output_currency]' => 'EUR',
            'initial_data_form[customer_name]' => '',
            'initial_data_form[vat]' => '',
            'initial_data_form[csv]' => dirname(__DIR__).'/pic.png',
        ]); 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('div:contains("Please upload a valid CSV file")'); 
    }    

    public function testPostingFormNotValidCSV()
    {     
        $data = new UploadedFile(dirname(__DIR__).'/bad_format.csv', 'bad_format.csv', 'text/csv', 123);
        $client = static::createClient();
        $client->request('POST', '/');
        $client->submitForm('Submit', [
           'initial_data_form[exchange_rates]' => '[{"code":"EUR","rate":1},{"code":"USD","rate":1.3},{"code":"GBP","rate":0.75}]',
            'initial_data_form[output_currency]' => 'EUR',
            'initial_data_form[customer_name]' => '',
            'initial_data_form[vat]' => '',
        ],['initial_data_form[csv]' => $data]); 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('div:contains("Not valid CSV format data provided or empty data set")'); 
        
    }  
    
    public function testPostingValidForm()
    {     
        $client = static::createClient();
        $client->request('POST', '/');
        $client->submitForm('Submit', [
           'initial_data_form[exchange_rates]' => '[{"code":"EUR","rate":1},{"code":"USD","rate":1.3},{"code":"GBP","rate":0.75}]',
            'initial_data_form[output_currency]' => 'EUR',
            'initial_data_form[customer_name]' => '',
            'initial_data_form[vat]' => '',
            'initial_data_form[csv]' => 'test.csv',
        ]); 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('div:contains("Results")'); 
    }     
}
