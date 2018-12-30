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

use mracine\IPTools\Iterators\SubnetIterator;
use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IP;
/**
 * Class Subnet represents an IPv4 IP address with a netmask to specify a subnet
 *
 * @package IPTools\IPv4
 */
class Subnet implements IP, \Countable, \ArrayAccess, \IteratorAggregate
{
    const FORMAT_STRING   = Address::FORMAT_STRING;
    const FORMAT_INTEGER  = Address::FORMAT_INTEGER;
    const FORMAT_CIDR     = 100;

    /**
     * @var Address
     */ 
    protected $network;

    /**
     * @var int 
     * /0 => 255.255.255.255 => 0xffffffff )
     * /24 => 255.255.255.0 => 0xffffff00
     */
    protected $netmask;

    /**
     * Construct an IPv4 Subnet from an IP address and a netmask
     *
     */
    protected function __construct()
    {
    }

    /**
     * Construct an IPv4 Subnet from a CIDR notation (#.#.#.#/#)
     *
     * @param Address
     * @param int $cidr
     * @return Subnet
     */
    public static function fromCidr(Address $network, int $cidr)
    {
        $netmask = Address::fromCidr($cidr)->int();

        $address = $network->int();
        if( ($address & $netmask) != $address)
        {
            throw new RangeException(sprintf("Invalid network adress %s, this address is not usable with the CIDR %d", (string)$network, $cidr));
        }

        $subnet = new self();
        $subnet->network = clone($network);
        $subnet->netmask = $netmask;
        return $subnet;
    }

    public static function fromAddresses(Address $network, Address $netmask)
    {
        return self::fromCidr($network, $netmask->asCidr());
    }

    /*
     * Construit un subnet à partir d'une addresse contenue dans celui-ci
      */
    public static function fromContainedAddressCidr(Address $address, int $cidr)
    {
        $netmask = Address::fromCidr($cidr)->int();

        $networkAddress = ($address->int() & $netmask);
        $subnet = new self();
        $subnet->network = Address::FromInteger($networkAddress);
        $subnet->netmask = $netmask;
        return $subnet;
    }

    public function getNetworkAddress()
    {
        return clone($this->network);
    }

    public function getNetmask()
    {
        return  Address::fromInteger($this->netmask);
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

    public function getBroadcastAddress()
    {
        $netmask_inverted = (0xffffffff & ~$this->netmask);
        $intBroadcast =  ($this->network->int() | $netmask_inverted);
        return Address::fromInteger($intBroadcast);
    }

    public function match(Subnet $subnet)
    {
        return (
            $this->getNetworkAddress()->match($subnet->getNetworkAddress()) &&
            $this->getBroadcastAddress()->match($subnet->getBroadcastAddress())
        );
    }

    public function count()
    {
        return (~$this->netmask & 0xffffffff)+1; 
    }

    public function toRange()
    {
        return new Range($this->getNetworkAddress(), $this->getBroadcastAddress());
    }

    public function next()
    {
        return $this->shift(1);
    }

    public function previous()
    {
        return $this->shift(-1);
    }

    public function shift(int $offset)
    {
        // On décale l'adresse de réseau en fonction de la taille du subnet
        $newNetwork = Address::fromInteger($this->getNetworkAddress()->int() + ($offset*count($this)));

        // On évite de manipuler directement les propriétés network d'un nouvek objet 
        // pour profiter de la validation en cas de dépassement des bornes.
        return Subnet::fromAddresses($newNetwork, $this->getNetmask());        
    }

    /**
     * Interface IP
     */
    public function version()
    {
        return self::IPv4;
    }

    /**
     * interface ArrayAccess
     *
     */

    public function offsetExists($offset)
    {
        if (!is_integer($offset))
        {
            throw new \TypeError(sprintf('Invalid key type %s, only integers can be used', gettype($offset)));
        }
        if ($offset<0 || $offset>=count($this))
        {
            return false;
        }
        return true;
    }

    public function  offsetGet($offset)
    {
        if (!$this->offsetExists($offset))
        {
            throw new OutOfBoundsException();
        }

        return $this->network->shift($offset);
    }

    public function offsetSet($offset,  $value)
    {
        throw new \BadMethodCallException(sprintf("class %s is immutable, cannot modify the value at offset %d", self::class, $offset));
    }
    
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException("Error Processing Request", 1);
    }

    /**
     * interface IteratorAggregate
     *
     */

    public function getIterator()
    {
        return new SubnetIterator($this);
    }
}
