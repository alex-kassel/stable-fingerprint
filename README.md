# StableFingerprint

[![PHP Version Require](https://img.shields.io/badge/php-%5E8.3%20%7C%7C%20%5E8.4-8892BF.svg)](https://packagist.org/packages/alex-kassel/stable-fingerprint)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

**Deterministic SHA-256 canonicalization and payload hashing for PHP 8.3+.**

`StableFingerprint` provides a compact, deterministic PHP canonicalization and hashing algorithm designed for small, pre-prepared data payloads. It validates payload grammar, normalizes key order for associative arrays, formats compact JSON, and outputs SHA-256 hex or raw binary digests.

---

## Grammar & Features

### Supported Payload Grammar
- `null`
- `bool` (`true`, `false`)
- `int`
- Valid UTF-8 `string`
- Sequential list arrays with integer keys `0..n-1`
- Associative arrays with string keys ONLY
- Recursive combinations of the above types

### Deterministic Canonicalization Specs
- **Associative Key Sorting**: Associative array keys are recursively sorted using bytewise `SORT_STRING`.
- **List Order Integrity**: Indexed list order is preserved (`0..n-1`).
- **No Type Coercion or Normalization**: Scalar types (`1`, `"1"`, `true`) hash distinctly. Strings are hashed exactly as passed without Unicode or business normalization.
- **Fixed SHA-256 Output**: Generates compact JSON (`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR`) and hashes via SHA-256.

### Rejected / Forbidden Types (`InvalidPayloadException`)
- `float`
- `object` (including DTOs, Enums, `DateTimeInterface`, `JsonSerializable`, `Traversable`, `Closure`)
- `resource`
- Mixed or non-sequential integer array keys
- Invalid UTF-8 strings

---

## Requirements

* **PHP**: `^8.3 || ^8.4`
* **PHP Extensions**: `ext-json`, `ext-hash`

---

## Installation

Install the package via Composer:

```bash
composer require alex-kassel/stable-fingerprint
```

---

## Public API

```php
namespace AlexKassel\StableFingerprint\Contracts;

use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;

interface StableFingerprintInterface
{
    /**
     * @param null|bool|int|string|array<mixed> $payload
     * @return lowercase-string 64-character SHA-256 hex string.
     * @throws InvalidPayloadException
     */
    public function hash(mixed $payload): string;

    /**
     * @param null|bool|int|string|array<mixed> $payload
     * @return non-empty-string Exact 32 raw SHA-256 bytes.
     * @throws InvalidPayloadException
     */
    public function binary(mixed $payload): string;
}
```

---

## Quick Start & Usage Examples

### 1. Generating Hashes

```php
use AlexKassel\StableFingerprint\StableFingerprint;

$fingerprint = new StableFingerprint();

$payload = [
    'user_id' => 1042,
    'tags' => ['admin', 'active'],
    'profile' => [
        'email' => 'alex@example.com',
        'verified' => true,
    ],
];

// Returns 64-character lowercase SHA-256 hex string
$hexHash = $fingerprint->hash($payload);
// e.g. "6a7b..."

// Returns raw 32 SHA-256 bytes
$binaryHash = $fingerprint->binary($payload);
assert($hexHash === bin2hex($binaryHash));
```

### 2. Key Order Invariance & List Preservation

```php
// Associative array keys order does not affect the generated hash:
$payload1 = ['b' => 2, 'a' => 1, 'nested' => ['z' => 10, 'y' => 5]];
$payload2 = ['a' => 1, 'b' => 2, 'nested' => ['y' => 5, 'z' => 10]];

assert($fingerprint->hash($payload1) === $fingerprint->hash($payload2));

// Sequential list array order is preserved:
$list1 = ['apple', 'banana'];
$list2 = ['banana', 'apple'];

assert($fingerprint->hash($list1) !== $fingerprint->hash($list2));
```

### 3. Handling Invalid Inputs

Any unsupported type (e.g. `float`, objects, non-UTF-8 strings, invalid array keys) throws `InvalidPayloadException`:

```php
use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;

try {
    $fingerprint->hash(['amount' => 99.99]); // Floats are prohibited
} catch (InvalidPayloadException $e) {
    echo $e->getMessage(); // "Float values are not allowed in payload."
}
```

---

## Testing & Quality Checks

Run the test suite and validate composer dependencies:

```bash
composer validate --strict
composer test
```

---

## License

This package is open-source software licensed under the [MIT License](LICENSE).
