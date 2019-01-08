# IPTOOLS 

PHP Library for manipulating network addresses.

This library implements tools for IP network address manipulations. It actualy impelments IPv4 only classes, but IPv6 will come.  It has no requirement others than PHP 7.0.

Classes :
 - IPv4\Address
 - IPv4\Subnet 
 - IPv4\Range

## QA
Service | Result
**Travis CI** (PHP 7.0 + 7.1 + 7.2) | [![Build Status](https://travis-ci.org/matracine/ip-tools.svg?branch=master)](https://travis-ci.org/matracine/ip-tools)
**Scrutinizer score** | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/matracine/ip-tools/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/matracine/ip-tools/?branch=master)
**Code coverage** | [![Code Coverage](https://scrutinizer-ci.com/g/matracine/ip-tools/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/matracine/ip-tools/?branch=master)

## Installation
With composer:
```
composer require mracine/ip-tools
```

## Usage

### IPv4\Address
Class for IPv4 Address manipulation.

An IP address is no more than a 32 bits integer. To be more human readable, the dot quad string  notation is commonly used (ex : "123.235.123.213").\
Class is immutable : once created cennot be modified. Opérations on an existing instance will return a new instance.

#### Namespace :
```php
use mracine\IPTools\IPv4\Address;
```

#### Create an IPv4 Address :
Constructor use integer
```php
$address = new Address(0xff00ff00); // 255.0.255.0 
```

Helpers
```php
$address = Address::fromString('10.11.12.13');   // From a dot quad string notation

$address = Address::fromArray([10, 11, 12, 13]); // From an Array of integers 
$address = Address::fromArray(['10', '11', '12', '13']); // From an Array  of strings

$address = Address::fromCidr(24); // From CIDR notation : 255.255.255.0
```

#### Retreive value
```php
// Get a string with dot quad notation
$value = $address->asDotQuad(); 
$value = (string)$address; 

// Get the integer representation of address
$value = $address->asInteger();
$value = $address->int();

// Get the CIDR value if possible else a DomainException is thrown
$value = $address->asCidr();
```

#### Operations
```php
// true if 2 addresses are equivalent (same value)
$address->match($address2);

// get an addresse from another shifted by un offset (negateive or positive)
$address2 = $address->shift(10);  // 10.0.0.0 => 10.0.0.10
$address2 = $address->shift(-10); // 10.0.0.0 => 9.255.255.246

// get next address
$address2 = $address->next();  // 10.0.0.0 => 10.0.0.1


// get previous address
$address2 = $address->previous();  // 10.0.0.0 => 9.255.255.255

```

#### Qualify
```php
// Address can be used as netmask ?
// A netmask is an addresse with uppers bits sets to 1 and lower bots sets to 0
if($address->isNetmask())
{...}

// Address is part of RFC 1918 subnets ?
// see : https://tools.ietf.org/html/rfc1918
if($address->isRFC1918())
{...}

// Address is part of RFC 6598 subnet ?
// see : https://tools.ietf.org/html/rfc6598
if($address->isRFC6598())
{...}

// Address is multicast ?
// see : https://tools.ietf.org/html/rfc1112
if($address->isRFCMulticast())
{...}

// get the CLASS of an address
// Obsolete notion for class routing
switch($addres->getClass())
{
    case Address::CLASS_A:
        ...
        break;
    case Address::CLASS_B:
        ...
        break;
    case Address::CLASS_C:
        ...²
        break;
    case Address::CLASS_D:
        ...
        break;
    case Address::CLASS_E:
        ...
        break;
}
```

#### TODO
A lot...

### IPv4\Subnet
Class for IPv4 Subnets manipulation.

A Subnet is a range of IP Addresses, determined by a network address and a netmask.
Class is immutable : once created cennot be modified. Opérations on an existing instance will return a new subnet instance or a fresh Address instance.

#### Namespace :
```php
use mracine\IPTools\IPv4\Subnet;
```

#### Create an IPv4 Subnet :
Constructor use an Address (network) and a CIDR notation for netmask. 

```php
$subnet = new Subnet(new Address('192.168.1.0'), 24);  // 192.168.1.0/24 same as 192.168.1.0/255.255.255.0
```

Helpers
```php
$subnet = Subnet::fromCidr(new Address('192.168.1.0'), 24);   // Same as constructor

$subnet = Address::fromAddresses(new Address('192.168.1.0'), new Address('255.255.255.0')); // From two addresses network andnetmask 

// You can also provide an address with the range of subnet, network address will be guessed
$subnet = Address::fromContainedAddressCidr(new Address('192.168.1.10'), 24); // 192.168.1.0/24 
```

#### Retreive values

```php
// Retreive the network Address
$address = $ubnet->getNetworkAddress();

// Retreive the netmask as an Address
$address = $ubnet->getNetmaskAddress();

// Retreive the broadcast Address
$address = $ubnet->getBroadcastAddress();
```

#### Operations
```php
// Check if an Address is contained in a subnet
if($subnet->contains($address))
{...}

// true if 2 subnets are equivalent (same network, same netmask)
if($subnet->match($subnet2)=
{...}

// Get count of contained addresses
$addressCount = $subnet->count();
$addressCount = count($subnet); // Interface Countable

// Implements ArrayAccess
$subnet = Subnet::fromCidr(new Address('192.168.1.0'), 24);   // Same as constructor
$address = $subnet[0]; // 192.168.1.0
$address = $subnet[10]; // 192.168.1.10

// Trying to access an address out of the subnet will raise an exception
$address = $subnet[-1]; // exception
// Read only : Trying to affect or unset an item will raise an exception
$subnet[1] = $address; // exception
unset($subnet[1]); // exception

// get a subnet from another shifted by an offset (negateive or positive), keeping the same netmask
$subnet2 = $subnet1->shift(2);  // 192.168.1.0/24 => 192.168.3.0/24
$subnet2 = $address->shift(-1); // 192.168.1.0/30 => 192.168.0.252/30

// get next adjacent subnet, same netmask
$subnet2 = $subnet->next();  // 192.168.1.0/24 => 192.168.2.0/24


// get previous adjacent subnet
$subnet2 = $subnet->previous();  // 192.168.1.0/24 => 192.168.0.0/24

// Iterate all the addresses of the subnet (netmask and broadcast)
foreach($subnet as $address)
{...}
```

#### TODO
 - Add qualifiers (RFC 1918...)
 - Change class archtecture (a Subnet is a Range)
 - Many others


### IPv4\Range
Class for IPv4 Ranges manipulation.

A Range of IP Addresses is consecutives IP addresses, determined by a lower and a upper boundaries.
Class is immutable : once created cannot be modified. Opérations on an existing instance will return a new range instance or a fresh Address instance.

#### Namespace :
```php
use mracine\IPTools\IPv4\Range;
```

#### Create an IPv4 Range :
Constructor use an lower bound Address and a upper bound Address.
Arrange bounds, no need to passer lower bound first

```php
$range = new Range(new Address('192.168.1.5'), new Address('192.168.1.34'));
$range = new Range(new Address('192.168.1.34'), new Address('192.168.1.5'));
```

#### Retreive values

```php
// Retreive the first Address
$address = $range->getUpperBound();

// Retreive the last Address
$address = $range->getLowerBound();

```

#### Operations
```php
// Check if an Address is contained in a range
if($range->contains($address))
{...}

// true if 2 ranges are equivalent (same pper and lower bounds)
if($range->match($range2)=
{...}

// Get count of contained addresses
$addressCount = $range->count();
$addressCount = count($range); // Interface Countable

// Implements ArrayAccess
$range = new Range(new Address('192.168.1.5'), new Address('192.168.1.10'));   // Same as constructor
$address = $range[0]; // 192.168.1.5
$address = $range[2]; // 192.168.1.7

// Trying to access an address out of the range will raise an exception
$address = $range[-1]; // exception
$address = $range[50]; // exception
// Read only : Trying to affect or unset an item will raise an exception
$range[1] = $address; // exception
unset($range[1]); // exception

// get a range from another shifted by an offset (negateive or positive), keeping the same address count
$range2 = $range1->shift(2);  // 192.168.1.10-192.168.1.15 => 192.168.1.27-192.168.1.32
$range2 = $range1->shift(-1); // 192.168.1.10-192.168.1.15 => 192.168.1.9-192.168.1.4

// get next adjacent range, with same address count 
$range2 = $range->next();  // 192.168.1.0-192.168.1.9 => 192.168.1.10-192.168.1.19


// get previous adjacent range, with same address count
$range2 = $range->previous();  // 192.168.1.0-192.168.1.9 => 192.168.0.246-192.168.0.255

// Iterate all the addresses of the range
foreach($subnet as $address)
{...}
```
#### TODO
 - Add qualifiers (RFC 1918...)
 - Change class archtecture (a Subnet is a Range)
 - Many others
