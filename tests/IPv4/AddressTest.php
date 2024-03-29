<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\IPv4;

use PHPUnit\Framework\TestCase;

use mracine\IPTools\IPVersion;
use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IPv4\Range;

/**
 * @coversDefaultClass mracine\IPTools\IPv4\Address
 */
class AddressTest extends TestCase
{
	/**
	 * @dataProvider constructorOutOfBoundsProvider
	 *
     * @covers ::__construct
	 * @param int $value
	 */

	public function testConstructorOutOfBounds(int $value)
	{
        $this->expectException(\OutOfBoundsException::class);
		$address = new Address($value);
	}

	public function constructorOutOfBoundsProvider()
	{
		return [
			"Negative"      => [              -1 ],
			"Very negative" => [            -255 ],
			"Limit up"      => [     0x100000000 ],
			"Huge one"      => [ 0xfffffffffffff ],
		];
	}

    /**
     * @dataProvider constructorProvider
     *
     * @covers ::__construct
     * @param int $value
     */
    public function testConstructor(int $value)
    {
        $address = new Address($value);
        $this->assertInstanceOf(Address::class, $address);
    }

    public function constructorProvider()
    {
        return [
            [ 0 ],
            [ 0xffffffff ]
        ];
    }

    /**
     * @covers ::minAddress
     */
    public function testAddress()
    {
        $address = Address::minAddress();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals(0, $address->int());
    }

    /**
     * @covers ::maxAddress
     */
    public function testMaxAddress()
    {
        $address = Address::maxAddress();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals(0xffffffff, $address->int());
    }

    /**
     * @dataProvider constructorProvider
     *
     * @covers ::fromInteger
     * @param int $value
     */
    public function testFromInteger(int $value)
    {
        $address = Address::fromInteger($value);
        $this->assertInstanceOf(Address::class, $address);
    }
    
    /**
     * @dataProvider fromArrayInvalidFormatProvider
     * @covers ::fromArray
     *
     * @param array $data
     */

    public function testFromArrayInvalidFormat(Array $data)
    {
        $this->expectException(\InvalidArgumentException::class);
        $address = Address::fromArray($data);
    }

    public function fromArrayInvalidFormatProvider()
    {
        return [
            [ [],          ],
            [ [1],         ],
            [ [1,1],       ],
            [ [1,1,1],     ],
            [ [1,1,1,1,1], ],
            [ [1,1,1,"", ] ],
            [ [1,1,1,"foo", ] ],
            [ [1,1,1,1.2, ] ],
        ];
    }

    /**
     * @dataProvider fromArrayOutOfBoundsProvider
     * @covers ::fromArray
    *
     * @param array $data
     */

    public function testFromArrayOutOfBounds(Array $data)
    {
        $this->expectException(\OutOfBoundsException::class);
        $address = Address::fromArray($data);
    }

    public function fromArrayOutOfBoundsProvider()
    {
        return [
            [ [256,1,1,1],  ],
            [ [1,256,1,1],  ],
            [ [1,1,256,1],  ],
            [ [1,1,1,256],  ],
            [ [1,1,1,-1],   ],
            [ [1,1,1,"-1"],   ],
        ];
    }

    /**
     * @dataProvider fromArrayProvider
     * @covers ::fromArray
     *
     * @param array $data
     */
    public function testFromArray(Array $data, string $strExpected, int $intExpected)
    {
        $address = Address::fromArray($data);
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals((string)$address, $strExpected);
        $this->assertIsInt($address->int());      
        $this->assertEquals($address->int(), $intExpected);
    }


    public function fromArrayProvider()
    {
        return [
            'FromArray : Good array Entry 0' => [ [0,0,0,0],         "0.0.0.0" ,                 0 ],
            'FromArray : Good array Entry 1' => [ [1,1,1,1],         "1.1.1.1"  ,       0x01010101 ],
            'FromArray : Good array Entry 2' => [ [1,255,1,1],       "1.255.1.1",       0x01ff0101 ],
            'FromArray : Good array Entry 3' => [ [1,1,255,1],       "1.1.255.1",       0x0101ff01 ],
            'FromArray : Good array Entry 4' => [ [1,"1",1,255],       "1.1.1.255",       0x010101ff ],
            'FromArray : Good array Entry 5' => [ [255,255,255,255], "255.255.255.255", 0xffffffff ],
            'FromArray : Good array Entry 6' => [ ["255",255,255,255], "255.255.255.255", 0xffffffff ],
        ];
    }

    /**
     * @dataProvider fromStringInvalidFormatProvider
     * @covers ::fromString
     *
     * @param string $data
     */

    public function testFromStringInvalidFormat(string $data)
    {
        $this->expectException(\InvalidArgumentException::class);
        $address = Address::fromString($data);
    }

    public function fromStringInvalidFormatProvider()
    {
        $a = [];
        foreach(self::fromArrayInvalidFormatProvider() as $key=>$data)
        {
            $a[$key] = [ implode('.', $data[0]) ];
        }
        return $a;
    }

    /**
     * @dataProvider fromStringOutOfBoundsProvider
     * @covers ::fromString
      *
     * @param string $data
     */

    public function testFromStringOutOfBounds(string $data)
    {
        $this->expectException(\OutOfBoundsException::class);
        $address = Address::fromString($data);
    }

    public function fromStringOutOfBoundsProvider()
    {
        $a = [];
        foreach(self::fromArrayOutOfBoundsProvider() as $key=>$data)
        {
            $a[$key] = [ implode('.', $data[0]) ];
        }
        return $a;
    }

    /**
     * @dataProvider fromStringProvider
     * @covers ::fromString
     *
     * @param string $data
     */
    public function testFromString(string $data, string $strExpected, int $intExpected)
    {
        $address = Address::fromString($data);
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals((string)$address, $strExpected);
        $this->assertIsInt($address->int());      
        $this->assertEquals($address->int(), $intExpected);
    }

    public function fromStringProvider()
    {
        $a = [];
        foreach (self::fromArrayProvider() as $key => $data)
        {
            $a[$key] = [ implode('.', $data[0]), $data[1], $data[2] ];
        }
        return $a;
    }

    /**
     * @dataProvider asDotQuadProvider
     * @covers ::asDotQuad
     */
    public function testAsDotQuad(Address $address, string $expected)
    {
        $this->assertEquals($expected, $address->asDotQuad());
    }

    public function asDotQuadProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'), '0.0.0.0'],
            [ Address::fromString('255.255.255.255'), '255.255.255.255'],
        ];
    }

    /**
     * @dataProvider asDotQuadProvider
     * @covers ::__toString
     */
    public function testToString(Address $address, string $expected)
    {
        $this->assertEquals($expected, (string)$address);
    }

    /**
     * @dataProvider asIntegerProvider
     * @covers ::asInteger
     */
    public function testAsInteger(Address $address, int $expected)
    {
        $this->assertEquals($expected, $address->asInteger());
    }

    public function asIntegerProvider()
    {
        return [
            [ Address::fromString('0.0.0.0'), 0],
            [ Address::fromString('255.255.255.255'), 0xffffffff],
        ];
    }

    /**
     * @dataProvider asIntegerProvider
     * @covers ::int
     */
    public function testInt(Address $address, int $expected)
    {
        $this->assertEquals($expected, $address->int());
    }

	/**
	 * @dataProvider matchProvider
     * @covers ::match
	 */
	public function testMatch(Address $address1, Address $address2)
	{
        $this->assertTrue($address1->match($address2));
		$this->assertTrue($address2->match($address1));
	}

	public function matchProvider()
	{
		return [
			[ Address::fromString('0.0.0.0'), new Address(0) ],
			[ Address::fromString('0.0.0.1'), new Address(1) ],
			[ Address::fromString('0.0.1.0'), new Address(1 << 8) ],
			[ Address::fromString('0.1.0.0'), new Address(1 << 16) ],
			[ Address::fromString('1.0.0.0'), new Address(1 << 24) ],
			[ Address::fromString('255.0.255.0'), new Address(0xff00ff00) ],
			[ Address::fromString('255.255.255.255'), new Address(0xffffffff) ],
		];
	}

    /**
     * @dataProvider isInProvider
     * @covers ::isIn
     */
    public function testIsIn(Address $address, Range $range)
    {
        $this->assertTrue($address->isIn($range));
    }

    public function isInProvider()
    {
        return [
            [ new Address(0),          new Range(new Address(0), new Address(0xffffffff)) ],
            [ new Address(0xffffffff), new Range(new Address(0), new Address(0xffffffff)) ],
            [ new Address(150),        new Range(new Address(0), new Address(0xffffffff)) ],
        ];
    }

	/**
	 * @dataProvider classProvider
     * @covers ::getClass  
	 */
	public function testClass(Address $address, $class)
	{
		$this->assertSame($address->getClass(), $class);
	}

	public function classProvider()
    {
    	return [
			[ Address::fromString("0.0.0.0"),         Address::CLASS_A ],
			[ Address::fromString("10.0.0.0"),        Address::CLASS_A ],
			[ Address::fromString("10.1.2.3"),        Address::CLASS_A ],
			[ Address::fromString("127.0.0.1"),       Address::CLASS_A ],
			[ Address::fromString("127.255.255.255"), Address::CLASS_A ],
			[ Address::fromString("128.0.0.0"),       Address::CLASS_B ],
			[ Address::fromString("191.255.255.255"), Address::CLASS_B ],
			[ Address::fromString("192.0.0.0"),       Address::CLASS_C ],
			[ Address::fromString("223.255.255.255"), Address::CLASS_C ],
			[ Address::fromString("224.0.0.0"),       Address::CLASS_D ],
			[ Address::fromString("239.255.255.255"), Address::CLASS_D ],
            [ Address::fromString("240.0.0.0"),       Address::CLASS_E ],
            [ Address::fromString("255.255.255.255"), Address::CLASS_E ],
        ];
    }

	/**
	 * @dataProvider multicastProvider
     * @covers ::isMulticast
	 */
	public function testIsMulticast(Address $address, bool $result)
	{
		if ($result)
		{
			$this->assertTrue($address->isMulticast());
		}
		else
		{
			$this->assertFalse($address->isMulticast());
		}
	}

	public function multicastProvider()
	{
		return [
            [ Address::fromString("0.0.0.0"),         false ],
			[ Address::fromString("10.3.5.6"),        false ],
			[ Address::fromString("223.255.255.255"), false ],
			[ Address::fromString("224.0.0.0"),       true ],
			[ Address::fromString("231.100.52.4"),    true ],
			[ Address::fromString("239.255.255.255"), true ],
			[ Address::fromString("240.0.0.0"),       false ],
			[ Address::fromString("255.255.255.255"), false ],
		];
	}

    /**
     * @covers ::version
     */
    public function testVersion()
    {
        $address = Address::fromInteger(1);
        $this->assertSame($address->version(), IPVersion::IPv4);
    }

    /**
     * @dataProvider RFC1918Provider  
     * @covers ::isRFC1918
     */
    public function testIsRFC1918(Address $address, bool $result)
    {
        if ($result)
        {
            $this->assertTrue($address->isRFC1918());
        }
        else
        {
            $this->assertFalse($address->isRFC1918());
        }
    }

    public function RFC1918Provider()
    {
        return [
            [ Address::fromString("0.0.0.0"),         false ],
            [ Address::fromString("9.255.255.255"),   false ],
            [ Address::fromString("10.0.0.0"),        true ],
            [ Address::fromString("10.123.32.173"),   true ],
            [ Address::fromString("10.255.255.255"),  true ],
            [ Address::fromString("100.63.255.255"),  false ],
            [ Address::fromString("100.64.0.0"),      false ],
            [ Address::fromString("100.127.255.255"), false ],
            [ Address::fromString("100.128.0.0"),     false ],
            [ Address::fromString("224.0.0.0"),       false ],
            [ Address::fromString("231.100.52.4"),    false ],
            [ Address::fromString("239.255.255.255"), false ],
            [ Address::fromString("240.0.0.0"),       false ],
            [ Address::fromString("255.255.255.255"), false ],
        ];
    }

    /**
     * @dataProvider RFC6598Provider  
     * @covers ::isRFC6598  
     */
    public function testIsRFC6598(Address $address, bool $result)
    {
        if ($result)
        {
            $this->assertTrue($address->isRFC6598());
        }
        else
        {
            $this->assertFalse($address->isRFC6598());
        }
    }

    public function RFC6598Provider()
    {
        return [
            [ Address::fromString("0.0.0.0"),         false ],
            [ Address::fromString("9.255.255.255"),   false ],
            [ Address::fromString("10.0.0.0"),        false ],
            [ Address::fromString("10.123.32.173"),   false ],
            [ Address::fromString("10.255.255.255"),  false ],
            [ Address::fromString("100.63.255.255"),  false ],
            [ Address::fromString("100.64.0.0"),      true ],
            [ Address::fromString("100.127.255.255"), true ],
            [ Address::fromString("100.128.0.0"),     false ],
            [ Address::fromString("224.0.0.0"),       false ],
            [ Address::fromString("231.100.52.4"),    false ],
            [ Address::fromString("239.255.255.255"), false ],
            [ Address::fromString("240.0.0.0"),       false ],
            [ Address::fromString("255.255.255.255"), false ],
        ];
    }

    /**
     * @dataProvider privateProvider  
     * @covers ::isPrivate  
     */
    public function testIsPrivate(Address $address, bool $result)
    {
        if ($result)
        {
            $this->assertTrue($address->isPrivate());
        }
        else
        {
            $this->assertFalse($address->isPrivate());
        }
    }

    public function privateProvider()
    {
        return [
            [ Address::fromString("0.0.0.0"),         false ],
            [ Address::fromString("9.255.255.255"),   false ],
            [ Address::fromString("10.0.0.0"),        true ],
            [ Address::fromString("10.123.32.173"),   true ],
            [ Address::fromString("10.255.255.255"),  true ],
            [ Address::fromString("100.63.255.255"),  false ],
            [ Address::fromString("100.64.0.0"),      true ],
            [ Address::fromString("100.127.255.255"), true ],
            [ Address::fromString("100.128.0.0"),     false ],
            [ Address::fromString("224.0.0.0"),       false ],
            [ Address::fromString("231.100.52.4"),    false ],
            [ Address::fromString("239.255.255.255"), false ],
            [ Address::fromString("240.0.0.0"),       false ],
            [ Address::fromString("255.255.255.255"), false ],
        ];
    }

    /**
     * @covers ::next  
     */
    public function testNextOutOfBounds()
    {
        $this->expectException(\OutOfBoundsException::class);
        Address::fromString("255.255.255.255")->next();
    }

    /**
     * @dataProvider nextProvider  
     * @covers ::next  
     */
    public function testNext(Address $address, Address $expected)
    {
        $this->assertTrue($expected->match($address->next()));
    }

    public function nextProvider()
    {
        return [
            [   Address::fromString("0.0.0.0"),         Address::fromString("0.0.0.1") ],
            [   Address::fromString("0.0.0.1"),         Address::fromString("0.0.0.2") ],
            [   Address::fromString("0.0.0.254"),       Address::fromString("0.0.0.255") ],
            [   Address::fromString("0.0.0.255"),       Address::fromString("0.0.1.0") ],
            [   Address::fromString("255.0.0.254"),     Address::fromString("255.0.0.255") ],
            [   Address::fromString("255.0.0.255"),     Address::fromString("255.0.1.0") ],
            [   Address::fromString("255.0.255.254"),   Address::fromString("255.0.255.255") ],
            [   Address::fromString("255.0.255.255"),   Address::fromString("255.1.0.0") ],
            [   Address::fromString("255.255.255.253"), Address::fromString("255.255.255.254") ],
            [   Address::fromString("255.255.255.254"), Address::fromString("255.255.255.255") ],
        ];
    }

    /**
     * @covers ::previous  
     */
    public function testPreviousOutOfBounds()
    {
        $this->expectException(\OutOfBoundsException::class);
        Address::fromString("0.0.0.0")->previous();
    }

    /**
     * @dataProvider nextProvider  
     * @covers ::previous  
     */
    public function testPrevious(Address $expected, Address $address)
    {
        $this->assertTrue($expected->match($address->previous()));
    }

    /**
     * @dataProvider shiftOutOfBoundProvider  
     * @covers ::shift  
     */
    public function testShiftOutOfBounds(Address $address, int $offset)
    {
        $this->expectException(\OutOfBoundsException::class);
        $address->shift($offset);
    }

    public function shiftOutOfBoundProvider()
    {
        return [
            [ Address::fromString("0.0.0.0"),        0x100000000 ],
            [ Address::fromString("0.0.0.0"),        -1 ],
            [ Address::fromString("0.0.0.1"),        0xffffffff ],
            [ Address::fromString("0.0.0.1"),        -2 ],
            [ Address::fromString("255.255.255.254"), 2 ],
            [ Address::fromString("255.255.255.255"), 1 ],
            [ Address::fromString("255.255.255.255"), -(0x100000000) ], //  -(255.255.255.256)  
            [ Address::fromString("255.255.255.254"), -(0xffffffff) ], // -(255.255.255.255)
        ];
    }

    /**
     * @dataProvider shiftrovider
     * @covers ::shift  
     */  
    public function testShift(Address $address, int $offset, Address $expected)
    {
        $this->assertTrue($address->shift($offset)->match($expected));
    }

    public function shiftrovider()
    {
        return [
            [ Address::fromString("0.0.0.0"), 1,          Address::fromString("0.0.0.1") ],
            [ Address::fromString("0.0.0.0"), 0xffffffff, Address::fromString("255.255.255.255") ],
            [ Address::fromString("0.0.0.2"), 2,          Address::fromString("0.0.0.4") ],
            [ Address::fromString("0.0.0.1"), 0xfffffffe, Address::fromString("255.255.255.255") ],
            [ Address::fromString("255.255.255.255"), -(0xffffffff), Address::fromString("0.0.0.0") ],
        ];
    }
}