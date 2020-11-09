<?php

declare(strict_types=1);

namespace Brick\Http\Tests;

use Brick\Http\Cookie;
use Brick\Http\MessageBody;
use Brick\Http\MessageBodyString;
use Brick\Http\MessageBodyResource;
use Brick\Http\Response;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for class Request.
 */
class ResponseTest extends TestCase
{
    public function testDefaults(): void
    {
        $response = new Response();

        $this->assertSame('HTTP/1.0 200 OK', $response->getStartLine());
        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame([], $response->getHeaders());
        $this->assertSame([], $response->getCookies());
        $this->assertNull($response->getBody());
    }

    /**
     * @dataProvider providerGetWithStatusCode
     *
     * @param int         $statusCode           The status code to set.
     * @param string|null $reasonPhrase         The reason phrase to set, or null to skip.
     * @param string      $expectedReasonPhrase The expected reason phrase.
     */
    public function testGetWithStatusCode(int $statusCode, ?string $reasonPhrase, string $expectedReasonPhrase): void
    {
        $response = new Response();

        $newResponse = $response->withStatusCode($statusCode, $reasonPhrase);

        $this->assertNotSame($response, $newResponse);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($statusCode, $newResponse->getStatusCode());
        $this->assertSame($expectedReasonPhrase, $newResponse->getReasonPhrase());
    }

    /**
     * @return array
     */
    public function providerGetWithStatusCode(): array
    {
        return [
            [404, null,     'Not Found'],
            [404, 'Custom', 'Custom'],
            [999, null,     'Unknown'],
            [999, 'Custom', 'Custom']
        ];
    }

    /**
     * @dataProvider providerWithInvalidStatusCodeThrowsException
     *
     * @param int $statusCode
     */
    public function testWithInvalidStatusCodeThrowsException(int $statusCode): void
    {
        $response = new Response();

        $this->expectException(InvalidArgumentException::class);

        $response->withStatusCode($statusCode);
    }

    /**
     * @return array
     */
    public function providerWithInvalidStatusCodeThrowsException(): array
    {
        return [
            [0],
            [99],
            [1000]
        ];
    }

    public function testWithWithoutCookies(): void
    {
        $response = new Response();

        $foo = new Cookie('foo', 'bar');
        $foo = $foo->withSecure(true);

        $responseWithFoo = $response->withCookie($foo);
        $this->assertNotSame($response, $responseWithFoo);
        $this->assertSame([], $response->getCookies());
        $this->assertSame([$foo], $responseWithFoo->getCookies());
        $this->assertSame(['foo=bar; Secure'], $responseWithFoo->getHeaderAsArray('Set-Cookie'));

        $bar = new Cookie('bar', 'baz');
        $bar = $bar->withHttpOnly(true);

        $responseWithFooBar = $responseWithFoo->withCookie($bar);

        $this->assertNotSame($responseWithFoo, $responseWithFooBar);
        $this->assertSame(['foo=bar; Secure'], $responseWithFoo->getHeaderAsArray('Set-Cookie'));
        $this->assertSame([$foo, $bar], $responseWithFooBar->getCookies());
        $this->assertSame(['foo=bar; Secure', 'bar=baz; HttpOnly'], $responseWithFooBar->getHeaderAsArray('Set-Cookie'));

        $responseWithNoCookies = $responseWithFooBar->withoutCookies();

        $this->assertNotSame($responseWithFooBar, $responseWithNoCookies);
        $this->assertSame([$foo, $bar], $responseWithFooBar->getCookies());
        $this->assertSame([], $responseWithNoCookies->getCookies());
        $this->assertSame([], $responseWithNoCookies->getHeaderAsArray('Set-Cookie'));
    }

    public function testWithContent(): void
    {
        $response = new Response();

        $newResponse = $response->withContent('Hello World');

        $this->assertNotSame($response, $newResponse);
        $this->assertSame('', (string) $response->getBody());
        $this->assertInstanceOf(MessageBody::class, $newResponse->getBody());
        $this->assertSame('Hello World', (string) $newResponse->getBody());
    }

    public function testWithContentAsResource(): void
    {
        $fp = fopen('php://memory', 'rb+');
        fwrite($fp, 'data');
        fseek($fp, 0);

        $response = new Response();

        $newResponse = $response->withContent($fp);
        $this->assertNotSame($response, $newResponse);
        $this->assertSame('', (string) $response->getBody());

        $newResponseBody = $newResponse->getBody();
        $this->assertInstanceOf(MessageBodyResource::class, $newResponseBody);
        $this->assertSame(4, $newResponseBody->getSize());
        $this->assertSame('data', (string) $newResponseBody);
    }

    public function testIsStatusCode(): void
    {
        $response = new Response();

        $this->assertTrue($response->isStatusCode(200));
        $this->assertFalse($response->isStatusCode(400));
    }

    public function testParseShouldThrowRuntimeExceptionError1(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not parse response (error 1).');

        Response::parse('<!DOCTYPE html><html>HTML strings</html>');
    }

    public function testParseShouldThrowRuntimeExceptionError2(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not parse response (error 2).');

        Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Type: text/plain' . "\r\n");
    }

    public function testParseShouldThrowRuntimeExceptionError3(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not parse response (error 3).');

        Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Typetext/plain' . "\r\n\r\n");
    }

    public function testParseShouldReturnResponseObject(): void
    {
        $result = Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Type: text/html' . "\r\n\r\n");

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('1.0', $result->getProtocolVersion());
        $this->assertSame(['Content-Type' => ['text/html'], 'Content-Length' => ['0']], $result->getHeaders());
    }

    public function testParseWithCookieHeaderShouldReturnResponseObject(): void
    {
        $result = Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Type: text/html' . "\r\n" . 'Set-Cookie: sessionid=38afes7a8; HttpOnly; Path=/' . "\r\n\r\n");

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('1.0', $result->getProtocolVersion());
        $this->assertSame(['Content-Type' => ['text/html'], 'Set-Cookie' => ['sessionid=38afes7a8; Path=/; HttpOnly'], 'Content-Length' => ['0']], $result->getHeaders());
    }

    public function testIsType(): void
    {
        $response = new Response();

        for ($statusCode = 100; $statusCode <= 999; $statusCode++) {
            $response = $response->withStatusCode($statusCode);
            $digit = substr((string) $statusCode, 0, 1);

            $this->assertSame($digit == 1, $response->isInformational());
            $this->assertSame($digit == 2, $response->isSuccessful());
            $this->assertSame($digit == 3, $response->isRedirection());
            $this->assertSame($digit == 4, $response->isClientError());
            $this->assertSame($digit == 5, $response->isServerError());
        }
    }

    public function testHasHeader(): void
    {
        $response = new Response();
        $response = $response->withHeader('Accept', 'image/png');

        $this->assertFalse($response->hasHeader('no_header_name'));
        $this->assertTrue($response->hasHeader('Accept'));
    }

    public function testWithAddedHeaderWithArrayValue(): void
    {
        $response = new Response();

        $newResponse = $response->withAddedHeader('Date', ['Sun', '18 Oct 2009 08:56:53 GMT']);

        $this->assertNotSame($response, $newResponse);
        $this->assertInstanceOf(Response::class, $newResponse);
        $this->assertSame('', $response->getHeader('Date'));
        $this->assertSame('Sun, 18 Oct 2009 08:56:53 GMT', $newResponse->getHeader('Date'));
    }

    public function testWithAddedHeaderWithExistingValue(): void
    {
        $response = new Response();
        $response = $response->withAddedHeader('Date', 'Sun');
        $response = $response->withAddedHeader('Date', ['18 Oct 2009 08:56:53 GMT']);

        $this->assertSame('Sun, 18 Oct 2009 08:56:53 GMT', $response->getHeader('Date'));
    }

    public function testWithAddedHeaders(): void
    {
        $response = new Response();

        $newResponse = $response->withAddedHeaders([
            'Content-Type' => 'text/html; charset=utf-8',
            'Server' => 'Apache',
        ]);

        $this->assertNotSame($response, $newResponse);
        $this->assertInstanceOf(Response::class, $newResponse);
        $this->assertSame([], $response->getHeaders());
        $this->assertSame('text/html; charset=utf-8', $newResponse->getHeader('Content-Type'));
        $this->assertSame('Apache', $newResponse->getHeader('Server'));
    }

    public function testGetHead(): void
    {
        $response = new Response();
        $response = $response->withAddedHeaders([
            'Content-Type' => 'text/html; charset=utf-8',
            'Server' => 'Apache',
        ]);
        $expectedHead = 'HTTP/1.0 200 OK' . "\r\n" . 'Content-Type: text/html; charset=utf-8' . "\r\n" . 'Server: Apache' . "\r\n\r\n";

        $this->assertSame($expectedHead, $response->getHead());
    }

    public function testGetContentLength(): void
    {
        $response = new Response();

        $this->assertSame(0, $response->getContentLength());
    }

    public function testGetContentLengthWithValue(): void
    {
        $response = new Response();
        $response = $response->withAddedHeader('Content-Length', 19730);

        $this->assertSame(19730, $response->getContentLength());
    }

    /**
     * @dataProvider providerIsContentType
     *
     * @param string $headerValue    The specific header value
     * @param bool   $expectedResult The expected result
     */
    public function testIsContentType(string $headerValue, bool $expectedResult): void
    {
        $response = new Response();
        $response = $response->withAddedHeader('Content-Type', $headerValue);

        $this->assertSame($expectedResult, $response->isContentType('text/html'));
    }

    /**
     * @return array
     */
    public function providerIsContentType(): array
    {
        return [
            ['text/html; charset=utf-8', true],
            ['text/html;', true],
            ['text/html', true],
            ['text/html charset=utf-8', false],
        ];
    }

    public function testCastToString(): void
    {
        $response = new Response();
        $response = $response->withBody(new MessageBodyString('param1=value1&param2=value2'));
        $response = $response->withHeader('Accept', 'image/png');
        $expectedString = 'HTTP/1.0 200 OK' . "\r\n" . 'Content-Length: 27' . "\r\n" . 'Accept: image/png' . "\r\n\r\n" . 'param1=value1&param2=value2';

        $this->assertSame($expectedString, (string) $response);
    }

    public function testClone(): void
    {
        $response = new Response();
        $response = $response->withBody(new MessageBodyString('param1=value1&param2=value2'));
        $cloneResponse = clone $response;

        $this->assertInstanceOf(Response::class, $cloneResponse);
        $this->assertInstanceOf(MessageBodyString::class, $cloneResponse->getBody());
        $this->assertSame(27, $cloneResponse->getContentLength());
        $this->assertSame('param1=value1&param2=value2', (string) $cloneResponse->getBody());
    }
}
