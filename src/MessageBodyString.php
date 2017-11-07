<?php

declare(strict_types=1);

namespace Brick\Http;

class MessageBodyString implements MessageBody
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var int
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
    public function read(int $length) : string
    {
        $string = substr($this->body, $this->offset, $length);

        $this->offset += $length;

        return (string) $string;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize() : ?int
    {
        return strlen($this->body);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() : string
    {
        return $this->body;
    }
}
