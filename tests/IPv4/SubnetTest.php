<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 * Inspired by Gijs Kunze https://github.com/gwkunze/IpAddress 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\IPTools\IPv4;

use PHPUnit\Framework\TestCase;

use OutOfBoundsException;
use RangeException;

use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IPv4\Subnet;
use mracine\IPTools\IPv4\Range;
use mracine\IPTools\Exceptions\InvalidFormatException;
use mracine\IPTools\IP;

class SubnetTest extends TestCase
{
    /**
     * @dataProvider fromCidrProvider
     */
    public function testFromCidr(Address $network, int $cidr, Address $expectedBroadcast)
    {
        $subnet = Subnet::fromCidr($network, $cidr);
        $this->AssertEquals($network->int(), $subnet->getNetworkAddress()->int());
        $this->AssertEquals($expectedBroadcast->int(), $subnet->getBroadcastAddress()->int());
    }

    /**
     * @dataProvider fromCidrRangeExceptionProvider
     * @expectedException RangeException
     */
    public function testFromCidrRangeException(Address $network, int $cidr)
    {
        Subnet::fromCidr($network, $cidr);
    }

    public function fromCidrProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'),          0, Address::fromString('255.255.255.255') ],
            [ Address::fromString('128.0.0.0'),        1, Address::fromString('255.255.255.255') ],
            [ Address::fromString('192.0.0.0'),        2, Address::fromString('255.255.255.255'), ],
            [ Address::fromString('255.255.128.0'),   17, Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.0'),   24, Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.252'), 30, Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.254'), 31, Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.255'), 32, Address::fromString('255.255.255.255') ],
            [ Address::fromString('10.2.4.0'),        24, Address::fromString('10.2.4.255') ],
            [ Address::fromString('10.2.4.0'),        30, Address::fromString('10.2.4.3') ],
        ];
    }

    public function fromCidrRangeExceptionProvider()
    {
        return [
             [ Address::fromString('255.255.255.253'), 30 ],
        ];
    }

    /**
     * @dataProvider fromAddressesProvider
     */
    public function testFromAddresses(Address $network, Address $netmask, Address $expectedBroadcast)
    {
        $subnet = Subnet::fromAddresses($network, $netmask);
        $this->AssertEquals($network->int(), $subnet->getNetworkAddress()->int());
        $this->AssertEquals($expectedBroadcast->int(), $subnet->getBroadcastAddress()->int());
    }

    /**
     * @dataProvider fromAddressesRangeExceptionProvider
     * @expectedException RangeException
     */
    public function testFromAddressesRangeException(Address $network, Address $netmask)
    {
        $subnet = Subnet::fromAddresses($network, $netmask);
    }

    public function fromAddressesProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'),         Address::fromString('0.0.0.0'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('128.0.0.0'),       Address::fromString('128.0.0.0'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('192.0.0.0'),       Address::fromString('192.0.0.0'), Address::fromString('255.255.255.255'), ],
            [ Address::fromString('255.255.128.0'),   Address::fromString('255.255.128.0'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.0'),   Address::fromString('255.255.255.0'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.252'), Address::fromString('255.255.255.252'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.254'), Address::fromString('255.255.255.254'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('255.255.255.255'), Address::fromString('255.255.255.255'), Address::fromString('255.255.255.255') ],
            [ Address::fromString('10.2.4.0'),        Address::fromString('255.255.255.0'), Address::fromString('10.2.4.255') ],
            [ Address::fromString('10.2.4.0'),        Address::fromString('255.255.255.252'), Address::fromString('10.2.4.3') ],
        ];
    }

    public function fromAddressesRangeExceptionProvider()
    {
        return [
             [ Address::fromString('255.255.255.253'), Address::fromString('255.255.255.0') ],
        ];        
    }


    /**
     * @dataProvider fromContainedAddressCidrProvider
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
     * @dataProvider getNetworkAddressProvider
     */
    public function testGetNetworkAddress(Subnet $subnet, Address $expectedNetwork)
    {
        $this->AssertEquals($expectedNetwork->int(), $subnet->getNetworkAddress()->int());
    }

    public function getNetworkAddressProvider()
    {        
        return [
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.0.10.243'), 0), Address::fromString('0.0.0.0') ],
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.2.4.1'),   30), Address::fromString('10.2.4.0') ],
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.2.4.2'),   30), Address::fromString('10.2.4.0') ],
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.2.4.3'),   30), Address::fromString('10.2.4.0') ],
        ];
    }

    /**
     * @dataProvider getNetmaskProvider
     */
    public function testGetNetwork(Subnet $subnet, Address $expectedNetmask)
    {
        $this->AssertEquals($expectedNetmask->int(), $subnet->getNetmask()->int());
    }

    public function getNetmaskProvider()
    {        
        return [
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.0.10.243'), 0), Address::fromString('0.0.0.0') ],
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.2.4.1'),   30), Address::fromString('255.255.255.252') ],
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.2.4.2'),   31), Address::fromString('255.255.255.254') ],
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.2.4.3'),   32), Address::fromString('255.255.255.255') ],
        ];
    }


    /**
     * @dataProvider containsProvider
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
     * @dataProvider toRangeProvider
     */

    public function testToRange(Subnet $subnet, Range $expected)
    {
        $this->assertTrue($subnet->toRange()->match($expected));
    }

    public function toRangeProvider()
    {
        return [
            [Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), new Range(Address::fromString('10.0.0.0'), Address::fromString('10.0.0.255'))],
            [Subnet::fromCidr(Address::fromString('10.0.0.4'), 30), new Range(Address::fromString('10.0.0.4'), Address::fromString('10.0.0.7'))],
        ];
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatch(Subnet $s1, Subnet $s2)
    {
        $this->assertTrue($s1->match($s2));
        $this->assertTrue($s2->match($s1));
    }

    /**
     * @dataProvider noMatchProvider
     */
    public function testNoMatch(Subnet $s1, Subnet $s2)
    {
        $this->assertFalse($s1->match($s2));
        $this->assertFalse($s2->match($s1));
    }

    public function matchProvider()
    {
        return [
            [ Subnet::fromContainedAddressCidr(Address::fromString('10.0.0.2'), 24), Subnet::fromContainedAddressCidr(Address::fromString('10.0.0.198'), 24) ],
        ];
    }

    public function noMatchProvider()
    {
        return [
            [ Subnet::fromContainedAddressCidr(Address::fromString('100.0.0.2'), 24), Subnet::fromContainedAddressCidr(Address::fromString('10.0.0.198'), 24) ],
        ];
    }

    /**
     * @dataProvider shiftProvider
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
     * @dataProvider nextProvider
     */
    public function testNext(Subnet $subnet, Subnet $expected)
    {
        $this->assertTrue($expected->match($subnet->next()));
    }

    public function nextProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), Subnet::fromCidr(Address::fromString('10.0.1.0'), 24) ],
            [ Subnet::fromCidr(Address::fromString('10.0.255.0'), 24), Subnet::fromCidr(Address::fromString('10.1.0.0'), 24) ],
        ];
    }

    /**
     * @dataProvider previousProvider
     */
    public function testPrevious(Subnet $subnet, Subnet $expected)
    {
        $this->assertTrue($expected->match($subnet->previous()));
    }

    public function previousProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 24), Subnet::fromCidr(Address::fromString('9.255.255.0'), 24) ],
            [ Subnet::fromCidr(Address::fromString('10.0.255.0'), 24), Subnet::fromCidr(Address::fromString('10.0.254.0'), 24) ],
        ];
    }

    /**
     * @dataProvider countProvider
     */
    public function testCount(Subnet $subnet, int $expected)
    {
        $this->AssertEquals($subnet->count(), $expected);
    }

    public function countProvider()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 4 ],
        ];
    }

    /**
     * @dataProvider ArrayAccessOutOfBounds
     * @expectedException OutOfBoundsException
     */
    public function testArrayAccessOutOfBounds(Subnet $subnet, int $offset)
    {
        $subnet[$offset];
    }

    /**
     * @dataProvider ArrayAccessTypeError
     * @expectedException InvalidArgumentException
     */
    public function testArrayAccessTypeError(Subnet $subnet, $offset)
    {
        $subnet[$offset];
    }

    /**
     * @dataProvider ArrayAccess
     */
    public function testArrayAccess(Subnet $subnet, $offset, Address $excpected)
    {
        $this->assertTrue($excpected->match($subnet[$offset]));
    }


    /**
     * @expectedException BadMethodCallException
     */
    public function testArrayAccesSetImmutable()
    {
        $subnet = Subnet::fromAddresses(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255"));
        $subnet[1] = Address::fromString('0.0.0.1');
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testArrayAccesUnsetImmutable()
    {
        $subnet = Subnet::fromAddresses(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.255"));
        unset($subnet[1]);
    }

    public function ArrayAccessOutOfBounds()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), -1],
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), 0x100000000],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), -1],
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 4],
        ];
    }

    public function ArrayAccessTypeError()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 0.1 ], 
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 'toto' ], 
            // [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), '0' ], 
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), [ 1 ] ], 
            // [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), new Object() ], 
        ];
    }

    public function ArrayAccess()
    {
        return [
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), 0, Address::fromString('0.0.0.0') ], 
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), 255, Address::fromString('0.0.0.255') ], 
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), 256, Address::fromString('0.0.1.0') ], 
            [ Subnet::fromCidr(Address::fromString('0.0.0.0'), 0), 257, Address::fromString('0.0.1.1') ], 
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 0, Address::fromString('10.0.0.0') ], 
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 1, Address::fromString('10.0.0.1') ], 
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 2, Address::fromString('10.0.0.2') ], 
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), 3, Address::fromString('10.0.0.3') ], 
            [ Subnet::fromCidr(Address::fromString('10.0.0.0'), 30), "3", Address::fromString('10.0.0.3') ], 
        ];
    }

    public function testIterator()
    {
        $subnet = Subnet::fromAddresses(Address::fromString("0.0.0.0"), Address::fromString("255.255.255.240"));
        $c = 0;
        foreach($subnet as $key=>$address)
        {
            $this->assertEquals($key, $address->int());
            $c++;
        }
        $this->AssertEquals($subnet->count(), $c);

        $subnet = Subnet::fromAddresses(Address::fromString("0.0.1.0"), Address::fromString("255.255.255.240"));
        foreach($subnet as $key=>$address)
        {
            $this->assertEquals($key+256, $address->int());
        }
    }

    public function testVersion()
    {
        $subnet = Subnet::fromCidr(Address::fromString("10.0.0.0"), 24);
        $this->assertSame($subnet->version(), IP::IPv4);
    }
}