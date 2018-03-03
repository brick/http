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
        $resultTrue = $this->path->contains('path1');
        $resultFalse = $this->path->contains('path3');

        $this->assertTrue($resultTrue);
        $this->assertFalse($resultFalse);
    }

    public function testStartsWith()
    {
        $resultTrue = $this->path->startsWith('/path1');
        $resultFalse = $this->path->startsWith('/path3');

        $this->assertTrue($resultTrue);
        $this->assertFalse($resultFalse);
    }

    public function testEndsWith()
    {
        $resultTrue = $this->path->endsWith('/path2');
        $resultFalse = $this->path->endsWith('/path3');

        $this->assertTrue($resultTrue);
        $this->assertFalse($resultFalse);
    }
}
