<h1 align="center">🔑 Stable Fingerprint</h1>

<p align="center">
  <strong>Deterministic SHA-256 fingerprinting and recursive canonical payload hashing for PHP 8.2+</strong>
</p>

<p align="center">
  <a href="#installation">Installation</a> •
  <a href="#basic-hashing">Basic Hashing</a> •
  <a href="#key-features">Key Features</a> •
  <a href="RELEASE-GATE.md">Release Gate</a> •
  <a href="CHANGELOG.md">Changelog</a>
</p>

<p align="center">
  <a href="RELEASE-GATE.md"><img src="https://img.shields.io/badge/Audit-Verified-10b981?logo=shield" alt="Audit Verified"></a>
  <a href="https://packagist.org/packages/alex-kassel/stable-fingerprint"><img src="https://img.shields.io/packagist/v/alex-kassel/stable-fingerprint?color=f59e0b&logo=packagist&logoColor=white" alt="Latest Version"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777bb4?logo=php&logoColor=white" alt="PHP Support"></a>
  <a href="RELEASE-GATE.md"><img src="https://img.shields.io/badge/PHPStan-Level%20Max-8b5cf6?logo=php&logoColor=white" alt="PHPStan Level Max"></a>
</p>

---

**Stable Fingerprint** generates small, deterministic SHA-256 fingerprints for pre-prepared PHP payloads.

It recursively sorts associative keys, preserves list order, canonicalizes finite float values, encodes compact JSON, and returns either a hexadecimal or raw binary SHA-256 digest. It deliberately avoids object normalization and business-specific data cleanup.

---

## Key Features

* **Deterministic Payload Hashing:** Generates consistent 64-character hex (`hash()`) or 32-byte binary (`binary()`) SHA-256 digests.
* **Recursive Key Sorting:** Normalizes associative arrays with byte-wise `SORT_STRING` key ordering regardless of initial key sequence.
* **List Order Preservation:** Preserves exact sequence for sequential indexed lists (`0..n-1`).
* **Finite Float Canonicalization:** Accurately normalizes finite floats while rejecting non-finite values (`INF`, `NAN`).
* **Strict Input Validation:** Rejects objects, enums, resources, invalid UTF-8 byte sequences, and cyclic references via `InvalidPayloadException`.
* **Static Convenience API:** Quick hashing with `StableFingerprint::compute($payload)`.

---

## Requirements

* **PHP:** 8.2+ (tested on 8.2, 8.3, 8.4, 8.5)
* **Standard PHP Extensions:** `ext-hash`, `ext-json`

---

## Installation

Install the package via Composer:

```bash
composer require alex-kassel/stable-fingerprint
```

---

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

$hex = StableFingerprint::compute(['vendor' => 'audi', 'model' => 'q4']);
```

---

## Testing

From the monorepo root, run the complete package verification pipeline:

```bash
composer pkg:check alex-kassel/stable-fingerprint --json
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

---

## Security Vulnerabilities

Please review the [security policy](https://github.com/alex-kassel/stable-fingerprint/security/policy) to report vulnerabilities.

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
