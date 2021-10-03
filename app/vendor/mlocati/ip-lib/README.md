[![Tests](https://github.com/mlocati/ip-lib/actions/workflows/tests.yml/badge.svg)](https://github.com/mlocati/ip-lib/actions?query=workflow%3A%22tests%22)
[![Coverage Status](https://coveralls.io/repos/github/mlocati/ip-lib/badge.svg?branch=master)](https://coveralls.io/github/mlocati/ip-lib?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mlocati/ip-lib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mlocati/ip-lib/?branch=master)
![Packagist Downloads](https://img.shields.io/packagist/dm/mlocati/ip-lib)

# IPLib - Handle IPv4, IPv6 and IP ranges

## Introduction

IPLib is a modern, PSR-compliant, test-driven IP addresses and subnets manipulation library. It implements primitives to handle IPv4 and IPv6 addresses, as well as IP ranges (subnets), in CIDR format (like `::1/128` or `127.0.0.1/32`) and in pattern format (like `::*:*` or `127.0.*.*`).

## Requirements

IPLib has very basic requirements as:

- Works with any PHP version greater than 5.3.3 (PHP **5.3.x**, **5.4.x**, **5.5.x**, **5.6.x**, **7.x**, and **8.x** are fully supported).
- **No external dependencies**
- **No special PHP configuration needed** (yes, it will __always work__ even if PHP has not been built with IPv6 support!).

## Installation

### Manual installation

[Download](https://github.com/mlocati/ip-lib/releases) the latest version, unzip it and add these lines in our PHP files:

```php
require_once 'path/to/iplib/ip-lib.php';
```

### Installation with Composer

Simply run 

```sh
composer require mlocati/ip-lib
```

or add these lines to your `composer.json` file:

```json
"require": {
    "mlocati/ip-lib": "^1"
}
```

## Sample usage

### Parse an address

To parse an IPv4 address:

```php
$address = \IPLib\Address\IPv4::parseString('127.0.0.1');
```

To parse an IPv6 address:

```php
$address = \IPLib\Address\IPv6::parseString('::1');
```

To parse an address in any format (IPv4 or IPv6):

```php
$address = \IPLib\Factory::parseAddressString('::1');
$address = \IPLib\Factory::parseAddressString('127.0.0.1');
```

### Get the next/previous addresses

```php
$address = \IPLib\Factory::parseAddressString('::1');

// This will print ::
echo (string) $address->getPreviousAddress();

// This will print ::2
echo (string) $address->getNextAddress();
```

### Get the addresses at a specified offset

For addresses:

```php
$address = \IPLib\Factory::parseAddressString('::1');

// This will print ::1
echo (string) $address->getAddressAtOffset(0);

// This will print ::2
echo (string) $address->getAddressAtOffset(1);

// This will print ::3
echo (string) $address->getAddressAtOffset(2);

// This will print ::3e9
echo (string) $address->getAddressAtOffset(1000);

// This will print ::
echo (string) $address->getAddressAtOffset(-1);

// This will print NULL
echo var_dump($address->getAddressAtOffset(-2));
```

For ranges:

```php
$range = \IPLib\Factory::parseRangeString('::ff00/120');

// This will print ::ff00
echo (string) $range->getAddressAtOffset(0);

// This will print ::ff10
echo (string) $range->getAddressAtOffset(16);

// This will print ::ff64
echo (string) $range->getAddressAtOffset(100);

// This will print NULL because the address ::1:0 is out of the range
var_dump($range->getAddressAtOffset(256));

// This will print ::ffff
echo (string) $range->getAddressAtOffset(-1);

// This will print ::fff0
echo (string) $range->getAddressAtOffset(-16);

// This will print ::ff00
echo (string) $range->getAddressAtOffset(-256);

// This will print NULL because the address ::feff is out of the range
var_dump($range->getAddressAtOffset(-257));
```

### Parse an IP address range

To parse a subnet (CIDR) range:

```php
$range = \IPLib\Range\Subnet::parseString('127.0.0.1/24');
$range = \IPLib\Range\Subnet::parseString('::1/128');
```

To parse a pattern (asterisk notation) range:

```php
$range = \IPLib\Range\Pattern::parseString('127.0.0.*');
$range = \IPLib\Range\Pattern::parseString('::*');
```

To parse an andress as a range:

```php
$range = \IPLib\Range\Single::parseString('127.0.0.1');
$range = \IPLib\Range\Single::parseString('::1');
```

To parse a range in any format:

```php
$range = \IPLib\Factory::parseRangeString('127.0.0.*');
$range = \IPLib\Factory::parseRangeString('::1/128');
$range = \IPLib\Factory::parseRangeString('::');
```

### Retrieve a range from its boundaries

You can calculate the smallest range that comprises two addresses:

```php
$range = \IPLib\Factory::getRangeFromBoundaries('192.168.0.1', '192.168.255.255');

// This will print 192.168.0.0/16
echo (string) $range;
```

You can also calculate a list of ranges that exactly describes all the addresses between two addresses:

```php
$ranges = \IPLib\Factory::getRangesFromBoundaries('192.168.0.0', '192.168.0.5');

// This will print 192.168.0.0/30 192.168.0.4/31
echo implode(' ', $ranges);
```

### Retrieve the boundaries of a range

```php
$range = \IPLib\Factory::parseRangeString('127.0.0.*');

// This will print 127.0.0.0
echo (string) $range->getStartAddress();

// This will print 127.0.0.255
echo (string) $range->getEndAddress();
```

### Format addresses and ranges

Both IP addresses and ranges have a `toString` method that you can use to retrieve a textual representation:

```php
// This will print 127.0.0.1
echo \IPLib\Factory::parseAddressString('127.0.0.1')->toString();

// This will print 127.0.0.1
echo \IPLib\Factory::parseAddressString('127.000.000.001')->toString();

// This will print ::1
echo \IPLib\Factory::parseAddressString('::1')->toString();

// This will print ::1
echo \IPLib\Factory::parseAddressString('0:0::1')->toString();

// This will print ::1/64
echo \IPLib\Factory::parseRangeString('0:0::1/64')->toString();
```

When working with IPv6, you may want the full (expanded) representation of the addresses. In this case, simply use a `true` parameter for the `toString` method:

```php
// This will print 0000:0000:0000:0000:0000:0000:0000:0000
echo \IPLib\Factory::parseAddressString('::')->toString(true);

// This will print 0000:0000:0000:0000:0000:0000:0000:0001
echo \IPLib\Factory::parseAddressString('::1')->toString(true);

// This will print 0fff:0000:0000:0000:0000:0000:0000:0000
echo \IPLib\Factory::parseAddressString('fff::')->toString(true);

// This will print 0000:0000:0000:0000:0000:0000:0000:0000
echo \IPLib\Factory::parseAddressString('::0:0')->toString(true);

// This will print 0001:0002:0003:0004:0005:0006:0007:0008
echo \IPLib\Factory::parseAddressString('1:2:3:4:5:6:7:8')->toString(true);

// This will print 0000:0000:0000:0000:0000:0000:0000:0001/64
echo \IPLib\Factory::parseRangeString('0:0::1/64')->toString();
```

The address and range objects implements the `__toString()` method, which call the `toString()` method.
So, if you want the string (short) representation of an object, you can do any of the following:

```php
$address = \IPLib\Address\IPv6::parseString('::1');

// All these will print ::1
echo $address->toString();
echo $address->toString(false);
echo (string) $address;
```

### Check if an address is contained in a range

All the range types offer a `contains` method, and all the IP address types offer a `matches` method: you can call them to check if an address is contained in a range:

```php
$address = \IPLib\Factory::parseAddressString('1:2:3:4:5:6:7:8');
$range = \IPLib\Factory::parseRangeString('0:0::1/64');

$contained = $address->matches($range);
// that's equivalent to
$contained = $range->contains($address);
```

Please remark that if the address is IPv4 and the range is IPv6 (or vice-versa), the result will always be `false`.

### Check if a range contains another range

All the range types offer a `containsRange` method: you can call them to check if an address range fully contains another range:

```php
$range1 = \IPLib\Factory::parseRangeString('0:0::1/64');
$range2 = \IPLib\Factory::parseRangeString('0:0::1/65');

$contained = $range1->containsRange($range2);
```

### Getting the type of an IP address

If you want to know if an address is within a private network, or if it's a public IP, or whatever you want, you can use the `getRangeType` method:

```php
$address = \IPLib\Factory::parseAddressString('::');

$type = $address->getRangeType();

$typeName = \IPLib\Range\Type::getName($type);
```

The most notable values of the range type are:

- `\IPLib\Range\Type::T_UNSPECIFIED` if the address is all zeros (`0.0.0.0` or `::`)
- `\IPLib\Range\Type::T_LOOPBACK` if the address is the localhost (usually `127.0.0.1` or `::1`)
- `\IPLib\Range\Type::T_PRIVATENETWORK` if the address is in the local network (for instance `192.168.0.1` or `fc00::1`)
- `\IPLib\Range\Type::T_PUBLIC` if the address is for public usage (for instance `104.25.25.33` or `2001:503:ba3e::2:30`)

### Getting the type of an IP address range

If you want to know the type of an address range, you can use the `getRangeType` method:

```php
$range = \IPLib\Factory::parseRangeString('2000:0::1/64');

// $type will contain the value of \IPLib\Range\Type::T_PUBLIC
$type = $range->getRangeType();

// This will print Public address
echo \IPLib\Range\Type::getName($type);
```

Please note that if a range spans across multiple range types, you'll get NULL as the range type:

```php
$range = \IPLib\Factory::parseRangeString('::/127');

// $type will contain null
$type = $range->getRangeType();

// This will print Unknown type
echo \IPLib\Range\Type::getName($type);
```

### Converting IP addresses

This library supports converting IPv4 to/from IPv6 addresses using the [6to4 notation](https://tools.ietf.org/html/rfc3056) or the [IPv4-mapped notation](https://tools.ietf.org/html/rfc4291#section-2.5.5.2):

```php
$ipv4 = \IPLib\Factory::parseAddressString('1.2.3.4');

// 6to4 notation
$ipv6 = $ipv4->toIPv6();

// This will print 2002:102:304::
echo (string) $ipv6;

// This will print 1.2.3.4
echo $ipv6->toIPv4();

// IPv4-mapped notation
$ipv6 = $ipv4->toIPv6IPv4Mapped();

// This will print ::ffff:1.2.3.4
echo (string) $ipv6;

// This will print 1.2.3.4
echo $ipv6_6to4->toIPv4();
```

### Converting IP ranges

This library supports IPv4/IPv6 ranges in pattern format (eg. `192.168.*.*`) and in CIDR/subnet format (eg. `192.168.0.0/16`), and it offers a way to convert between the two formats:

```php
// This will print ::*:*:*:*
echo \IPLib\Factory::parseRangeString('::/64')->asPattern()->toString();

// This will print 1:2::/96
echo \IPLib\Factory::parseRangeString('1:2::*:*')->asSubnet()->toString();

// This will print 192.168.0.0/24
echo \IPLib\Factory::parseRangeString('192.168.0.*')->asSubnet()->toString();

// This will print 10.*.*.*
echo \IPLib\Factory::parseRangeString('10.0.0.0/8')->asPattern()->toString();
```

Please remark that all the range types implement the `asPattern()` and `asSubnet()` methods.

### Getting the subnet mask for IPv4 ranges

You can use the `getSubnetMask()` to get the subnet mask for IPv4 ranges:

```php
// This will print 255.255.255.0
echo \IPLib\Factory::parseRangeString('192.168.0.*')->getSubnetMask()->toString();

// This will print 255.255.255.252
echo \IPLib\Factory::parseRangeString('192.168.0.12/30')->getSubnetMask()->toString();
```

### Getting the range size

You can use the `getSize()` to get the count of addresses this IP range contains:

```php
// This will print 256
echo \IPLib\Factory::parseRangeString('192.168.0.*')->getSize();

// This will print 4
echo \IPLib\Factory::parseRangeString('192.168.0.12/30')->getSize();

// This will print 1
echo \IPLib\Factory::parseRangeString('192.168.0.1')->getSize();
```

### Getting the reverse DNS lookup address

To perform reverse DNS queries, you need to use a special format of the IP addresses.

You can use the `getReverseDNSLookupName()` method of the IP address instances to retrieve it easily:

```php
$ipv4 = \IPLib\Factory::parseAddressString('1.2.3.255');
$ipv6 = \IPLib\Factory::parseAddressString('1234:abcd::cafe:babe');

// This will print 255.3.2.1.in-addr.arpa
echo $ipv4->getReverseDNSLookupName();

// This will print e.b.a.b.e.f.a.c.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.d.c.b.a.4.3.2.1.ip6.arpa
echo $ipv6->getReverseDNSLookupName();
```

You can also use `getReverseDNSLookupName()` for IP ranges.
In this case, the result is an array of strings:

```php
$range = \IPLib\Factory::parseRangeString('10.155.16.0/22');

/*
 * This will print:
 * array (
 *   0 => '16.155.10.in-addr.arpa',
 *   1 => '17.155.10.in-addr.arpa',
 *   2 => '18.155.10.in-addr.arpa',
 *   3 => '19.155.10.in-addr.arpa',
 * )
*/
var_export($range->getReverseDNSLookupName());
```

### Using a database

This package offers a great feature: you can store address ranges in a database table, and check if an address is contained in one of the saved ranges with a simple query.

To save a range, you need to store the address type (for IPv4 it's `4`, for IPv6 it's `6`), as well as two values representing the start and the end of the range.
These methods are:
```php
$range->getAddressType();
$range->getComparableStartString();
$range->getComparableEndString();
```

Let's assume that you saved the type in a field called `addressType`, and the range boundaries in two fields called `rangeFrom` and `rangeTo`.

When you want to check if an address is within a stored range, simply use the `getComparableString` method of the address and check if it's between the fields `rangeFrom` and `rangeTo`, and check if the stored `addressType` is the same as the one of the address instance you want to check.

Here's a sample code:

```php
/*
 * Let's assume that:
 * - $pdo is a PDO instance
 * - $range is a range object
 * - $address is an address object
 */

// Save the $range object
$insertQuery = $pdo->prepare('
    insert into ranges (addressType, rangeFrom, rangeTo)
    values (:addressType, :rangeFrom, :rangeTo)
');

$insertQuery->execute(array(
    ':addressType' => $range->getAddressType(),
    ':rangeFrom' => $range->getComparableStartString(),
    ':rangeTo' => $range->getComparableEndString(),
));

// Retrieve the saved ranges where an address $address falls:
$searchQuery = $pdo->prepare('
    select * from ranges
    where addressType = :addressType
    and :address between rangeFrom and rangeTo
');

$searchQuery->execute(array(
    ':addressType' => $address->getAddressType(),
    ':address' => $address->getComparableString(),
));

$rows = $searchQuery->fetchAll();
$searchQuery->closeCursor();
```

## Handling non-standard address and range strings

### Accepting ports

If you want to accept addresses that may include ports, you can specify the `IPLib\ParseStringFlag::MAY_INCLUDE_PORT` flag:

```php
use IPLib\Factory;
use IPLib\ParseStringFlag;

require_once __DIR__ . '/../ip-lib.php';

// These will print NULL
var_export(Factory::parseAddressString('127.0.0.1:80'));
var_export(Factory::parseAddressString('[::]:80'));

// This will print 127.0.0.1
echo (string) Factory::parseAddressString('127.0.0.1:80', ParseStringFlag::MAY_INCLUDE_PORT);
// This will print ::
echo (string) Factory::parseAddressString('[::]:80', ParseStringFlag::MAY_INCLUDE_PORT);
```

### Accepting IPv6 zone IDs

If you want to accept IPv6 addresses that may include a zone ID, you can specify the `IPLib\ParseStringFlag::MAY_INCLUDE_ZONEID` flag:

```php
use IPLib\Factory;
use IPLib\ParseStringFlag;

// This will print NULL
var_export(Factory::parseAddressString('::%11'));

// This will print ::
echo (string) Factory::parseAddressString('::%11', ParseStringFlag::MAY_INCLUDE_ZONEID);
```

### Accepting non-decimal IPv4 addresses

IPv4 addresses are usually expressed in decimal notation, for example as `192.168.0.1`.

By the way, the GNU (used in many Linux distros), BSD (used in Mac) and Windows implementations of `inet_aton` and `inet_addr` accept IPv4 addresses with numbers in octal and/or hexadecimal format.
Please remark that this does not apply to the `inet_pton` and `ip2long` functions, as well as to the Musl implementation (used in Alpine Linux) of `inet_aton` and `inet_addr`.

So, for example, these addresses are all equivalent to `192.168.0.1`:

- `0xC0.0xA8.0x0.0x01` (only hexadecimal)
- `0300.0250.00.01` (only octal)
- `192.0250.0.0x01` (decimal, octal and hexadecimal numbers)

(try it: if you browse to [`http://0177.0.0.0x1`](http://0177.0.0.0x1), your browser will try to browse `http://127.0.0.1`).

If you want to accept this non-decimal syntax, you may use the `IPLib\ParseStringFlag::IPV4_MAYBE_NON_DECIMAL` flag:

```php
use IPLib\Factory;
use IPLib\ParseStringFlag;

// This will print NULL
var_export(Factory::parseAddressString('0177.0.0.0x1'));

// This will print 127.0.0.1
var_export((string) Factory::parseAddressString('0177.0.0.0x1', ParseStringFlag::IPV4_MAYBE_NON_DECIMAL));

// This will print NULL
var_export(Factory::parseRangeString('0177.0.0.0x1/32'));

// This will print 127.0.0.1/32
var_export((string) Factory::parseRangeString('0177.0.0.0x1/32', ParseStringFlag::IPV4_MAYBE_NON_DECIMAL));
```

Please be aware that the `IPV4_MAYBE_NON_DECIMAL` flag may also affect parsing decimal numbers:

```php
use IPLib\Factory;
use IPLib\ParseStringFlag;

// This will print 127.0.0.10 since the last digit is assumed to be decimal
var_export((string) Factory::parseAddressString('127.0.0.010'));

// This will print 127.0.0.8 since the last digit is assumed to be octal
var_export((string) Factory::parseAddressString('127.0.0.010', ParseStringFlag::IPV4_MAYBE_NON_DECIMAL));
```

### Accepting IPv4 addresses in nod-quad-dotted notation

IPv4 addresses are usually expressed with 4 numbers, for example as `192.168.0.1`.

By the way, the GNU (used in many Linux distros), BSD (used in Mac) and Windows implementations of `inet_aton` and `inet_addr` [accept IPv4 addresses with 1 to 4 numbers](https://man7.org/linux/man-pages/man3/inet_addr.3.html#DESCRIPTION).

Please remark that this does not apply to the `inet_pton` and `ip2long` functions, as well as to the Musl implementation (used in Alpine Linux) of `inet_aton` and `inet_addr`.

If you want to accept this non-decimal syntax, you may use the `IPLib\ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED` flag:

```php
use IPLib\Factory;
use IPLib\ParseStringFlag;

// This will print NULL
var_export(Factory::parseAddressString('1.2.500'));

// This will print 0.0.0.0
var_export((string) Factory::parseAddressString('0', ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED));

// This will print 0.0.0.1
var_export((string) Factory::parseAddressString('1', ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED));

// This will print 0.0.1.244
var_export((string) Factory::parseAddressString('0.0.500', ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED));

// This will print 255.255.255.255
var_export((string) Factory::parseAddressString('4294967295', ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED));
```

### Accepting compact IPv4 subnet notation

Even if there isn't an RFC that describe it, IPv4 subnet notation may also be written in a compact form, omitting extra digits (for example, `127.0.0.0/24` may be written as `127/24`).
If you want to accept such format, you can specify the `IPLib\ParseStringFlag::IPV4SUBNET_MAYBE_COMPACT` flag:

```php
use IPLib\Factory;
use IPLib\ParseStringFlag;

// This will print NULL
var_export(Factory::parseRangeString('127/24'));

// This will print 127.0.0.0/24
echo (string) Factory::parseRangeString('127/24', ParseStringFlag::IPV4SUBNET_MAYBE_COMPACT);
```

### Combining multiple flags

Of course, you may use more than one `IPLib\ParseStringFlag` flag at once:

```php
use IPLib\Factory;
use IPLib\ParseStringFlag;

// This will print 127.0.0.255
var_export((string) Factory::parseAddressString('127.0.0.0xff:80', ParseStringFlag::MAY_INCLUDE_PORT | ParseStringFlag::IPV4_MAYBE_NON_DECIMAL));

// This will print ::
var_export((string) Factory::parseAddressString('[::%11]:80', ParseStringFlag::MAY_INCLUDE_PORT | ParseStringFlag::MAY_INCLUDE_ZONEID));
```

## Do you really want to say thank you?

You can offer me a [monthly coffee](https://github.com/sponsors/mlocati) or a [one-time coffee](https://paypal.me/mlocati) :wink:
