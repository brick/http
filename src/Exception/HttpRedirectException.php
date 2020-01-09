<?php

declare(strict_types=1);

namespace Brick\Http\Exception;

/**
 * Exception thrown when the resource is located elsewhere.
 */
class HttpRedirectException extends HttpException
{
    /**
     * Class constructor.
     *
     * @param string          $location    The redirect URL.
     * @param int             $statusCode  The HTTP status code, 3XX.
     * @param string          $message     An optional exception message for debugging.
     * @param \Throwable|null $previous    An optional previous exception for chaining.
     *
     * @throws \RuntimeException
     */
    public function __construct(string $location, int $statusCode = 302, string $message = '', ?\Throwable $previous = null)
    {
        if ($statusCode < 300 || $statusCode >= 400) {
            throw new \RuntimeException('Invalid HTTP redirect status code: ' . $statusCode);
        }

        parent::__construct($statusCode, [
            'Location' => $location
        ], $message, $previous);
    }
}
