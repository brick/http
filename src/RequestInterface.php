<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * Interface for an immutable HTTP request.
 */
interface RequestInterface
{
    /**
     * Returns the request method.
     *
     * Examples: GET, HEAD, POST, PUT, DELETE
     *
     * @return string
     */
    public function getMethod() : string;

    /**
     * Returns whether the request method is safe (no side effects).
     *
     * The request is considered safe when the method is GET or HEAD.
     *
     * @return bool
     */
    public function isMethodSafe() : bool;

    /**
     * Returns the scheme of the request.
     *
     * Examples: http, https
     *
     * @return string
     */
    public function getScheme() : string;

    /**
     * Returns the host of the requested URL.
     *
     * Example: www.host.com
     *
     * @return string
     */
    public function getHost() : string;

    /**
     * Returns the host parts of the requested URL (using dot as a separator).
     *
     * Example: ['www', 'host', 'com']
     *
     * @return array
     */
    public function getHostParts() : array;

    /**
     * Returns the port the request targets.
     *
     * Example: 80
     *
     * @return int
     */
    public function getPort() : int;

    /**
     * Returns the path of the requested URL, without the query string.
     *
     * Example: /path/to/resource
     *
     * @return string
     */
    public function getPath() : string;

    /**
     * Returns the path parts of the requested URL (using slash as a separator).
     *
     * Empty values are excluded.
     *
     * Example: ['path', 'to', 'resource']
     *
     * @return array
     */
    public function getPathParts() : array;

    /**
     * Returns whether the requested URL has a query string.
     *
     * @return bool
     */
    public function hasQueryString() : bool;

    /**
     * Returns the query string, or an empty string if no query string is present.
     *
     * Example: a=1&b=2
     *
     * @return string
     */
    public function getQueryString() : string;

    /**
     * Returns the request URI, which includes the path and the query string.
     *
     * Example: /path/to/resource?a=1&b=2
     *
     * @return string
     */
    public function getRequestUri() : string;

    /**
     * Returns the full requested URL.
     *
     * Example: http://www.host.com/path/to/resource?key=value
     *
     * @return string
     */
    public function getUrl() : string;

    /**
     * @return array
     */
    public function getQueryParameters() : array;

    /**
     * @return array
     */
    public function getPostParameters() : array;

    /**
     * @return array
     */
    public function getCookies() : array;

    /**
     * @return array
     */
    public function getHeaders() : array;

    /**
     * @return array
     */
    public function getUploadedFiles() : array;

    public function getBody();
}
