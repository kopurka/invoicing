<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Entity\Currency;

class CurrencyTest extends TestCase
{
    public function testCreateCurrencyBadCode()
    {
        $code = '123456';       
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid currency code ('.$code.').');
        $currency = new Currency($code,1);
    }

    public function testCreateCurrencyBadRate()
    {
        $rate = -1.24;       
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Try to set negative value for the currency rate.');
        $currency = new Currency('EUR',$rate);
    }    

    public function testCreateCurrencyBadRateType()
    {
        $rate = "bad value";       
        $this->expectException(\TypeError::class);
        $currency = new Currency('EUR',$rate);
    }       
}
