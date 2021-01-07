<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Entity\Customer;

class CustomerTest extends TestCase
{
    public function testCreatingCustomerWithEmptyName()
    {
        $name = '';
        $vat = '123456787';
        $this->expectException(\RuntimeException::class);
        $customer = new Customer($name,$vat);        
    }

    public function testCreatingCustomerWithEmptyVAT()
    {
        $name = 'Test name';
        $vat = '';
        $this->expectException(\RuntimeException::class);
        $customer = new Customer($name,$vat);        
    } 
    
    public function testCreatingCustomerWithTooLongName()
    {
        $name = 'VeryLongNameStringMoreThen100SymbolsVeryLongNameStringMoreThen100SymbolsVeryLongNameStringMoreThen100SymbolsVeryLongNameStringMoreThen100Symbols';
        $vat = '123456787';
        $this->expectException(\RuntimeException::class);
        $customer = new Customer($name,$vat);        
    }  
    
    public function testCreatingCustomerWithTooLongVAT()
    {
        $name = 'Test name';
        $vat = '1412356t2387657812365756126';
        $this->expectException(\RuntimeException::class);
        $customer = new Customer($name,$vat);        
    }     
}
