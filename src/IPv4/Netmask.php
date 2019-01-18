<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools\IPv4;

use DomainException;
use OutOfBoundsException;
use Countable;

use mracine\IPTools\IPv4\Address;

/**
 * Class Netmask represents an IPv4 netmask, wich is an address (32 bits integer) with special requirements
 *
 * @todo detail requirements
 *
 * @package IPTools
 */


class Netmask extends Address  implements Countable
{
    /**
     * Creates an instance of Netmask from an integer (0x00000000 to 0xffffffff)
     *
     * Must be a valid integer : lefts bits 1, lows bits 0
     *
     * @throws OutOfBoundsException when the value is negative or greater than 32 bits value
     * @throws DomainException when the value does not represents a netmask
     * @param int $netask 32 bits integer representing the address
     * @return self
     */
    public function __construct(int $netmask)
    {
        parent::__construct($netmask);
        if (0==$netmask)
        {
            return;
        }
        // Netamsk validation
        $mask = 0xffffffff;
        do {
            if ($mask == $netmask)
            {
                // valid netmask
                return;
            }
            $mask = ($mask << 1) & 0xffffffff;   
        } while ($mask>0);

        if ($netmask!=0)
        {
            throw new DomainException(sprintf("Cannot convert 0x%x (%s) to netmask", $netmask, (string)$this));
        }
    } // @codeCoverageIgnore

    /**
     * Creates an instance of Netmask from an integer between 0 and 32
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
        return new static($netmask);
    }    

    /**
     * Get a Netmask object from an Address if possible
     *
     * @throws DomainException when the Address cannot be converted to a Netmask
     * @return \mracine\IPTools\IPv4\Netmask
     */

    public static function fromAddress(Address $address)
    {
        return new static($address->int());
    }

    /**
     * Get the CIDR integer value (0-32) from the netmask
     *
     * @see Netmask::fromCidr()
     * @throws DomainException when the address cannot be converted to CIDR
     * @return integer A value beteween 0 to 32  
     */
    public function asCidr()
    {
        // Pas très élégant.... 
        for ($cidr=32 ; $cidr>=0 ; $cidr--)
        {
            $n = (0xffffffff << (32 - $cidr)) & 0xffffffff;
            if( $n == $this->int() )
            {
                return $cidr;
            }
        }
    } // @codeCoverageIgnore

    /**
     * Returns how many address are incuded in the netask
     *
     * @return int
     */ 
    public function count()
    {
        return (~($this->int()) & 0xffffffff)+1;
    }

    public function shift(int $offset)
    {
        return static::fromCidr($this->asCidr() + $offset);
    }
}