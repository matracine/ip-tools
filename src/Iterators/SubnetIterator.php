<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools\Iterators;

use mracine\IPTools\IPv4\Subnet;

/**
 * Subnet implementation of Iterator
 *
 * @package IPTools
 */
class SubnetIterator implements  \Iterator
{
    /**
     * @var Subnet $subnet local reference to the subnet to iterate
     */
    protected $subnet;

    /**
     * @var int $index internal pointer in the subnet
     */
    protected $index;


    /**
     * Créates the iterator
     *
     * Initialize internal pointer to the first position of the Subnet
     *
     * @param Subnet $subnet
     */
    public function __construct(Subnet $subnet)
    {
        $this->subnet = $subnet;
        $this->rewind();
    }

    /**
     * Return an Address representing the current element of the Subnet 
     *
     * Use the ArrayAccess interface of the subnet. 
     *
     * @return \mracine\IPTools\IPv4\Address
     */
    public function current()
    {
        return $this->subnet[$this->index];
    }

    /**
     * Return the key of the current element 
     *
     * @return int
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * Move the internal pointer to the next element 
     *
     * @return void
     */
    public function next()
    {
        $this->index++;
    }

    /**
     * Move the internal pointer to the first element 
     *
     * @return void
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * Tells if the internal pointer designs a valid element 
     *
     * @return bool
     */
    public function valid()
    {
        try
        {
            $this->subnet[$this->index];       
        }
        catch(\Exception $e)
        {
            return false;
        }
        return true;
    }
}


