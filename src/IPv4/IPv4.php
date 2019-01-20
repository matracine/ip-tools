<?php
/**
 * Trait IPv4 implements version 4
 *
 * @package IPTools
 */
namespace mracine\IPTools\IPV4;

use mracine\IPTools\IPVersion;;

trait IPv4
{
    public function version()
    {
        return IPVersion::IPv4; 
    }

    public static function minAddress()
    {
        return new Address(0);
    }

    public static function maxAddress()
    {
        return new Address(0xffffffff);
    }
}