<?php

declare(strict_types=1);

namespace Brick\Http\Exception;

/**
 * Base class for HTTP exceptions.
 */
class HttpException extends \RuntimeException
{
    /**
     * @var array
     */
    private $headers;

    /**
     * Class constructor.
     *
     * @param int             $statusCode The HTTP status code.
     * @param array           $headers    An optional associative array of HTTP headers.
     * @param string          $message    An optional exception message for debugging.
     * @param \Throwable|null $previous   An optional previous exception for chaining.
     */
    public function __construct(int $statusCode, array $headers = [], string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);

        $this->headers = $headers;
    }

    /**
     * Returns the HTTP status code that corresponds to this exception.
     *
     * This is an alias for `getCode()`.
     *
     * @return int
     */
    final public function getStatusCode() : int
    {
        return $this->code;
    }

    /**
     * Returns an array of HTTP headers that should be returned in the response, as key/value pairs.
     *
     * @return array
     */
    final public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    final public function withHeader(string $name, string $value) : HttpException
    {
        $this->headers[$name] = $value;

        return $this;
    }
}
