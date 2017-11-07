<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * Interface for an immutable HTTP response.
 */
interface ResponseInterface
{
    /**
     * Returns the HTTP status code of the response.
     *
     * @return int
     */
    public function getStatusCode() : int;

    /**
     * Returns the HTTP protocol version of the response.
     *
     * @return string
     */
    public function getProtocolVersion() : string;

    /**
     * Returns an associative array of the response headers.
     *
     * @return array
     */
    public function getHeaders() : array;

    /**
     * Returns the response content as a string.
     *
     * @return string
     */
    public function getContent() : string;

    /**
     * @return bool
     */
    public function isInformational() : bool;

    /**
     * @return bool
     */
    public function isSuccessful() : bool;

    /**
     * @return bool
     */
    public function isRedirection() : bool;

    /**
     * @return bool
     */
    public function isClientError() : bool;

    /**
     * @return bool
     */
    public function isServerError() : bool;
}
