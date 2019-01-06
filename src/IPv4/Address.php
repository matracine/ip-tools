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
 * Represents an IPv4 address
 *
 * Used to represents and manipulate IPv4 addresses  
 * 
 * IMMUTABLE : once instancied, cannot be modified.
 * New instances are created and returned when results implies differents values. 
 *
 * @package IPTools
 */
class Address implements IP
{
    /**
     * Classes of an address
     *
     * Obsolete for pure routing but still significant for multicat addresses (class D)
     * 
     * @see https://en.wikipedia.org/wiki/Classful_network 
     */ 
    const CLASS_A = "A",
          CLASS_B = "B",
          CLASS_C = "C",
          CLASS_D = "D",
          CLASS_E = "E";

    /**
     * IPv4 addresses are stored as a 32 bits integer
     *
     * @var int
     */
    protected $address = 0;
    
    /**
     * Creates an instance of Adress from an integer (0x00000000 to 0xffffffff)
     *
     * @see Address::fromInteger
     * @see Address::fromString
     * @see Address::fromCidr
     * @see Address::fromArray
     * @param int $address 32 bits integer reprsenting the adress
     * @throws OutOfBoundsException when the value is negative or greater than 32 bits value
     * @return self
     */
    public function __construct(int $address)
    {
        if ($address<0 || $address>0xffffffff)
        {
            throw new OutOfBoundsException(sprintf("Cannot convert %d to an IPv4 address", $address));
            
        } 

        $this->address =  $address;
    }

    /**
     * Creates an instance of Adress from an integer (0x00000000 to 0xffffffff)
     *
     * Maintained to avoid BC-break
     *
     * @param int $address 32 bits integer reprsenting the adress
     * @throws OutOfBoundsException when the value is negative or greater than 32 bits value
     * @return self
     */

    public static function fromInteger(int $address)
    {
        return new self($address);
    }


    /**
     * Creates an instance of Address from an array.
     *
     * The array must contains excly 4 elements (strings or integer).
     * Each elemnt represents a digit (byte) of the dotted quad notation ([192,168,1,10])
     *
     * @param (int|string)[] $address
     * @throws InvalidArgumentException when the array doesn not contains four elements
     * @throws InvalidArgumentException when a string element is empty 
     * @throws InvalidArgumentException when a string element is not a pure integer representation 
     * @throws InvalidArgumentException when an element is not a string nor an integer 
     * @throws OutOfBoundsException when a string element represent a negative value
     * @throws OutOfBoundsException when an element is negative or greater than 32 bits integer
     * @return self
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
            // shift adress left from one byte (8 bits), then add digit (the last byte)
            $buffer = ($buffer << 8) | $digit;
        }
        return self::fromInteger($buffer);
    }

    /**
     * Creates an instance of Address from a dotted quad formated string ("#.#.#.#"")
     *
     * @param string $address
     * @throws InvalidFormatException
     * @return self
     */
    public static function fromString(string $address)
    {
        return self::fromArray(explode('.', $address));
    }

    /**
     * Creates an instance of address from an integer between 0 and 32
     *
     * Usefull to create netmask notations :
     * A netmask is an adress (32 bits) with its left bits stes to 1 and rights to 0.
     * 1 => 10000000000000000000000000000000 => "128.0.0.0"
     * ...
     * 24 => 11111111111111111111111100000000 => "255.255.255.0"
     *
     * @param int $cidr CIDR notation of address
     * @throws OutOfBoundsException when param is negatve or greater than 32
     * @return self
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
     * Get the default 'dotted-quad' representation of the IPv4 address ("#.#.#.#")
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

    /**
     * Get the integer value of the IPv4 address between 0 to 0xffffffff
     *
     * @return int
     */

    public function asInteger()
    {
        return $this->address;
    }

    /**
     * Get the integer value of the IPv4 address between 0 to 0xffffffff
     *
     * Just a helper/short way to call asInteger()
     *
     * @see Address::asInteger()
     * @return int
     */

    public function int()
    {
        return $this->asInteger();
    }

    /**
     * Get the CIDR integer value (0-32) from the adress if possible (eg : "255.255.255.0" returns 24)
     *
     * @see Address::fromCidr()
     * @throws DomainException when the address cannot be converted to CIDR
     * @return integer A value beteween 0 to 32  
     */
    public function asCidr()
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


    /**
     * Get a string representation of address
     *
     * Automagically called when PHP needs to convert an address to a string.
     * I choose to return the default 'dotted-quad' representation of the IPv4 address ("#.#.#.#")
     *
     * @return string
     */
    public function __toString()
    {
        return $this->asDotQuad();
    }



    /**
     * Get the class of the IP address (for obsolete classfull routing)
     *
     * @return string a constant CLASS_[A-E]
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
     * Get the IP version (IPv4 or IPv6) of this address instance
     * 
     * @return int a constant IPv4 or IPv6
     */
    public function version()
    {
        return self::IPv4;
    }

    /**
     * Tells if the adresse can be used as a netmask
     * 
     * A netmask is a kind of address.
     * Only a little nuber of adresses can be used as netmasks (33).
     * Only adresses with left bits sets to 1 and rights bits sets to 0, with no mix between this two zones are eligible to be netmasks.
     * NOTE : An adress can be a netmask if it can be converted to a CIDR value and vice versa
     *
     * @see Address::fromCidr()
     * @return bool  
     */
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
     * Returns whether the address is part of the subnets defined in RFC 1918
     *
     * RFC1918 defines privates, non Internet routables subnets for end users's privates network
     * This subnets are :
     *  - 10.0.0.0/8
     *  - 172.16.0.0/12
     *  - 192.168.0.0/16
     * This subnets should be used by end users to build their privates networks.
     *
     * @see https://tools.ietf.org/html/rfc1918
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

    /**
     * Returns whether the address is part of the subnets defined in RFC 6598
     *
     * RFC6598 defines a private, non Internet routable subnet for Carrir Grade Networks, mainly used by FAI or non end users network providers 
     * This subnet is 100.64.0.0/10
     * This subnet should not be used by end users
     *
     * @see https://tools.ietf.org/html/rfc6598
     * @return bool
     */
    public function isRFC6598()
    {
        $subnet = Subnet::fromCidr(Address::fromString("100.64.0.0"), 10);
        return $subnet->contains($this) ? true : false;
    }

    /**
     * Returns whether the address is part of non Internet routable subnets
     * 
     * @todo improve: multicast, other on routables, ... ?
     * @return bool 
     */
    public function isPrivate()
    {
        return ($this->isRFC1918()  || $this->isRFC6598());
    }

    /**
     * Returns whether the address is part of multicast address range
     * 
     * @return bool 
     */
    public function isMulticast()
    {
        return $this->getClass() === self::CLASS_D;
    }

    /**
     * Tests if two addresses are equivalent (same value)
     *
     * @param self $address
     * @return bool
     */
     public function match(Address $address)
    {
        return $this->int() == $address->int();
    }

    /**
     * Returns an address shifted by an amount of units 
     *
     * NOTICE : A new instance of address is instanciated, does not shitf the instance used.
     *
     * @param int $offset 
     * @throws OutOfBoundsException when the resulting address is out of the bounds (negative or greater than 32 bits value)
     * @return self
     */
    public function shift(int $offset)
    {
        return self::fromInteger($this->address+$offset);
    }

    /**
     * Returns the address immediately following this address 
     *
     * Examples :
     *   - "1.2.3.4" => "1.2.3.5"
     *   - "1.2.3.255" => "1.2.4.0"
     *
     *  NOTICE : A new instance of address is instanciated, does not modify the instance used.
     *
     * @throws OutOfBoundsException when the resulting address is out of the bounds (more than 32 bits value). Happend only if try to get next of "255.255.255.255"
     * @return self
     */
    public function next()
    {
        return $this->shift(1);
    }

    /**
     * Returns the address immediately preceding this address 
     *
     * Examples :
     *   - "1.2.3.5" => "1.2.3.4"
     *   - "1.2.4.0" => "1.2.3.255"
     *
     * NOTICE : A new instance of address is instanciated, does not modify the instance used.
     *
     * @throws OutOfBoundsException when the resulting address is out of the bounds (less than 0). Happend only if try to get previous of "0.0.0.0"
     * @return self
     */
    public function previous()
    {
        return $this->shift(-1);
    }

}