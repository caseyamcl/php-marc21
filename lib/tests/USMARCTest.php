<?php

require_once(__DIR__ . '/../PhpMarc/USMARC.php');

class USMARCTest extends PHPUnit_Framework_TestCase {
  
  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }  
  
  /*
   * Tests
   */
  
  public function testCreateUSMARCResultsInNewObject() {

    $USMARCobj = new \PhpMarc\USMARC();
    $this->assertInstanceOf('\PhpMarc\USMARC', $USMARCobj);
  }
  
}

/* USMARCTest.php */