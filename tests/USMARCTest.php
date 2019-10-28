<?php

namespace PhpMarc;


use PHPUnit\Framework\TestCase;

class USMARCTest extends TestCase
{
    public function testCreateUSMARCResultsInNewObject()
    {
        $this->assertInstanceOf(USMARC::class, (new USMARC()));
    }
}
