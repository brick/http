<?php

declare(strict_types=1);

namespace Brick\Http;

final class MessageBodyString implements MessageBody
{
    /**
     * @var string
     */
    private string $body;

    /**
     * @var int
     */
    private int $offset = 0;

    /**
     * @param string $body The message body.
     */
    public function __construct(string $body)
    {
        $this->body = $body;
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
