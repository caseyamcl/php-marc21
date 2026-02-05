<?php

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../PhpMarc/Record.php');

class RecordTest extends TestCase
{
    /**
     * @covers \PhpMarc\Record::__construct
     */
  public function testCreateRecordResultsInNewObject() {

    $Recordobj = new \PhpMarc\Record();
    $this->assertInstanceOf('\PhpMarc\Record', $Recordobj);
  }
}
