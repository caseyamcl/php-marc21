<?php

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../PhpMarc/File.php');

class FileTest extends TestCase
{
    /**
     * @covers \PhpMarc\File::__construct
     */
  public function testCreateFileResultsInNewObject() {

    $fileobj = new \PhpMarc\File();
    $this->assertInstanceOf('\PhpMarc\File', $fileobj);
  }
  
}
