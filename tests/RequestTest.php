<?php

declare(strict_types=1);

namespace Brick\Http\Tests;

use Brick\Http\Exception\HttpBadRequestException;
use Brick\Http\Request;
use Brick\Http\Url;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class Request.
 */
class RequestTest extends TestCase
{
    public function testDefaults(): void
    {
        $request = new Request();

        $this->assertSame('GET / HTTP/1.0', $request->getStartLine());
        $this->assertSame('1.0', $request->getProtocolVersion());
        $this->assertNull($request->getBody());
        $this->assertSame([], $request->getHeaders());
        $this->assertSame([], $request->getQuery());
        $this->assertSame([], $request->getPost());
        $this->assertSame([], $request->getCookie());
        $this->assertSame([], $request->getFiles());
        $this->assertFalse($request->isSecure());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('localhost', $request->getHost());
        $this->assertSame(80, $request->getPort());
        $this->assertSame('/', $request->getRequestUri());
        $this->assertSame('/', $request->getPath());
        $this->assertSame('', $request->getQueryString());
        $this->assertSame('0.0.0.0', $request->getClientIp());
    }

    public function testGetCurrentWithNoServerVariablesUsesDefaults(): void
    {
        $_SERVER = [];

        $this->assertEquals(new Request(), Request::getCurrent());
    }

    /**
     * @dataProvider providerGetCurrentWithHttps
     *
     * @param string $https    The HTTPS server value.
     * @param bool   $isSecure The expected isSecure() value.
     */
    public function testGetCurrentWithHttps(string $https, bool $isSecure): void
    {
        $_SERVER = ['HTTPS' => $https];
        $request = Request::getCurrent();

        $this->assertSame($isSecure, $request->isSecure());
    }

    /**
     * @return array
     */
    public function providerGetCurrentWithHttps(): array
    {
        return [
            ['on', true],
            ['1', true],
            ['off', false],
            ['0', false],
            ['', false],
            ['anything else', false]
        ];
    }

    /**
     * @dataProvider providerGetCurrentWithHostPort
     *
     * @param array   $server         The contents of the $_SERVER array.
     * @param int     $hostPortSource The value that will be passed to getCurrent().
     * @param string  $expectedHost   The expected host name.
     * @param int     $expectedPort   The expected port number.
     */
    public function testGetCurrentWithHostPort(array $server, int $hostPortSource, string $expectedHost, int $expectedPort): void
    {
        $_SERVER = $server;
        $request = Request::getCurrent(false, $hostPortSource);

        $this->assertSame($expectedHost, $request->getHost());
        $this->assertSame($expectedPort, $request->getPort());
    }

    /**
     * @return array
     */
    public function providerGetCurrentWithHostPort(): array
    {
        $https = [
            'HTTPS' => 'on'
        ];

        $httpHost = [
            'HTTP_HOST' => 'foo'
        ];

        $httpHostPort = [
            'HTTP_HOST' => 'foo:81'
        ];

        $serverName = [
            'SERVER_NAME' => 'bar'
        ];

        $serverPort = [
            'SERVER_PORT' => '82'
        ];

        return [
            [[],                                                 Request::PREFER_HTTP_HOST,   'localhost', 80],
            [[],                                                 Request::PREFER_SERVER_NAME, 'localhost', 80],
            [[],                                                 Request::ONLY_HTTP_HOST,     'localhost', 80],
            [[],                                                 Request::ONLY_SERVER_NAME,   'localhost', 80],

            [$https,                                             Request::PREFER_HTTP_HOST,   'localhost', 443],
            [$https,                                             Request::PREFER_SERVER_NAME, 'localhost', 443],
            [$https,                                             Request::ONLY_HTTP_HOST,     'localhost', 443],
            [$https,                                             Request::ONLY_SERVER_NAME,   'localhost', 443],

            [$httpHost,                                          Request::PREFER_HTTP_HOST,   'foo',       80],
            [$httpHost,                                          Request::PREFER_SERVER_NAME, 'foo',       80],
            [$httpHost,                                          Request::ONLY_HTTP_HOST,     'foo',       80],
            [$httpHost,                                          Request::ONLY_SERVER_NAME,   'localhost', 80],

            [$httpHost + $https,                                 Request::PREFER_HTTP_HOST,   'foo',       443],
            [$httpHost + $https,                                 Request::PREFER_SERVER_NAME, 'foo',       443],
            [$httpHost + $https,                                 Request::ONLY_HTTP_HOST,     'foo',       443],
            [$httpHost + $https,                                 Request::ONLY_SERVER_NAME,   'localhost', 443],

            [$httpHostPort,                                      Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort,                                      Request::PREFER_SERVER_NAME, 'foo',       81],
            [$httpHostPort,                                      Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort,                                      Request::ONLY_SERVER_NAME,   'localhost', 80],

            [$httpHostPort + $https,                             Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort + $https,                             Request::PREFER_SERVER_NAME, 'foo',       81],
            [$httpHostPort + $https,                             Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort + $https,                             Request::ONLY_SERVER_NAME,   'localhost', 443],

            [$serverName,                                        Request::PREFER_HTTP_HOST,   'bar',       80],
            [$serverName,                                        Request::PREFER_SERVER_NAME, 'bar',       80],
            [$serverName,                                        Request::ONLY_HTTP_HOST,     'localhost', 80],
            [$serverName,                                        Request::ONLY_SERVER_NAME,   'bar',       80],

            [$serverName + $https,                               Request::PREFER_HTTP_HOST,   'bar',       443],
            [$serverName + $https,                               Request::PREFER_SERVER_NAME, 'bar',       443],
            [$serverName + $https,                               Request::ONLY_HTTP_HOST,     'localhost', 443],
            [$serverName + $https,                               Request::ONLY_SERVER_NAME,   'bar',       443],

            [$serverPort,                                        Request::PREFER_HTTP_HOST,   'localhost', 82],
            [$serverPort,                                        Request::PREFER_SERVER_NAME, 'localhost', 82],
            [$serverPort,                                        Request::ONLY_HTTP_HOST,     'localhost', 80],
            [$serverPort,                                        Request::ONLY_SERVER_NAME,   'localhost', 82],

            [$serverPort + $https,                               Request::PREFER_HTTP_HOST,   'localhost', 82],
            [$serverPort + $https,                               Request::PREFER_SERVER_NAME, 'localhost', 82],
            [$serverPort + $https,                               Request::ONLY_HTTP_HOST,     'localhost', 443],
            [$serverPort + $https,                               Request::ONLY_SERVER_NAME,   'localhost', 82],

            [$serverName + $serverPort,                          Request::PREFER_HTTP_HOST,   'bar',       82],
            [$serverName + $serverPort,                          Request::PREFER_SERVER_NAME, 'bar',       82],
            [$serverName + $serverPort,                          Request::ONLY_HTTP_HOST,     'localhost', 80],
            [$serverName + $serverPort,                          Request::ONLY_SERVER_NAME,   'bar',       82],

            [$serverName + $serverPort + $https,                 Request::PREFER_HTTP_HOST,   'bar',       82],
            [$serverName + $serverPort + $https,                 Request::PREFER_SERVER_NAME, 'bar',       82],
            [$serverName + $serverPort + $https,                 Request::ONLY_HTTP_HOST,     'localhost', 443],
            [$serverName + $serverPort + $https,                 Request::ONLY_SERVER_NAME,   'bar',       82],

            [$httpHost + $serverName,                            Request::PREFER_HTTP_HOST,   'foo',       80],
            [$httpHost + $serverName,                            Request::PREFER_SERVER_NAME, 'bar',       80],
            [$httpHost + $serverName,                            Request::ONLY_HTTP_HOST,     'foo',       80],
            [$httpHost + $serverName,                            Request::ONLY_SERVER_NAME,   'bar',       80],

            [$httpHost + $serverName + $https,                   Request::PREFER_HTTP_HOST,   'foo',       443],
            [$httpHost + $serverName + $https,                   Request::PREFER_SERVER_NAME, 'bar',       443],
            [$httpHost + $serverName + $https,                   Request::ONLY_HTTP_HOST,     'foo',       443],
            [$httpHost + $serverName + $https,                   Request::ONLY_SERVER_NAME,   'bar',       443],

            [$httpHost + $serverPort,                            Request::PREFER_HTTP_HOST,   'foo',       80],
            [$httpHost + $serverPort,                            Request::PREFER_SERVER_NAME, 'foo',       82],
            [$httpHost + $serverPort,                            Request::ONLY_HTTP_HOST,     'foo',       80],
            [$httpHost + $serverPort,                            Request::ONLY_SERVER_NAME,   'localhost', 82],

            [$httpHost + $serverPort + $https,                   Request::PREFER_HTTP_HOST,   'foo',       443],
            [$httpHost + $serverPort + $https,                   Request::PREFER_SERVER_NAME, 'foo',       82],
            [$httpHost + $serverPort + $https,                   Request::ONLY_HTTP_HOST,     'foo',       443],
            [$httpHost + $serverPort + $https,                   Request::ONLY_SERVER_NAME,   'localhost', 82],

            [$httpHost + $serverName + $serverPort,              Request::PREFER_HTTP_HOST,   'foo',       80],
            [$httpHost + $serverName + $serverPort,              Request::PREFER_SERVER_NAME, 'bar',       82],
            [$httpHost + $serverName + $serverPort,              Request::ONLY_HTTP_HOST,     'foo',       80],
            [$httpHost + $serverName + $serverPort,              Request::ONLY_SERVER_NAME,   'bar',       82],

            [$httpHost + $serverName + $serverPort + $https,     Request::PREFER_HTTP_HOST,   'foo',       443],
            [$httpHost + $serverName + $serverPort + $https,     Request::PREFER_SERVER_NAME, 'bar',       82],
            [$httpHost + $serverName + $serverPort + $https,     Request::ONLY_HTTP_HOST,     'foo',       443],
            [$httpHost + $serverName + $serverPort + $https,     Request::ONLY_SERVER_NAME,   'bar',       82],

            [$httpHostPort + $serverName,                        Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort + $serverName,                        Request::PREFER_SERVER_NAME, 'bar',       81],
            [$httpHostPort + $serverName,                        Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort + $serverName,                        Request::ONLY_SERVER_NAME,   'bar',       80],

            [$httpHostPort + $serverName + $https,               Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort + $serverName + $https,               Request::PREFER_SERVER_NAME, 'bar',       81],
            [$httpHostPort + $serverName + $https,               Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort + $serverName + $https,               Request::ONLY_SERVER_NAME,   'bar',       443],

            [$httpHostPort + $serverPort,                        Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort + $serverPort,                        Request::PREFER_SERVER_NAME, 'foo',       82],
            [$httpHostPort + $serverPort,                        Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort + $serverPort,                        Request::ONLY_SERVER_NAME,   'localhost',  82],

            [$httpHostPort + $serverPort + $https,               Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort + $serverPort + $https,               Request::PREFER_SERVER_NAME, 'foo',       82],
            [$httpHostPort + $serverPort + $https,               Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort + $serverPort + $https,               Request::ONLY_SERVER_NAME,   'localhost',  82],

            [$httpHostPort + $serverName + $serverPort,          Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort + $serverName + $serverPort,          Request::PREFER_SERVER_NAME, 'bar',       82],
            [$httpHostPort + $serverName + $serverPort,          Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort + $serverName + $serverPort,          Request::ONLY_SERVER_NAME,   'bar',       82],

            [$httpHostPort + $serverName + $serverPort + $https, Request::PREFER_HTTP_HOST,   'foo',       81],
            [$httpHostPort + $serverName + $serverPort + $https, Request::PREFER_SERVER_NAME, 'bar',       82],
            [$httpHostPort + $serverName + $serverPort + $https, Request::ONLY_HTTP_HOST,     'foo',       81],
            [$httpHostPort + $serverName + $serverPort + $https, Request::ONLY_SERVER_NAME,   'bar',       82],
        ];
    }

    public function testGetCurrentWithRequestMethod(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST'];
        $request = Request::getCurrent();

        $this->assertSame('POST', $request->getMethod());
    }

    /**
     * @dataProvider providerGetCurrentWithRequestUri
     *
     * @param string $requestUri  The REQUEST_URI server value.
     * @param string $path        The expected path.
     * @param string $queryString The expected query string.
     * @param array  $query       The expected query array.
     */
    public function testGetCurrentWithRequestUri(string $requestUri, string $path, string $queryString, array $query): void
    {
        $_SERVER = ['REQUEST_URI' => $requestUri];
        $request = Request::getCurrent();

        $this->assertSame($requestUri, $request->getRequestUri());
        $this->assertSame($path, $request->getPath());
        $this->assertSame($queryString, $request->getQueryString());
        $this->assertSame($query, $request->getQuery());
    }

    /**
     * @return array
     */
    public function providerGetCurrentWithRequestUri(): array
    {
        return [
            ['/foo',             '/foo',     '',        []],
            ['/foo/bar?',        '/foo/bar', '',        []],
            ['/foo/bar?a=1',     '/foo/bar', 'a=1',     ['a' => '1']],
            ['/foo/bar?a=1&a=2', '/foo/bar', 'a=1&a=2', ['a' => '2']],
            ['/foo/bar?a=1&b=2', '/foo/bar', 'a=1&b=2', ['a' => '1', 'b' => '2']],
            ['/foo/bar?a[]=1',   '/foo/bar', 'a[]=1',   ['a' => ['1']]]
        ];
    }

    public function testGetCurrentWithServerProtocol(): void
    {
        $_SERVER = ['SERVER_PROTOCOL' => 'HTTP/1.1'];
        $request = Request::getCurrent();

        $this->assertSame('1.1', $request->getProtocolVersion());
    }

    public function testGetCurrentWithRemoteAddr(): void
    {
        $_SERVER = ['REMOTE_ADDR' => '12.34.56.78'];
        $request = Request::getCurrent();

        $this->assertSame('12.34.56.78', $request->getClientIp());
    }

    public function testGetCurrentWithHeaders(): void
    {
        $_SERVER = [
            'CONTENT_TYPE'    => 'text/xml',
            'CONTENT_LENGTH'  => '1234',
            'HTTP_USER_AGENT' => 'Mozilla'
        ];

        $request = Request::getCurrent();

        $this->assertEquals([
            'Content-Type'   => ['text/xml'],
            'Content-Length' => ['1234'],
            'User-Agent'     => ['Mozilla']
        ], $request->getHeaders());
    }

    /**
     * @dataProvider providerGetCurrentWithBody
     *
     * @param array $server
     * @param bool  $hasBody
     */
    public function testGetCurrentWithBody(array $server, bool $hasBody): void
    {
        $_SERVER = $server;
        $request = Request::getCurrent();

        $this->assertSame($hasBody, $request->getBody() !== null);
    }

    /**
     * @return array
     */
    public function providerGetCurrentWithBody(): array
    {
        return [
            [[],                                      false],
            [['CONTENT_TYPE' => 'text/plain'],        false],
            [['CONTENT_LENGTH' => '123'],             true],
            [['HTTP_TRANSFER_ENCODING' => 'chunked'], true]
        ];
    }

    public function testGetCurrentWithCookie(): void
    {
        $_COOKIE = ['foo' => 'bar'];
        $request = Request::getCurrent();

        $this->assertSame($_COOKIE, $request->getCookie());
    }

    public function testGetCurrentWithPost(): void
    {
        $_POST = ['foo' => 'bar'];
        $request = Request::getCurrent();

        $this->assertSame($_POST, $request->getPost());
    }

    public function testGetCurrentTrustProxyOff(): void
    {
        $_SERVER = $this->getProxyServerVariables();
        $request = Request::getCurrent();

        $this->assertSame('1.2.3.4', $request->getClientIp());
        $this->assertSame('foo', $request->getHost());
        $this->assertSame(81, $request->getPort());
        $this->assertFalse($request->isSecure());
    }

    public function testGetCurrentTrustProxyOn(): void
    {
        $_SERVER = $this->getProxyServerVariables();
        $request = Request::getCurrent(true);

        $this->assertSame('5.6.7.8', $request->getClientIp());
        $this->assertSame('bar', $request->getHost());
        $this->assertSame(444, $request->getPort());
        $this->assertTrue($request->isSecure());
    }

    /**
     * @return array
     */
    private function getProxyServerVariables(): array
    {
        return [
            'REMOTE_ADDR' => '1.2.3.4',
            'HTTP_HOST'   => 'foo:81',
            'HTTPS'       => 'off',

            'HTTP_X_FORWARDED_FOR'   => '5.6.7.8',
            'HTTP_X_FORWARDED_HOST'  => 'bar',
            'HTTP_X_FORWARDED_PORT'  => '444',
            'HTTP_X_FORWARDED_PROTO' => 'https'
        ];
    }

    /**
     * @dataProvider providerGetCurrentWithFiles
     *
     * @param string $key    The array key.
     * @param string $path   The expected path.
     * @param string $name   The expected file name.
     * @param string $type   The expected MIME type.
     * @param int    $size   The expected file size.
     * @param int    $status The expected upload status.
     */
    public function testGetCurrentWithFiles(string $key, string $path, string $name, string $type, int $size, int $status): void
    {
        $_FILES = $this->getSampleFilesArray();
        $request = Request::getCurrent();
        $file = $request->getFile($key);

        $this->assertSame($name, $file->getName());
        $this->assertSame($type, $file->getType());
        $this->assertSame($size, $file->getSize());
        $this->assertSame($path, $file->getPath());
        $this->assertSame($status, $file->getStatus());
    }

    /**
     * @return array
     */
    public function providerGetCurrentWithFiles(): array
    {
        return [
            ['logo',                       '/tmp/001', 'logo.png',  'image/png',  1001, UPLOAD_ERR_OK],
            ['pictures[0]',                '/tmp/002', 'a.jpg',     'image/jpeg', 1002, UPLOAD_ERR_EXTENSION],
            ['pictures[1]',                '/tmp/003', 'b.jpx',     'image/jpx',  1003, UPLOAD_ERR_CANT_WRITE],
            ['files[images][logo][small]', '/tmp/004', 'small.bmp', 'image/bmp',  1004, UPLOAD_ERR_FORM_SIZE],
            ['files[images][logo][large]', '/tmp/005', 'large.gif', 'image/gif',  1005, UPLOAD_ERR_PARTIAL]
        ];
    }

    public function testGetWithQuery(): void
    {
        $query = [
            'a' => 'x',
            'b' => [
                'c' => [
                    'd' => 'y',
                ]
            ]
        ];

        $request = new Request();
        $request = $request->withRequestUri('/test?foo=bar');

        $newRequest = $request->withQuery($query);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame(['foo' => 'bar'], $request->getQuery());
        $this->assertSame($query, $newRequest->getQuery());
        $this->assertSame('a=x&b%5Bc%5D%5Bd%5D=y', $newRequest->getQueryString());
        $this->assertSame('/test?a=x&b%5Bc%5D%5Bd%5D=y', $newRequest->getRequestUri());

        $this->assertNull($newRequest->getQuery('foo'));
        $this->assertSame('x', $newRequest->getQuery('a'));
        $this->assertSame('y', $newRequest->getQuery('b.c.d'));
        $this->assertSame('y', $newRequest->getQuery('b[c][d]'));
        $this->assertSame(['d' => 'y'], $newRequest->getQuery('b.c'));
        $this->assertSame(['d' => 'y'], $newRequest->getQuery('b[c]'));
        $this->assertNull($newRequest->getQuery('b.c.d.e'));
        $this->assertNull($newRequest->getQuery('b[c][d][e]'));
    }

    public function testGetWithPost(): void
    {
        $post = [
            'a' => 'x',
            'b' => [
                'c' => 'y'
            ]
        ];

        $request = new Request();

        $newRequest = $request->withPost($post);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame([], $request->getPost());

        $this->assertSame($post, $newRequest->getPost());
        $this->assertSame('x-www-form-urlencoded', $newRequest->getHeader('Content-Type'));
        $this->assertSame('14', $newRequest->getHeader('Content-Length'));
        $this->assertSame('a=x&b%5Bc%5D=y', (string) $newRequest->getBody());

        $this->assertNull($newRequest->getPost('foo'));
        $this->assertSame('x', $newRequest->getPost('a'));
        $this->assertSame('y', $newRequest->getPost('b.c'));
        $this->assertSame('y', $newRequest->getPost('b[c]'));
        $this->assertSame(['c' => 'y'], $newRequest->getPost('b'));
        $this->assertNull($newRequest->getPost('b.c.d'));
        $this->assertNull($newRequest->getPost('b[c][d]'));
    }

    public function testGetWithCookies(): void
    {
        $cookies = [
            'a' => 'w',
            'b' => 'y'
        ];

        $request = new Request();

        $requestWithCookies = $request->withCookies($cookies);

        $this->assertNotSame($request, $requestWithCookies);
        $this->assertSame([], $request->getCookie());
        $this->assertSame($cookies, $requestWithCookies->getCookie());

        $requestWithAddedCookies = $requestWithCookies->withAddedCookies([
            'a' => 'x',
            'c' => 'z'
        ]);

        $this->assertNotSame($requestWithCookies, $requestWithAddedCookies);
        $this->assertSame($cookies, $requestWithCookies->getCookie());
        $this->assertSame('x', $requestWithAddedCookies->getCookie('a'));
        $this->assertSame('y', $requestWithAddedCookies->getCookie('b'));
        $this->assertSame('z', $requestWithAddedCookies->getCookie('c'));
    }

    /**
     * @dataProvider providerGetFileReturnsNullWhenNotAFile
     *
     * @param string $name
     */
    public function testGetFileReturnsNullWhenNotAFile(string $name): void
    {
        $_FILES = $this->getSampleFilesArray();
        $request = Request::getCurrent();

        $this->assertNull($request->getFile($name));
    }

    /**
     * @return array
     */
    public function providerGetFileReturnsNullWhenNotAFile(): array
    {
        return [
            ['foo'],
            ['pictures'],
            ['pictures.0.test'],
            ['pictures[0][test]'],
            ['files'],
            ['files.images'],
            ['files.images.logo'],
            ['files.images.logo.small.test'],
            ['files[images]'],
            ['files[images[logo]'],
            ['files[images][logo][small][test]'],
        ];
    }

    /**
     * @dataProvider providerGetFile
     *
     * @param string $key
     * @param string $expectedFileName
     */
    public function testGetFile(string $key, string $expectedFileName): void
    {
        $_FILES = $this->getSampleFilesArray();
        $request = Request::getCurrent();

        $this->assertSame($expectedFileName, $request->getFile($key)->getName());
    }

    /**
     * @return array
     */
    public function providerGetFile(): array
    {
        return [
            ['logo',                       'logo.png'],
            ['pictures.0',                 'a.jpg'],
            ['pictures[1]',                'b.jpx'],
            ['files.images.logo.small',    'small.bmp'],
            ['files[images][logo][large]', 'large.gif']
        ];
    }

    /**
     * @dataProvider providerGetFiles
     *
     * @param string $key
     * @param array  $expectedFileNames
     */
    public function testGetFiles(string $key, array $expectedFileNames): void
    {
        $_FILES = $this->getSampleFilesArray();
        $request = Request::getCurrent();

        $files = $request->getFiles($key);

        foreach ($files as & $file) {
            $file = $file->getName();
        }

        $this->assertSame($expectedFileNames, $files);
    }

    /**
     * @return array
     */
    public function providerGetFiles(): array
    {
        return [
            ['foo',                       []],
            ['logo',                      ['logo.png']],
            ['pictures',                  [0 => 'a.jpg', 1 => 'b.jpx']],
            ['files',                     []],
            ['files.images',              []],
            ['files[images]',             []],
            ['files.images.logo',         ['small' => 'small.bmp', 'large' => 'large.gif']],
            ['files[images][logo]',       ['small' => 'small.bmp', 'large' => 'large.gif']],
            ['files.images.logo.test',    []],
            ['files[images][logo][test]', []],
        ];
    }

    public function testGetStartLine(): void
    {
        $request = (new Request())
            ->withMethod('POST')
            ->withRequestUri('/test')
            ->withProtocolVersion('1.1');

        $this->assertSame('POST /test HTTP/1.1', $request->getStartLine());
    }

    /**
     * @dataProvider providerGetWithIsMethod
     *
     * @param string $setMethod The method to set.
     * @param string $getMethod The expected method to get.
     * @param array  $isMethod  An associative array of methods to test against.
     */
    public function testGetWithIsMethod(string $setMethod, string $getMethod, array $isMethod): void
    {
        $request = new Request();

        $newRequest = $request->withMethod($setMethod);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($getMethod, $newRequest->getMethod());

        foreach ($isMethod as $method => $expectedIsMethod) {
            $this->assertSame($expectedIsMethod, $newRequest->isMethod($method));
        }
    }

    /**
     * @return array
     */
    public function providerGetWithIsMethod(): array
    {
        return [
            ['GET',     'GET',     ['GET'     => true, 'get'     => true,  'Get'     => true,  'POST' => false]],
            ['Connect', 'CONNECT', ['Connect' => true, 'connect' => true,  'CONNECT' => true,  'GET'  => false]],
            ['Delete',  'DELETE',  ['Delete'  => true, 'delete'  => true,  'DELETE'  => true,  'GET'  => false]],
            ['Get',     'GET',     ['Get'     => true, 'get'     => true,  'GET'     => true,  'POST' => false]],
            ['Head',    'HEAD',    ['Head'    => true, 'head'    => true,  'HEAD'    => true,  'GET'  => false]],
            ['Options', 'OPTIONS', ['Options' => true, 'options' => true,  'OPTIONS' => true,  'GET'  => false]],
            ['Post',    'POST',    ['Post'    => true, 'post'    => true,  'POST'    => true,  'GET'  => false]],
            ['Put',     'PUT',     ['Put'     => true, 'put'     => true,  'PUT'     => true,  'GET'  => false]],
            ['Trace',   'TRACE',   ['Trace'   => true, 'trace'   => true,  'TRACE'   => true,  'GET'  => false]],
            ['Track',   'TRACK',   ['Track'   => true, 'track'   => true,  'TRACK'   => true,  'GET'  => false]],
            ['Other',   'Other',   ['Other'   => true, 'other'   => false, 'OTHER'   => false, 'GET'  => false]]
        ];
    }

    /**
     * @dataProvider providerIsMethodSafe
     *
     * @param string $method
     * @param bool   $isSafe
     */
    public function testIsMethodSafe(string $method, bool $isSafe): void
    {
        $request = new Request();

        $request = $request->withMethod($method);
        $this->assertSame($isSafe, $request->isMethodSafe());

        $request = $request->withMethod(strtolower($method));
        $this->assertSame($isSafe, $request->isMethodSafe());
    }

    /**
     * @return array
     */
    public function providerIsMethodSafe(): array
    {
        return [
            ['GET', true],
            ['HEAD', true],
            ['POST', false],
            ['PUT', false],
            ['DELETE', false]
        ];
    }

    /**
     * @dataProvider providerGetWithScheme
     *
     * @param string $setScheme The scheme to set.
     * @param string $getScheme The expected scheme to get.
     * @param bool   $isSecure  The expected secure flag.
     */
    public function testGetWithScheme(string $setScheme, string $getScheme, bool $isSecure): void
    {
        $request = new Request();

        $newRequest = $request->withScheme($setScheme);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('http', $request->getScheme());
        $this->assertSame($getScheme, $newRequest->getScheme());
        $this->assertSame($isSecure, $newRequest->isSecure());
    }

    /**
     * @return array
     */
    public function providerGetWithScheme(): array
    {
        return [
            ['http',  'http',  false],
            ['Http',  'http',  false],
            ['HTTP',  'http',  false],
            ['https', 'https', true],
            ['Https', 'https', true],
            ['HTTPS', 'https', true]
        ];
    }

    public function testWithInvalidSchemeThrowsException(): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $request->withScheme('ftp');
    }

    public function testGetWithHostPort(): void
    {
        $request = new Request();

        $requestWithHost = $request->withHost('example.com');

        $this->assertNotSame($request, $requestWithHost);
        $this->assertSame('localhost', $request->getHost());
        $this->assertSame('example.com', $requestWithHost->getHost());
        $this->assertSame('example.com', $requestWithHost->getHeader('Host'));

        $requestWithHostAndPort = $requestWithHost->withPort(81);

        $this->assertNotSame($requestWithHost, $requestWithHostAndPort);
        $this->assertSame(80, $requestWithHost->getPort());
        $this->assertSame(81, $requestWithHostAndPort->getPort());
        $this->assertSame('example.com:81', $requestWithHostAndPort->getHeader('Host'));
    }

    public function testGetHostParts(): void
    {
        $request = new Request();
        $request = $request->withHost('www.example.com');

        $this->assertSame(['www', 'example', 'com'], $request->getHostParts());
    }

    /**
     * @dataProvider providerIsHost
     *
     * @param string $requestHost
     * @param string $testHost
     * @param bool   $includeSubDomains
     * @param bool   $result
     */
    public function testIsHost(string $requestHost, string $testHost, bool $includeSubDomains, bool $result): void
    {
        $request = new Request();
        $request = $request->withHost($requestHost);

        $this->assertSame($result, $request->isHost($testHost, $includeSubDomains));
    }

    /**
     * @return array
     */
    public function providerIsHost(): array
    {
        return [
            ['example.com', 'example.com', false, true],
            ['example.com', 'example.com', true, true],

            ['anexample.com', 'example.com', false, false],
            ['anexample.com', 'example.com', true, false],

            ['example2.com', 'example.com', false, false],
            ['example2.com', 'example.com', true, false],

            ['example.com', 'anexample.com', false, false],
            ['example.com', 'anexample.com', true, false],

            ['example.com', 'example2.com', false, false],
            ['example.com', 'example2.com', true, false],

            ['en.example.com', 'Example.com', false, false],
            ['en.example.com', 'Example.com', true, true],

            ['EN.admin.Example.com', 'example.com', false, false],
            ['EN.admin.Example.com', 'example.com', true, true],

            ['admin.anexample.com', 'example.com', false, false],
            ['admin.anexample.com', 'example.com', true, false],

            ['admin.example2.com', 'example.com', false, false],
            ['admin.example2.com', 'example.com', true, false],

            ['admin.example.com', 'anexample.com', false, false],
            ['admin.example.com', 'anexample.com', true, false],

            ['admin.example.com', 'example2.com', false, false],
            ['admin.example.com', 'example2.com', true, false],

            ['admin.example.com', 'admin.example.com', false, true],
            ['admin.Example.com', 'ADMIN.example.com', true, true],

            ['fr.admin.example.com', 'admin.example.com', false, false],
            ['fr.admin.Example.com', 'ADMIN.example.com', true, true],

            ['example.com', 'admin.example.com', false, false],
            ['example.com', 'admin.example.com', true, false],
        ];
    }

    public function testGetWithPath(): void
    {
        $request = new Request();
        $request = $request->withRequestUri('/user/profile?user=123');

        $newRequest = $request->withPath('/user/edit');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('/user/profile', $request->getPath());
        $this->assertSame('/user/edit', $newRequest->getPath());
        $this->assertSame('/user/edit?user=123', $newRequest->getRequestUri());
    }

    /**
     * @dataProvider providerGetPathParts
     *
     * @param string $path
     * @param array  $expectedParts
     */
    public function testGetPathParts(string $path, array $expectedParts): void
    {
        $request = new Request();
        $request = $request->withPath($path);

        $this->assertSame($expectedParts, $request->getPathParts());
    }

    /**
     * @return array
     */
    public function providerGetPathParts(): array
    {
        return [
            ['/',            []],
            ['//',           []],
            ['/foo',         ['foo']],
            ['//foo',        ['foo']],
            ['/foo/',        ['foo']],
            ['//foo//',      ['foo']],
            ['/foo/bar',     ['foo', 'bar']],
            ['//foo//bar//', ['foo', 'bar']]
        ];
    }

    public function testWithPathThrowsExceptionWhenPathIsInvalid(): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $request->withPath('/user/edit?user=123');
    }

    public function testGetWithQueryString(): void
    {
        $request = new Request();
        $request = $request->withRequestUri('/user/profile?user=123');

        $newRequest = $request->withQueryString('user=456');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('user=123', $request->getQueryString());
        $this->assertSame('user=456', $newRequest->getQueryString());
        $this->assertSame('/user/profile?user=456', $newRequest->getRequestUri());
    }

    public function testGetWithRequestUri(): void
    {
        $request = new Request();

        $newRequest = $request->withRequestUri('/test?foo=bar');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('/', $request->getRequestUri());
        $this->assertSame('/test?foo=bar', $newRequest->getRequestUri());
        $this->assertSame('/test', $newRequest->getPath());
        $this->assertSame('foo=bar', $newRequest->getQueryString());
        $this->assertSame(['foo' => 'bar'], $newRequest->getQuery());
    }

    /**
     * @dataProvider providerGetWithUrl
     *
     * @param string      $url         The URL to test.
     * @param string|null $expectedUrl The expected URL, or NULL to use the original URL.
     * @param string      $host        The expected host name.
     * @param int         $port        The expected port number.
     * @param string      $requestUri  The expected request URI.
     * @param string      $path        The expected path.
     * @param string      $qs          The expected query string.
     * @param bool        $isSecure    The expected isSecure flag.
     * @param array       $query       The expected query parameters.
     */
    public function testGetWithUrl(string $url, ?string $expectedUrl, string $host, int $port, string $requestUri, string $path, string $qs, bool $isSecure, array $query): void
    {
        $request = new Request();

        // Set some values to ensure the defaults get overridden.
        $request = $request->withPort(999);
        $request = $request->withSecure(true);
        $request = $request->withRequestUri('/path?a=b');

        $newRequest = $request->withUrl($url);
        $this->assertNotSame($request, $newRequest);

        // original request should be unaffected
        $this->assertSame(999, $request->getPort());
        $this->assertSame(true, $request->isSecure());
        $this->assertSame('/path?a=b', $request->getRequestUri());

        $this->assertSame($expectedUrl ?: $url, $newRequest->getUrl());
        $this->assertSame($host, $newRequest->getHost());
        $this->assertSame($port, $newRequest->getPort());
        $this->assertSame($requestUri, $newRequest->getRequestUri());
        $this->assertSame($path, $newRequest->getPath());
        $this->assertSame($qs, $newRequest->getQueryString());
        $this->assertSame($isSecure, $newRequest->isSecure());
        $this->assertSame($query, $newRequest->getQuery());
    }

    /**
     * @return array
     */
    public function providerGetWithUrl(): array
    {
        return [
            ['http://foo',            'http://foo/',  'foo', 80,  '/',      '/',     '',    false, []],
            ['https://foo/',          'https://foo/', 'foo', 443, '/',      '/',     '',    true,  []],
            ['http://foo:81/x/y',     null,           'foo', 81,  '/x/y',   '/x/y',  '',    false, []],
            ['https://x.y:444/z?x=y', null,           'x.y', 444, '/z?x=y', '/z',    'x=y', true,  ['x' => 'y']]
        ];
    }

    /**
     * @dataProvider providerUrlBase
     *
     * @param string $url
     * @param string $expectedUrlBase
     */
    public function testGetUrlBase(string $url, string $expectedUrlBase): void
    {
        $request = new Request();
        $request = $request->withUrl($url);

        $this->assertSame($expectedUrlBase, $request->getUrlBase());
    }

    /**
     * @return array
     */
    public function providerUrlBase(): array
    {
        return [
            ['http://example.com',              'http://example.com'],
            ['http://example.com/',             'http://example.com'],
            ['https://example.com',             'https://example.com'],
            ['https://example.com/',            'https://example.com'],
            ['http://example.com/foo/bar',      'http://example.com'],
            ['https://example.com/foo/bar?x=y', 'https://example.com']
        ];
    }

    public function testIsWithSecure(): void
    {
        $request = new Request();

        $newRequest = $request->withSecure(true);
        $this->assertNotSame($request, $newRequest);

        // Making the request secure should change the port number to 443.
        $this->assertTrue($newRequest->isSecure());
        $this->assertSame(443, $newRequest->getPort());

        // Reverting to non-secure should change the port number to 80.
        $newRequest = $newRequest->withSecure(false);
        $this->assertFalse($newRequest->isSecure());
        $this->assertSame(80, $newRequest->getPort());

        // Making the request secure with a non-standard port should not change the port.
        $newRequest = $newRequest->withPort(81)->withSecure(true);
        $this->assertTrue($newRequest->isSecure());
        $this->assertSame(81, $newRequest->getPort());
    }

    public function testGetWithClientIp(): void
    {
        $request = new Request();

        $newRequest = $request->withClientIp('4.3.2.1');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('0.0.0.0', $request->getClientIp());
        $this->assertSame('4.3.2.1', $newRequest->getClientIp());
    }

    public function testGetFirstLastHeader(): void
    {
        $request = new Request();
        $request = $request->withAddedHeader('Referer', 'http://example.com/1');
        $request = $request->withAddedHeader('Referer', 'http://example.com/2');
        $request = $request->withAddedHeader('Referer', 'http://example.com/3');

        $this->assertSame('http://example.com/1', $request->getFirstHeader('Referer'));
        $this->assertSame('http://example.com/3', $request->getLastHeader('Referer'));
    }

    public function testGetFirstLastHeaderNull(): void
    {
        $request = new Request();
        $this->assertNull($request->getFirstHeader('Referer'));
        $this->assertNull($request->getLastHeader('Referer'));
    }

    public function testGetReferer(): void
    {
        $request = new Request();
        $request = $request->withAddedHeader('Referer', 'https://example.com/path?query=string');
        $referer = $request->getReferer();

        $this->assertInstanceOf(Url::class, $referer);
        $this->assertSame('https://example.com/path?query=string', (string) $referer);
    }

    public function testGetRefererEmpty(): void
    {
        $request = new Request();
        $this->assertNull($request->getReferer());
    }

    public function testGetRefererInvalid(): void
    {
        $request = new Request();
        $request = $request->withAddedHeader('Referer', 'example.com');
        $this->assertNull($request->getReferer());
    }

    public function testGetCurrentWithInvalidProtocolThrowsException(): void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'invalid_protocol';

        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Invalid protocol: invalid_protocol');
        Request::getCurrent();
    }

    public function testWithFiles(): void
    {
        $uploadedFile = UploadedFileTest::createSampleUploadedFile();

        $expectedArray = [
            'image' => $uploadedFile,
            'documents' => [
                $uploadedFile,
                $uploadedFile,
            ]
        ];

        $request = new Request();
        $newRequest = $request->withFiles($expectedArray);

        $this->assertNotSame($request, $newRequest);
        $this->assertInstanceOf(Request::class, $newRequest);
        $this->assertSame([], $request->getFiles());
        $this->assertSame($expectedArray, $newRequest->getFiles());
    }

    public function testWithFilesWithInvalidContentsThrowException(): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $request->withFiles(['nested' => ['array' => ['contains' => 'string']]]);
    }

    public function testWithCookiesShouldUpdateCookieHeader(): void
    {
        $request = new Request();

        $newRequest = $request->withCookies(['Key' => 'Value']);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame([], $request->getCookie());
        $this->assertCount(1, $newRequest->getCookie());
        $this->assertSame('Key=Value', $newRequest->getHeader('Cookie'));

        $newRequestWithNoCookies = $newRequest->withCookies([]);

        $this->assertNotSame($newRequest, $newRequestWithNoCookies);
        $this->assertCount(1, $newRequest->getCookie());
        $this->assertCount(0, $newRequestWithNoCookies->getCookie());
        $this->assertSame('', $newRequestWithNoCookies->getHeader('Cookie'));
    }

    /**
     * @dataProvider providerWithRequestUriWithNoQueryString
     *
     * @param string $requestUri
     */
    public function testWithRequestUriWithNoQueryString(string $requestUri): void
    {
        $request = new Request();
        $newRequest = $request->withRequestUri($requestUri);

        $this->assertNotSame($request, $newRequest);
        $this->assertInstanceOf(Request::class, $newRequest);
        $this->assertSame('/', $request->getRequestUri());
        $this->assertSame($requestUri, $newRequest->getRequestUri());
        $this->assertSame([], $newRequest->getQuery());
        $this->assertSame('', $newRequest->getQueryString());
        $this->assertSame(rtrim($requestUri, '?'), $newRequest->getPath());
    }

    /**
     * @return array
     */
    public function providerWithRequestUriWithNoQueryString(): array
    {
        return [
            ['/foo'],
            ['/foo/bar?']
        ];
    }

    /**
     * @dataProvider providerAccept
     *
     * @param string $accept         The Accept header.
     * @param array  $expectedResult The expected result.
     */
    public function testGetAccept(string $accept, array $expectedResult): void
    {
        $request = new Request();
        $request = $request->withHeader('Accept', $accept);
        $this->assertSame($expectedResult, $request->withUrl('http://localhost:8000')->getAccept());
    }

    /**
     * @return array
     */
    public function providerAccept(): array
    {
        return [
            ['', []],
            [' ', []],
            ['image/png', ['image/png' => 1.0]],
            ['image/png, image/jpeg', ['image/png' => 1.0, 'image/jpeg' => 1.0]],
            ['image/*', ['image/*' => 1.0]],
            ['text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8', ['text/html' => 1.0, 'application/xhtml+xml' => 1.0, 'application/xml' => 0.9, '*/*' => 0.8]]
        ];
    }

    /**
     * @dataProvider providerIsAjax
     *
     * @param string $ajax           X-Requested-With header.
     * @param bool   $expectedResult The expected result.
     */
    public function testIsAjax(string $ajax, bool $expectedResult): void
    {
        $request = new Request();
        $request = $request->withHeader('X-Requested-With', $ajax);
        $this->assertSame($expectedResult, $request->isAjax());
    }

    /**
     * @return array
     */
    public function providerIsAjax(): array
    {
        return [
            ['', false],
            ['XMLHttpRequest', true]
        ];
    }

    public function testWithUrlWithInvalidUrl(): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL provided is not valid.');

        $request->withUrl('http:////invalid_url');
    }

    public function testWithUrlWithNoUrlScheme(): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL must have a scheme.');

        $request->withUrl('invalid_protocol://invalid_url');
    }

    public function testWithUrlWithUnsupportedProtocol(): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL scheme "ftp" is not acceptable.');

        $request->withUrl('ftp://invalid_url');
    }

    public function testWithUrlWithNoHostName(): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL must have a host name.');

        $request->withUrl('http:sub.site.org');
    }

    /**
     * @dataProvider providerAcceptLanguage
     *
     * @param string $acceptLanguage The Accept-Language header.
     * @param array  $expectedResult The expected result.
     */
    public function testGetAcceptLanguage(string $acceptLanguage, array $expectedResult): void
    {
        $request = new Request();

        $request = $request->withHeader('Accept-Language', $acceptLanguage);
        $this->assertSame($expectedResult, $request->getAcceptLanguage());
    }

    /**
     * @return array
     */
    public function providerAcceptLanguage(): array
    {
        return [
            ['', []],
            [' ', []],
            ['en-us', ['en-us' => 1.0]],
            ['en-us, fr-fr', ['en-us' => 1.0, 'fr-fr' => 1.0]],
            ['en-us, fr-fr; q=0.5', ['en-us' => 1.0, 'fr-fr' => 0.5]],
            ['en-us; q=0.5, fr-fr', ['fr-fr' => 1.0, 'en-us' => 0.5]],
            ['en-us, fr-fr; q=0.5, en-gb', ['en-us' => 1.0, 'en-gb' => 1.0, 'fr-fr' => 0.5]],
            ['en-ca; q=0.6, en; q=0.5, fr; q=0, en-us, en-gb; q=0.9', ['en-us' => 1.0, 'en-gb' => 0.9, 'en-ca' => 0.6, 'en' => 0.5, 'fr' => 0.0]],
            [' en-ca, fr-fr ; q=1.1, fr-ca ; q=0.9 , en-us  ,  pt-br ; q=0.000, en-gb ; q=1.000, fr-be ; q=1.0000', ['en-ca' => 1.0, 'en-us' => 1.0, 'en-gb' => 1.0, 'fr-ca' => 0.9, 'pt-br' => 0.0]]
        ];
    }

    /**
     * @return array
     */
    private function getSampleFilesArray(): array
    {
        return [
            'logo' => [
                'tmp_name' => '/tmp/001',
                'name'     => 'logo.png',
                'type'     => 'image/png',
                'size'     => 1001,
                'error'    => UPLOAD_ERR_OK,
            ],
            'pictures' => [
                'tmp_name' => [
                    0 => '/tmp/002',
                    1 => '/tmp/003'
                ],
                'name' => [
                    0 => 'a.jpg',
                    1 => 'b.jpx'
                ],
                'type' => [
                    0 => 'image/jpeg',
                    1 => 'image/jpx'
                ],
                'size' => [
                    0 => 1002,
                    1 => 1003
                ],
                'error' => [
                    0 => UPLOAD_ERR_EXTENSION,
                    1 => UPLOAD_ERR_CANT_WRITE
                ]
            ],
            'files' => [
                'tmp_name' => [
                    'images' => [
                        'logo' => [
                            'small' => '/tmp/004',
                            'large' => '/tmp/005'
                        ]
                    ]
                ],
                'name' => [
                    'images' => [
                        'logo' => [
                            'small' => 'small.bmp',
                            'large' => 'large.gif'
                        ]
                    ]
                ],
                'type' => [
                    'images' => [
                        'logo' => [
                            'small' => 'image/bmp',
                            'large' => 'image/gif'
                        ]
                    ]
                ],
                'size' => [
                    'images' => [
                        'logo' => [
                            'small' => 1004,
                            'large' => 1005
                        ]
                    ]
                ],
                'error' => [
                    'images' => [
                        'logo' => [
                            'small' => UPLOAD_ERR_FORM_SIZE,
                            'large' => UPLOAD_ERR_PARTIAL
                        ]
                    ]
                ],
            ]
        ];
    }
}
