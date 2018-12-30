<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools\IPv4;

use OutOfBoundsException;
use InvalidArgumentException;
use DomainException;
use RuntimeException;

use mracine\IPTools\IP;
use mracine\IPTools\IPAddress;

/**
 * Class Address represents an IPv4 address
 *
 * @package IPTools
 */
class Address implements IP
{
    const CLASS_A = "A";
    const CLASS_B = "B";
    const CLASS_C = "C";
    const CLASS_D = "D";
    const CLASS_E = "E";

    const FORMAT_STRING  = 1;
    const FORMAT_INTEGER  = 2;

    /**
     * Addresses are stored as an 32 bits integer
     *
     * @var int
     */
    protected $address = 0;
    
    /**
     * Protcetd to prevent direct creation : use static methods fromString, fromInteger or fromArray 
     *
     */
    protected function __construct()
    {
    }

    /**
     * Creates an instance of Adress from an integer (0x00000000 to 0xffffffff)
     *
     * @param int $address
     * @throws \IPTools\Exceptions\InvalidFormatException
     * @throws \OutOfBoundsException
     * @return \IPTools\IPv4\Address
     */

    public static function fromInteger(int $address)
    {
        if ($address<0 || $address>0xffffffff)
        {
            throw new OutOfBoundsException(sprintf("Cannot convert %d to an IPv4 address", $address));
            
        } 
        //= Utility::parseUint($address, 32);

        $newAddress = new self();
        $newAddress->address =  $address;
        return $newAddress;
    }

    /**
     * Creates an instance of Address from a dotted quad (#.#.#.#)
     *
     * @param string $address
     * @throws \IPTools\Exceptions\InvalidFormatException
     * @return \IPTools\IPv4\Address
     */
    public static function fromString(string $address)
    {
        return self::fromArray(explode('.', $address));
    }

    /*
     * Transforme un CIDR en une adress IP
     */
    public static function fromCidr(int $cidr)
    {
        if ($cidr<0 || $cidr>32 )
        {
            throw new OutOfBoundsException(sprintf("Invalid CIDR value %d", $cidr));
        }
        $netmask = (0xffffffff << (32 - $cidr)) & 0xffffffff;
        return Address::fromInteger($netmask);
    }    


    /**
     * Creates an instance of Address from an array ([192,168,1,10])
     *
     * @param string $address
     * @throws OutOfBoundsException
     * @throws InvalidArgumentException
     * @return \IPTools\IPv4\Address
     */

    public static function fromArray(Array $address)
    {
        if( count($address) != 4)
        {
            throw new InvalidArgumentException(sprintf("Array must contain 4 digits, %d found", count($address)));
        }

        $buffer = 0;
        foreach($address as $digit)
        {
            if (is_string($digit) )
            {        
                $digit = trim($digit);
                if (strlen($digit)==0)
                {
                    throw new InvalidArgumentException(sprintf("The array must contains only integers or strings with integer content , string found with empty value"));
                }
                if ( $digit[0] == '-' && ctype_digit(ltrim($digit, '-')) )
                {
                    // string type : "-123"
                    throw new OutOfBoundsException(sprintf("Cannot convert %d to Ipv4 addresss digit", $digit));
                }
                if (!ctype_digit($digit))
                {
                    throw new InvalidArgumentException(sprintf("The array must contains only integers or strings with integer content , string found with bad value %s", $digit));
                }
                // Here the string contains only numbers, can be casted to int safely
                $digit = (int)$digit;
            }
            
            if (!is_integer($digit) )
            {
                throw new InvalidArgumentException(sprintf("The array must contains only integers or strings with integer content , %s found", gettype($digit)));
            }

            if($digit<0 || $digit>0xff)
            {
                throw new OutOfBoundsException(sprintf("Cannot convert %d to Ipv4 addresss digit", $digit));

            }
            $buffer = ($buffer << 8) | $digit;
        }
        return Address::fromInteger($buffer);
    }


    /**
     * Get the default 'dotted-quad' representation of the IPv4 address
     *
     * @return string
     */
    public function asDotQuad()
    {
        return sprintf(
            "%u.%u.%u.%u",
            (($this->address & (0xff << 24)) >> 24),
            (($this->address & (0xff << 16)) >> 16),
            (($this->address & (0xff << 8)) >> 8),
            ($this->address & 0xff)
        );
    }

    public function asInteger()
    {
        return $this->address;
    }

    /**
     * Returns CIDR from adress if possible (eg : 255.255.255.0 returns 24)
     *
     */
    public function asCIDR()
    {
        // Pas très élégant.... 
        $cidr = 32;
        for ($cidr=32 ; $cidr>=0 ; $cidr--)
        {
            $n = (0xffffffff << (32 - $cidr)) & 0xffffffff;
            if( $n == $this->address )
            {
                return $cidr;
            }
        }
        throw new DomainException(sprintf("Cannot convert address %s to CIDR, not a netmask", (string)$this));
    }

    public function __toString()
    {
        return $this->asDotQuad();
        // return sprintf(
        //     "%u.%u.%u.%u",
        //     (($this->address & (0xff << 24)) >> 24),
        //     (($this->address & (0xff << 16)) >> 16),
        //     (($this->address & (0xff << 8)) >> 8),
        //     ($this->address & 0xff)
        // );
    }


    public function int()
    {
        return $this->asInteger();
    }

    /**
     * Get the class of the IP address (for classful routing)
     */
    public function getClass()
    {
        $higherOctet = $this->address >> 24;

        if (($higherOctet & 0x80) == 0) {
            return self::CLASS_A;
        }

        if (($higherOctet & 0xC0) == 0x80) {
            return self::CLASS_B;
        }

        if (($higherOctet & 0xE0) == 0xC0) {
            return self::CLASS_C;
        }

        if (($higherOctet & 0xF0) == 0xE0) {
            return self::CLASS_D;
        }

        if (($higherOctet & 0xF0) == 0xF0) {
            return self::CLASS_E;
        }

        // Should never be triggered
        // @codeCoverageIgnoreStart
        throw new RuntimeException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return int
     */
    public function version()
    {
        return self::IPv4;
    }

    public function isNetmask()
    {
        // Pas très élégant non plus.... 
        try
        {
            $this->asCidr();
        }
        catch(DomainException $e)
        {
            return false;
        }
        return true;
    }

    /**
     * Returns whether the address is part of the private address pool (RFC 1918)
     *
     * @return bool
     */
    public function isRFC1918()
    {
        /** @var $subnets Subnet[] */
        $subnets = array(
            Subnet::fromCidr(Address::fromString("10.0.0.0"), 8),
            Subnet::fromCidr(Address::fromString("172.16.0.0"), 12),
            Subnet::fromCidr(Address::fromString("192.168.0.0"), 16),
        );
        foreach($subnets as $subnet) {
            if($subnet->contains($this)) {
                return true;
            }
        }
        return false;
    }

    public function isRFC6598()
    {
        $subnet = Subnet::fromCidr(Address::fromString("100.64.0.0"), 10);
        return $subnet->contains($this) ? true : false;
    }

    public function isPrivate()
    {
        return ($this->isRFC1918()  || $this->isRFC6598());
    }

    public function isMulticast()
    {
        return $this->getClass() === self::CLASS_D;
    }

    /**
     * Tests if two addresses are equivalent
     *
     * @param $address
     * @return bool
     */
     public function match(Address $address)
    {
        return $this->address == $address->int();
    }

    public function shift(int $offset)
    {
        return self::fromInteger($this->address+$offset);
    }

    public function next()
    {
        return $this->shift(1);
    }

    public function previous()
    {
        return $this->shift(-1);
    }

}