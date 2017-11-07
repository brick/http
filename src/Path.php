<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * The path of a URL.
 */
class Path
{
    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Returns the parts of the path separated by slashes, as an array.
     *
     * Example: `/user/profile` => `['user', 'profile']`
     *
     * @return array
     */
    public function getParts() : array
    {
        return preg_split('|/|', $this->path, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public function contains(string $string) : bool
    {
        return strpos($this->path, $string) !== false;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public function startsWith(string $string) : bool
    {
        return substr($this->path, 0, strlen($string)) === $string;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public function endsWith(string $string) : bool
    {
        return substr($this->path, - strlen($string)) === $string;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->path;
    }
}
