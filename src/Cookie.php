<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * An HTTP cookie. This class is immutable.
 *
 * @todo Max-Age support.
 */
final class Cookie
{
    /**
     * The name of the cookie.
     *
     * @var string
     */
    private string $name;

    /**
     * The value of the cookie.
     *
     * @var string
     */
    private string $value;

    /**
     * The unix timestamp at which the cookie expires.
     *
     * Zero if the cookie should expire at the end of the browser session.
     *
     * @var int
     */
    private int $expires = 0;

    /**
     * The path on which the cookie is valid, or null if not set.
     *
     * @var string|null
     */
    private ?string $path = null;

    /**
     * The domain on which the cookie is valid, or null if not set.
     *
     * @var string|null
     */
    private ?string $domain = null;

    /**
     * Whether the cookie should only be sent on a secure connection.
     *
     * @var bool
     */
    private bool $secure = false;

    /**
     * Whether the cookie should only be sent over the HTTP protocol.
     *
     * @var bool
     */
    private bool $httpOnly = false;

    /**
     * Class constructor.
     *
     * @param string $name  The name of the cookie.
     * @param string $value The value of the cookie.
     */
    public function __construct(string $name, string $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * Creates a cookie from the contents of a Set-Cookie header.
     *
     * @param string $string
     *
     * @return Cookie The cookie.
     *
     * @throws \InvalidArgumentException If the cookie string is not valid.
     */
    public static function parse(string $string) : Cookie
    {
        $parts = preg_split('/;\s*/', $string);
        $nameValue = explode('=', array_shift($parts), 2);

        if (count($nameValue) !== 2) {
            throw new \InvalidArgumentException('The cookie string is not valid.');
        }

        [$name, $value] = $nameValue;

        if ($name === '') {
            throw new \InvalidArgumentException('The cookie string is not valid.');
        }

        if ($value === '') {
            throw new \InvalidArgumentException('The cookie string is not valid.');
        }

        $value = rawurldecode($value);
        $expires = 0;
        $path = null;
        $domain = null;
        $secure = false;
        $httpOnly = false;

        foreach ($parts as $part) {
            switch (strtolower($part)) {
                case 'secure':
                    $secure = true;
                    break;

                case 'httponly':
                    $httpOnly = true;
                    break;

                default:
                    $elements = explode('=', $part, 2);
                    if (count($elements) === 2) {
                        switch (strtolower($elements[0])) {
                            case 'expires':
                                // Using @ to suppress the timezone warning, might not be the best thing to do.
                                if (is_int($time = @ strtotime($elements[1]))) {
                                    $expires = $time;
                                }
                                break;

                            case 'path':
                                $path = $elements[1];
                                break;

                            case 'domain':
                                $domain = strtolower(ltrim($elements[1], '.'));
                        }
                    }
            }
        }

        return (new Cookie($name, $value))
            ->withExpires($expires)
            ->withPath($path)
            ->withDomain($domain)
            ->withSecure($secure)
            ->withHttpOnly($httpOnly);
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue() : string
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getExpires() : int
    {
        return $this->expires;
    }

    /**
     * Returns a copy of this cookie with a new expiration time.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param int $expires The expiration time as a unix timestamp, or zero for a transient cookie.
     *
     * @return Cookie The updated cookie.
     */
    public function withExpires(int $expires): Cookie
    {
        if ($expires === $this->expires) {
            return $this;
        }

        $that = clone $this;
        $that->expires = $expires;

        return $that;
    }

    /**
     * @return string|null
     */
    public function getPath() : ?string
    {
        return $this->path;
    }

    /**
     * Returns a copy of this cookie with a new path.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string|null $path The cookie path, or null to unset.
     *
     * @return Cookie The updated cookie.
     */
    public function withPath(?string $path): Cookie
    {
        if ($path === $this->path) {
            return $this;
        }

        $that = clone $this;
        $that->path = $path;

        return $that;
    }

    /**
     * @return string|null
     */
    public function getDomain() : ?string
    {
        return $this->domain;
    }

    /**
     * Returns a copy of this cookie with a new domain.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string|null $domain The domain, or null to unset.
     *
     * @return Cookie The updated cookie.
     */
    public function withDomain(?string $domain): Cookie
    {
        if ($domain === $this->domain) {
            return $this;
        }

        $that = clone $this;
        $that->domain = $domain;

        return $that;
    }

    /**
     * @return bool
     */
    public function isHostOnly() : bool
    {
        return $this->domain === null;
    }

    /**
     * @return bool
     */
    public function isSecure() : bool
    {
        return $this->secure;
    }

    /**
     * Returns a copy of this cookie with an updated Secure flag.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param bool $secure Whether the cookie should only be sent over a secure connection.
     *
     * @return Cookie The updated cookie.
     */
    public function withSecure(bool $secure): Cookie
    {
        if ($secure === $this->secure) {
            return $this;
        }

        $that = clone $this;
        $that->secure = $secure;

        return $that;
    }

    /**
     * Returns whether to limit the scope of this cookie to HTTP requests.
     *
     * @return bool True if this cookie should only be sent over a secure connection, false otherwise.
     */
    public function isHttpOnly() : bool
    {
        return $this->httpOnly;
    }

    /**
     * Returns a copy of this cookie with an updated HttpOnly flag.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * Set to true to instruct the user agent to omit the cookie when providing access to
     * cookies via "non-HTTP" APIs (such as a web browser API that exposes cookies to scripts).
     *
     * This helps mitigate the risk of client side script accessing the protected cookie
     * (provided that the user agent supports it).
     *
     * @param bool $httpOnly Whether to limit the scope of this cookie to HTTP requests.
     *
     * @return Cookie The updated cookie.
     */
    public function withHttpOnly(bool $httpOnly): Cookie
    {
        if ($httpOnly === $this->httpOnly) {
            return $this;
        }

        $that = clone $this;
        $that->httpOnly = $httpOnly;

        return $that;
    }

    /**
     * Returns whether this cookie has expired.
     *
     * @return bool
     */
    public function isExpired() : bool
    {
        return $this->expires !== 0 && $this->expires < time();
    }

    /**
     * Returns whether the cookie is persistent.
     *
     * If false, the cookie should be discarded at the end of the session.
     * If true, the cookie should be discarded when the expiry time is reached.
     *
     * @return bool
     */
    public function isPersistent() : bool
    {
        return $this->expires !== 0;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        $cookie = $this->name . '=' . rawurlencode($this->value);

        if ($this->expires !== 0) {
            $cookie .= '; Expires=' . gmdate('r', $this->expires);
        }

        if ($this->domain !== null) {
            $cookie .= '; Domain=' . $this->domain;
        }

        if ($this->path !== null) {
            $cookie .= '; Path=' . $this->path;
        }

        if ($this->secure) {
            $cookie .= '; Secure';
        }

        if ($this->httpOnly) {
            $cookie .= '; HttpOnly';
        }

        return $cookie;
    }
}
