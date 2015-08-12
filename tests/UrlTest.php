<?php

namespace Brick\Http\Tests;
use Brick\Http\Url;

/**
 * Tests for class Url.
 */
class UrlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerValidUrl
     *
     * @param string $url
     * @param string $toString
     * @param string $scheme
     * @param string $host
     * @param int    $port
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @param bool   $isStandardPort
     * @param bool   $isSecure
     */
    public function testValidUrl($url, $toString, $scheme, $host, $port, $path, $query, $fragment, $isStandardPort, $isSecure)
    {
        $url = new Url($url);

        $this->assertSame($toString, (string) $url);
        $this->assertSame($scheme, $url->getScheme());
        $this->assertSame($host, $url->getHost());
        $this->assertSame($port, $url->getPort());
        $this->assertSame($path, $url->getPath());
        $this->assertSame($query, $url->getQuery());
        $this->assertSame($fragment, $url->getFragment());
        $this->assertSame($isStandardPort, $url->isStandardPort());
        $this->assertSame($isSecure, $url->isSecure());
    }

    /**
     * @return array
     */
    public function providerValidUrl()
    {
        return [
            ['http://example.com', 'http://example.com/', 'http', 'example.com', 80, '/', '', '', true, false],
            ['https://example.com/test?', 'https://example.com/test', 'https', 'example.com', 443, '/test', '', '', true, true],
            ['http://example.com:81/?a=b', 'http://example.com:81/?a=b', 'http', 'example.com', 81, '/', 'a=b', '', false, false],
            ['http://test.example.com:443/#', 'http://test.example.com:443/', 'http', 'test.example.com', 443, '/', '', '', false, false],
            ['https://test.example.com:443/#test', 'https://test.example.com/#test', 'https', 'test.example.com', 443, '/', '', 'test', true, true],
            ['https://example.com:80/a?b#c', 'https://example.com:80/a?b#c', 'https', 'example.com', 80, '/a', 'b', 'c', false, true],
            ['http://test.example.com:80/a?b#c', 'http://test.example.com/a?b#c', 'http', 'test.example.com', 80, '/a', 'b', 'c', true, false],
        ];
    }

    /**
     * @dataProvider providerInvalidUrl
     * @expectedException \InvalidArgumentException
     *
     * @param string $url
     */
    public function testInvalidUrl($url)
    {
        new Url($url);
    }

    /**
     * @return array
     */
    public function providerInvalidUrl()
    {
        return [
            ['ftp://example.com/'],
            ['http://'],
            ['https:///path'],
            ['http://host:port'],
        ];
    }
}
