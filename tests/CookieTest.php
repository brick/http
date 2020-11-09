<?php

declare(strict_types=1);

namespace Brick\Http\Tests;

use Brick\Http\Cookie;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class Cookie.
 */
class CookieTest extends TestCase
{
    public function testConstructorAndDefaults(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $this->assertSame('foo', $cookie->getName());
        $this->assertSame('bar', $cookie->getValue());

        $this->assertSame(0, $cookie->getExpires());
        $this->assertSame(null, $cookie->getPath());
        $this->assertSame(null, $cookie->getDomain());
        $this->assertFalse($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
    }

    public function testIsHostOnlyShouldReturnTrue(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $this->assertTrue($cookie->isHostOnly());
    }

    public function testIsHostOnlyShouldReturnFalse(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $cookie = $cookie->withDomain('http://localhost');

        $this->assertFalse($cookie->isHostOnly());
    }

    /**
     * @dataProvider providerParse
     *
     * @param string      $cookieString
     * @param string      $name
     * @param string      $value
     * @param int         $expires
     * @param string|null $path
     * @param string|null $domain
     * @param bool        $secure
     * @param bool        $httpOnly
     */
    public function testParse(string $cookieString, string $name, string $value, int $expires, ?string $path, ?string $domain, bool $secure, bool $httpOnly): void
    {
        $cookie = Cookie::parse($cookieString);

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame($name, $cookie->getName());
        $this->assertSame($value, $cookie->getValue());
        $this->assertSame($expires, $cookie->getExpires());
        $this->assertSame($path, $cookie->getPath());
        $this->assertSame($domain, $cookie->getDomain());
        $this->assertSame($secure, $cookie->isSecure());
        $this->assertSame($httpOnly, $cookie->isHttpOnly());

        $cookie = Cookie::parse(strtoupper($cookieString));

        $name = strtoupper($name);
        $value = strtoupper($value);

        if ($path !== null) {
            $path = strtoupper($path);
        }

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame($name, $cookie->getName());
        $this->assertSame($value, $cookie->getValue());
        $this->assertSame($expires, $cookie->getExpires());
        $this->assertSame($path, $cookie->getPath());
        $this->assertSame($domain, $cookie->getDomain());
        $this->assertSame($secure, $cookie->isSecure());
        $this->assertSame($httpOnly, $cookie->isHttpOnly());
    }

    /**
     * @return array
     */
    public function providerParse(): array
    {
        return [
            ['foo=bar', 'foo', 'bar', 0, null, null, false, false],
            ['foo=bar; unknown=parameter', 'foo', 'bar', 0, null, null, false, false],
            ['foo=bar; expires=Sun, 09 Sep 2001 01:46:40 GMT', 'foo', 'bar', 1000000000, null, null, false, false],
            ['foo=bar; path=/baz', 'foo', 'bar', 0, '/baz', null, false, false],
            ['foo=bar; domain=example.com', 'foo', 'bar', 0, null, 'example.com', false, false],
            ['foo=bar; secure', 'foo', 'bar', 0, null, null, true, false],
            ['foo=bar; httponly', 'foo', 'bar', 0, null, null, false, true],
        ];
    }

    /**
     * @dataProvider providerParseInvalidCookieThrowsException
     *
     * @param string $cookieString
     */
    public function testParseInvalidCookieThrowsException(string $cookieString): void
    {
        $this->expectException(InvalidArgumentException::class);
        Cookie::parse($cookieString);
    }

    /**
     * @return array
     */
    public function providerParseInvalidCookieThrowsException(): array
    {
        return [
            [''],
            ['='],
            ['foo='],
            ['=bar']
        ];
    }

    public function testGetWithExpires(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $newCookie = $cookie->withExpires(123456789);

        $this->assertNotSame($cookie, $newCookie);
        $this->assertSame(0, $cookie->getExpires());
        $this->assertSame(123456789, $newCookie->getExpires());
    }

    public function testGetWithPath(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $newCookie = $cookie->withPath('/');

        $this->assertNotSame($cookie, $newCookie);
        $this->assertSame(null, $cookie->getPath());
        $this->assertSame('/', $newCookie->getPath());

        $newCookieWithNoPath = $newCookie->withPath(null);

        $this->assertNotSame($newCookie, $newCookieWithNoPath);
        $this->assertSame('/', $newCookie->getPath());
        $this->assertNull($newCookieWithNoPath->getPath());
    }

    public function testGetWithDomain(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $newCookie = $cookie->withDomain('example.com');

        $this->assertNotSame($cookie, $newCookie);
        $this->assertSame(null, $cookie->getDomain());
        $this->assertSame('example.com', $newCookie->getDomain());

        $newCookieWithNoDomain = $newCookie->withDomain(null);

        $this->assertNotSame($newCookie, $newCookieWithNoDomain);
        $this->assertSame('example.com', $newCookie->getDomain());
        $this->assertSame(null, $newCookieWithNoDomain->getDomain());
    }

    public function testIsWithSecure(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $newCookie = $cookie->withSecure(true);

        $this->assertNotSame($cookie, $newCookie);
        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($newCookie->isSecure());
    }

    public function testIsWithHttpOnly(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $newCookie = $cookie->withHttpOnly(true);

        $this->assertNotSame($cookie, $newCookie);
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertTrue($newCookie->isHttpOnly());
    }

    /**
     * @dataProvider providerIsExpiredIsPersistent
     *
     * @param int  $expires      The cookie expiration time.
     * @param bool $isExpired    The expected value for isExpired.
     * @param bool $isPersistent The expected value for isPersistent.
     */
    public function testIsExpiredIsPersistent(int $expires, bool $isExpired, bool $isPersistent): void
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withExpires($expires);

        $this->assertSame($isExpired, $cookie->isExpired());
        $this->assertSame($isPersistent, $cookie->isPersistent());
    }

    /**
     * @return array
     */
    public function providerIsExpiredIsPersistent(): array
    {
        return [
            [0,             false, false],
            [1,             true,  true],
            [time() - 1,    true,  true],
            [time() + 3600, false, true],
            [PHP_INT_MAX,   false, true]
        ];
    }

    public function testToString(): void
    {
        $cookie = new Cookie('foo', 'bar');
        $this->assertSame('foo=bar', (string) $cookie);

        $cookie = $cookie->withExpires(2000000000);
        $this->assertSame('foo=bar; Expires=Wed, 18 May 2033 03:33:20 +0000', (string) $cookie);

        $cookie = $cookie->withExpires(0)->withDomain('example.com');
        $this->assertSame('foo=bar; Domain=example.com', (string) $cookie);

        $cookie = $cookie->withDomain(null)->withPath('/');
        $this->assertSame('foo=bar; Path=/', (string) $cookie);

        $cookie = $cookie->withPath(null)->withSecure(true);
        $this->assertSame('foo=bar; Secure', (string) $cookie);

        $cookie = $cookie->withHttpOnly(true);
        $this->assertSame('foo=bar; Secure; HttpOnly', (string) $cookie);
    }
}
