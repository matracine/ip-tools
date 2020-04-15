<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\IPTools\IPv4;

use PHPUnit\Framework\TestCase;

use OutOfBoundsException;
use RangeException;

use mracine\IPTools\IPv4\Subnet;
use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IPv4\Netmask;
use mracine\IPTools\IPv4\Range;
use mracine\IPTools\Exceptions\InvalidFormatException;
use mracine\IPTools\IPVersion;

/**
 * @coversDefaultClass mracine\IPTools\IPv4\Subnet
 */
class SubnetTest extends TestCase
{
    /**
     * @dataProvider contructStrictProvider
     * @covers ::__construct
     */    
    public function testConstructStrict(Address $network, Address $netmask, Range $expected)
    {
        $subnet = new Subnet($network, $netmask);
        $this->assertEquals($expected->getLowerBound()->int(), $subnet->getLowerBound()->int());
        $this->assertEquals($expected->getUpperBound()->int(), $subnet->getUpperBound()->int());
    }

    public function contructStrictProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'), Netmask::fromString('255.255.255.255'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.0') )],
            [ Address::fromString('0.0.0.0'), Netmask::fromString('255.255.255.0'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.255') )],
            [ Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.0') )],
            [ Address::fromString('0.0.0.0'), Address::fromString('255.255.255.0'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.255') )],
        ];
    }

    /**
     * @dataProvider constructNotStrictProvider
     * @covers ::__construct
     */    
    public function testConstructNotStrict(Address $network, Netmask $netmask, Range $expected)
    {
        $subnet = new Subnet($network, $netmask, false);
        $this->assertEquals($expected->getLowerBound()->int(), $subnet->getLowerBound()->int());
        $this->assertEquals($expected->getUpperBound()->int(), $subnet->getUpperBound()->int());
    }

    public function constructNotStrictProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'), Netmask::fromString('255.255.255.255'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.0') )],
            [ Address::fromString('0.0.0.0'), Netmask::fromString('255.255.255.0'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.255') )],
            [ Address::fromString('0.0.0.10'), Netmask::fromString('255.255.255.0'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.255') )],
            [ Address::fromString('0.0.0.255'), Netmask::fromString('255.255.255.0'), new Range(Address::fromString('0.0.0.0'), Address::fromString('0.0.0.255') )],
        ];
    }


    // /**
    //  * @covers ::__construct
    //  * @expectedException InvalidArgumentException
    //  */    
    // public function testConstructInvalidArgument()
    // {
    //     $subnet = new Subnet(new Address(0), new Address(0));
    // }

    /**
     * @covers ::__construct
     * @expectedException RangeException
     */    
    public function testConstructRangeException()
    {
        $subnet = new Subnet(new Address(1), Netmask::fromString("255.255.255.252"));
    }

    /**
     * @dataProvider fromCidrStrictProvider
     * @covers ::fromCidr
     */
    public function testFromCidrStrict(Address $network, int $cidr, Range $expected)
    {
        $subnet = Subnet::fromCidr($network, $cidr);

        $this->AssertEquals($expected->getLowerBound()->int(), $subnet->getNetworkAddress()->int());
        $this->AssertEquals($expected->getUpperBound()->int(), $subnet->getBroadcastAddress()->int());
    }

    public function fromCidrStrictProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'),          0, new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255')) ],
            // [ Address::fromString('128.0.0.0'),        1, Address::fromString('255.255.255.255') ],
            // [ Address::fromString('192.0.0.0'),        2, Address::fromString('255.255.255.255'), ],
            // [ Address::fromString('255.255.128.0'),   17, Address::fromString('255.255.255.255') ],
            // [ Address::fromString('255.255.255.0'),   24, Address::fromString('255.255.255.255') ],
            // [ Address::fromString('255.255.255.252'), 30, Address::fromString('255.255.255.255') ],
            // [ Address::fromString('255.255.255.254'), 31, Address::fromString('255.255.255.255') ],
            // [ Address::fromString('255.255.255.255'), 32, Address::fromString('255.255.255.255') ],
            // [ Address::fromString('10.2.4.0'),        24, Address::fromString('10.2.4.255') ],
            // [ Address::fromString('10.2.4.0'),        30, Address::fromString('10.2.4.3') ],
        ];
    }

    /**
     * @dataProvider fromCidrNotStrictProvider
     * @covers ::fromCidr
     */
    public function testFromCidrNotStrict(Address $network, int $cidr, Range $expected)
    {
        $subnet = Subnet::fromCidr($network, $cidr, false);

        $this->AssertEquals($expected->getLowerBound()->int(), $subnet->getNetworkAddress()->int());
        $this->AssertEquals($expected->getUpperBound()->int(), $subnet->getBroadcastAddress()->int());
    }

    public function fromCidrNotStrictProvider()
    {
        return [
            [ Address::fromString('10.0.10.243'),   0, new Range(Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255')) ],
            [ Address::fromString('10.2.4.0'),     30, new Range(Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3')) ],
            [ Address::fromString('10.2.4.1'),     30, new Range(Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3')) ],
            [ Address::fromString('10.2.4.2'),     30, new Range(Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3')) ],
            [ Address::fromString('10.2.4.3'),     30, new Range(Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3')) ],
        ];
    }

    /**
     * @dataProvider fromCidrRangeExceptionProvider
     * @expectedException RangeException
     * @covers ::fromCidr
     */
    public function testFromCidrRangeException(Address $network, int $cidr)
    {
        Subnet::fromCidr($network, $cidr);
    }


    public function fromCidrRangeExceptionProvider()
    {
        return [
             [ Address::fromString('255.255.255.253'), 30 ],
        ];
    }

    /**
     * @dataProvider fromContainedAddressCidrProvider
     * @covers ::fromContainedAddressCidr
     */
    public function testFromContainedAddressCidr(Address $network, int $cidr, Address $excpectedNetwork, Address $expectedBroadcast)
    {
        $subnet = Subnet::fromContainedAddressCidr($network, $cidr);
        $this->AssertEquals($excpectedNetwork->int(), $subnet->getNetworkAddress()->int());
        $this->AssertEquals($expectedBroadcast->int(), $subnet->getBroadcastAddress()->int());
    }

    public function fromContainedAddressCidrProvider()
    {
        return [
            [ Address::fromString('10.0.10.243'),   0, Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('10.2.4.0'),     30, Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3') ],
            [ Address::fromString('10.2.4.1'),     30, Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3') ],
            [ Address::fromString('10.2.4.2'),     30, Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3') ],
            [ Address::fromString('10.2.4.3'),     30, Address::fromString('10.2.4.0'), Address::fromString('10.2.4.3') ],
        ];
    }

    /**
     * @dataProvider fromStringInvalidArgumentProvider
     * @expectedException InvalidArgumentException 
     * @covers ::fromString
     */
    public function testFromstringInvalidArgument(string $value)
    {
        $subnet = Subnet::fromString($value);
        // $this->AssertEquals($excpectedNetwork->int(), $subnet->getNetworkAddress()->int());
        // $this->AssertEquals($expectedBroadcast->int(), $subnet->getBroadcastAddress()->int());
    }

    public function fromStringInvalidArgumentProvider()
    {
        return [
            [""],
            ["/"],
            ["//"],
            ["///"],
            ["0/0"],
            ["0/0/0"],
            ["0/32"],
            ["/0/0"],
            ["0/0/"],
            ["/0/0/"],
            ["0.0.0.0"],
            ["0.0.0.0/"],
            ["0.0.0.0//"],
            ["/0.0.0.0"],
            ["//0.0.0.0"],
            ["123.123.123.0"],
            ["123.123.123.0/A"],
        ];
    }

    /**
     * @dataProvider fromStringProvider
     * @covers ::fromString
     */
    public function testFromstring(string $value, Address $expectedNetwork, Address $expectedBroadcast)
    {
        $subnet = Subnet::fromString($value);
        $this->AssertEquals($expectedNetwork->int(), $subnet->getNetworkAddress()->int());
        $this->AssertEquals($expectedBroadcast->int(), $subnet->getBroadcastAddress()->int());
    }

    public function fromStringProvider()
    {
        return [
            ["0.0.0.0/0", new Address(0), new Address(0xffffffff)],
            ["0.0.0.0/24", new Address(0), new Address(0x000000ff)],
            ["0.0.0.0/32", new Address(0), new Address(0)],
            ["255.255.255.255/32", new Address(0xffffffff), new Address(0xffffffff)],
            ["0.0.0.0/0.0.0.0", new Address(0), new Address(0xffffffff)],
            ["0.0.0.0/255.255.255.0", new Address(0), new Address(0x000000ff)],
            ["0.0.0.0/255.255.255.255", new Address(0), new Address(0)],
            ["255.255.255.255/255.255.255.255", new Address(0xffffffff), new Address(0xffffffff)],
        ];
    }

    /**
     * @dataProvider getNetworkAddressProvider
     * @covers ::getNetworkAddress
     */
    public function testGetNetworkAddress(Subnet $subnet, Address $expectedNetwork)
    {
        $this->AssertEquals($expectedNetwork->int(), $subnet->getNetworkAddress()->int());
    }

    public function getNetworkAddressProvider()
    {        
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.10.243'), 0, false), Address::fromString('0.0.0.0') ],
            [ Subnet::fromCidr(Address::fromString('10.2.4.1'), 30, false), Address::fromString('10.2.4.0') ],
            [ Subnet::fromCidr(Address::fromString('10.2.4.2'), 30, false), Address::fromString('10.2.4.0') ],
            [ Subnet::fromCidr(Address::fromString('10.2.4.3'), 30, false), Address::fromString('10.2.4.0') ],
        ];
    }

    /**
     * @dataProvider getNetmaskProvider
     * @covers ::getNetmaskAddress
     */
    public function testGetNetmaskAddress(Subnet $subnet, Address $expectedNetmask)
    {
        $this->AssertEquals($expectedNetmask->int(), $subnet->getNetmaskAddress()->int());
    }

    public function getNetmaskProvider()
    {        
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.10.243'), 0, false), Address::fromString('0.0.0.0') ],
            [ Subnet::fromCidr(Address::fromString('10.2.4.1'),   30, false), Address::fromString('255.255.255.252') ],
            [ Subnet::fromCidr(Address::fromString('10.2.4.2'),   31, false), Address::fromString('255.255.255.254') ],
            [ Subnet::fromCidr(Address::fromString('10.2.4.3'),   32, false), Address::fromString('255.255.255.255') ],
        ];
    }


    /**
     * @dataProvider containsProvider
     * @covers ::contains
     */

    public function testContains(Subnet $subnet, Address $address, bool $expected)
    {
        if( $expected )
        {
            $this->assertTrue($subnet->contains($address));
        }
        else
        {
            $this->assertFalse($subnet->contains($address));
        }
    }

    public function containsProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), Address::fromString('10.0.0.1'), true ],
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), Address::fromString('10.45.34.252'), true ],
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), Address::fromString('255.255.255.255'), true ],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), Address::fromString('10.0.0.1'), true ],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), Address::fromString('10.0.0.1'), true ],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), Address::fromString('10.0.1.1'), false ],
        ];
    }

    /**
     * @dataProvider getBroadcastAddressProvider
     * @covers ::getBroadcastAddress
     */

    public function testGetBroadcastAddress(Subnet $subnet, Address $expected)
    {
        $this->AssertEquals($expected->int(), $subnet->getBroadcastAddress()->int());
    }

    public function getBroadcastAddressProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), Address::fromString('255.255.255.255') ],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), Address::fromString('10.0.0.255') ],
            [ Subnet::fromCidr(Address::fromString('255.10.5.0'), 24), Address::fromString('255.10.5.255') ],
            [ Subnet::fromCidr(Address::fromString('255.10.5.0'), 30), Address::fromString('255.10.5.3') ],
            [ Subnet::fromCidr(Address::fromString('255.10.5.4'), 30), Address::fromString('255.10.5.7') ],
            [ Subnet::fromCidr(Address::fromString('255.10.0.0'), 16), Address::fromString('255.10.255.255') ],
        ];
    }

    /**
     * @dataProvider AsDotQuadAndCidrroviderProvider
     * @covers ::asDotQuadAndCidr
     */
    public function testAsDotQuadAndCidr(Subnet $subnet, string $separator, string $expected)
    {
        $this->assertEquals($expected, $subnet->asDotQuadAndCidr($separator));
        // Test default separator
        if ('/' === $separator) {
            $this->assertEquals($expected, $subnet->asDotQuadAndCidr());
        }
    }

    public function AsDotQuadAndCidrroviderProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), '/', '0.0.0.0/0' ],
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), '-', '0.0.0.0-0' ],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), '/', '10.0.0.0/24' ],
        ];
    }

    /**
     * @dataProvider matchProvider
     * @covers ::match
     */
    public function testMatch(Subnet $s1, Subnet $s2)
    {
        $this->assertTrue($s1->match($s2));
        $this->assertTrue($s2->match($s1));
    }

    /**
     * @dataProvider noMatchProvider
     * @covers ::match
     */
    public function testNoMatch(Subnet $s1, Subnet $s2)
    {
        $this->assertFalse($s1->match($s2));
        $this->assertFalse($s2->match($s1));
    }

    public function matchProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.0.2'), 24, false), Subnet::fromCidr(Address::fromString('10.0.0.198'), 24, false) ],
        ];
    }

    public function noMatchProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('100.0.0.2'), 24, false), Subnet::fromCidr(Address::fromString('10.0.0.198'), 24, false) ],
        ];
    }

    /**
     * @dataProvider shiftProvider
     * @covers ::shift
     */
    public function testShift(Subnet $subnet, int $offset, Subnet $expected)
    {
        $this->assertTrue($expected->match($subnet->shift($offset)));
        $this->assertTrue($subnet->shift($offset)->match($expected));
    }

    public function shiftProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24),  1, Subnet::fromCidr(Address::fromString('10.0.1.0'), 24) ],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), -1, Subnet::fromCidr(Address::fromString('9.255.255.0'), 24) ],
        ];
    }

    /**
     * @covers ::getIterator
     * @covers mracine\IPTools\Iterators\SubnetIterator::__construct
     * @covers mracine\IPTools\Iterators\SubnetIterator::current
     * @covers mracine\IPTools\Iterators\SubnetIterator::key
     * @covers mracine\IPTools\Iterators\SubnetIterator::next
     * @covers mracine\IPTools\Iterators\SubnetIterator::rewind
     * @covers mracine\IPTools\Iterators\SubnetIterator::valid
     */
    public function testIterator()
    {
        $subnet = new Subnet(Address::fromString("0.0.0.0"), Netmask::fromString("255.255.255.0"));
        $c = 0;
        foreach($subnet as $key=>$address)
        {
            $this->assertEquals($key, $address->int());
            $c++;
        }
        $this->AssertEquals($subnet->count(), $c);

        $subnet = new Subnet(Address::fromString("0.0.1.0"), Netmask::fromString("255.255.255.240"));
        foreach($subnet as $key=>$address)
        {
            $this->assertEquals($key+256, $address->int());
        }
    }
}