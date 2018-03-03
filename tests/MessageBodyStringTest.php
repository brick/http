<?php

namespace Brick\Http\Tests;

use Brick\Http\MessageBodyString;

use PHPUnit\Framework\TestCase;

/**
 * Tests for class MessageBodyString.
 */
class MessageBodyStringtTest extends TestCase
{
    public function testRead()
    {
        $messageBodyString = new MessageBodyString('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta http-equiv="X-UA-Compatible" content="ie=edge"><title>Document</title></head><body></body></html>');

        $this->assertSame('<!DOCTYPE html>', $messageBodyString->read(15));
    }
}
