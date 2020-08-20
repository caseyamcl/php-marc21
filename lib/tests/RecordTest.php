<?php

require_once(__DIR__ . '/../PhpMarc/Record.php');

class RecordTest extends PHPUnit_Framework_TestCase {
  
  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }  
  
  /*
   * Tests
   */
  
  public function testCreateRecordResultsInNewObject() {

    $Recordobj = new \PhpMarc\Record();
    $this->assertInstanceOf('\PhpMarc\Record', $Recordobj);
  }
  
}


/* EOF: RecordTest.php */