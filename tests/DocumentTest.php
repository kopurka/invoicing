<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Entity\Document;

class DocumentTest extends TestCase
{
    public function testCreateDocumentBadNumberType()
    {
        $number = "bad value";   
        $type = 1;
        $total = 2.35;    
        $this->expectException(\TypeError::class);
        $document = new Document($number,$type,$total);
    }    
    
    public function testCreateDocumentBadTypeType()
    {
        $number = 123414;   
        $type = 'a';
        $total = 2.35;    
        $this->expectException(\TypeError::class);
        $document = new Document($number,$type,$total);
    } 
    
    public function testCreateDocumentBadTotalType()
    {
        $number = 123414;
        $type = 1;
        $total = 'fun';    
        $this->expectException(\TypeError::class);
        $document = new Document($number,$type,$total);
    }  
    
    public function testCreateCurrencyBadNumber()
    {
        $number = -12323;  
        $type = 1;
        $total = 2.35;                
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Try to set negative/zero value for the document number.');
        $document = new Document($number,$type,$total);
    }
    
    public function testCreateCurrencyUnkmownType()
    {
        $number = 12323;  
        $type = 7;
        $total = 2.35;                
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Try to set unknown document type.');
        $document = new Document($number,$type,$total);
    }
    
    public function testCreateCurrencyBadTotal()
    {
        $number = 12323;  
        $type = 1;
        $total = -2.35;        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Try to set negative/zero value for the document total.');
        $document = new Document($number,$type,$total);
    }    
}
