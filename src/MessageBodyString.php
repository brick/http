<?php

namespace Brick\Http;

use Psr\Http\Message\StreamInterface;

class MessageBodyString implements MessageBody, StreamInterface
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var integer
     */
    private $offset = 0;

    /**
     * @param string $body
     */
    public function __construct($body)
    {
        $this->body = (string) $body;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->offset = strlen($this->body);

        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return strlen($this->body);
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        return $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return $this->offset == strlen($this->body);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        $offset = (int) $offset;

        if ($whence === SEEK_SET) {
            $this->offset = $offset;
        }
        elseif ($whence === SEEK_CUR) {
            $this->offset += $offset;
        }
        elseif ($whence === SEEK_END) {
            $this->offset = strlen($this->body) + $offset;
        }
        else {
            throw new \RuntimeException('Invalid whence parameter.');
        }

        if ($this->offset < 0) {
            throw new \RuntimeException('Negative offset.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->offset = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        $length = strlen($this->body);

        if ($this->offset > $length) {
            $this->body .= str_repeat("\0", $this->offset - $length);
        }

        $this->body = substr($this->body, 0, $this->offset)
            . $string
            . substr($this->body, $this->offset + strlen($string));

        $this->offset = strlen($this->body);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        $string = (string) substr($this->body, $this->offset, $length);

        $this->offset += strlen($string);

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $string = (string) substr($this->body, $this->offset);

        $this->offset += strlen($string);

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        return ($key === null) ? [] : null;
    }
}
