<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CurrencyRepository::class)
 * @UniqueEntity("code")
 */
class Currency
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=3)
     * @Assert\Length(
     *      min = 3,
     *      max = 3,
     *      minMessage = "Currency code must be 3 characters long",
     *      maxMessage = "Currency code must be 3 characters long"
     * )
     */
    private $code;

    /**
     * @ORM\Column(type="float")
     * @Assert\GreaterThan(
     *     value = 0,
     *     message = "Currency rate must be positive"
     * )
     */
    private $rate;

    public function __construct(string $code, float $rate)
    {
        $this->setCode($code);
        $this->setRate($rate);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
    * @return self
    *
    * @throws \RuntimeException if the code is not 3 symbols
    */
    public function setCode(string $code): self
    {
        if (strlen($code)!=3) {
            throw new \RuntimeException(sprintf('Invalid currency code (%s).',$code));
        }        
        
        $this->code = strtoupper($code);

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    /**
    * @return self
    *
    * @throws \RuntimeException if the rate is not greater than 0
    */
    public function setRate(float $rate): self
    {
        if ($rate<=0) {
            throw new \RuntimeException('Try to set negative value for the currency rate.');
        }

        $this->rate = $rate;

        return $this;
    }
}
