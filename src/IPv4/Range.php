<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools\IPv4;

use OutOfBoundsException;

use mracine\IPTools\Iterators\RangeIterator;
use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IP;
/**
 * Represents a range of IPv4 addresses
 *
 * A range of IPv4 address are consecutives addresses 
 * 
 * @package IPAddress\IPv4
 */
class Range implements IP, \Countable, \ArrayAccess, \IteratorAggregate
{

    /**
     * @var Address
     */
    private $lowerBound;

    /**
     * @var Address
     */
    private $upperBound;

    /**
     * Create an IP range from a couple of IP addresses
     *
     * @param Address $ip1
     * @param Address $ip2
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
     * Get the lower part of the range
     *
     * @return Address
     */
    public function getLowerBound()
    {
        return clone($this->lowerBound);
    }

    /**
     * Get the upper part of the range
     *
     * @return Address
     */
    public function getUpperBound()
    {
        return clone($this->upperBound);
    }

    public function contains(Address $ip)
    {
        $ipVal = $ip->int();

        return ($ipVal>=$this->lowerBound->int()) && ($ipVal<=$this->upperBound->int() );
    }

    public function match(Range $range)
    {
        return ( ($this->getLowerBound()->int()==$range->getLowerBound()->int()) && ($this->getUpperBound()->int()==$range->getUpperBound()->int()));
    }

    public function count()
    {
        return $this->upperBound->int() - $this->lowerBound->int() + 1; 
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
        $this->validOffset($offset);
        if ($offset<0 || $offset>=count($this))
        {
            return false;
        }
        return true;
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset))
        {
            throw new OutOfBoundsException();
        }

        return $this->lowerBound->shift($offset);
    }

    public function offsetSet($offset,  $value)
    {
        throw new \BadMethodCallException(sprintf("class %s is immutable, cannot modify the value at offset %d", self::class, $offset));
    }
    
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException("Error Processing Request", 1);
    }

    protected function validOffset($offset)
    {
        if (!is_integer($offset))
        {
            throw new \TypeError(sprintf('Invalid key type (%s), only integers can be used to acces address in a range', gettype($offset)));
        }        
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

    /**
     * interface IteratorAggregate
     *
     */

    public function getIterator()
    {
        return new RangeIterator($this);
    }
}
