<?php

declare(strict_types=1);

namespace Brick\Http\Tests;

use Brick\Http\Path;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class Path.
 */
class PathTest extends TestCase
{
    protected $path;

    public function setUp(): void
    {
        $this->path = new Path('/path1/path2');
    }

    public function testGetParts(): void
    {
        $result = $this->path->getParts();

        $this->assertCount(2, $result);
        $this->assertSame('path1', $result[0]);
        $this->assertSame('path2', $result[1]);
    }

    public function testContains(): void
    {
        $this->assertTrue($this->path->contains('path1'));
        $this->assertFalse($this->path->contains('path3'));
    }

    public function testStartsWith(): void
    {
        $this->assertTrue($this->path->startsWith('/path1'));
        $this->assertFalse($this->path->startsWith('/path3'));
    }

    public function testEndsWith(): void
    {
        $this->assertTrue($this->path->endsWith('/path2'));
        $this->assertFalse($this->path->endsWith('/path3'));
    }
}
