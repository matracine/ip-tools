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
use mracine\IPTools\IP;
/**
 * Class Subnet represents an IPv4 IP address with a netmask to specify a subnet
 *
 * Subnet implements :
 *  * Countable : number of addresses
 *  * ArrayAccess : Address can be acceesed by offset (ex : $range[10])
 *  * IteratorAggregate :   range can be itrated (foreach)

 * @package IPTools
 */
class Subnet implements IP, \Countable, \ArrayAccess, \IteratorAggregate
{
    /**
     * @var Address $network the network address of the subnet
     */ 
    protected $network;

    /**
     * Netmask of the subnet
     *
     * Stored as a 32 bits integer

     * /24 => 255.255.255.0 => 0xffffff00
     *
     * @var int nemask
     */
    protected $netmask;

    /**
     * Construct an IPv4 Subnet from a CIDR notation (#.#.#.#/#)
     *
     * @param Address $network the network base address  of the subnet
     * @param int $cidr the CIDR notation of the netmask
     * @throws OutOfBoundsException when $cidr is negatve or greater than 32
     * @throws RangeException when network address and netmask does not form a valid subnet 
     */
    public function __construct(Address $network, int $cidr)
    {
        // Ensure CIDR is valid, use Address::fromCidr to have validation logic in only one place 
        $netmask = Address::fromCidr($cidr)->int();
        $address = $network->int();
        if( ($address & $netmask) != $address)
        {
            throw new RangeException(sprintf("Invalid network adress %s, this address is not usable with the CIDR %d", (string)$network, $cidr));
        }

        $this->network = clone($network);
        $this->netmask = $netmask;
    }

    /**
     * Construct an IPv4 Subnet from a CIDR notation (#.#.#.#/#)
     *
     * Maintained to avoid BC-break 
     *
     * @param Address the network base address  of the subnet
     * @param int $cidr the CIDR notation of the netmask
     * @throws OutOfBoundsException when $cidr is negatve or greater than 32
     * @throws RangeException when network address and netmask does not form a valid subnet 
     * @return Subnet
     */
    public static function fromCidr(Address $network, int $cidr)
    {
        return new self($network, $cidr);
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
    public static function fromAddresses(Address $network, Address $netmask)
    {
        return self::fromCidr($network, $netmask->asCidr());
    }

    /**
     * Return a subnet from an addresss and a CIDR form netmask, the addresscan be within the subnet, not only the network address
     *
     * @param Address $address an address within the desired subnet
     * @param int $cidr the CIDR notation of the netmask
     * @return Subnet
     */
    public static function fromContainedAddressCidr(Address $address, int $cidr)
    {
        $netmask = Address::fromCidr($cidr)->int();
        $networkAddress = Address::fromInteger($address->int() & $netmask);
        return new self($networkAddress, $cidr);
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
        return clone($this->network);
    }

    /**
     * Get the netmask address as an Address object 
     *
     * @return Address
     */
    public function getNetmaskAddress()
    {
        return  Address::fromInteger($this->netmask);
    }

    /**
     * Get the netmask address as an Address object 
     *
     * Maitained to avoid BC break
     *
     * @return Address
     */
    public function getNetmask()
    {
        return  $this->getNetmaskAddress();
    }

    /**
     * Get the broadcast address as an Address object 
     *
     * @return Address
     */
    public function getBroadcastAddress()
    {
        $netmask_inverted = (0xffffffff & ~$this->netmask);
        $intBroadcast =  ($this->network->int() | $netmask_inverted);
        return Address::fromInteger($intBroadcast);
    }

    /**
     * Test whether the given IPv4 address is in the subnet
     *
     * @param Address $ip 
     * @return bool
     */
    public function contains(Address $ip)
    {
        return (($this->network->int() & $this->netmask) == ($ip->int() & $this->netmask));
    }

    /**
     * Test if two subnet are the sames
     *
     * @param Subnet $subnet 
     * @return bool
     */
    public function match(Subnet $subnet)
    {
        return (
            $this->getNetworkAddress()->match($subnet->getNetworkAddress()) &&
            $this->getBroadcastAddress()->match($subnet->getBroadcastAddress())
        );
    }
    /**
     * Return the number of Addresses in the subnet (including boundaries)
     *
     * @return int
     */
    public function count()
    {
        return (~$this->netmask & 0xffffffff)+1; 
    }

    /**
     * Get the equivalent Range of a Subnet
     * 
     * @return Range
     */
    public function toRange()
    {
        return new Range($this->getNetworkAddress(), $this->getBroadcastAddress());
    }

    /**
     * Returns a subnet shifted by an amount of units (netmask)
     *
     * Need explain...
     *
     * @param int $offset
     * @throws OutOfBounsException when the resulting subet is invalid
     * @return Subnet
     */  

    public function shift(int $offset)
    {
        // On décale l'adresse de réseau en fonction de la taille du subnet
        $newNetwork = Address::fromInteger($this->getNetworkAddress()->int() + ($offset*count($this)));

        // On évite de manipuler directement les propriétés network d'un nouvek objet 
        // pour profiter de la validation en cas de dépassement des bornes.
        return Subnet::fromAddresses($newNetwork, $this->getNetmask());        
    }

    /**
     * Returns a subnet with the same netmask having network addres one more than broadcast address of the original subnet ($this)
     *
     * Need explain...
     *
     * @param int $offset
     * @throws OutOfBounsException when the resulting subet is invalid (when braodcast address of self = 255.255.255.255)
     * @return Subnet
     */  
    public function next()
    {
        return $this->shift(1);
    }

    /**
     * Returns a subnet with the same netmask having broacast addres one less than network address of the original subnet ($this)
     *
     * Need explain...
     *
     * @param int $offset
     * @throws OutOfBounsException when the resulting subet is invalid (when braodcast address of self = 255.255.255.255)
     * @return Subnet
     */  
    public function previous()
    {
        return $this->shift(-1);
    }


    /**
     * Get the IP version (IPv4 or IPv6) of this Rnage instance
     * 
     * @return int a constant IPv4 or IPv6
     */
    public function version()
    {
        return self::IPv4;
    }

    /*
     * interface ArrayAccess
     *
     */

    /**
     * Checks if there is an Address at the given index within the Range.
     *
     * @param int $offset address index
     * @throws InvalidArgumentException when $offset is not an integer
     * @return boolean
     */
    public function offsetExists($offset)
    {
        if (is_string($offset))
        {
            if (!preg_match('/^-?[0-9]+$/', $offset))
            {
                throw new InvalidArgumentException(sprintf('Invalid key type (%s), only integers or strings representing integers can be used to acces address in a Subnet', gettype($offset)));
            }
            $offset = (int)$offset;
        }
        if (!is_int($offset))
        {
            throw new InvalidArgumentException(sprintf('Invalid key type (%s), only integers or strings representing integers can be used to acces address in a Subnet', gettype($offset)));
        }
        if ($offset<0 || $offset>=count($this))
        {
            return false;
        }
        return true;
    }

    /**
     * Return the Address at the given index within the Subnet.
     *
     * NOTICE : a fresh instance of Address is returned each time the method is called
     *
     * @param int $offset address index
     * @throws InvalidArgumentException when $offset is not an integer
     * @throws OutOfBoundsException when $offset is negative or is greater than range size
     * @return Address a fresh instance that represents the address at $offset  
     */

    public function  offsetGet($offset)
    {
        if (!$this->offsetExists($offset))
        {
            throw new OutOfBoundsException();
        }

        return $this->network->shift($offset);
    }
    /**
     * Set the value of the address at a particular offset in a Subnet
     *
     * Unsupported : non-sense
     *
     * @param int $offset
     * @param Address $value
     * @throws BadMethodCallException always
     */ 

    public function offsetSet($offset,  $value)
    {
        throw new BadMethodCallException(sprintf("class %s is immutable, cannot modify an address value in a Subnet", self::class));
    }
    
    /**
     * Remove an address at a particular offset in a Subnet
     *
     * Unsupported : non-sense
     *
     * @param int $offset
     * @throws BadMethodCallException always
     */ 
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException(sprintf("class %s is immutable, cannot unset an address value in a Subnet", self::class));
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
