<?php

namespace Brick\Http\Tests;

use Brick\Http\Cookie;
use Brick\Http\MessageBody;
use Brick\Http\MessageBodyString;
use Brick\Http\Response;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class Request.
 */
class ResponseTest extends TestCase
{
    public function testDefaults()
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
     * @dataProvider providerGetSetStatusCode
     *
     * @param string      $statusCode           The status code to set.
     * @param string|null $reasonPhrase         The reason phrase to set, or null to skip.
     * @param string      $expectedReasonPhrase The expected reason phrase.
     */
    public function testGetSetStatusCode($statusCode, $reasonPhrase, $expectedReasonPhrase)
    {
        $response = new Response();

        $this->assertSame($response, $response->setStatusCode($statusCode, $reasonPhrase));
        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($expectedReasonPhrase, $response->getReasonPhrase());
    }

    /**
     * @return array
     */
    public function providerGetSetStatusCode()
    {
        return [
            [404, null,     'Not Found'],
            [404, 'Custom', 'Custom'],
            [999, null,     'Unknown'],
            [999, 'Custom', 'Custom']
        ];
    }

    /**
     * @dataProvider providerSetInvalidStatusCodeThrowsException
     * @expectedException \InvalidArgumentException
     *
     * @param integer $statusCode
     */
    public function testSetInvalidStatusCodeThrowsException($statusCode)
    {
        $response = new Response();
        $response->setStatusCode($statusCode);
    }

    /**
     * @return array
     */
    public function providerSetInvalidStatusCodeThrowsException()
    {
        return [
            [0],
            [99],
            [1000]
        ];
    }

    public function testSetRemoveCookie()
    {
        $response = new Response();

        $foo = new Cookie('foo', 'bar');
        $foo->setSecure(true);

        $this->assertSame($response, $response->setCookie($foo));
        $this->assertSame([$foo], $response->getCookies());
        $this->assertSame(['foo=bar; Secure'], $response->getHeaderAsArray('Set-Cookie'));

        $bar = new Cookie('bar', 'baz');
        $bar->setHttpOnly(true);

        $this->assertSame($response, $response->setCookie($bar));
        $this->assertSame([$foo, $bar], $response->getCookies());
        $this->assertSame(['foo=bar; Secure', 'bar=baz; HttpOnly'], $response->getHeaderAsArray('Set-Cookie'));

        $this->assertSame($response, $response->removeCookies());
        $this->assertSame([], $response->getCookies());
        $this->assertSame([], $response->getHeaderAsArray('Set-Cookie'));
    }

    public function testSetContent()
    {
        $response = new Response();

        $this->assertSame($response, $response->setContent('Hello World'));
        $this->assertInstanceOf(MessageBody::class, $response->getBody());
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testSetContentWithResource()
    {
        $response = new Response();

        $this->assertSame($response, $response->setContent(fopen('php://input', 'rb')));
        $this->assertInstanceOf(MessageBody::class, $response->getBody());
        $this->assertSame('', (string) $response->getBody());
    }

    public function testIsStatusCode()
    {
        $response = new Response();

        $this->assertTrue($response->isStatusCode(200));
        $this->assertFalse($response->isStatusCode(400));
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Could not parse response (error 1).
     */
    public function testParseShouldReturnRuntimeExceptionError1()
    {
        Response::parse('<!DCOTYPE html><html>HTML strings</html>');
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Could not parse response (error 2).
     */
    public function testParseShouldReturnRuntimeExceptionError2()
    {
        Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Length: 20' . "\r\n");
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Could not parse response (error 3).
     */
    public function testParseShouldReturnRuntimeExceptionError3()
    {
        Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Length20' . "\r\n\r\n");
    }

    public function testParseShouldReturnResponseObject()
    {
        $result = Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Length: 20' . "\r\n\r\n");

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testParseWithCookieHeaderShouldReturnResponseObject()
    {
        $result = Response::parse('HTTP/1.0 200 OK' . "\r\n" . 'Content-Length: 20' . "\r\n" . 'Set-Cookie: sessionid=38afes7a8; HttpOnly; Path=/' . "\r\n\r\n");

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testIsType()
    {
        $response = new Response();

        for ($statusCode = 100; $statusCode <= 999; $statusCode++) {
            $response->setStatusCode($statusCode);
            $digit = substr($statusCode, 0, 1);

            $this->assertSame($digit == 1, $response->isInformational());
            $this->assertSame($digit == 2, $response->isSuccessful());
            $this->assertSame($digit == 3, $response->isRedirection());
            $this->assertSame($digit == 4, $response->isClientError());
            $this->assertSame($digit == 5, $response->isServerError());
        }
    }

    public function testHasHeader()
    {
        $response = new Response();
        //$resultTrue = $response->

        $this->assertFalse($response->hasHeader('no_header_name'));
    }

    public function testAddHeaderWithArrayValue()
    {
        $response = new Response();

        $this->assertInstanceOf(Response::class, $response->addHeader('Date', ['Sun', '18 Oct 2009 08:56:53 GMT']));
        $this->assertSame('Sun, 18 Oct 2009 08:56:53 GMT', $response->getHeader('Date'));
    }

    public function testAddHeaderWithArrayValueAppendExistedHeader()
    {
        $response = new Response();
        $response->addHeader('Date', 'Sun');
        $response->addHeader('Date', ['18 Oct 2009 08:56:53 GMT']);

        $this->assertSame('Sun, 18 Oct 2009 08:56:53 GMT', $response->getHeader('Date'));
    }

    public function testAddHeaders()
    {
        $response = new Response();
        $result = $response->addHeaders([
            'Content-Type' => 'text/html; charset=utf-8',
            'Server' => 'Apache',
        ]);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('text/html; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertSame('Apache', $response->getHeader('Server'));
    }

    public function testGetHead()
    {
        $response = new Response();
        $response->addHeaders([
            'Content-Type' => 'text/html; charset=utf-8',
            'Server' => 'Apache',
        ]);
        $expectedHead = 'HTTP/1.0 200 OK' . "\r\n" . 'Content-Type: text/html; charset=utf-8' . "\r\n" . 'Server: Apache' . "\r\n\r\n";

        $this->assertSame($expectedHead, $response->getHead());
    }

    public function testGetContentLength()
    {
        $response = new Response();

        $this->assertSame(0, $response->getContentLength());
    }

    public function testGetContentLengthShouldReturn19730()
    {
        $response = new Response();
        $response->addHeader('Content-Length', 19730);

        $this->assertSame(19730, $response->getContentLength());
    }

    public function testIsContentType()
    {
        $response = new Response();
        $response->addHeader('Content-Type', 'text/html; charset=utf-8');

        $this->assertTrue($response->isContentType('text/html'));
    }

    public function testClassInstanceShouldReturnMessageBodyString()
    {
        $response = new Response();
        $response->setBody(new MessageBodyString('param1=value1&param2=value2'));
        $response->addHeader('Content-Length', 19730);
        $expectedString = 'HTTP/1.0 200 OK' . "\r\n" . 'Content-Length: 27' . "\r\n" . 'Content-Length: 19730' . "\r\n\r\n" . 'param1=value1&param2=value2';

        $this->assertSame($expectedString, (string)$response);
    }

    public function testClassInstanceShouldBeCloned()
    {
        $response = new Response();
        $response->setBody(new MessageBodyString('param1=value1&param2=value2'));
        $cloneResponse = clone $response;

        $this->assertInstanceOf(Response::class, $cloneResponse);
        $this->assertInstanceOf(MessageBodyString::class, $cloneResponse->getBody());
    }
}
