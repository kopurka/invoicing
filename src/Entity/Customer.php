<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Document;
use App\Entity\Currency;

/**
 * @ORM\Entity(repositoryClass=CustomerRepository::class)
 * @UniqueEntity("vat")
 */
class Customer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(
     *      message = "Customer name can not be empty."
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 100,
     *      minMessage = "Customer name must be at least {{ limit }} characters long",
     *      maxMessage = "Customer name cannot be longer than {{ limit }} characters"
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank(
     *      message = "Customer VAT can not be empty."
     * )
     * @Assert\Length(
     *      min = 9,
     *      max = 20,
     *      minMessage = "Customer VAT must be at least {{ limit }} characters long",
     *      maxMessage = "Customer VAT cannot be longer than {{ limit }} characters"
     * )
     */
    private $vat;

    /**
     * @ORM\OneToMany(targetEntity=Document::class, mappedBy="customer")
     */
    private $documents;

    public function __construct(string $name, string $vat)
    {
        $this->documents = new ArrayCollection();
        $this->setName($name);
        $this->setVat($vat);        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        if (strlen($name)<2 || strlen($name)>100) {
            throw new \RuntimeException('Customer name must contain between 2 and 100 symbols');
        }
        
        $this->name = $name;

        return $this;
    }

    public function getVat(): ?string
    {
        return $this->vat;
    }

    public function setVat(string $vat): self
    {
        if (strlen($vat)<2 || strlen($vat)>20) {
            throw new \RuntimeException('Customer VAT must contain between 2 and 20 symbols');
        }

        $this->vat = $vat;

        return $this;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setCustomer($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getCustomer() === $this) {
                $document->setCustomer(null);
            }
        }

        return $this;
    }

    public function getInvoices(): array 
    {
        return array_filter($this->getDocuments()->toArray(), function($doc) {
            return $doc->getType() == 1;
        });
    }

    public function getDue(Currency $currency,array &$error): float 
    {
        $return_value = 0.0;

        foreach($this->getInvoices() as $invoice) {
            $invoice_due = $invoice->calcDue($currency);
            if ($invoice_due>=0) {
                $return_value += $invoice->calcDue($currency);
            } else {
                $error[] = 'Overpay for Invoice:'.$invoice->getNumber().'. All related documents skipped from calculation for customer '.$this->getName().'!';
            }
        }

        return $return_value;
    }
}
