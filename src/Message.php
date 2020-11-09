<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * Base class for Request and Response. This class is immutable.
 *
 * @psalm-immutable
 */
abstract class Message
{
    public const CRLF = "\r\n";

    /**
     * @var string
     */
    protected string $protocolVersion = '1.0';

    /**
     * The message headers.
     *
     * The keys represent the lowercase header name, and
     * each value is an array of strings associated with the header.
     *
     * @var array<string, list<string>>
     */
    protected array $headers = [];

    /**
     * @var MessageBody|null
     */
    protected ?MessageBody $body = null;

    /**
     * Returns the protocol version, such as '1.0'.
     *
     * @return string
     */
    public function getProtocolVersion() : string
    {
        return $this->protocolVersion;
    }

    /**
     * Returns a copy of this message with a new protocol version.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $version The HTTP protocol version.
     *
     * @return static The updated message.
     */
    public function withProtocolVersion(string $version): Message
    {
        $that = clone $this;
        $that->protocolVersion = $version;

        return $that;
    }

    /**
     * Gets all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * @return array<string, list<string>>
     */
    public function getHeaders() : array
    {
        $headers = [];

        foreach ($this->headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', strtolower($name))));
            $headers[$name] = $values;
        }

        return $headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name) : bool
    {
        $name = strtolower($name);

        return isset($this->headers[$name]);
    }

    /**
     * Retrieves a header by the given case-insensitive name as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * @param string $name
     *
     * @return string
     */
    public function getHeader(string $name) : string
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? implode(', ', $this->headers[$name]) : '';
    }

    /**
     * Returns the value of the first header by the given case-insensitive name, or null if no such header is present.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getFirstHeader(string $name) : ?string
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? reset($this->headers[$name]) : null;
    }

    /**
     * Returns the value of the last header by the given case-insensitive name, or null if no such header is present.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getLastHeader(string $name) : ?string
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? end($this->headers[$name]) : null;
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $name
     *
     * @return array
     */
    public function getHeaderAsArray(string $name) : array
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? $this->headers[$name] : [];
    }

    /**
     * Returns a copy of this message with a new header.
     *
     * This replaces any existing values of any headers with the same
     * case-insensitive name in the original message.
     *
     * The header value MUST be a string or an array of strings.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string          $name  The header name.
     * @param string|string[] $value The header values(s).
     *
     * @return static The updated message.
     */
    public function withHeader(string $name, $value): Message
    {
        $that = clone $this;

        $name = strtolower($name);
        $that->headers[$name] = is_array($value) ? array_values($value) : [$value];

        return $that;
    }

    /**
     * Returns a copy of this message with new headers.
     *
     * This replaces any headers that were set on the original message.
     *
     * The array keys MUST be strings. The array values MUST be strings or arrays of strings.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array<string, string|string[]> $headers The header names & values.
     *
     * @return static The updated message.
     */
    public function withHeaders(array $headers): Message
    {
        $that = $this;

        foreach ($headers as $name => $value) {
            $that = $that->withHeader($name, $value);
        }

        return $that;
    }

    /**
     * Returns a copy of this message with additional header values.
     *
     * The value is added to any existing values associated with the given header name.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string          $name  The header name.
     * @param string|string[] $value The header value(s).
     *
     * @return static The updated message.
     */
    public function withAddedHeader(string $name, $value): Message
    {
        $that = clone $this;

        $name = strtolower($name);

        if (is_array($value)) {
            $value = array_values($value);
            $that->headers[$name] = isset($that->headers[$name])
                ? array_merge($that->headers[$name], $value)
                : $value;
        } else {
            $that->headers[$name][] = $value;
        }

        return $that;
    }

    /**
     * Returns a copy of this message with additional headers.
     *
     * Each array key MUST be a string representing the case-insensitive name
     * of a header. Each value MUST be either a string or an array of strings.
     * For each value, the value is appended to any existing header of the same
     * name, or, if a header does not already exist by the given name, then the
     * header is added.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array<string, string|string[]> $headers
     *
     * @return static The updated message.
     */
    public function withAddedHeaders(array $headers): Message
    {
        $that = $this;

        foreach ($headers as $name => $value) {
            $that = $that->withAddedHeader($name, $value);
        }

        return $that;
    }

    /**
     * Returns a copy of this message without a specific header.
     *
     * The header name is matched case-insensitively
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $name The header name.
     *
     * @return static The updated message.
     */
    public function withoutHeader(string $name) : Message
    {
        $that = clone $this;

        $name = strtolower($name);
        unset($that->headers[$name]);

        return $that;
    }

    /**
     * @return string
     */
    public function getHead() : string
    {
        $result = $this->getStartLine() . Message::CRLF;

        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $result .= $name . ': ' . $value . Message::CRLF;
            }
        }

        return $result . Message::CRLF;
    }

    /**
     * Returns the body of the message.
     *
     * @return \Brick\Http\MessageBody|null The body, or null if not set.
     */
    public function getBody() : ?MessageBody
    {
        return $this->body;
    }

    /**
     * Returns a copy of this message with a new body.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @return static The updated message.
     */
    public function withBody(?MessageBody $body): Message
    {
        $that = clone $this;

        $that->body = $body;

        if ($body) {
            $size = $body->getSize();

            if ($size === null) {
                $that = $that->withHeader('Transfer-Encoding', 'chunked');
                $that = $that->withoutHeader('Content-Length');
            } else {
                $that = $that->withHeader('Content-Length', (string) $size);
                $that = $that->withoutHeader('Transfer-Encoding');
            }
        } else {
            $that = $that->withoutHeader('Content-Length');
            $that = $that->withoutHeader('Transfer-Encoding');
        }

        return $that;
    }

    /**
     * Returns the reported Content-Length of this Message.
     *
     * If the Content-Length header is absent or invalid, this method returns zero.
     *
     * @return int
     */
    public function getContentLength() : int
    {
        $contentLength = $this->getHeader('Content-Length');

        if (preg_match('/^[0-9]+$/', $contentLength) === 1) {
            return (int) $contentLength;
        }

        return 0;
    }

    /**
     * Returns whether this message has the given Content-Type.
     *
     * The given Content-Type must consist of the type and subtype, without parameters.
     * The comparison is case-insensitive, as per RFC 1521.
     *
     * @param string $contentType The Content-Type to check, such as `text/html`.
     *
     * @return bool
     */
    public function isContentType(string $contentType) : bool
    {
        $thisContentType = $this->getHeader('Content-Type');

        $pos = strpos($thisContentType, ';');

        if ($pos !== false) {
            $thisContentType = substr($thisContentType, 0, $pos);
        }

        return strtolower($contentType) === strtolower($thisContentType);
    }

    /**
     * Returns the start line of the Request or Response.
     *
     * @return string
     */
    abstract public function getStartLine() : string;

    /**
     * @return string
     */
    public function __toString() : string
    {
        $message = $this->getHead();

        if ($this->body) {
            $message .= (string) $this->body;
        }

        return $message;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        if ($this->body) {
            $this->body = clone $this->body;
        }
    }
}
