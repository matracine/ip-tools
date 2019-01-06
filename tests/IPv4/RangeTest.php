<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 * Inspired by Gijs Kunze https://github.com/gwkunze/IpAddress 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\IPv4;

use PHPUnit\Framework\TestCase;

use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IPv4\Range;
use mracine\IPTools\IP;

class RangeTest extends TestCase
{
    /**
     * @dataProvider constructionProvider
     *
     * @param Address $ip1
     * @param Address $ip2
     */
    public function testConstruction(Address $ip1, Address $ip2)
    {
        $range = new Range($ip1, $ip2);

        $this->assertGreaterThanOrEqual($range->getLowerBound()->int(), $range->getUpperBound()->int());
    }

    /**
     * @dataProvider constructionProvider
     *
     * @param Address $ip1
     * @param Address $ip2
     * @param int $count
     */
    public function testCount(Address $ip1, Address $ip2, int $count)
    {
        $range = new Range($ip1, $ip2);

        $this->assertEquals(count($range), $count);
    }

    /**
     * @dataProvider containsProvider
     *
     * @param Address $ip1
     * @param Address $ip2
     */
    public function testContains(Address $ip1, Address $ip2, Address $ipTest, bool $expected)
    {
        $range = new Range($ip1, $ip2);
        if ($expected)
        {
            $this->assertTrue($range->contains($ipTest));
        }
        else
        {
            $this->assertFalse($range->contains($ipTest));
        }
    }

    public function constructionProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'),         Address::fromString('0.0.0.0') ,        1 ],
            [ Address::fromString('0.0.0.0'),         Address::fromString('255.255.255.255'), 0xffffffff+1 ],
            [ Address::fromString('255.255.255.255'), Address::fromString('0.0.0.0'),         0xffffffff+1 ],
            [ Address::fromString('255.255.255.255'), Address::fromString('255.255.255.255'), 1 ],
            [ Address::fromString('10.2.35.4'),       Address::fromString('10.2.35.10'),      7 ],
            [ Address::fromString('10.2.35.10'),      Address::fromString('10.2.35.4'),       7 ],
        ];
    }

    public function containsProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'),         Address::fromString('0.0.0.0') ,        Address::fromString('0.0.0.0'), true ],
            [ Address::fromString('0.0.0.0'),         Address::fromString('0.0.0.0') ,        Address::fromString('0.0.0.1'), false ],
            [ Address::fromString('0.0.0.0'),         Address::fromString('255.255.255.255'), Address::fromString('0.0.0.0'), true ],
            [ Address::fromString('0.0.0.0'),         Address::fromString('255.255.255.255'), Address::fromString('255.255.255.255'), true ],
            [ Address::fromString('255.255.255.255'), Address::fromString('0.0.0.0'),         Address::fromString('0.0.0.0'), true ],
            [ Address::fromString('255.255.255.255'), Address::fromString('0.0.0.0'),         Address::fromString('1.2.3.4'), true ],
            [ Address::fromString('255.255.255.255'), Address::fromString('0.0.0.0'),         Address::fromString('255.255.255.255'), true ],
            [ Address::fromString('0.0.0.0'),         Address::fromString('10.0.0.0'),        Address::fromString('9.255.255.255'), true ],
            [ Address::fromString('10.255.255.255'),  Address::fromString('255.255.255.255'), Address::fromString('11.255.255.255'), true ],
            [ Address::fromString('10.255.255.255'),  Address::fromString('255.255.255.255'), Address::fromString('11.0.0.0'), true ],
        ];

    }

    public function testImmutable()
    {
        $ip1 = Address::fromString('10.0.0.1');
        $ip2 = Address::fromString('10.0.0.2');

        $range = new Range($ip1, $ip2);

        $this->assertNotSame($ip1, $range->getLowerBound());
        $this->assertNotSame($ip2, $range->getUpperBound());
        $this->assertNotSame($range->getLowerBound(), $range->getLowerBound());
        $this->assertNotSame($range->getUpperBound(), $range->getUpperBound());
    }

    /**
     * @dataProvider arrrayAccessProvider
     */
    public function testArrayAcces(Range $range, $offset, Address $expected)
    {
        $this->assertEquals($expected->int(), $range[$offset]->int());
    }

    /**
     * @dataProvider arrrayAccessInvalidOffsetTypeProvider
     * @expectedException InvalidArgumentException
     */
    public function testArrayAccesInvalidOffsetType(Range $range, $offset)
    {
        $range[$offset];
    }

    /**
     * @dataProvider arrrayAccessOutOfBoundsProvider
     * @expectedException OutOfBoundsException
     */
    public function testArrrayAccessOutOfBounds(Range $range, $offset)
    {
        $range[$offset];
    }

    public function arrrayAccessProvider()
    {
        return [
             [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), 0, Address::fromString("0.0.0.0") ],
             [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), 0xffffffff, Address::fromString("255.255.255.255") ],
             [ new Range(Address::fromString("0.0.0.255"), Address::fromString("255.255.255.255")), 1, Address::fromString("0.0.1.0") ],
             [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), "0", Address::fromString("0.0.0.0") ],
        ];
    }

    public function arrrayAccessInvalidOffsetTypeProvider()
    {
        return [
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), 0.2 ],
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), 'foo' ],
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), [0] ],
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), true ],
        ];        
    }


    public function arrrayAccessOutOfBoundsProvider()
    {
        return [
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), 0xffffffff+1 ],
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("0.0.0.0")), 1 ],
            [ new Range(Address::fromString("10.10.10.0"), Address::fromString("10.10.10.10")), 11 ],
            [ new Range(Address::fromString("10.10.10.0"), Address::fromString("10.10.10.10")), -1 ],
        ];        
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatch(Range $r1, Range $r2)
    {
        $this->assertTrue($r1->match($r2));
        $this->assertTrue($r2->match($r1));
    }

    public function matchProvider()
    {
        return [
            [ new Range(Address::fromString('10.2.4.0'), Address::fromString('10.2.4.255')), new Range(Address::fromString('10.2.4.0'), Address::fromString('10.2.4.255'))],
        ];
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testArrayAccesSetImmutable()
    {
        $range = new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255"));
        $range[1] = Address::fromString('0.0.0.1');
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testArrayAccesUnsetImmutable()
    {
        $range = new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255"));
        unset($range[1]);
    }

    public function testIterator()
    {
        $range = new Range(Address::fromString("0.0.0.0"), Address::fromString("0.0.0.5"));
        foreach($range as $key=>$address)
        {
            $this->assertEquals($key, $address->int());
        }

        $range = new Range(Address::fromString("0.0.0.255"), Address::fromString("0.0.1.4"));
        foreach($range as $key=>$address)
        {
            $this->assertEquals($key+255, $address->int());
        }
    }

    public function testVersion()
    {
        $range = new Range(Address::fromString("10.0.0.0"), Address::fromString("10.0.0.1"));
        $this->assertSame($range->version(), IP::IPv4);
    }

}