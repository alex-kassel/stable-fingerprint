# StableFingerprint

[![Latest Version](https://img.shields.io/packagist/v/alex-kassel/stable-fingerprint?style=for-the-badge&logo=packagist&logoColor=white&color=orange)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![Tests](https://img.shields.io/github/actions/workflow/status/alex-kassel/stable-fingerprint/tests.yml?branch=main&style=for-the-badge&logo=github&logoColor=white&label=tests&color=brightgreen)](https://github.com/alex-kassel/stable-fingerprint/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/alex-kassel/stable-fingerprint/php?style=for-the-badge&logo=php&logoColor=white&color=777bb4)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![Downloads](https://img.shields.io/packagist/dt/alex-kassel/stable-fingerprint?style=for-the-badge&logo=packagist&logoColor=white&color=blue)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![License](https://img.shields.io/packagist/l/alex-kassel/stable-fingerprint?style=for-the-badge&color=success)](LICENSE)

Small, deterministic SHA-256 fingerprints for pre-prepared PHP payloads.

StableFingerprint recursively sorts associative keys, preserves list order, encodes compact JSON and returns either a hexadecimal or raw binary SHA-256 digest. It deliberately avoids object normalization, floating-point policy and business-specific data cleanup.

## Installation

```bash
composer require alex-kassel/stable-fingerprint
```

Requires PHP 8.3+ with the standard JSON and Hash extensions.

## Usage

```php
use AlexKassel\StableFingerprint\StableFingerprint;

$fingerprint = new StableFingerprint();

$payload = [
    'price' => '19.90',
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

Associative key order does not affect the result:

```php
$fingerprint->hash(['b' => 2, 'a' => 1])
    === $fingerprint->hash(['a' => 1, 'b' => 2]);
```

List order remains significant:

```php
$fingerprint->hash(['first', 'second'])
    !== $fingerprint->hash(['second', 'first']);
```

## Supported values

- `null`, booleans, integers and valid UTF-8 strings;
- lists with sequential integer keys `0..n-1`;
- associative arrays with string keys;
- recursive combinations of those values.

Floats, objects, enums, resources, invalid UTF-8 and mixed or non-sequential array keys throw `InvalidPayloadException`.

The package does not remove volatile fields or normalize business values. Prepare the payload before hashing so the fingerprint represents exactly the changes relevant to your application.

## Deterministic format

1. Validate the supported PHP value grammar.
2. Sort associative keys recursively using bytewise `SORT_STRING`.
3. Preserve list order.
4. Encode compact JSON with `JSON_UNESCAPED_UNICODE`, `JSON_UNESCAPED_SLASHES` and `JSON_THROW_ON_ERROR`.
5. Hash the resulting bytes with SHA-256.

This is a deliberately small PHP canonicalization contract, not an implementation of RFC 8785 JCS.

## Development

```bash
composer validate --strict
composer test
```

## License

StableFingerprint is open-source software licensed under the [MIT License](LICENSE).
