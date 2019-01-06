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
use BadMethodCallException;

use mracine\IPTools\Iterators\RangeIterator;
use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IP;
/**
 * Represents a range of IPv4 addresses
 *
 * A range of IPv4 address are consecutives addresses limited by a upper & a lower address (boundaries)
 *
 * A range implements :
 *  * Countable : number of addresses
 *  * ArrayAccess : Address can be acceesed by offset (ex : $range[10])
 *  * IteratorAggregate :   range can be itrated (foreach)
 * 
 * @package IPTools
 */
class Range implements IP, \Countable, \ArrayAccess, \IteratorAggregate
{

    /**
     * @var Address $lowerBound The first address of the range
     */
    private $lowerBound;

    /**
     * @var Address upperBound The last address of the range
     */
    private $upperBound;

    /**
     * Creates an IP range from a couple of IP addresses
     *
     * Lower and upper bound are automaticly choosen, no need to choose wich one to pas first.
     *
     * @param Address $ip1 a bound
     * @param Address $ip2 other bound
     */
    public function __construct(Address $ip1, Address $ip2)
    {
        if ($ip1->int() < $ip2->int())
        {
            $this->lowerBound = clone($ip1);
            $this->upperBound = clone($ip2);
        }
        else
        {
            $this->lowerBound = clone($ip2);
            $this->upperBound = clone($ip1);
        }
    }

    /**
     * Get the lower bound adress of the range
     *
     * NOTICE : The returned address is a newly created address instance.
     * 
     * @return Address
     */
    public function getLowerBound()
    {
        return clone($this->lowerBound);
    }

    /**
     * Returns the first address of the range
     *
     * NOTICE : The returned address is a newly created address instance.   
     *
     * @return Address
     */
    public function getUpperBound()
    {
        return clone($this->upperBound);
    }

    /**
     * Tells if an address is contained in the range
     *
     * Return true if the provided address is between the boundaries of the range.  
     *
     * @param Address $ip 
     * @return bool
     */
    public function contains(Address $ip)
    {
        $ipVal = $ip->int();

        return ($ipVal>=$this->lowerBound->int()) && ($ipVal<=$this->upperBound->int() );
    }


    /**
     * Tells if two ranges are equals (sames boundaries)
     *
     * Return true if the provided range is the same as this one.  
     *
     * @param Range $range 
     * @return bool
     */
    public function match(Range $range)
    {
        return ( ($this->getLowerBound()->int()==$range->getLowerBound()->int()) && ($this->getUpperBound()->int()==$range->getUpperBound()->int()));
    }

    /**
     * Return the number of Addresses in the range (including boundaries)
     *
     * @return int
     */
    public function count()
    {
        return $this->upperBound->int() - $this->lowerBound->int() + 1; 
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
                throw new InvalidArgumentException(sprintf('Invalid key type (%s), only integers or strings representing integers can be used to acces address in a Range', gettype($offset)));
            }           
            $offset = (int)$offset;
        }
       if (!is_int($offset))
        {
            throw new InvalidArgumentException(sprintf('Invalid key type (%s), only integers or strings representing integers can be used to acces address in a Range', gettype($offset)));
        }
        if ($offset<0 || $offset>=count($this))
        {
            return false;
        }
        return true;
    }

    /**
     * Return the Address at the given index within the Range.
     *
     * NOTICE : a fresh instance of Address is returned each time the method is called
     *
     * @param int $offset address index
     * @throws InvalidArgumentException when $offset is not an integer
     * @throws OutOfBoundsException when $offset is negative or is greater than range size
     * @return Address a fresh instance that represents the address at $offset  
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset))
        {
            throw new OutOfBoundsException();
        }

        return $this->lowerBound->shift($offset);
    }

    /**
     * Set the value of the address at a particular offset in a Range
     *
     * Unsupported : non-sense
     *
     * @param int $offset
     * @param Address $value
     * @throws BadMethodCallException always
     */ 
    public function offsetSet($offset,  $value)
    {
        throw new BadMethodCallException(sprintf("class %s is immutable, cannot modify an address value in a Range", self::class));
    }
    
    /**
     * Remove an address at a particular offset in a range
     *
     * Unsupported : non-sense
     *
     * @param int $offset
     * @throws BadMethodCallException always
     */ 
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException(sprintf("class %s is immutable, cannot unset an address value in a Range", self::class));
    }

    /*
     * interface IteratorAggregate
     */

    /**
     * Obtain an iterator to traverse the range
     *
     * Allows to iterate in the range (foreach ($subnet as $address) {...} )
     *
     * @return RangeIterator
     */

    public function getIterator()
    {
        return new RangeIterator($this);
    }
    // public function isSubnet()
    // {
    //     try
    //     {
    //         $this->toSubnet();
    //     }
    //     catch (\Exception $e)
    //     {
    //         return false;
    //     }
    //     return true;
    // }

    // public function toSubnet()
    // {
    //     $l = $this->getLowerBound()->int();
    //     $u = $this->getUpperBound()->int();

    //     $netmask = 0xffffffff;
    //     while($netmask!=0)
    //     {
    //         // Déterminer le netmask pour la partie commune des 2 IPs
    //         if (($u & $netmask) == ($l & $netmask))
    //         {
    //             // La bprne basse ne doit avoir que des 0 dan
    //             if ( ($l & (~$netmask))    
    //              echo "\n***\ntoSubnet : ".$this->getLowerBound()." / ". Address::fromInteger($netmask)."\n" ;
    //             return Subnet::fromAddresses($this->getLowerBound(), Address::fromInteger($netmask));
    //         }
    //         $netmask = ($netmask << 1) & 0xffffffff;
    //     }
    //     throw new \Exception("Error Processing Request", 1);
    // }

}
