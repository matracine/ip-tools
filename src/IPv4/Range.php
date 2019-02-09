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
use mracine\IPTools\IPVersion;

/**
 * Represents a range of IPv4 addresses
 *
 * A range of IPv4 address are consecutives addresses
 *
 * A range implements :
 *  * Countable : number of addresses
 *  * ArrayAccess : Address can be acceesed by offset (ex : $range[10])
 *  * IteratorAggregate :   range can be itrated (foreach)
 * 
 * @package IPTools
 */

class Range implements IPVersion, \Countable, \ArrayAccess, \IteratorAggregate
{

    /**
     * @var Address $baseAddress The first address of the range
     */
    protected $baseAddress;

    /**
     * @var Address upperBound The last address of the range
     */
    // private $upperBound;
    /**
     * @var int $count the address count in the range
     */
    protected $count;

    use IPv4;

    /**
     * Creates an IP range from a couple of IP addresses
     *
     * Lower and upper bound are automaticly choosen, no need to choose wich one to pas first.
     *
     * @param Address $baseAddress the first address of the range
     * @param int $count the number  other bound
     */
    public function __construct(Address $lowerBound, Address $upperBound)
    {
        $lower = $lowerBound->int();
        $upper = $upperBound->int();
        if ($lower > $upper)
        {
            $temp = $lower;
            $lower = $upper;
            $upper = $temp;
        }
        $this->baseAddress = new Address($lower);
        $this->count = $upper - $lower + 1;
    } 

    /**
     * Returns an Range object from a base Address and a count of Addresses in the range 
     *
     * Negative count values rearange the lower bound Address
     * @param Address $baseAddress the lower bound
     * @param int $count the number of addresses in the Range
     * @return self
     */
    public static function fromCount(Address $baseAddress, int $count)
    {
        if ($count == 0)
        {
            throw new InvalidArgumentException("Cannot assign a range of 0 addresses");
        }
        if ($count<0)
        {
            $delta = $count+1;
        }
        else
        {
            $delta = $count-1;
        }
        $upperBound = $baseAddress->shift($delta);
        return new static($baseAddress, $upperBound);
    }


    /**
     * Get the first address of the range
     *
     * NOTICE : The returned address is a newly created address instance.
     * 
     * @return Address
     */
    public function getLowerBound()
    {
        return clone($this->baseAddress);
    }

    /**
     * Returns the last address of the range
     *
     * NOTICE : The returned address is a newly created address instance.   
     *
     * @return Address
     */
    public function getUpperBound()
    {
        return new Address($this->getLowerBound()->int()+$this->count-1);
    }

    /**
     * Tells if an address is contained in the range
     *
     * Return true if the provided address is between the boundaries of the range.  
     *
     * @deprecated Use Address::isIn
     * @see Address::isIn
     * @param Address $ip
     * @return bool
     */
    public function contains(Address $ip)
    {
        return $ip->isIn($this);
    }

    /**
     * Tells if the range is contained in an other range
     *
     * Return true if the provided address is between the boundaries of the range.  
     *
     * @param Address $ip 
     * @return bool
     */
    public function isIn(Range $container)
    {
        return ($container->getLowerBound()->int()<=$this->getLowerBound()->int()) && ($this->getUpperBound()->int()<=$container->getUpperBound()->int());
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
        return $this->count; 
    }

    /**
     * Returns a range shifted by an amount of units (count)
     *
     * Need explain...
     *
     * @param int $offset
     * @throws OutOfBounsException when the resulting subet is invalid
     * @return Subnet
     */  
    public function shift(int $offset)
    {
        return static::fromCount($this->getLowerBound()->shift($offset*count($this)), count($this));
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

    /*
     * interface ArrayAccess
     */

    /**
     * Checks if there is an Address at the given index within the Range.
     *
     * @param int|string $offset address index
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

        return $this->getLowerBound()->shift($offset);
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
}
