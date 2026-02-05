<?php

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../PhpMarc/USMARC.php');

class USMARCTest extends TestCase
{
    /**
     * @covers \PhpMarc\USMARC::__construct
     */
  public function testCreateUSMARCResultsInNewObject()
  {
    $USMARCobj = new \PhpMarc\USMARC();
    $this->assertInstanceOf('\PhpMarc\USMARC', $USMARCobj);
  }
}