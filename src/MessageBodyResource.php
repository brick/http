<?php

declare(strict_types=1);

namespace Brick\Http;

final class MessageBodyResource implements MessageBody
{
    /**
     * @var resource
     */
    private $body;

    /**
     * @var bool
     */
    private bool $seekable;

    /**
     * @param resource $body
     */
    public function __construct($body)
    {
        $this->body = $body;

        $metadata = stream_get_meta_data($body);
        $this->seekable = $metadata['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length) : string
    {
        return stream_get_contents($this->body, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize() : ?int
    {
        if ($this->seekable) {
            $offset = ftell($this->body);
            fseek($this->body, 0, SEEK_END);

            $size = ftell($this->body);
            fseek($this->body, $offset, SEEK_SET);

            return $size;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() : string
    {
        return stream_get_contents($this->body);
    }
}
