<?php

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../PhpMarc/Field.php');

class FieldTest extends TestCase
{
    /**
     * @covers \PhpMarc\Field::__construct
     */
  public function testCreateFieldResultsInNewObject() {

    $field = new \PhpMarc\Field();
    $this->assertInstanceOf('\PhpMarc\Field', $field);
  }
  
}
