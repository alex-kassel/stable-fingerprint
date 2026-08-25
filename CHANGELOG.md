# Changelog

All notable changes to `alex-kassel/stable-fingerprint` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.1] - 2026-08-25

### Changed
- Formatted `README.md` to strictly adhere to canonical structure and 7-color badge palette standards.

## [2.2.0] - 2026-08-25

### Added
- Compatibility with PHP 8.2 (`^8.2 || ^8.3 || ^8.4`).
- Expanded CI test matrix to cover PHP 8.2, 8.3, and 8.4.

## [2.1.0] - 2026-08-25

### Added
- Finite float (`is_finite()`) canonicalization support within payloads.
- Automated distribution packaging isolation via `.gitattributes` `export-ignore`.
- Formal Release Gate audit certification under Laravel Package Audit framework.

### Changed
- Refined docblock type hints for `mixed $payload` in `StableFingerprintInterface` and implementation.

## [2.0.0] - 2026-08-25

### Changed
- **Breaking**: Simplified architecture from strict RFC 8785 JCS implementation to lightweight, deterministic PHP payload fingerprinting.
- Public interface updated to provide `hash(mixed $payload): string`, `binary(mixed $payload): string`, and static helper `StableFingerprint::compute(mixed $payload): string`.
- Recursive byte-wise key sorting (`SORT_STRING`) for associative arrays and list order preservation (`array_is_list()`).

## [1.0.0] - 2026-08-25

### Added
- Initial release with RFC 8785 JSON Canonicalization Scheme (JCS) support.
