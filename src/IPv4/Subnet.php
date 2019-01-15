<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools\IPv4;

use OutOfBoundsException;
use RangeException;
use BadMethodCallException;
use InvalidArgumentException;

use mracine\IPTools\Iterators\SubnetIterator;
use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IPv4\Netmask;
use mracine\IPTools\IPVersion;
/**
 * Class Subnet represents an IPv4 IP address with a netmask to specify a subnet
 *
 * Subnet implements :
 *  * Countable : number of addresses
 *  * ArrayAccess : Address can be acceesed by offset (ex : $range[10])
 *  * IteratorAggregate :   range can be itrated (foreach)

 * @package IPTools
 */
class Subnet extends Range
{
    /**
     * @var Netmask $netmask 
     */
    protected $netmask;

     /**
     * Creates an IP Subnet from a an IP network addresses and a netamsk
     *
     * Lower and upper bound are automaticly choosen, no need to choose wich one to pass first.
     * Only 
     *
     * @param Address $baseAddress the first address of the range
     * @param int $count the number  other bound
     */
    public function __construct(Address $network, Address $netmask)
    {
        if (!($netmask instanceof Netmask))
        {
            /**
             * @todo implement
             */   
            throw new InvalidArgumentException("Not implemented");
        }
        // Verify parameters validity
        if( ($network->int() & $netmask->int()) != $network->int())
        {
            throw new RangeException(sprintf("Invalid network adress %s, this address is not usable with the netmask %s", (string)$network, (string)$netmask));
        }



        parent::__construct($network, $network->shift(count($netmask)-1));
        $this->netmask = $netmask;
    }

   /**
     * Construct an IPv4 Subnet from a CIDR notation (#.#.#.#/#)
     *
     * @param Address $network the network base address  of the subnet
     * @param int $cidr the CIDR notation of the netmask
     * @throws OutOfBoundsException when $cidr is negatve or greater than 32
     * @throws RangeException when network address and netmask does not form a valid subnet 
     * @return Subnet
     */
    public static function fromCidr(Address $network, int $cidr)
    {
        // Ensure CIDR is valid, use Address::fromCidr to have validation logic in only one place 
        $netmask = Netmask::fromCidr($cidr);
        return new static($network, $netmask);
    }

    /**
     * Returns an IPv4 Subnet from network address and netmask given as an address 
     *
     * @param Address $network the network base address of the subnet
     * @param Address $netmask the netmask address
     * @throws DomainException when the netmask address is not a valid netmask
     * @throws RangeException when network and netmask addresses does not form a valid subnet 
     * @return Subnet
     */
    // public static function fromAddresses(Address $network, Address $netmask)
    // {
    //     return new static($network, $netmask->asCidr());
    // }

    /**
     * Return a subnet from an addresss and a CIDR form netmask, the addresscan be within the subnet, not only the network address
     *
     * @param Address $address an address within the desired subnet
     * @param int $cidr the CIDR notation of the netmask
     * @return Subnet
     */
    public static function fromContainedAddressCidr(Address $address, int $cidr)
    {
        $netmask = Netmask::fromCidr($cidr)->int();
        $network = Address::fromInteger($address->int() & $netmask);
        return static::fromCidr($network, $cidr);
    }

    /**
     * Get the network address
     *
     * NOICE : return a fresh instance of address
     *
     * @return Address
     */
    public function getNetworkAddress()
    {
        return $this->getLowerBound();
    }

    /**
     * Get the netmask address 
     *
     * @return Netmask
     */
    public function getNetmaskAddress()
    {
        return $this->netmask;
    }
    /**
     * Get the broadcast address 
     *
     * @return Address
     */
    public function getBroadcastAddress()
    {
        return $this->getUpperBound();
    }

    /**
     * Returns a Subnet shifted by an amount of units (count)
     *
     * Need explain...
     *
     * @param int $offset
     * @throws OutOfBounsException when the resulting subet is invalid
     * @return Subnet
     */  
    public function shift(int $offset)
    {
        return static::fromCidr(
            $this->getNetworkAddress()->shift($offset*count($this)),
            $this->getNetmaskAddress()->asCidr()
         );
    }

    /*
     * interface IteratorAggregate
     */

    /**
     * Obtain an iterator to traverse the Subnet
     *
     * Allows to iterate in the subnet with foreach ($subnet as $address) {...} )
     *
     * @return SubnetIterator
     */
    public function getIterator()
    {
        return new SubnetIterator($this);
    }
}
