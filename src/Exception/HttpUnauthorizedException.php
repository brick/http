<?php

declare(strict_types=1);

namespace Brick\Http\Exception;

/**
 * Exception thrown when the request requires user authentication.
 *
 * According to RFC 7235:
 *
 * The 401 (Unauthorized) status code indicates that the request has not
 * been applied because it lacks valid authentication credentials for
 * the target resource.  The server generating a 401 response MUST send
 * a WWW-Authenticate header field (Section 4.1) containing at least one
 * challenge applicable to the target resource.
 *
 * @see https://tools.ietf.org/html/rfc7235#section-3.1
 */
class HttpUnauthorizedException extends HttpException
{
    /**
     * Class constructor.
     *
     * @param string          $wwwAuthenticate The contents of the WWW-Authenticate header.
     * @param string          $message         An optional exception message for debugging.
     * @param \Throwable|null $previous        An optional previous exception for chaining.
     */
    public function __construct(string $wwwAuthenticate, string $message = '', ?\Throwable $previous = null)
    {
        $headers = [
            'WWW-Authenticate' => $wwwAuthenticate
        ];

        parent::__construct(401, $headers, $message, $previous);
    }
}
