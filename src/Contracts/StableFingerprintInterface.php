<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint\Contracts;

use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;
use AlexKassel\StableFingerprint\Exceptions\UnsupportedAlgorithmException;

interface StableFingerprintInterface
{
    /**
     * Calculate a deterministic canonical hash from the input payload.
     *
     * @param mixed $payload Structured array, object, DTO, or scalar value.
     * @param array<int, string> $excludePaths Dot-notation paths to strip, supporting wildcards (e.g. ['meta.timestamp', 'items.*.created_at']).
     * @param string $algo Any valid PHP hashing algorithm (default 'md5').
     * @return string Hexadecimal hash string.
     *
     * @throws InvalidPayloadException If payload contains non-serializable elements (resource, Closure, NAN, INF, circular graph).
     * @throws UnsupportedAlgorithmException If $algo is not registered in hash_algos().
     */
    public function hash(mixed $payload, array $excludePaths = [], string $algo = 'md5'): string;
}
