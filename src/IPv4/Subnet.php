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
     * @param Address $baseAddress the first address of the range
     * @param int $count the number  other bound
     * @param bool $strict if true $network
     */
    public function __construct(Address $network, Address $netmask, bool $strict=true)
    {
        $finalNetmask = clone($netmask);
        if (!($netmask instanceof Netmask))
        {
            $finalNetmask = Netmask::fromAddress($netmask);  
        }
        
        // Verify parameters validity
        if( (($network->int() & $finalNetmask->int()) != $network->int()) && $strict)
        {
            throw new RangeException(sprintf("Invalid network adress %s, this address is not usable with the netmask %s", (string)$network, (string)$finalNetmask));
        }

        $finalNetwork = new Address($network->int() & $finalNetmask->int());

        parent::__construct($finalNetwork, $finalNetwork->shift(count($finalNetmask)-1));
        $this->netmask = $finalNetmask;
    }

   /**
     * Construct an IPv4 Subnet from a CIDR notation (#.#.#.#/#)
     *
     * @param Address $network the network base address  of the subnet
     * @param int $cidr the CIDR notation of the netmask
     * @param bool $strict 
     * @throws OutOfBoundsException when $cidr is negatve or greater than 32
     * @throws RangeException when network address and netmask does not form a valid subnet 
     * @return Subnet
     */
    public static function fromCidr(Address $network, int $cidr, bool $strict=true)
    {
        // Ensure CIDR is valid, use Address::fromCidr to have validation logic in only one place 
        $netmask = Netmask::fromCidr($cidr);
        return new static($network, $netmask, $strict);
    }

    /**
     * Return a subnet from an addresss and a CIDR form netmask, the addresscan be within the subnet, not only the network address
     *
     * @deprecated use $strict parameter of Subnet::fromCidr
     * @see Subnet::fromCidr
     * @param Address $address an address within the desired subnet
     * @param int $cidr the CIDR notation of the netmask
     * @return Subnet
     */
    public static function fromContainedAddressCidr(Address $address, int $cidr)
    {
        @trigger_error(
            'The fromContainedAddressCidr functon is deprecated and will be removed soon.'
            .' Use fromCidr with strict parameter set to false instead.',
        E_USER_DEPRECATED
    );
        return static::fromCidr($address, $cidr, false);
    }

    /**
     * Construct a subnet from string
     * 
     * @param string $subnet a CIDR formated or netmask representtation of the subnet : x.x.x.x/24 or x.x.x.x/255.255.255.0
     * @param bool $strict if true address must be a netwoek bound, else an be an address within the range
     * @throws InvaidArgumentException when provided sting cannot be converted to a valid subnet
     * @return Subnet
     */
    public static function fromString(string $subnet, bool $strict=true)
    {
        @list($address, $netmask, $trash) = explode('/', $subnet);
        if (!is_null($trash) || is_null($netmask))
        {
            throw new InvalidArgumentException(sprintf("%s cannot be converted to subnet. Valid formats are : x.x.x.x/cidr or x.x.x.x/y.y.y.y", $subnet));
        }

        if(ctype_digit($netmask))
        {
            return static::fromCidr(Address::fromString($address), (int)$netmask, $strict);
        }
        return new static(Address::fromString($address), Netmask::fromString($netmask), $strict);
    }

    /**
     * Retrun a string representing the Subnet un DotQuad notation and CIDR
     * 
     * ex: 192.168.0.0/24
     *
     * @param string $separator The separator used between network an CIDR, defaults to /
     * @return string
     */
    public function asDotQuadAndCidr(string $separator='/')
    {
        return (string)$this->getNetworkAddress() . $separator . (string)$this->getNetmaskAddress()->asCidr();
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
