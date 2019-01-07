<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools\Iterators;

use mracine\IPTools\IPv4\Range;

/**
 * Range implementation of Iterator
 *
 * @package IPTools
 */
class RangeIterator implements \Iterator
{
    /**
     * @var Range $range local reference to the range to iterate
     */
    protected $range;

    /**
     * @var int $index internal pointer in the range
     */
    protected $index;

    /**
     * Créates the iterator
     *
     * Initialize internal pointer to the first position of the Range
     *
     * @param Range $range
     */
    public function __construct(Range $range)
    {
        $this->range = $range;
        $this->rewind();
    }

    /**
     * Return an Address representing the current element of the Range 
     *
     * Use the ArrayAccess interface of the Range. 
     *  
     * @return \mraccine\IPTools\IPv4\Address
     */
    public function current()
    {
        return $this->range[$this->index];
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
            $this->range[$this->index];       
        }
        catch(\Exception $e)
        {
            return false;
        }
        return true;
    }
}


