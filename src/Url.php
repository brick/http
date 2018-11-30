<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * An http(s) URL.
 *
 * Note that the URL is normalized by applying the following transformations:
 *
 * - The scheme and host name are converted to lower case;
 * - If a standard port number is explicitly specified, it is removed;
 * - If a path is not present, it is defaulted to a single `/` character;
 * - If a query separator is present but the query string is empty, the separator is removed;
 * - If a fragment separator is present but the fragment is empty, the separator is removed.
 */
class Url
{
    /**
     * @var string
     */
    private $url;

    /**
     * The scheme, http or https.
     *
     * @var string
     */
    private $scheme;

    /**
     * The host name.
     *
     * @var string
     */
    private $host;

    /**
     * The port number.
     *
     * @var int
     */
    private $port;

    /**
     * The path.
     *
     * @var string
     */
    private $path;

    /**
     * The query string, after the `?` character. Can be empty.
     *
     * @var string
     */
    private $query;

    /**
     * The fragment, after the `#` character. Can be empty.
     *
     * @var string
     */
    private $fragment;

    /**
     * @param string $url
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $url)
    {
        $parts = parse_url($url);

        if ($parts === false) {
            throw new \InvalidArgumentException('URL is malformed.');
        }

        if (! isset($parts['scheme'])) {
            throw new \InvalidArgumentException('URL must contain a scheme, http or https.');
        }

        if (! isset($parts['host'])) {
            throw new \InvalidArgumentException('URL must contain a host name.');
        }

        $this->scheme = strtolower($parts['scheme']);
        $this->host = strtolower($parts['host']);

        if ($this->scheme !== 'http' && $this->scheme !== 'https') {
            throw new \InvalidArgumentException('URL scheme must be http or https.');
        }

        $this->port = $parts['port'] ?? ($this->scheme === 'https' ? 443 : 80);

        if (isset($parts['path'])) {
            $this->path = new Path($parts['path']);
        } else {
            $this->path = new Path('/');
        }

        $this->query = $parts['query'] ?? '';

        $this->fragment = $parts['fragment'] ?? '';

        $url = $this->scheme . '://' . $this->host;

        if (! $this->isStandardPort()) {
            $url .= ':' . $this->port;
        }

        $url .= $this->path;

        if ($this->query !== '') {
            $url .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $url .= '#' . $this->fragment;
        }

        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getScheme() : string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getHost() : string
    {
        return $this->host;
    }

    /**
     * Returns whether the host is on the given domain, or optionally on a sub-domain of it.
     *
     * @param string $host
     * @param bool   $includeSubDomains
     *
     * @return bool
     */
    public function isHost(string $host, bool $includeSubDomains = false) : bool
    {
        $thisHost = strtolower($this->host);
        $thatHost = strtolower($host);

        if (! $includeSubDomains) {
            return $thisHost === $thatHost;
        }

        $thisHost = explode('.', $thisHost);
        $thatHost = explode('.', $thatHost);

        return array_slice($thisHost, - count($thatHost)) === $thatHost;
    }

    /**
     * @return int
     */
    public function getPort() : int
    {
        return $this->port;
    }
    /**
     * @return Path
     */
    public function getPath() : Path
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getQuery() : string
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getFragment() : string
    {
        return $this->fragment;
    }

    /**
     * @return bool
     */
    public function isStandardPort() : bool
    {
        return $this->port === ($this->scheme === 'https' ? 443 : 80);
    }

    /**
     * @return bool
     */
    public function isSecure() : bool
    {
        return $this->scheme === 'https';
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->url;
    }
}
