<?php

require_once(__DIR__ . '/../PhpMarc/File.php');

class FileTest extends PHPUnit_Framework_TestCase {
  
  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }  
  
  /*
   * Tests
   */
  
  public function testCreateFileResultsInNewObject() {

    $fileobj = new \PhpMarc\File();
    $this->assertInstanceOf('\PhpMarc\File', $fileobj);
  }
  
}

/* EOF: FileTest.php */