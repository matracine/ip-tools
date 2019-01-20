<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools;
/**
 * Interface for all classes of the IPTool package
 */
interface IPVersion
{
    /**
     * Version 4
     */
    const IPv4 = 4;
    /**
     * Version 6
     */
    const IPv6 = 6;

    /**
     * Get the IP version (IPv4 or IPv6) of this Rnage instance
     * 
     * @return int a constant IPv4 or IPv6
     */
    public function version();

    /**
     * Get the minimal IP Address
     * 
     * @return IPVersion the minimal Address
     */
    public static function minAddress();

    /**
     * Get the maximal IP Address
     * 
     * @return IPVersion the maximal Address
     */
    public static function maxAddress();
}