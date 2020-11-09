<?php

declare(strict_types=1);

namespace Brick\Http\Tests;

use Brick\Http\UploadedFile;

use PHPUnit\Framework\TestCase;

/**
 * Tests for class UploadedFile.
 */
class UploadedFileTest extends TestCase
{
    protected UploadedFile $uploadedFile;

    public static function createSampleUploadedFile() : UploadedFile
    {
        return UploadedFile::create([
            'tmp_name' => 'uploaded_temp_file.txt',
            'name' => 'uploaded_file.txt',
            'type' => 'txt',
            'size' => 1024,
            'error' => 0,
        ]);
    }

    protected function setUp(): void
    {
        $this->uploadedFile = self::createSampleUploadedFile();
    }

    public function testGetExtension(): void
    {
        $this->assertSame('txt', $this->uploadedFile->getExtension());
    }

    public function testGetPath(): void
    {
        $this->assertSame('uploaded_temp_file.txt', $this->uploadedFile->getPath());
    }

    public function testGetName(): void
    {
        $this->assertSame('uploaded_file.txt', $this->uploadedFile->getName());
    }

    public function testGetType()
    {
        $this->assertSame('txt', $this->uploadedFile->getType());
    }

    public function testGetSize()
    {
        $this->assertSame(1024, $this->uploadedFile->getSize());
    }

    public function testGetStatus()
    {
        $this->assertSame(0, $this->uploadedFile->getStatus());
    }

    public function testIsValid(): void
    {
        $uploadedFile = UploadedFile::create([
            'tmp_name' => 'uploaded_temp_file.txt',
            'name' => 'uploaded_file.txt',
            'type' => 'txt',
            'size' => 1024,
            'error' => 1,
        ]);

        $this->assertTrue($this->uploadedFile->isValid());
        $this->assertFalse($uploadedFile->isValid());
    }

    public function testIsSelected(): void
    {
        $uploadedFile = UploadedFile::create([
            'tmp_name' => 'uploaded_temp_file.txt',
            'name' => 'uploaded_file.txt',
            'type' => 'txt',
            'size' => 1024,
            'error' => 4,
        ]);

        $this->assertTrue($this->uploadedFile->isSelected());
        $this->assertFalse($uploadedFile->isSelected());
    }
}
