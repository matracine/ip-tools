<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools\Iterators;

use mracine\IPTools\IPv4\Address;
use mracine\IPTools\IPv4\Subnet;

/**
 * Class Subnet represents an IP address with a netmask to specify a subnet
 *
 * @package IPTools\IPv4
 */
class SubnetIterator implements  \Iterator
{
    protected $subnet;
    protected $position;

    public function __construct(Subnet $subnet)
    {
        $this->subnet = $subnet;
        $this->position = 0;
    }

    public function current()
    {
        return clone($this->subnet[$this->position]);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        try
        {
            $this->subnet[$this->position];       
        }
        catch(\Exception $e)
        {
            return false;
        }
        return true;
    }
}


