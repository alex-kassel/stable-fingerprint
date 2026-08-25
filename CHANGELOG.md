# Changelog

All notable changes to `alex-kassel/stable-fingerprint` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-25

### Added
- Deterministic SHA-256 canonicalization and hashing via `StableFingerprint` and `StableFingerprintInterface`.
- Support for hex hashing via `hash()` and raw 32-byte binary hashing via `binary()`.
- Static convenience helper `StableFingerprint::compute()`.
- Recursive byte-wise key sorting (`SORT_STRING`) for associative arrays.
- Preservation of list ordering (`array_is_list()`).
- Support for null, booleans, integers, finite floats, and valid UTF-8 strings.
- Strict input validation rejecting non-finite floats, objects, enums, resources, invalid UTF-8, and recursion cycles via `InvalidPayloadException`.
- Automated test harness with PHPUnit 12 and full PHP 8.3/8.4 test matrix.
