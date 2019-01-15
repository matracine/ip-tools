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

use mracine\IPTools\IPv4\Netmask;
use mracine\IPTools\IPv4\Address;

/**
 * @coversDefaultClass mracine\IPTools\IPv4\Netmask
 */
class NetmaskTest extends TestCase
{
    /**
     * @dataProvider ConstructOutOfBoundsProvider
     * @expectedException OutOfBoundsException
     * @covers ::__construct
     */
    public function testConstructOutOfBounds(int $netmask)
    {
        $netmask = new Netmask($netmask);
    }

    public function ConstructOutOfBoundsProvider()
    {
        return [
            [-1],
            [0xffffffff+1],
        ];
    }

    /**
     * @dataProvider ConstructDomainExceptionProvider
     * @expectedException DomainException
     * @covers ::__construct
     */
    public function testConstructDomainException(int $netmask)
    {
        $netmask = new Netmask($netmask);
    }

    public function ConstructDomainExceptionProvider()
    {
        return [
            [1],
            [0xfffffeff],
        ];
    }

    /**
     * @dataProvider ConstructProvider
     * @covers ::__construct
     */
    public function testConstruct(int $netmask)
    {
        $this->AssertInstanceOf(Netmask::class, new Netmask($netmask));
    }

    public function ConstructProvider()
    {
        return [
            [0xffffffff],
            [0xfffffffe],
            [0x80000000],
            [0],
        ];
    }

    /**
     * @dataProvider ConstructProvider
     * @coversNothing
     */
    public function testFromInteger(int $netmask)
    {
        $this->assertInstanceOf(Netmask::class, Netmask::fromInteger($netmask));
    }

    /**
     * @dataProvider fromStringProvider
     * @coversNothing
     */
    public function testFromString(string $netmask)
    {
         $this->assertInstanceOf(Netmask::class, Netmask::fromString($netmask));
    }

    public function fromStringProvider()
    {
        return [
            ["255.255.255.255"],
            ["0.0.0.0"],
        ];        
    }

    /**
     * @dataProvider fromCidrOutOfBoundExceptionProvider
     * @expectedException OutOfBoundsException
     * @covers ::fromCidr
     */
    public function testFromCidrOutOfBoundException(int $cidr)
    {
        Netmask::fromCidr($cidr);
    }

    public function fromCidrOutOfBoundExceptionProvider()
    {
        return [
            [-1],
            [33],
        ];
    }

    /**
     * @dataProvider fromCidrProvider
     * @covers ::fromCidr
     */
    public function testFromCidr(int $cidr)
    {
         $this->assertInstanceOf(Netmask::class, Netmask::fromCidr($cidr));
         $this->assertSame(Netmask::fromCidr($cidr)->asCidr(), $cidr);
    }

    public function fromCidrProvider()
    {
        for($i=0 ; $i<=32 ; $i++)
        {
            yield [$i];
        }
    }

    /**
     * @dataProvider fromAddressProvider
     * @covers ::fromAddress
     */
    public function testFromAddress(Address $address)
    {
        $netmask = Netmask::fromAddress($address);
         $this->assertInstanceOf(Netmask::class, $netmask);
         $this->assertSame($netmask->int(), $address->int());
    }

    public function fromAddressProvider()
    {
        return [
            [ new Address(0), ],
            [ Address::fromString("255.255.0.0"), ],
            [ Address::fromString("255.255.255.255"), ],
        ];
    }

    /**
     * @dataProvider asCidrProvider
     * @covers ::asCidr
     */
    public function testAsCidr(Netmask $netmask, int $expected)
    {
        $this->assertEquals($expected, $netmask->asCidr());
    }

    public function asCidrProvider()
    {
        return [
            [Netmask::fromString("0.0.0.0"),         0],
            [Netmask::fromString("255.255.255.255"), 32],
        ];
    }

    /**
     * @dataProvider countProvider
     * @covers ::count
     */
    public function testCount(Netmask $netmask, int $expected)
    {
        $this->AssertEquals($expected, count($netmask));
    }

    public function countProvider()
    {
        return [
            [ Netmask::fromString('255.255.255.255'), 1],
            [ Netmask::fromString('255.255.255.0'), 256],
            [ Netmask::fromString('0.0.0.0'), 0xffffffff+1],
        ];
    }

    /**
     * @dataProvider shiftProvider
     * @covers ::shift
     */
    public function testShift(Netmask $netmask, int $offset, Netmask $excpectedNetmask)
    {
        echo $this->assertEquals($excpectedNetmask, $netmask->shift($offset));
    }

    public function shiftProvider()
    {
        return [
            [ new Netmask(0xffffffff), -1, new Netmask(0xfffffffe)],
            [ new Netmask(0xffffffff), -32, new Netmask(0)],
            [ new Netmask(0), 1, new Netmask(0x80000000)],
            [ new Netmask(0), 32, new Netmask(0xffffffff)],
        ];
    }
}