<?php

namespace ajf\GGON;

class Tests extends \PHPUnit_Framework_TestCase
{
    public function testBarewords() {
        // completely valid bareword string
        $this->assertEquals(
            Parser::parse('_.+-2717281982abcdfoobar'),
            '_.+-2717281982abcdfoobar'
        );
    }

    public function testStrings() {
        $this->assertEquals(
            Parser::parse('\'foobar\n\r\t\0blah\''),
            "foobar\n\r\t\x00blah"
        );
    }

    public function testMaps() {
        $this->assertEquals(
            Parser::parse('{}'),
            []
        );

        $this->assertEquals(
            Parser::parse('{foo:bar,
\'baz\': \'qux\' }'),
            ['foo' => 'bar', 'baz' => 'qux']
        );
    }
}
