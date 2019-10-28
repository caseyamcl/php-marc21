<?php

namespace PhpMarc;

use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public function testCreateFieldResultsInNewObject()
    {
        $this->assertInstanceOf(Field::class, (new Field()));
    }
}
