<?php

declare(strict_types=1);

namespace Brick\Http;

interface MessageBody
{
    /**
     * @param int $length
     *
     * @return string
     */
    public function read(int $length) : string;

    /**
     * Returns the size of the body if known.
     *
     * @return int|null The size in bytes if known, or null if unknown.
     */
    public function getSize() : ?int;

    /**
     * @return string
     */
    public function __toString() : string;
}
