<?php

namespace Brick\Http;

use Psr\Http\Message\StreamInterface;

class MessageBodyResource implements StreamInterface
{
    /**
     * The resource, or null if it has been detached.
     *
     * @var resource|null
     */
    private $body;

    /**
     * Whether the underlying resource is seekable.
     *
     * @var bool
     */
    private $seekable = false;

    /**
     * Whether the underlying resource is readable.
     *
     * @var bool
     */
    private $readable = false;

    /**
     * Whether the underlying resource is writable.
     *
     * @var bool
     */
    private $writable = false;

    /**
     * The possible modes for readable streams.
     *
     * @var array
     */
    private $readModes = [
        'r', 'rb', 'rt',
    ];

    /**
     * The possible modes for writable streams.
     *
     * @var array
     */
    private $writeModes = [
        'w', 'wb', 'wt',
        'a', 'ab', 'at',
        'x', 'xb', 'xt',
        'c', 'cb', 'ct',
    ];

    /**
     * The possible modes for readable & writable streams.
     *
     * @var array
     */
    private $readWriteModes = [
        'r+', 'rb+', 'rt+', 'r+b', 'r+t',
        'w+', 'wb+', 'wt+', 'w+b', 'w+t',
        'a+', 'ab+', 'at+', 'a+b', 'a+t',
        'x+', 'xb+', 'xt+', 'x+b', 'x+t',
        'c+', 'cb+', 'ct+', 'c+b', 'c+t',
    ];

    /**
     * @param resource $body
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($body)
    {
        if (! is_resource($body)) {
            throw new \InvalidArgumentException('Expected resource, got ' . gettype($body));
        }

        $this->body = $body;

        $metadata = stream_get_meta_data($body);

        if (isset($metadata['seekable'])) {
            $this->seekable = $metadata['seekable'];
        }

        if (isset($metadata['mode'])) {
            $mode = $metadata['mode'];

            $readWrite = in_array($mode, $this->readWriteModes, true);

            $this->readable = $readWrite || in_array($mode, $this->readModes, true);
            $this->writable = $readWrite || in_array($mode, $this->writeModes, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (! is_resource($this->body) || ! $this->seekable) {
            return '';
        }

        return (string) stream_get_contents($this->body, -1, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (is_resource($this->body)) {
            fclose($this->body);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->body;

        $this->body = null;

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (! is_resource($this->body) || ! $this->seekable) {
            return null;
        }

        $current = $this->tell();
        $this->seek(0, SEEK_END);
        $size = $this->tell();
        $this->seek($current);

        return $size;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if ($this->body === null) {
            throw new \RuntimeException('The stream is detached.');
        }

        if (! is_resource($this->body)) {
            throw new \RuntimeException('The stream is closed.');
        }

        $result = ftell($this->body);

        if ($result === false) {
            throw new \RuntimeException('An error occurred while checking the position of the stream pointer.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return ! is_resource($this->body) || feof($this->body);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->body === null) {
            throw new \RuntimeException('The stream is detached.');
        }

        if (! is_resource($this->body)) {
            throw new \RuntimeException('The stream is closed.');
        }

        if (! $this->seekable) {
            throw new \RuntimeException('The stream is not seekable.');
        }

        $result = fseek($this->body, $offset, $whence);

        if ($result !== 0) {
            throw new \RuntimeException('Could not seek to the requested position.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if ($this->body === null) {
            throw new \RuntimeException('The stream is detached.');
        }

        if (! is_resource($this->body)) {
            throw new \RuntimeException('The stream is closed.');
        }

        if (! $this->writable) {
            throw new \RuntimeException('The stream is not writable.');
        }

        $result = fwrite($this->body, $string);

        if ($result === false) {
            throw new \RuntimeException('An error occurred while writing to the stream.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if ($this->body === null) {
            throw new \RuntimeException('The stream is detached.');
        }

        if (! is_resource($this->body)) {
            throw new \RuntimeException('The stream is closed.');
        }

        if (! $this->readable) {
            throw new \RuntimeException('The stream is not readable.');
        }

        $result = stream_get_contents($this->body, $length);

        if ($result === false) {
            throw new \RuntimeException('An error occurred while reading from the stream.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if ($this->body === null) {
            throw new \RuntimeException('The stream is detached.');
        }

        if (! is_resource($this->body)) {
            throw new \RuntimeException('The stream is closed.');
        }

        if (! $this->readable) {
            throw new \RuntimeException('The stream is not readable.');
        }

        $result = stream_get_contents($this->body);

        if ($result === false) {
            throw new \RuntimeException('An error occurred while reading from the stream.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (! is_resource($this->body)) {
            return ($key === null) ? [] : null;
        }

        $metadata = stream_get_meta_data($this->body);

        if ($key === null) {
            return $metadata;
        }

        return isset($metadata[$key]) ? $metadata[$key] : null;
    }
}
