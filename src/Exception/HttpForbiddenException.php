<?php

declare(strict_types=1);

namespace Brick\Http\Exception;

/**
 * Exception thrown when the client is not allowed to access the resource.
 */
class HttpForbiddenException extends HttpException
{
    /**
     * Class constructor.
     *
     * @param string          $message  An optional exception message for debugging.
     * @param \Throwable|null $previous An optional previous exception for chaining.
     */
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(403, [], $message, $previous);
    }
}
