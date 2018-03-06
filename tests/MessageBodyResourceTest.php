<?php

namespace Brick\Http\Tests;

use Brick\Http\MessageBodyResource;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class MessageBodyResource.
 */
class MessageBodyResourceTest extends TestCase
{
    /**
     * @var MessageBodyResource
     */
    protected $messageBodyResource;

    public function setUp()
    {
        $fp = fopen('php://memory', 'rb+');

        fwrite($fp, 'data');
        fseek($fp, 0);

        $this->messageBodyResource = new MessageBodyResource($fp);
    }

    public function testRead()
    {
        $this->assertSame('da', $this->messageBodyResource->read(2));
        $this->assertSame('ta', $this->messageBodyResource->read(1024));
    }

    public function testGetSize()
    {
        $this->assertSame(4, $this->messageBodyResource->getSize());
    }
}
