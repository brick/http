<?php

declare(strict_types=1);

namespace Brick\Http;

use Brick\Http\Exception\HttpBadRequestException;

/**
 * Represents an HTTP request received by the server. This class is immutable.
 *
 * @psalm-immutable
 */
final class Request extends Message
{
    public const PREFER_HTTP_HOST   = 0; // Prefer HTTP_HOST, fall back to SERVER_NAME and SERVER_PORT.
    public const PREFER_SERVER_NAME = 1; // Prefer SERVER_NAME and SERVER_PORT, fall back to HTTP_HOST.
    public const ONLY_HTTP_HOST     = 2; // Only use HTTP_HOST if available, ignore SERVER_NAME and SERVER_PORT.
    public const ONLY_SERVER_NAME   = 3; // Only use SERVER_NAME and SERVER_PORT if available, ignore HTTP_HOST.

    /**
     * The standard HTTP methods.
     *
     * According to RFC 2616, all methods should be treated in a case-sensitive way.
     * However, many implementations still do not comply with the spec and treat the method as case-insensitive.
     *
     * For maximum compatibility, this implementation follows the pragmatic principle of XMLHttpRequest:
     * - If the method case-insensitively matches a standard method, consider it case-insensitive.
     * - If the method is not in this list, consider it case-sensitive.
     *
     * @see http://www.w3.org/TR/XMLHttpRequest/
     */
    private const STANDARD_METHODS = [
        'CONNECT',
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'POST',
        'PUT',
        'TRACE',
        'TRACK'
    ];

    /**
     * @var array
     */
    private array $query = [];

    /**
     * @var array
     */
    private array $post = [];

    /**
     * @var array
     */
    private array $cookies = [];

    /**
     * @var array
     */
    private array $files = [];

    /**
     * @var bool
     */
    private bool $isSecure = false;

    /**
     * The request method.
     *
     * @var string
     */
    private string $method = 'GET';

    /**
     * @var string
     */
    private string $host = 'localhost';

    /**
     * @var int
     */
    private int $port = 80;

    /**
     * The Request-URI.
     *
     * @var string
     */
    private string $requestUri = '/';

    /**
     * The part before the `?` in the Request-URI.
     *
     * @var string
     */
    private string $path = '/';

    /**
     * The part after the `?` in the Request-URI.
     *
     * @var string
     */
    private string $queryString = '';

    /**
     * @var string
     */
    private string $clientIp = '0.0.0.0';

    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * Returns a Request object representing the current request.
     *
     * Note that due to the way PHP works, the request body will be empty
     * when the Content-Type is multipart/form-data.
     *
     * Note that the query string data is purposefully parsed from the REQUEST_URI,
     * and not just taken from the $_GET superglobal.
     * This is to provide a consistent behaviour even when mod_rewrite is in use.
     *
     * @param bool $trustProxy     Whether to trust X-Forwarded-* headers.
     * @param int  $hostPortSource One of the PREFER_* or ONLY_* constants.
     *
     * @return Request
     */
    public static function getCurrent(bool $trustProxy = false, int $hostPortSource = self::PREFER_HTTP_HOST) : Request
    {
        $request = new Request();

        /** @var array<string, string> $_SERVER */

        if (isset($_SERVER['HTTPS'])) {
            if ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1') {
                $request->isSecure = true;
                $request->port = 443;
            }
        }

        $httpHost = null;
        $httpPort = null;

        $serverName = null;
        $serverPort = null;

        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            $pos = strrpos($host, ':');

            if ($pos === false) {
                $httpHost = $host;
                $httpPort = $request->port;
            } else {
                $httpHost = substr($host, 0, $pos);
                $httpPort = (int) substr($host, $pos + 1);
            }
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            $serverName = $_SERVER['SERVER_NAME'];
        }

        if (isset($_SERVER['SERVER_PORT'])) {
            $serverPort = (int) $_SERVER['SERVER_PORT'];
        }

        $host = null;
        $port = null;

        switch ($hostPortSource) {
            case self::PREFER_HTTP_HOST:
                $host = ($httpHost !== null) ? $httpHost : $serverName;
                $port = ($httpPort !== null) ? $httpPort : $serverPort;
                break;

            case self::PREFER_SERVER_NAME:
                $host = ($serverName !== null) ? $serverName : $httpHost;
                $port = ($serverPort !== null) ? $serverPort : $httpPort;
                break;

            case self::ONLY_HTTP_HOST:
                $host = $httpHost;
                $port = $httpPort;
                break;

            case self::ONLY_SERVER_NAME:
                $host = $serverName;
                $port = $serverPort;
                break;
        }

        if ($host !== null) {
            $request->host = $host;
        }

        if ($port !== null) {
            $request->port = $port;
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $request->method = $_SERVER['REQUEST_METHOD'];
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $request = $request->withRequestUri($_SERVER['REQUEST_URI']);
        }

        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            if (preg_match('|^HTTP/(.+)$|', $_SERVER['SERVER_PROTOCOL'], $matches) !== 1) {
                throw new HttpBadRequestException('Invalid protocol: ' . $_SERVER['SERVER_PROTOCOL']);
            }

            $request->protocolVersion = $matches[1];
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $request->clientIp = $_SERVER['REMOTE_ADDR'];
        }

        $request->headers = self::getCurrentRequestHeaders();

        $request->post    = $_POST;
        $request->cookies = $_COOKIE;
        $request->files   = UploadedFileMap::createFromFilesGlobal($_FILES);

        if (isset($_SERVER['CONTENT_LENGTH']) || isset($_SERVER['HTTP_TRANSFER_ENCODING'])) {
            $request->body = new MessageBodyResource(fopen('php://input', 'rb'));
        }

        if ($trustProxy) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = preg_split('/,\s*/', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $request->clientIp = array_pop($ips);
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $request->host = $_SERVER['HTTP_X_FORWARDED_HOST'];
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
                $request->port = (int) $_SERVER['HTTP_X_FORWARDED_PORT'];
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $request->isSecure = $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
            }
        }

        return $request;
    }

    /**
     * Returns the current request headers.
     *
     * The headers are prepared in the format expected by `Message::$headers`:
     *   - keys are lowercased
     *   - values are arrays of strings
     *
     * @return array<string, list<string>>
     */
    private static function getCurrentRequestHeaders() : array
    {
        $headers = [];

        if (function_exists('apache_request_headers')) {
            /** @var array<string, string> $requestHeaders */
            $requestHeaders = apache_request_headers();

            if ($requestHeaders) {
                foreach ($requestHeaders as $key => $value) {
                    $key = strtolower($key);
                    $headers[$key] = [$value];
                }

                return $headers;
            }
        }

        /** @var array<string, string> $_SERVER */

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
            } elseif ($key !== 'CONTENT_TYPE' && $key !== 'CONTENT_LENGTH') {
                continue;
            }

            $key = strtolower(str_replace('_', '-', $key));
            $headers[$key] = [$value];
        }

        return $headers;
    }

    /**
     * Returns the query parameter(s).
     *
     * You can optionally use a full path to the query parameter:
     *
     *     $request->getQuery('foo[bar]');
     *
     * Or even simpler:
     *
     *     $request->getQuery('foo.bar');
     *
     * @param string|null $name The parameter name, or null to return all query parameters.
     *
     * @return string|array|null The query parameter(s), or null if the path is not found.
     */
    public function getQuery(?string $name = null)
    {
        if ($name === null) {
            return $this->query;
        }

        return $this->resolvePath($this->query, $name);
    }

    /**
     * Returns a copy of this request with new query parameters.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array $query The associative array of parameters.
     *
     * @return Request The updated request.
     */
    public function withQuery(array $query): Request
    {
        $that = clone $this;

        $that->queryString = http_build_query($query);

        // Ensure that we get a value for getQuery() that's consistent with the query string, and whose scalar values
        // have all been converted to strings, to get a result similar to what we'd get with an incoming HTTP request.
        /** @psalm-suppress ImpureFunctionCall */
        parse_str($that->queryString, $that->query);

        $that->requestUri = $that->path;

        if ($that->queryString !== '') {
            $that->requestUri .= '?' . $that->queryString;
        }

        return $that;
    }

    /**
     * Returns the post parameter(s).
     *
     * @param string|null $name The parameter name, or null to return all post parameters.
     *
     * @return string|array|null The post parameter(s), or null if the path is not found.
     */
    public function getPost(?string $name = null)
    {
        if ($name === null) {
            return $this->post;
        }

        return $this->resolvePath($this->post, $name);
    }

    /**
     * Returns a copy of this request with new post parameters.
     *
     * This will set a request body with the URL-encoded data,
     * unless the Content-Type of this request is multipart/form-data,
     * in which case the body is left as is.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array $post The associative array of parameters.
     *
     * @return Request The updated request.
     */
    public function withPost(array $post): Request
    {
        $that = clone $this;

        // Ensure that we get a value for getQuery() that's consistent with the query string, and whose scalar values
        // have all been converted to strings, to get a result similar to what we'd get with an incoming HTTP request.
        /** @psalm-suppress ImpureFunctionCall */
        parse_str(http_build_query($post), $that->post);

        if (! $that->isContentType('multipart/form-data')) {
            $body = http_build_query($post);
            $body = new MessageBodyString($body);

            $that = $that->withBody($body);
            $that = $that->withHeader('Content-Type', 'x-www-form-urlencoded');
        }

        return $that;
    }

    /**
     * Returns the uploaded file by the given name.
     *
     * If no uploaded file exists by that name,
     * or if the name maps to an array of uploaded files,
     * this method returns NULL.
     *
     * @param string $name The name of the file input field.
     *
     * @return \Brick\Http\UploadedFile|null The uploaded file, or NULL if not found.
     */
    public function getFile(string $name) : ?UploadedFile
    {
        $file = $this->resolvePath($this->files, $name);

        if ($file instanceof UploadedFile) {
            return $file;
        }

        return null;
    }

    /**
     * Returns the uploaded files under the given name.
     *
     * If no uploaded files by that name exist, an empty array is returned.
     *
     * @param string $name The name of the file input fields.
     *
     * @return \Brick\Http\UploadedFile[] The uploaded files.
     */
    public function getFiles(?string $name = null) : array
    {
        if ($name === null) {
            return $this->files;
        }

        $files = $this->resolvePath($this->files, $name);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (is_array($files)) {
            return array_filter($files, static function ($file) {
                return $file instanceof UploadedFile;
            });
        }

        return [];
    }

    /**
     * Returns a copy of this request with new uploaded files.
     *
     * The resulting request will have an empty message body; this is in line with the values available when dealing
     * with a multipart request in PHP.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @return Request The updated request.
     *
     * @param array $files An associative array of (potentially nested) UploadedFile instances.
     *
     * @throws \InvalidArgumentException If the UploadedFile array is invalid.
     */
    public function withFiles(array $files): Request
    {
        $that = clone $this;

        $that->checkFiles($files);

        $that->files = $files;
        $that->body  = new MessageBodyString('');

        return $that->withHeaders([
            'Content-Type'   => 'multipart/form-data',
            'Content-Length' => '0'
        ]);
    }

    /**
     * @param array $files
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function checkFiles(array $files) : void
    {
        foreach ($files as $value) {
            if (is_array($value)) {
                $this->checkFiles($value);
            } elseif (! $value instanceof UploadedFile) {
                $type = is_object($value) ? get_class($value) : gettype($value);

                throw new \InvalidArgumentException('Expected ' . UploadedFile::class . ' or array, got ' . $type);
            }
        }
    }

    /**
     * Returns the cookie(s).
     *
     * @param string|null $name The cookie name, or null to return all cookies.
     *
     * @return string|array|null The cookie value(s), or null if the path is not found.
     */
    public function getCookie(?string $name = null)
    {
        if ($name === null) {
            return $this->cookies;
        }

        return $this->resolvePath($this->cookies, $name);
    }

    /**
     * Returns a copy of this request with added cookies.
     *
     * Existing cookies with the same name will be replaced.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array $cookies An associative array of cookies.
     *
     * @return Request The updated request.
     */
    public function withAddedCookies(array $cookies): Request
    {
        return $this->withCookies($cookies + $this->cookies);
    }

    /**
     * Returns a copy of this request with new cookies.
     *
     * All cookies of the original request will be replaced.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array $cookies An associative array of cookies.
     *
     * @return Request The updated request.
     */
    public function withCookies(array $cookies): Request
    {
        $that = clone $this;

        $query = http_build_query($cookies);

        /** @psalm-suppress ImpureFunctionCall */
        parse_str($query, $that->cookies);

        if ($cookies) {
            $cookie = str_replace('&', '; ', $query);
            $that = $that->withHeader('Cookie', $cookie);
        } else {
            $that = $that->withoutHeader('Cookie');
        }

        return $that;
    }

    /**
     * @param array  $value
     * @param string $path
     *
     * @return mixed
     */
    private function resolvePath(array $value, string $path)
    {
        $path = preg_replace('/\[(.*?)\]/', '.$1', $path);
        $path = explode('.', $path);

        foreach ($path as $item) {
            if (is_array($value) && isset($value[$item])) {
                $value = $value[$item];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartLine() : string
    {
        return sprintf('%s %s HTTP/%s', $this->method, $this->requestUri, $this->protocolVersion);
    }

    /**
     * Returns the request method, such as GET or POST.
     *
     * @return string The request method.
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * Returns a copy of this request with a new request method.
     *
     * If the method case-insensitively matches a standard method, it will be converted to uppercase.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $method The new request method.
     *
     * @return Request The updated request.
     */
    public function withMethod(string $method): Request
    {
        $that = clone $this;
        $that->method = $that->fixMethodCase($method);

        return $that;
    }

    /**
     * Returns whether this request method matches the given one.
     *
     * Example: $request->isMethod('POST');
     *
     * If the method case-insensitively matches a standard method, the comparison will be case-insensitive.
     * Otherwise, the comparison will be case-sensitive.
     *
     * @param string $method The method to test this request against.
     *
     * @return bool True if this request matches the method, false otherwise.
     */
    public function isMethod(string $method) : bool
    {
        return $this->method === $this->fixMethodCase($method);
    }

    /**
     * Returns whether this request method is GET or HEAD.
     *
     * @return bool
     */
    public function isMethodSafe() : bool
    {
        return $this->method === 'GET' || $this->method === 'HEAD';
    }

    /**
     * Fixes a method case.
     *
     * @see Request::STANDARD_METHODS
     *
     * @param string $method
     *
     * @return string
     */
    private function fixMethodCase(string $method) : string
    {
        $upperMethod = strtoupper($method);
        $isStandard = in_array($upperMethod, self::STANDARD_METHODS, true);

        return $isStandard ? $upperMethod : $method;
    }

    /**
     * Returns the request scheme, http or https.
     *
     * @return string
     */
    public function getScheme() : string
    {
        return $this->isSecure ? 'https' : 'http';
    }

    /**
     * Returns a copy of this request with a new scheme.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $scheme The new request scheme, 'http' or 'https'.
     *
     * @return Request The updated request.
     */
    public function withScheme(string $scheme): Request
    {
        $that = clone $this;

        $scheme = strtolower($scheme);

        if ($scheme === 'http') {
            $that->isSecure = false;
        } elseif ($scheme === 'https') {
            $that->isSecure = true;
        } else {
            throw new \InvalidArgumentException('The scheme must be http or https.');
        }

        return $that;
    }

    /**
     * Returns the host name of this request.
     *
     * @return string The host name.
     */
    public function getHost() : string
    {
        return $this->host;
    }

    /**
     * Returns the parts of the host name separated by dots, as an array.
     *
     * Example: `www.example.com` => `['www', 'example', 'com']`
     *
     * @return array The host parts.
     */
    public function getHostParts() : array
    {
        return explode('.', $this->host);
    }

    /**
     * Returns whether the host is on the given domain, or optionally on a sub-domain of it.
     *
     * @todo should be used against getUrl(), which should return a Url object.
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
     * Returns a copy of this request with a new host.
     *
     * This will update the Host header accordingly.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $host The new host name.
     *
     * @return Request The updated request.
     */
    public function withHost(string $host): Request
    {
        $that = clone $this;
        $that->host = $host;

        return $that->updateHostHeader();
    }

    /**
     * Returns the port number of this request.
     *
     * @return int The port number.
     */
    public function getPort() : int
    {
        return $this->port;
    }

    /**
     * Returns a copy of this request with a new port.
     *
     * This will update the Host header accordingly.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param int $port The new port number.
     *
     * @return Request The updated request.
     */
    public function withPort(int $port): Request
    {
        $that = clone $this;
        $that->port = $port;

        return $that->updateHostHeader();
    }

    /**
     * Updates the Host header from the values of host and port.
     *
     * @return static This request.
     */
    private function updateHostHeader() : Request
    {
        $host = $this->host;
        $standardPort = $this->isSecure ? 443 : 80;

        if ($this->port !== $standardPort) {
            $host .= ':' . $this->port;
        }

        return $this->withHeader('Host', $host);
    }

    /**
     * Returns the request path.
     *
     * This does not include the query string.
     *
     * Example: `/user/profile`
     *
     * @return string The request path.
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Returns the parts of the path separated by slashes, as an array.
     *
     * Example: `/user/profile` => `['user', 'profile']`
     *
     * @return array
     */
    public function getPathParts() : array
    {
        return preg_split('|/|', $this->path, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Returns a copy of this request with a new path.
     *
     * Example: `/user/profile`
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $path The new request path.
     *
     * @return Request The updated request.
     *
     * @throws \InvalidArgumentException If the path is not valid.
     */
    public function withPath(string $path): Request
    {
        if ($path === $this->path) {
            return $this;
        }

        if (strpos($path, '?') !== false) {
            throw new \InvalidArgumentException('The request path must not contain a query string.');
        }

        $that = clone $this;
        $that->path = $path;

        $that->requestUri = $that->recomputeRequestUri();

        return $that;
    }

    /**
     * Returns the query string.
     *
     * The query string is the part after the `?` in the URL.
     *
     * If this request has no query string, an empty string is returned.
     *
     * @return string The query string.
     */
    public function getQueryString() : string
    {
        return $this->queryString;
    }

    /**
     * Returns a copy of this request with a new query string.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $queryString The new query string.
     *
     * @return Request The updated request.
     */
    public function withQueryString(string $queryString): Request
    {
        if ($queryString === $this->queryString) {
            return $this;
        }

        $that = clone $this;

        $that->queryString = $queryString;

        /** @psalm-suppress ImpureFunctionCall */
        parse_str($that->queryString, $that->query);

        $that->requestUri = $that->recomputeRequestUri();

        return $that;
    }

    /**
     * Recomputes the request URI from the values of path and query string.
     */
    private function recomputeRequestUri(): string
    {
        $requestUri = $this->path;

        if ($this->queryString !== '') {
            $requestUri .= '?' . $this->queryString;
        }

        return $requestUri;
    }

    /**
     * Returns the request URI.
     *
     * @return string The request URI.
     */
    public function getRequestUri() : string
    {
        return $this->requestUri;
    }

    /**
     * Returns a copy of this request with a new request URI.
     *
     * This will update the request path, query string, and query parameters.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $requestUri The new request URI.
     *
     * @return Request The updated request.
     */
    public function withRequestUri(string $requestUri): Request
    {
        if ($requestUri === $this->requestUri) {
            return $this;
        }

        $that = clone $this;

        $pos = strpos($requestUri, '?');

        if ($pos === false) {
            $that->path = $requestUri;
            $that->queryString = '';
            $that->query = [];
        } else {
            $that->path        = substr($requestUri, 0, $pos);
            $that->queryString = substr($requestUri, $pos + 1);

            if ($that->queryString === '') {
                $that->query = [];
            } else {
                /** @psalm-suppress ImpureFunctionCall */
                parse_str($that->queryString, $that->query);
            }
        }

        $that->requestUri = $requestUri;

        return $that;
    }

    /**
     * Returns the URL of this request.
     *
     * @return string The request URL.
     */
    public function getUrl() : string
    {
        return $this->getUrlBase() . $this->requestUri;
    }

    /**
     * Returns the scheme and host name of this request.
     *
     * @return string The base URL.
     */
    public function getUrlBase() : string
    {
        $url = sprintf('%s://%s', $this->getScheme(), $this->host);
        $isStandardPort = ($this->port === ($this->isSecure ? 443 : 80));

        return $isStandardPort ? $url : $url . ':' . $this->port;
    }

    /**
     * Returns a copy of this request with a new URL.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $url The new URL.
     *
     * @return Request The updated request.
     *
     * @throws \InvalidArgumentException If the URL is not valid.
     */
    public function withUrl(string $url): Request
    {
        $that = clone $this;

        $components = parse_url($url);

        if ($components === false) {
            throw new \InvalidArgumentException('The URL provided is not valid.');
        }

        if (! isset($components['scheme'])) {
            throw new \InvalidArgumentException('The URL must have a scheme.');
        }

        $scheme = strtolower($components['scheme']);

        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new \InvalidArgumentException(sprintf('The URL scheme "%s" is not acceptable.', $scheme));
        }

        $isSecure = ($scheme === 'https');

        if (! isset($components['host'])) {
            throw new \InvalidArgumentException('The URL must have a host name.');
        }

        $host = $components['host'];

        if (isset($components['port'])) {
            $port = $components['port'];
            $hostHeader = $host . ':' . $port;
        } else {
            $port = ($isSecure ? 443 : 80);
            $hostHeader = $host;
        }

        $that->path = isset($components['path']) ? $components['path'] : '/';
        $requestUri = $that->path;

        if (isset($components['query'])) {
            $that->queryString = $components['query'];
            /** @psalm-suppress ImpureFunctionCall */
            parse_str($that->queryString, $that->query);
            $requestUri .= '?' . $that->queryString;
        } else {
            $that->queryString = '';
            $that->query = [];
        }

        $that->host = $host;
        $that->port = $port;
        $that->isSecure = $isSecure;
        $that->requestUri = $requestUri;

        return $that->withHeader('Host', $hostHeader);
    }

    /**
     * Returns the Referer URL if it is present and valid, else null.
     *
     * @return Url|null
     */
    public function getReferer() : ?Url
    {
        $referer = $this->getFirstHeader('Referer');

        if ($referer === null) {
            return null;
        }

        try {
            return new Url($referer);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Returns whether this request is sent over a secure connection (HTTPS).
     *
     * @return bool True if the request is secure, false otherwise.
     */
    public function isSecure() : bool
    {
        return $this->isSecure;
    }

    /**
     * Returns a copy of this request with the secure flag changed.
     *
     * Sets whether the request is sent over a secure connection.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param bool $isSecure True to mark the request as secure, false to mark it as not secure.
     *
     * @return Request The updated request.
     */
    public function withSecure(bool $isSecure): Request
    {
        if ($isSecure === $this->isSecure) {
            return $this;
        }

        $that = clone $this;

        $isStandardPort = ($that->port === ($that->isSecure ? 443 : 80));

        if ($isStandardPort) {
            $that->port = $isSecure ? 443 : 80;
        }

        $that->isSecure = $isSecure;

        return $that;
    }

    /**
     * Returns the client IP address.
     *
     * @return string The IP address.
     */
    public function getClientIp() : string
    {
        return $this->clientIp;
    }

    /**
     * Returns a copy of this request with a new client IP address.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $ip The new IP address.
     *
     * @return Request The updated request.
     */
    public function withClientIp(string $ip): Request
    {
        $that = clone $this;
        $that->clientIp = $ip;

        return $that;
    }

    /**
     * Returns the content types accepted by the client.
     *
     * This method parses the Accept header,
     * and returns an associative array where keys are content types,
     * and values are the associated weights in the range [0-1]. Highest weights are returned first.
     *
     * @return array The accepted languages.
     */
    public function getAccept() : array
    {
        return $this->parseQualityValues($this->getHeader('Accept'));
    }

    /**
     * Returns the languages accepted by the client.
     *
     * This method parses the Accept-Language header,
     * and returns an associative array where keys are language tags,
     * and values are the associated weights in the range [0-1]. Highest weights are returned first.
     *
     * @return array The accepted languages.
     */
    public function getAcceptLanguage() : array
    {
        return $this->parseQualityValues($this->getHeader('Accept-Language'));
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * Returns a copy of this request with the given attribute set.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $name  The attribute name.
     * @param mixed  $value The attribute value.
     *
     * @return Request The updated request.
     */
    public function withAttribute(string $name, $value): Request
    {
        $that = clone $this;

        $that->attributes[$name] = $value;

        return $that;
    }

    /**
     * Parses quality values as defined per RFC 7231 § 5.3.1.
     *
     * @param string $header The header value as a string.
     *
     * @return array The parsed values as an associative array mapping the string to the quality value.
     */
    private function parseQualityValues(string $header) : array
    {
        $values = $this->parseHeaderParameters($header);

        /** @psalm-var list<array{string, float, int}> $result */
        $result = [];

        $count = count($values);
        $position = $count - 1;

        foreach ($values as $value => $parameters) {
            $parameters = array_change_key_case($parameters, CASE_LOWER);

            if (isset($parameters['q'])) {
                if (preg_match('/^((?:0\.?[0-9]{0,3})|(?:1\.?0{0,3}))$/', $parameters['q']) === 0) {
                    continue;
                }

                $quality = (float) $parameters['q'];
            } else {
                $quality = 1.0;
            }

            $weight = $position + $count * (int) ($quality * 1000.0);

            $result[] = [$value, $quality, $weight];

            $position--;
        }

        usort($result, static function(array $a, array $b): int {
            return $b[2] - $a[2];
        });

        $values = [];

        foreach ($result as $value) {
            $values[$value[0]] = $value[1];
        }

        return $values;
    }

    /**
     * Parses a header with multiple values and optional parameters.
     *
     * Example: text/html; charset=utf8, text/xml
     *       => ['text/html' => ['charset' => 'utf8'], 'text/xml' => []]
     *
     * @param string $header The header to parse.
     *
     * @return array<string, array<string, string>> An associative array of values and theirs parameters.
     */
    private function parseHeaderParameters(string $header) : array
    {
        $result = [];
        $values = explode(',', $header);

        foreach ($values as $parts) {
            $parameters = [];
            $parts = explode(';', $parts);
            $item = trim(array_shift($parts));

            if ($item === '') {
                continue;
            }

            foreach ($parts as $part) {
                if (preg_match('/^\s*([^\=]+)\=(.*?)\s*$/', $part, $matches) === 0) {
                    continue;
                }

                $parameters[$matches[1]] = $matches[2];
            }

            $result[$item] = $parameters;
        }

        return $result;
    }

    /**
     * Returns whether this request is sent with an XMLHttpRequest object.
     *
     * @return bool True if the request is AJAX, false otherwise.
     */
    public function isAjax() : bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
}
