<?php

declare(strict_types=1);

namespace Brick\Http\Listener;

use Brick\Http\Message;
use Brick\Http\Request;
use Brick\Http\Response;

class EchoListener implements MessageListener
{
    const REQUEST_HEADER  = 1;
    const REQUEST_BODY    = 2;
    const RESPONSE_HEADER = 4;
    const RESPONSE_BODY   = 8;

    const HEADER = 5;
    const BODY   = 10;

    const REQUEST  = 3;
    const RESPONSE = 12;

    const ALL = 15;

    /**
     * @var int
     */
    private $mask;

    /**
     * @param int $mask
     */
    public function __construct(int $mask = self::ALL)
    {
        $this->mask = $mask;
    }

    /**
     * {@inheritdoc}
     */
    public function listen(Message $message) : void
    {
        if ($message instanceof Request) {
            if ($this->mask & self::REQUEST_HEADER) {
                echo $message->getHead();
            }

            if ($this->mask & self::REQUEST_BODY) {
                echo $body = $message->getBody();

                if ($body) {
                    echo Message::CRLF . Message::CRLF;
                }
            }
        }

        if ($message instanceof Response) {
            if ($this->mask & self::RESPONSE_HEADER) {
                echo $message->getHead();
            }

            if ($this->mask & self::RESPONSE_BODY) {
                echo $body = $message->getBody();

                if ($body) {
                    echo Message::CRLF . Message::CRLF;
                }
            }
        }
    }
}
