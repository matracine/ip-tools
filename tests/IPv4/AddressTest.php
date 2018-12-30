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
use mracine\IPTools\IP;

class AddressTest extends TestCase
{
	/**
	 * @dataProvider fromArrayInvalidFormatProvider
	 * @expectedException InvalidArgumentException
	  *
	 * @param array $data
	 */

	public function testFromArrayInvalidFormat(Array $data)
	{
		$address = Address::fromArray($data);
    }

	/**
	 * @dataProvider fromArrayOutOfBoundsProvider
	 * @expectedException OutOfBoundsException
	  *
	 * @param array $data
	 */

	public function testFromArrayOutOfBounds(Array $data)
	{
		$address = Address::fromArray($data);
    }

	/**
	 * @dataProvider fromArrayProvider
	 *
	 * @param array $data
	 */
    public function testFromArray(Array $data, string $strExpected, int $intExpected)
    {
		$address = Address::fromArray($data);
		$this->assertInstanceOf(Address::class, $address);
		$this->assertEquals((string)$address, $strExpected);
		$this->assertInternalType('int', $address->int());		
		$this->assertEquals($address->int(), $intExpected);

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

    public function fromArrayOutOfBoundsProvider()
    {
        return [
            [ [256,1,1,1],  ],
            [ [1,256,1,1],  ],
            [ [1,1,256,1],  ],
            [ [1,1,1,256],  ],
            [ [1,1,1,-1],   ],
		];
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
	 * @expectedException InvalidArgumentException
	  *
	 * @param string $data
	 */

	public function testFromStringInvalidFormat(string $data)
	{
		$address = Address::fromString($data);
    }

	/**
	 * @dataProvider fromStringOutOfBoundsProvider
	 * @expectedException OutOfBoundsException
	  *
	 * @param string $data
	 */

	public function testFromStringOutOfBounds(string $data)
	{
		$address = Address::fromString($data);
    }

	/**
	 * @dataProvider fromStringProvider
	 *
	 * @param string $data
	 */
    public function testFromString(string $data, string $strExpected, int $intExpected)
    {
		$address = Address::fromString($data);
		$this->assertInstanceOf(Address::class, $address);
		$this->assertEquals((string)$address, $strExpected);
		$this->assertInternalType('int', $address->int());		
		$this->assertEquals($address->int(), $intExpected);
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

    public function fromStringInvalidFormatProvider()
    {
        $a = [];
    	foreach(self::fromArrayInvalidFormatProvider() as $key=>$data)
    	{
    		$a[$key] = [ implode('.', $data[0]) ];
    	}
    	return $a;
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
	 * @dataProvider fromIntegerOutOfBoundsProvider
	 * @expectedException OutOfBoundsException
	 *
	 * @param int $data
	 */

	public function testFromIntegerOutOfBounds(int $data)
	{
		$address = Address::FromInteger($data);
	}

	/**
	 * @dataProvider fromIntegerProvider
	 *
	 * @param int $data
	 */
	public function testFromInteger(int $data, string $strExpected, int $intExpected)
	{
		$address = Address::FromInteger($data);
		$this->assertInstanceOf(Address::class, $address);
		$this->assertEquals((string)$address, $strExpected);
		$this->assertEquals((string)$address,  $strExpected);
		$this->assertInternalType('int', $address->int());		
        $this->assertEquals($address->int(), $intExpected);
	}

	public function fromIntegerOutOfBoundsProvider()
	{
		return [
			"Negative"      => [              -1 ],
			"Very negative" => [            -255 ],
			"Limit up"      => [     0x100000000 ],
			"Huge one"      => [ 0xfffffffffffff ],
		];
	}

    public function fromIntegerProvider()
    {
    	foreach (self::fromArrayProvider() as $key => $data)
    	{
    		$a[$key] = [ $data[2], $data[1], $data[2] ];
    	}
    	return $a;
    }


    /**
     * @dataProvider fromCIDROutOfBoundsProvider
     * @expectedException OutOfBoundsException
     *
     * @param int $data
     */

    public function testFromCIDROutOfBounds(int $cidr)
    {
        $address = Address::FromCIDR($cidr);
    }

    /**
     * @dataProvider fromCIDRProvider
     *
     * @param int $data
     */
    public function testFromCIDR(int $cidr, string $strExpected, int $intExpected)
    {
        $address = Address::FromCIDR($cidr);
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals((string)$address, $strExpected);
        $this->assertEquals((string)$address,  $strExpected);
        $this->assertInternalType('int', $address->int());        
        $this->assertEquals($address->int(), $intExpected);
    }

    public function fromCIDROutOfBoundsProvider()
    {
        return [
            [ -10 ],
            [ -2 ],
            [ -1 ],
            [ 33 ],
            [ 34 ],
            [ 75 ],
        ];
    }

    public function fromCIDRProvider()
    {
        return [
            [ 0,  '0.0.0.0',                  0 ],
            [ 1,  '128.0.0.0',       0x80000000 ],
            [ 24, '255.255.255.0',   0xffffff00 ],
            [ 30, '255.255.255.252', 0xfffffffc ],
            [ 31, '255.255.255.254', 0xfffffffe ],
            [ 32, '255.255.255.255', 0xffffffff ],
        ];
    }

	/**
	 * @dataProvider matchProvider  
	 */
	public function testMatch(string $string, int $integer)
	{
		$address1 = Address::fromString($string);
		$address2 = Address::fromInteger($integer);

		$this->assertTrue($address1->match($address2));
	}

	public function matchProvider()
	{
		return [
			[ '0.0.0.0', 0 ],
			[ '0.0.0.1', 1 ],
			[ '0.0.1.0', 1 << 8 ],
			[ '0.1.0.0', 1 << 16 ],
			[ '1.0.0.0', 1 << 24 ],
			[ '255.0.255.0', 0xff00ff00 ],
			[ '255.255.255.255', 0xffffffff ],
		];
	}

	/**
	 * @dataProvider classProvider  
	 */
	public function testClass(string $string, $class)
	{
		$address = Address::fromString($string);
		
		$this->assertSame($address->getClass(), $class);
	}

	public function classProvider()
    {
    	return [
			["0.0.0.0",         Address::CLASS_A ],
			["10.0.0.0",        Address::CLASS_A ],
			["10.1.2.3",        Address::CLASS_A ],
			["127.0.0.1",       Address::CLASS_A ],
			["127.255.255.255", Address::CLASS_A ],
			["128.0.0.0",       Address::CLASS_B ],
			["191.255.255.255", Address::CLASS_B ],
			["192.0.0.0",       Address::CLASS_C ],
			["223.255.255.255", Address::CLASS_C ],
			["224.0.0.0",       Address::CLASS_D ],
			["239.255.255.255", Address::CLASS_D ],
            ["240.0.0.0",       Address::CLASS_E ],
            ["255.255.255.255", Address::CLASS_E ],
        ];
    }


	/**
	 * @dataProvider multicastProvider  
	 */
	public function testIsMulticast(string $string, bool $result)
	{
		$address = Address::fromString($string);
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
            [         "0.0.0.0", false ],
			[        "10.3.5.6", false ],
			[ "223.255.255.255", false ],
			[       "224.0.0.0", true ],
			[    "231.100.52.4", true ],
			[ "239.255.255.255", true ],
			[       "240.0.0.0", false ],
			[ "255.255.255.255", false ],
		];
	}

    public function testVersion()
    {
        $address = Address::fromInteger(1);
        $this->assertSame($address->version(), IP::IPv4);
    }

    /**
     * @dataProvider RFC1918Provider  
     */
    public function testIsRFC1918(string $string, bool $result)
    {
        $address = Address::fromString($string);
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
            [         "0.0.0.0", false ],
            [   "9.255.255.255", false ],
            [        "10.0.0.0", true ],
            [   "10.123.32.173", true ],
            [  "10.255.255.255", true ],
            [  "100.63.255.255", false ],
            [      "100.64.0.0", false ],
            [ "100.127.255.255", false ],
            [     "100.128.0.0", false ],
            [       "224.0.0.0", false ],
            [    "231.100.52.4", false ],
            [ "239.255.255.255", false ],
            [       "240.0.0.0", false ],
            [ "255.255.255.255", false ],
        ];
    }

    /**
     * @dataProvider RFC6598Provider  
     */
    public function testIsRFC6598(string $string, bool $result)
    {
        $address = Address::fromString($string);
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
            [         "0.0.0.0", false ],
            [   "9.255.255.255", false ],
            [        "10.0.0.0", false ],
            [   "10.123.32.173", false ],
            [  "10.255.255.255", false ],
            [  "100.63.255.255", false ],
            [      "100.64.0.0", true ],
            [ "100.127.255.255", true ],
            [     "100.128.0.0", false ],
            [       "224.0.0.0", false ],
            [    "231.100.52.4", false ],
            [ "239.255.255.255", false ],
            [       "240.0.0.0", false ],
            [ "255.255.255.255", false ],
        ];
    }

    /**
     * @dataProvider privateProvider  
     */
    public function testIsPrivate(string $string, bool $result)
    {
        $address = Address::fromString($string);
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
            [         "0.0.0.0", false ],
            [   "9.255.255.255", false ],
            [        "10.0.0.0", true ],
            [   "10.123.32.173", true ],
            [  "10.255.255.255", true ],
            [  "100.63.255.255", false ],
            [      "100.64.0.0", true ],
            [ "100.127.255.255", true ],
            [     "100.128.0.0", false ],
            [       "224.0.0.0", false ],
            [    "231.100.52.4", false ],
            [ "239.255.255.255", false ],
            [       "240.0.0.0", false ],
            [ "255.255.255.255", false ],
        ];
    }

    /**
     * @dataProvider nextOutOfBoundProvider  
     * @expectedException OutOfBoundsException
     */
    public function testNextOutOfBounds(Address $address)
    {
        $address->next();
    }

    /**
     * @dataProvider nextProvider  
     */
    public function testNext(Address $address, Address $expected)
    {
        $this->assertTrue($expected->match($address->next()));
    }

    /**
     * @dataProvider previousOutOfBoundProvider  
     * @expectedException OutOfBoundsException
     */
    public function testPreviousOutOfBounds(Address $address)
    {
        $address->previous();
    }

    /**
     * @dataProvider nextProvider  
     */
    public function testPrevious(Address $expected, Address $address)
    {
        $this->assertTrue($expected->match($address->previous()));
    }

    public function nextProvider()
    {
        return [
            [   Address::fromString("0.0.0.0"), Address::fromString("0.0.0.1") ],
            [   Address::fromString("0.0.0.1"), Address::fromString("0.0.0.2") ],
            [   Address::fromString("0.0.0.254"), Address::fromString("0.0.0.255") ],
            [   Address::fromString("0.0.0.255"), Address::fromString("0.0.1.0") ],
            [   Address::fromString("255.0.0.254"), Address::fromString("255.0.0.255") ],
            [   Address::fromString("255.0.0.255"), Address::fromString("255.0.1.0") ],
            [   Address::fromString("255.0.255.254"), Address::fromString("255.0.255.255") ],
            [   Address::fromString("255.0.255.255"), Address::fromString("255.1.0.0") ],
            [   Address::fromString("255.255.255.253"), Address::fromString("255.255.255.254") ],
            [   Address::fromString("255.255.255.254"), Address::fromString("255.255.255.255") ],
        ];
    }

    public function nextOutOfBoundProvider()
    {
        return [
            [   Address::fromString("255.255.255.255") ],
        ];
    }

    public function previousOutOfBoundProvider()
    {
        return [
            [   Address::fromString("0.0.0.0") ],
        ];
    }

    /**
     * @dataProvider shiftOutOfBoundProvider  
     * @expectedException OutOfBoundsException
     */
    public function testShiftOutOfBounds(Address $address, int $offset)
    {
        $address->shift($offset);
    }

    /**
     * @dataProvider shiftrovider
     */  
    public function testShift(Address $address, int $offset, Address $expected)
    {
        $this->assertTrue($address->shift($offset)->match($expected));
    }

    public function shiftOutOfBoundProvider()
    {
        return [
            [ Address::fromString("0.0.0.0"), 0x100000000 ],
            [ Address::fromString("0.0.0.0"), -1 ],
            [ Address::fromString("0.0.0.1"), 0xffffffff ],
            [ Address::fromString("0.0.0.1"), -2 ],
            [ Address::fromString("255.255.255.254"), 2 ],
            [ Address::fromString("255.255.255.255"), 1 ],
            [ Address::fromString("255.255.255.255"), -(0x100000000) ], //  -(255.255.255.256)  
            [ Address::fromString("255.255.255.254"), -(0xffffffff) ], // -(255.255.255.255)
        ];
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


    /**
     * @dataProvider asCidrInvalidFormatProvider
     * @expectedException DomainException
     */

    public function testAsCidrInvalidFormat(Address $address)
    {
        $address->asCidr();
    }

    /**
     * @dataProvider asCidrProvider
     */

    public function testAsCidr(Address $address, int $expected)
    {
        $this->assertEquals($expected, $address->asCidr());
    }


    /**
     * @dataProvider asCidrProvider
     */
    public function testIsNetmask(Address $address)
    {
        $this->AssertTrue($address->isNetmask());
    }

    /**
     * @dataProvider asCidrInvalidFormatProvider
     */
    public function testIsNotNetmask(Address $address)
    {
        $this->AssertFalse($address->isNetmask());
    }

    public function asCidrInvalidFormatProvider()
    {
        return [
            [ Address::fromString("0.0.0.1") ],
            [ Address::fromString("255.255.255.253") ],
        ];
    }

    public function asCidrProvider()
    {
        return [
            [ Address::fromString("0.0.0.0"), 0 ],
            [ Address::fromString("128.0.0.0"), 1 ],
            [ Address::fromString("255.255.0.0"), 16 ],
            [ Address::fromString("255.255.255.0"), 24 ],
            [ Address::fromString("255.255.255.252"), 30 ],
            [ Address::fromString("255.255.255.254"), 31 ],
            [ Address::fromString("255.255.255.255"), 32 ],
        ];
    }
}