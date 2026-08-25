# Stable Fingerprint

[![Audited by Laravel Package Audit](https://img.shields.io/badge/Audited%20by-Laravel%20Package%20Audit-10b981?style=for-the-badge&logo=shield)](RELEASE-GATE.md)
[![Latest Version](https://img.shields.io/packagist/v/alex-kassel/stable-fingerprint?style=for-the-badge&logo=packagist&logoColor=white&color=f59e0b)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-8b5cf6?style=for-the-badge&logo=php&logoColor=white)](RELEASE-GATE.md)
[![PHP](https://img.shields.io/packagist/dependency-v/alex-kassel/stable-fingerprint/php?style=for-the-badge&logo=php&logoColor=white&color=777bb4)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![Downloads](https://img.shields.io/packagist/dt/alex-kassel/stable-fingerprint?style=for-the-badge&logo=packagist&logoColor=white&color=0284c7)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![License](https://img.shields.io/github/license/alex-kassel/stable-fingerprint?style=for-the-badge&color=0d9488)](LICENSE)

Small, deterministic SHA-256 fingerprints for pre-prepared PHP payloads.

StableFingerprint recursively sorts associative keys, preserves list order, canonicalizes finite float values, encodes compact JSON, and returns either a hexadecimal or raw binary SHA-256 digest. It deliberately avoids object normalization and business-specific data cleanup.

## Features

- **Deterministic Payload Hashing**: Generates consistent 64-character hex (`hash()`) or 32-byte binary (`binary()`) SHA-256 digests.
- **Recursive Key Sorting**: Normalizes associative arrays with byte-wise `SORT_STRING` key ordering regardless of initial key sequence.
- **List Order Preservation**: Preserves exact sequence for sequential indexed lists (`0..n-1`).
- **Finite Float Canonicalization**: Accurately normalizes finite floats while rejecting non-finite values (`INF`, `NAN`).
- **Strict Input Validation**: Rejects objects, enums, resources, invalid UTF-8 byte sequences, and cyclic references via `InvalidPayloadException`.
- **Static Convenience API**: Quick hashing with `StableFingerprint::compute($payload)`.

## Requirements

- PHP 8.2, 8.3, or 8.4
- Standard PHP extensions: `ext-hash` and `ext-json`

## Installation

```bash
composer require alex-kassel/stable-fingerprint
```

## Usage

### Basic Hashing

```php
use AlexKassel\StableFingerprint\StableFingerprint;

$fingerprint = new StableFingerprint();

$payload = [
    'price' => 19.90,
    'available' => true,
    'variants' => [
        ['size' => 'M', 'stock' => 4],
        ['size' => 'L', 'stock' => 2],
    ],
];

$hex = $fingerprint->hash($payload);      // 64 lowercase hexadecimal characters
$binary = $fingerprint->binary($payload); // 32 raw bytes

assert($hex === bin2hex($binary));
```

### Static Convenience Helper

```php
use AlexKassel\StableFingerprint\StableFingerprint;

$hex = StableFingerprint::compute($payload);
```

### Associative Key Normalization

Associative key order does not affect the resulting hash:

```php
$fingerprint->hash(['b' => 2, 'a' => 1])
    === $fingerprint->hash(['a' => 1, 'b' => 2]);
```

### List Ordering

List element order remains strictly significant:

```php
$fingerprint->hash(['first', 'second'])
    !== $fingerprint->hash(['second', 'first']);
```

### Supported Values and Limitations

- `null`, booleans, integers, finite floats (`is_finite()`), and valid UTF-8 strings.
- Sequential lists with integer keys `0..n-1`.
- Associative arrays with string keys.
- Nested combinations of supported types.

Non-finite floats (`INF`, `NAN`), objects, enums, resources, invalid UTF-8, and mixed or non-sequential array keys throw `InvalidPayloadException`.

The package does not remove volatile fields or normalize business values. Prepare the payload before hashing so the fingerprint represents exactly the changes relevant to your application.

## Testing

```bash
composer test
# or
vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
