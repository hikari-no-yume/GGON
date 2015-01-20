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

    public function testListDecode() {
        $this->assertEquals(
            Parser::parse('[]'),
            ['length' => '0']
        );

        $this->assertEquals(
            Parser::parse('[a, b]'),
            ['length' => '2', '0' => 'a', '1' => 'b']
        );
    }

    public function testMixed() {
        // contrived, but probably semi-representative sample
        $testCase = "{
    items: [
        {
            type: foo,
            x: 12.0,
            y: 13.5
        },
        {
            type: bar,
            x: 12.0,
            y: 13.5,
            'sub items': []
        }
    ]
}";

        $parsed = Parser::parse($testCase);

        $this->assertEquals(
            $parsed,
            [
                "items" => [
                    [
                        "type" => "foo",
                        "x" => "12.0",
                        "y" => "13.5"
                    ],
                    [
                        "type" => "bar",
                        "x" => "12.0",
                        "y" => "13.5",
                        "sub items" => [
                            "length" => 0
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(
            Encoder::encode($parsed),
             "{items:[{type:foo,x:12.0,y:13.5},{type:bar,x:12.0,y:13.5,'sub items':[]}]}"
        );
    }

    public function testListEncode() {
        // valid empty GGON list
        $this->assertEquals(
            Encoder::encode(["length" => "0"]),
            "[]"
        );

        // valid GGON list
        $this->assertEquals(
            Encoder::encode(["length" => "2", "0" => "a", "1" => "b"]),
            "[a,b]"
        );

        $encoded = Encoder::encode(["length" => "a", "0" => "a", "1" => "b"]);

        // length is invalid, not valid GGON list
        $this->assertEquals(
            $encoded[0],
            "{"
        );

        $encoded = Encoder::encode(["length" => "3", "0" => "a", "1" => "b"]);

        // length is wrong, also not valid GGON list
        $this->assertEquals(
            $encoded[0],
            "{"
        );
        
        $encoded = Encoder::encode(["length" => "3", "0" => "a", "2" => "c"]);

        // sparse, not a valid GGON list
        $this->assertEquals(
            $encoded[0],
            "{"
        );
    }
}
