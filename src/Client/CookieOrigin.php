<?php

namespace Brick\Http\Client;

use Brick\Http\Request;

/**
 * Encapsulates details of an origin server that
 * are relevant when parsing, validating or matching HTTP cookies.
 */
class CookieOrigin
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $secure;

    /**
     * @param string $host
     * @param string $path
     * @param bool   $secure
     */
    public function __construct(string $host, string $path, bool $secure)
    {
        $this->host   = $host;
        $this->path   = $path;
        $this->secure = $secure;
    }

    /**
     * Creates a CookieOrigin from a Request instance.
     *
     * @param Request $request
     *
     * @return CookieOrigin
     */
    public static function createFromRequest(Request $request) : CookieOrigin
    {
        return new self($request->getHost(), $request->getPath(), $request->isSecure());
    }

    /**
     * @return string
     */
    public function getHost() : string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function isSecure() : bool
    {
        return $this->secure;
    }
}
