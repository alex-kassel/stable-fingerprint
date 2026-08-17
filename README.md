# StableFingerprint

[![PHP Version Require](https://img.shields.io/badge/php-%5E8.4-8892BF.svg)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

**Deterministic canonicalization and payload hashing for PHP 8.4+ adhering strictly to RFC 8785 JSON Canonicalization Scheme (JCS).**

---

## Overview

In modern PHP applications, generating deterministic hashes for data structures (DTOs, API request payloads, database entities, webhooks) is often unreliable. Default `json_encode()` outputs vary across environments due to:
* Unsorted associative array keys.
* Differing float serialization precision settings (`serialize_precision`).
* Non-standardized `DateTimeInterface` timezones and formats.
* Inconsistent Unicode normalization forms (NFC vs NFD).
* Presence of transient dynamic fields like timestamps, nonces, or request IDs.

`StableFingerprint` solves this by normalizing any PHP payload (arrays, objects, Enums, dates, scalar values) into a canonical structure following **RFC 8785 JCS** principles before producing a stable cryptographic digest.

---

## Key Features

* **RFC 8785 JCS Compliance**: Strict float serialization precision handling (`serialize_precision = -1`) and key ordering.
* **Deterministic Array Key Sorting**: Alphabetical recursive `ksort` for associative arrays while preserving indexed list sequence integrity (`array_is_list`).
* **UTC DateTime Normalization**: Automatic conversion of any `DateTimeInterface` instance to standardized UTC ISO-8601 strings (`Y-m-d\TH:i:s.u\Z`).
* **PHP 8 Enums & Interfaces Support**: Built-in support for `BackedEnum`, `UnitEnum`, `JsonSerializable`, `Traversable`, and standard Data Transfer Objects (DTOs).
* **Wildcard Segment Path Exclusion**: Dot-notation wildcard pattern matching (e.g. `meta.timestamp`, `*.nonce`, `user.id`) to ignore volatile attributes during hashing.
* **Circular Reference Guard**: Memory safety via `SplObjectStorage` preventing infinite loops on nested object graphs.
* **Unicode Canonicalization**: Normalization of UTF-8 strings into Unicode Form C (NFC) via `ext-intl`.
* **Flexible Algorithm Choice**: Support for any PHP native hashing algorithm (`md5`, `sha256`, `xxh128`, `sha3-512`, etc.).

---

## Requirements

* **PHP**: `^8.4`
* **PHP Extensions**: `ext-json`, `ext-hash`, `ext-intl`

---

## Installation

Install the package via Composer:

```bash
composer require alex-kassel/stable-fingerprint
```

---

## Quick Start

```php
use AlexKassel\StableFingerprint\StableFingerprint;

$fingerprint = new StableFingerprint();

// 1. Basic deterministic payload hashing
$payload = [
    'b' => 2,
    'a' => 1,
    'user' => [
        'email' => 'user@example.com',
        'role' => 'admin',
    ],
];

// Returns deterministic MD5 hash (default algorithm)
$hash = $fingerprint->hash($payload);
```

Regardless of key order, float precision, or PHP environment, the generated hash remains identical.

---

## Advanced Usage

### Custom Hashing Algorithms

You can specify any algorithm supported by `hash_algos()` (e.g. `sha256`, `xxh128`, `sha3-256`):

```php
$sha256Hash = $fingerprint->hash($payload, algo: 'sha256');
$xxhHash = $fingerprint->hash($payload, algo: 'xxh128');
```

### Excluding Volatile & Dynamic Fields

To hash payloads while ignoring transient properties (such as timestamps, request IDs, or nonces), pass dot-notation exclusion patterns as the second argument:

```php
$payload = [
    'order_id' => 1042,
    'amount' => 99.99,
    'meta' => [
        'created_at' => new DateTimeImmutable(),
        'nonce' => 'abc-123-xyz',
    ],
    'history' => [
        ['timestamp' => 1700000000, 'status' => 'pending'],
        ['timestamp' => 1700000500, 'status' => 'completed'],
    ],
];

// Exclude exact paths or wildcard sub-keys
$hash = $fingerprint->hash($payload, excludePaths: [
    'meta.created_at',
    '*.nonce',
    'history.*.timestamp',
]);
```

### Object, Enum & DateTime Handling

`StableFingerprint` handles complex PHP structures out of the box:

```php
enum UserStatus: string {
    case Active = 'active';
}

class OrderDTO {
    public function __construct(
        public int $id,
        public UserStatus $status,
        public DateTimeImmutable $createdAt,
    ) {}
}

$dto = new OrderDTO(
    id: 42,
    status: UserStatus::Active,
    createdAt: new DateTimeImmutable('2026-08-17 20:00:00', new DateTimeZone('Europe/Berlin'))
);

// DateTime objects are automatically converted to UTC ISO-8601 strings
// Enums are resolved to their backing scalar values or names
$hash = $fingerprint->hash($dto);
```

---

## Testing

Run the PHPUnit test suite:

```bash
composer test
```

Or execute PHPUnit directly:

```bash
vendor/bin/phpunit
```

---

## License

This package is open-source software licensed under the [MIT License](LICENSE).

Created and maintained by [Alexander Macenko](https://github.com/alex-kassel).
