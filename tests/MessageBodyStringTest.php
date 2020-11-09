<?php

declare(strict_types=1);

namespace Brick\Http\Tests;

use Brick\Http\MessageBodyString;

use PHPUnit\Framework\TestCase;

/**
 * Tests for class MessageBodyString.
 */
class MessageBodyStringTest extends TestCase
{
    public function testRead(): void
    {
        $messageBodyString = new MessageBodyString('<!DOCTYPE html><html lang="en"><body></body></html>');

        $this->assertSame('<!DOCTYPE html>', $messageBodyString->read(15));
        $this->assertSame('<html lang="en">', $messageBodyString->read(16));
        $this->assertSame('<body></body></html>', $messageBodyString->read(1024));

        $this->assertSame(51, $messageBodyString->getSize());
    }
}
