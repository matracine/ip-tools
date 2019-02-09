# IPTOOLS 

PHP Library for manipulating network addresses.

This library implements tools for IP network address manipulations. It actualy impelments IPv4 only classes, but IPv6 will come.  It has no dependencies others than PHP 7.0.

Classes :
 - IPv4\Address
 - IPv4\Network
 - IPv4\Range
 - IPv4\Subnet 

Please feal free to open issue for improvements, bugs, requests... IPv6 implementation is on the way.

Docblock documentation implemented.

## QA
Service | Result
--- | ---
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

```

#### Retreive value
```php
// Get a string with dot quad notation
$value = (string)$address; 
$value = $address->asDotQuad(); 
// returns "1.2.3.4"

// Get the integer representation of address
$value = $address->asInteger();
$value = $address->int();

```

#### Operations
```php
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
// Test if two addresses are equivalent (same value)
if ($address->match($address2))
{...}

// Test if an address is in Range or Subnet
if($address->isIn($range))
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
if($address->isMulticast())
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
 * Add string formater

### IPv4\Netmask
Class for IPv4 Netmask manipulation.

A Netmask is an IP Address (Netmask extends Address) with only special values allowed (255.255.255.0, 255.0.0.0 etc.). In fact only 33 values are allowed... 
All Address methods are usable with Netmask, even if certains are meaningless (isRFC1918, getClass...).

#### Namespace :
```php
use mracine\IPTools\IPv4\Netmask;
```

#### Create an IPv4 Netmask :
Constructor use integer
```php
$netmask = new Netmask(0xffffff00); // 255.255.255.0

// trying to constrct a netmask with wrong value will throw an exception (\DomainException)
$netmask = new Netmask(0x12345678); // throws \DomainException

```

Helpers

Netmask extends Address, so Address helpers are availables. Check are done, Exceptions are thrown when values cannot be used to create valid Netmask. 
```php
// Construct a Netmask from a CIDR notation
$netmask = Netmask::fromCidr(24); // '255.255.255.0'
$netmask = Netmask::fromCidr(-1); // throws \OutOfBoundsException
$netmask = Netmask::fromCidr(33); // throws \OutOfBoundsException

// Construct a Netmask from an Address
$netmask = Netmask::fromAddress(new Adress::fromString('255.255.255.0')); // 255.255.255.0

// In fact, Netmask extends Address, so you can use Address Helpers
$netmask = Netmask::fromString('255.255.255.0'); // 255.255.255.0
$netmask = Netmask::fromString('255.0.255.0'); // throws \DomainException, invalid netmask...

```

#### Retreive value
Use Address methods plus

```php
// Get the CIDR representation of Netmask (integer between 0 to 32
$cidr = $netmask->asCidr(); 

// Count the number of addresses in a netmask
// Imple!ents Countable
$count = count(Netmask::fromString('255.255.255.0')); // 256

$count = Netmask::fromString('255.255.255.0')->count(); // 256

```
#### Operations
Address methods are modified to correspond to the Netmask logic. When shifting a Netmask, we increment or decrement CIDR vaue by the offset.

```php
// get a Netmask from another shifted by un offset (negateive or positive)
$netmask2 = $netmask->shift(1);  // 255.255.255.0 => 255.255.255.128
$netmask2 = $netmask->shift(-1); // 255.255.255.0 => 255.255.254.0

// get next netmask
$netmask2 = $netmask->next();  // 255.255.255.0 => 255.255.255.128

// get previous netmask
$netmask = $netmask->previous();  // // 255.255.255.0 => 255.255.254.0

```
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

Helpers

```php
$range = Range::fromCount(new Address('192.168.1.5'), 10); // From a lower bound, 10 addresses : 192.168.1.5-192.168.1.14
$range = Range::fromCount(new Address('192.168.1.5'), -5); // From a uppen bound, 10 addresses : 192.168.0.252-192.168.1.5

```

#### Retreive values

```php
// Retreive the first Address
$address = $range->getUpperBound();

// Retreive the last Address
$address = $range->getLowerBound();

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

```

#### Operations
```php

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

#### Qualify
```php
// true if 2 ranges are equivalent (same pper and lower bounds)
if($range->match($range2))
{...}

// Check if an Address is contained in a range
if($range->contains($address))
{...}

// Check if a range is contained in an other range
if($range->isIn($range2))
{...}

// Get count of addresses in a range
$addressCount = count($range); // Interface Countable
$addressCount = $range->count();

```

### IPv4\Subnet
Class for IPv4 Subnets manipulation.

A Subnet is a range (Subnet extends Range) of IP Addresses, determined by a network address and a netmask.
Class is immutable : once created cennot be modified. Opérations on an existing instance will return a new subnet instance or a fresh Address instance.

#### Namespace :
```php
use mracine\IPTools\IPv4\Subnet;
```

#### Create an IPv4 Subnet :
Constructor use an Address (network address) and Netmask. 

```php
$subnet = new Subnet(new Address('192.168.1.0'), new Netmask('255.255.255.250'));

```
By default, strict network/netmask validity checking is enabled. You can specify third optional parameter (strict) to false to enable intelligent network address calculation, and provide an address within the subnet. 
```php
$subnet = new Subnet(new Address('192.168.1.50'), new Netmask('255.255.255.250'), false);  // network address will be calculated to 192.168.1.0

```

Helpers
```php
// Creates a subnet from CIDR notation
$subnet = Subnet::fromCidr(new Address('192.168.1.0'), 24);

// Creates a subnet from a string formated with CIDR or address/netmask notation
$subnet = Subnet::fromString('192.168.1.0/24');
$subnet = Subnet::fromString('192.168.1.0/255.255.255.0');

```

#### Retreive values
Range methods plus :

```php
// Retreive the network as an Address object 
$address = $ubnet->getNetworkAddress(); // 192.168.1.0

// Retreive the netmask as a Netmask object
$netmask = $ubnet->getNetmaskAddress(); // 255.25.255.0

// Retreive the broadcast address as an Address object
$address = $ubnet->getBroadcastAddress(); // 192.168.1.255
```

#### Operations
Same as Range methods... 
