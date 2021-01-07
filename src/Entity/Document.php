<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Currency;

/**
 * @ORM\Entity(repositoryClass=DocumentRepository::class)
 */
class Document
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $number;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="float")
     */
    private $total;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="documents")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @ORM\ManyToOne(targetEntity=Currency::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\ManyToOne(targetEntity=Document::class, inversedBy="relatedDocuments")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=Document::class, mappedBy="parent")
     */
    private $relatedDocuments;

    public function __construct(int $number,int $type,float $total)
    {
        $this->relatedDocuments = new ArrayCollection();
        $this->setNumber($number);
        $this->setType($type);
        $this->setTotal($total);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        if ($number<=0) {
            throw new \RuntimeException('Try to set negative/zero value for the document number.');
        }    

        $this->number = $number;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        if (!in_array($type,[1,2,3])) {
            throw new \RuntimeException('Try to set unknown document type.');
        } 

        $this->type = $type;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        if ($total<=0) {
            throw new \RuntimeException('Try to set negative/zero value for the document total.');
        }  

        $this->total = $total;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getRelatedDocuments(): Collection
    {
        return $this->relatedDocuments;
    }

    public function addRelatedDocument(self $relatedDocument): self
    {
        if (!$this->relatedDocuments->contains($relatedDocument)) {
            $this->relatedDocuments[] = $relatedDocument;
            $relatedDocument->setParent($this);
        }

        return $this;
    }

    public function removeRelatedDocument(self $relatedDocument): self
    {
        if ($this->relatedDocuments->removeElement($relatedDocument)) {
            // set the owning side to null (unless already changed)
            if ($relatedDocument->getParent() === $this) {
                $relatedDocument->setParent(null);
            }
        }

        return $this;
    }

    public function getTotalInCurrency(Currency $currency):float 
    {
        return $this->total / $this->getCurrency()->getRate() * $currency->getRate();
    }

    public function calcDue(Currency $currency): float 
    {
        $return_value = $this->getTotalInCurrency($currency);
        $related = $this->getRelatedDocuments();
        if ($related) {
            foreach($related as $document) {
                if ($document->getType()==2) $return_value -= $document->getTotalInCurrency($currency);
                if ($document->getType()==3) $return_value += $document->getTotalInCurrency($currency);
            }
        }
        return $return_value;
    }
}
