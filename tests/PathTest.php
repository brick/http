<?php

namespace Brick\Http\Tests;

use Brick\Http\Path;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class Path.
 */
class PathTest extends TestCase
{
    protected $path;

    public function setUp()
    {
        $this->path = new Path('/path1/path2');
    }

    public function testGetParts()
    {
        $result = $this->path->getParts();

        $this->assertCount(2, $result);
        $this->assertSame('path1', $result[0]);
        $this->assertSame('path2', $result[1]);
    }

    public function testContains()
    {
        $this->assertTrue($this->path->contains('path1'));
        $this->assertFalse($this->path->contains('path3'));
    }

    public function testStartsWith()
    {
        $this->assertTrue($this->path->startsWith('/path1'));
        $this->assertFalse($this->path->startsWith('/path3'));
    }

    public function testEndsWith()
    {
        $this->assertTrue($this->path->endsWith('/path2'));
        $this->assertFalse($this->path->endsWith('/path3'));
    }
}
