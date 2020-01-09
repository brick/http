<?php

declare(strict_types=1);

namespace Brick\Http\Exception;

/**
 * Exception thrown when the server is temporarily unable to handle the request.
 *
 * This might be due to a temporary overloading or maintenance of the server.
 */
class HttpServiceUnavailableException extends HttpException
{
    /**
     * Class constructor.
     *
     * @param string          $message  An optional exception message for debugging.
     * @param \Throwable|null $previous An optional previous exception for chaining.
     */
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(503, [], $message, $previous);
    }
}
