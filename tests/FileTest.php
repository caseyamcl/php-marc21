<?php

namespace PhpMarc;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testCreateFileResultsInNewObject()
    {
        $this->assertInstanceOf(File::class, (new File()));
    }
}
