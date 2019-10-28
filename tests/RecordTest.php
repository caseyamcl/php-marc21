<?php

namespace PhpMarc;

use PHPUnit\Framework\TestCase;

class RecordTest extends TestCase
{
    public function testCreateRecordResultsInNewObject()
    {
        $this->assertInstanceOf(Record::class, new Record());
    }
}
