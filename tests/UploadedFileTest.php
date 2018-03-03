<?php

namespace Brick\Http\Tests;

use Brick\Http\UploadedFile;

use PHPUnit\Framework\TestCase;

/**
 * Tests for class UploadedFile.
 */
class UploadedFileTest extends TestCase
{
    protected $uploadedFile;

    protected function setUp()
    {
        $this->uploadedFile = UploadedFile::create([
            'tmp_name' => 'uploaded_temp_file.txt',
            'name' => 'uploaded_file.txt',
            'type' => 'txt',
            'size' => 1024,
            'error' => 0,
        ]);
    }

    public function testGetExtension()
    {
        $this->assertSame('txt', $this->uploadedFile->getExtension());
    }

    public function testIsValid()
    {
        $this->assertTrue($this->uploadedFile->isValid());
    }

    public function testIsSelected()
    {
        $this->assertTrue($this->uploadedFile->isSelected());
    }
}
