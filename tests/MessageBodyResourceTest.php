<?php

namespace Brick\Http\Tests;

use Brick\Http\MessageBodyResource;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class Path.
 */
class MessageBodyResourceTest extends TestCase
{
    protected $messageBodyResource;

    public function setUp()
    {
        $this->messageBodyResource = new MessageBodyResource(fopen('php://input', 'rb'));
    }

    public function testRead()
    {
        $this->assertSame('', $this->messageBodyResource->read(1));
    }

    public function testGetSizeShouldReturnZero()
    {
        $this->assertSame(0, $this->messageBodyResource->getSize());
    }

    public function testClassInstanceShouldReturnString()
    {
        $messageBodyResource = new MessageBodyResource(fopen('php://input', 'rb'));
        $this->assertEquals('', $messageBodyResource);
    }
}
