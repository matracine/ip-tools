<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\IPv4;

use PHPUnit\Framework\TestCase;

use mracine\IPTools\IPv4\Range;
use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IPv4\Netmask;
use mracine\IPTools\IPVersion;

/**
 * @coversDefaultClass mracine\IPTools\IPv4\Range
 */
class RangeTest extends TestCase
{
    /**
     * @dataProvider constructorProvider
     * @covers ::__construct()
     */
    public function testConstructor(Address $lower, Address $upper)
    {
        $range = new Range($lower, $upper);

        $this->assertGreaterThanOrEqual($range->getLowerBound()->int(), $range->getUpperBound()->int());
    }

    public function constructorProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'), Address::fromString('0.0.0.0')  ],
            [ Address::fromString('0.0.0.0'), Address::fromString('0.0.0.1')  ],
            [ Address::fromString('0.0.0.1'), Address::fromString('0.0.0.0')  ],
            [ Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255')  ],
            [ Address::fromString('255.255.255.255'), Address::fromString('0.0.0.0') ],
        ];
    }

    /**
     * @covers ::fromCount()
     */
    public function testFromCountInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $range = Range::fromCount(Address::fromString("0.0.0.0"), 0);
    }

    /**
     * @dataProvider fromCountProvider
     * @covers ::fromCount()
     */
    public function testFromCount(Address $base, int $count)
    {
        $range = Range::fromCount($base, $count);
        $this->assertGreaterThanOrEqual($range->getLowerBound()->int(), $range->getUpperBound()->int());
        $this->assertEquals(abs($count), $range->getUpperBound()->int() - $range->getLowerBound()->int() + 1);
    }

    public function fromCountProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'),         1 ],
            [ Address::fromString('0.0.0.0'),         1 ],
            [ Address::fromString('0.0.0.1'),         -1 ],
            [ Address::fromString('255.255.255.255'), -(0xffffffff) ],
            [ Address::fromString('255.255.255.255'), -1],
            [ Address::fromString('10.2.35.4'),        7 ],
            [ Address::fromString('10.2.35.10'),      -7 ],
        ];
    }

    /**
     * @dataProvider countProvider
     * @covers ::count()
     */
    public function testCount(Range $range, int $expected)
    {
        $this->assertEquals($expected, count($range));
    }

    public function countProvider()
    {
        return [
            [ new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.0')), 1 ],
            [ new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255')), 0xffffffff+1 ],
            [ new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.1')), 2 ],
        ];

    }

    /**
     * @dataProvider containsProvider
     * @covers ::contains()
     */
    public function testContains(Range $range, Address $ipTest, bool $expected)
    {
        if ($expected)
        {
            $this->assertTrue($range->contains($ipTest));
        }
        else
        {
            $this->assertFalse($range->contains($ipTest));
        }
    }

    public function containsProvider()
    {
        $rangeFull    = new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255'));
        $rangeMinimal = new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.0'));

        return [
            [ $rangeFull,    Address::fromString('0.0.0.0'), true ],
            [ $rangeFull,    Address::fromString('0.0.0.1'), true ],
            [ $rangeFull,    Address::fromString('255.255.255.255'), true ],
            [ $rangeMinimal, Address::fromString('0.0.0.0'), true ],
            [ $rangeMinimal, Address::fromString('0.0.0.1'), false ],
            [ $rangeMinimal, Address::fromString('255.255.255.255'), false ],
        ];

    }

    /**
     * @dataProvider isInProvider
     * @covers ::isIn()
     */
    public function testIsIn(Range $range, Range $container, bool $expected)
    {
        if ($expected)
        {
            $this->assertTrue($range->isIn($container));
        }
        else
        {
            $this->assertFalse($range->isIn($container));
        }
    }

    public function isInProvider()
    {
        $rangeMinimal = new Range(new Address(0), new Address(0));
        $rangeFull    = new Range(new Address(0), new Address(0xffffffff));

        return [
            [ $rangeFull,    $rangeMinimal, false ],
            [ $rangeFull,    $rangeFull, true ],
            [ $rangeMinimal, $rangeMinimal, true ],
            [ $rangeMinimal, $rangeFull, true ],
            [ new Range(new Address(1), new Address(10)), new Range(new Address(1), new Address(11)), true ],
            [ new Range(new Address(1), new Address(10)), new Range(new Address(1), new Address(9)), false ],
            [ new Range(new Address(1), new Address(10)), new Range(new Address(0), new Address(10)), true ],
            [ new Range(new Address(1), new Address(10)), new Range(new Address(2), new Address(10)), false ],
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
     * @dataProvider arrayAccessProvider
     * @covers ::offsetExists
     * @covers ::offsetGet
     */
    public function testArrayAccess(Range $range, $offset, Address $expected)
    {
        $this->assertEquals($expected->int(), $range[$offset]->int());
    }

    public function arrayAccessProvider()
    {
        $rangeFull = new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255'));
        return [
             [ $rangeFull, 0, Address::fromString("0.0.0.0") ],
             [ $rangeFull, 0xffffffff, Address::fromString("255.255.255.255") ],
             [ new Range(Address::fromString("0.0.0.255"), Address::fromString("255.255.255.255")), 1, Address::fromString("0.0.1.0") ],
             [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), "0", Address::fromString("0.0.0.0") ],
        ];
    }

    /**
     * @dataProvider arrayAccessInvalidOffsetTypeProvider
     * @covers ::offsetExists
     */
    public function testArrayAccesInvalidOffsetType(Range $range, $offset)
    {
        $this->expectException(\InvalidArgumentException::class);
        $range[$offset];
    }

    public function arrayAccessInvalidOffsetTypeProvider()
    {
        $rangeFull = new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255'));
        return [
            [ $rangeFull, 0.2 ],
            [ $rangeFull, 'foo' ],
            [ $rangeFull, [0] ],
            [ $rangeFull, true ],
        ];        
    }

    /**
     * @dataProvider arrayAccessOutOfBoundsProvider
     * @covers ::offsetExists
     */
    public function testArrayAccessOutOfBounds(Range $range, $offset)
    {
        $this->expectException(\OutOfBoundsException::class);
        $range[$offset];
    }


    public function arrayAccessOutOfBoundsProvider()
    {
        return [
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255")), 0xffffffff+1 ],
            [ new Range(Address::fromString("0.0.0.0"), Address::fromString("0.0.0.0")), 1 ],
            [ new Range(Address::fromString("10.10.10.0"), Address::fromString("10.10.10.10")), 11 ],
            [ new Range(Address::fromString("10.10.10.0"), Address::fromString("10.10.10.10")), -1 ],
        ];        
    }

    /**
     * @covers ::offsetGet
     * @covers ::getIterator
     * @covers mracine\IPTools\Iterators\RangeIterator::__construct
     * @covers mracine\IPTools\Iterators\RangeIterator::current
     * @covers mracine\IPTools\Iterators\RangeIterator::key
     * @covers mracine\IPTools\Iterators\RangeIterator::next
     * @covers mracine\IPTools\Iterators\RangeIterator::rewind
     * @covers mracine\IPTools\Iterators\RangeIterator::valid
     */

    public function testIterator()
    {
        $range = new Range(Address::fromString("0.0.0.0"), Address::fromString("0.0.0.5"));
        foreach($range as $key=>$address)
        {
            $this->assertEquals($key, $address->int());
        }

        $range = new Range(Address::fromString("0.0.0.255"), Address::fromString("0.0.0.250"));
        foreach($range as $key=>$address)
        {
            $this->assertEquals($key+250, $address->int());
        }
    }

    /**
     * @covers ::offsetUnset
     */
    public function testArrayAccesUnsetImmutable()
    {
        $rangeFull = new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255'));
        $this->expectException(\BadMethodCallException::class);
        unset($rangeFull[1]);
    }


    /**
     * @covers ::version
     */
    public function testVersion()
    {
        $range = new Range(Address::fromString("10.0.0.0"), Address::fromString("10.0.0.5"));
        $this->assertSame($range->version(), IPVersion::IPv4);
    }

    /**
     * @dataProvider matchProvider
     * @covers: ::match
     */
    public function testMatch(Range $r1, Range $r2)
    {
        $this->assertTrue($r1->match($r2));
        $this->assertTrue($r2->match($r1));
    }

    public function matchProvider()
    {
        $rangeFull = new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255'));
        return [
            [clone($rangeFull), clone($rangeFull)],
        ];
    }

    /**
     * @covers ::offsetSet
     */
    public function testArrayAccesSetImmutable()
    {
        $rangeFull = new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255'));
        $this->expectException(\BadMethodCallException::class);
        $rangeFull[1] = Address::fromString('0.0.0.1');
        $rangeFull[10] = Address::fromString('0.0.0.10');
        $rangeFull[0xffffffff] = Address::fromString('255.255.255.255');
    }

    /**
     * @dataProvider shiftProvider
     */
    public function testShift(Range $range, int $offset, Range $expected)
    {
        $this->assertTrue($expected->match($range->shift($offset)));
        $this->assertTrue($range->shift($offset)->match($expected));
    }

    public function shiftProvider()
    {
        return [
            [ Range::fromCount(Address::fromString('10.0.0.0'), 256),  1, Range::fromCount(Address::fromString('10.0.1.0'), 256) ],
        ];
    }

    /**
     * @dataProvider nextProvider
     * @covers ::next
     */
    public function testNext(Range $range, Range $expected)
    {
        $this->assertTrue($expected->match($range->next()));
    }

    public function nextProvider()
    {
        return [
            [ Range::fromCount(Address::fromString('10.0.0.0'), 256), Range::fromCount(Address::fromString('10.0.1.0'), 256) ],
        ];
    }

    /**
     * @dataProvider previousProvider
     * @covers ::previous
     */
    public function testPrevious(Range $range, Range $expected)
    {
        $this->assertTrue($expected->match($range->previous()));
    }

    public function previousProvider()
    {
        return [
            [ Range::fromCount(Address::fromString('10.0.1.0'), 256), Range::fromCount(Address::fromString('10.0.0.0'), 256) ],
        ];
    }
}