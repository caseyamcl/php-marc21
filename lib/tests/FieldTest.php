<?php

require_once(__DIR__ . '/../PhpMarc/Field.php');

class FieldTest extends PHPUnit_Framework_TestCase {
  
  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }  
  
  /*
   * Tests
   */
  
  public function testCreateFieldResultsInNewObject() {

    $field = new \PhpMarc\Field();
    $this->assertInstanceOf('\PhpMarc\Field', $field);
  }
  
}

/* EOF: FieldTest.php */