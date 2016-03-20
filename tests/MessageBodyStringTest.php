<?php

namespace Brick\Http\Tests;

use Brick\Http\MessageBodyString;

class MessageBodyStringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerWrite
     *
     * @param string $string   The original string.
     * @param int    $seek     The seek position.
     * @param string $write    The string to write at that position.
     * @param string $expected The expected result string.
     */
    public function testWrite($string, $seek, $write, $expected)
    {
        $body = new MessageBodyString($string);
        $body->seek($seek);
        $body->write($write);

        $this->assertSame($expected, (string) $body);
    }

    /**
     * @return array
     */
    public function providerWrite()
    {
        return [
            ['', 0, 'hello', 'hello'],
            ['', 1, 'hello', "\0hello"],
            ['', 2, 'hello', "\0\0hello"],

            ['hello', 0, 'world', 'world'],
            ['hello', 1, 'world', 'hworld'],
            ['hello', 2, 'world', 'heworld'],
            ['hello', 3, 'world', 'helworld'],
            ['hello', 4, 'world', 'hellworld'],
            ['hello', 5, 'world', 'helloworld'],
            ['hello', 6, 'world', "hello\0world"],
            ['hello', 7, 'world', "hello\0\0world"],

            ['hello world', 0, 'XXXXX', 'XXXXX world'],
            ['hello world', 1, 'XXXXX', 'hXXXXXworld'],
            ['hello world', 2, 'XXXXX', 'heXXXXXorld'],
            ['hello world', 3, 'XXXXX', 'helXXXXXrld'],
            ['hello world', 4, 'XXXXX', 'hellXXXXXld'],
            ['hello world', 5, 'XXXXX', 'helloXXXXXd'],
            ['hello world', 6, 'XXXXX', 'hello XXXXX'],
            ['hello world', 7, 'XXXXX', 'hello wXXXXX'],
            ['hello world', 8, 'XXXXX', 'hello woXXXXX'],
            ['hello world', 9, 'XXXXX', 'hello worXXXXX'],
            ['hello world', 10, 'XXXXX', 'hello worlXXXXX'],
            ['hello world', 11, 'XXXXX', 'hello worldXXXXX'],
            ['hello world', 12, 'XXXXX', "hello world\0XXXXX"],
        ];
    }

    public function testWriteTwice()
    {
        $body = new MessageBodyString('Hello');
        $body->seek(2);
        $body->write('LLO ');
        $body->write('world');

        $this->assertSame('HeLLO world', (string) $body);
    }
}
