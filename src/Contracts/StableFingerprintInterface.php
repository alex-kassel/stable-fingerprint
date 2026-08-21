<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint\Contracts;

use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;

interface StableFingerprintInterface
{
    /**
     * @param null|bool|int|string|array<mixed> $payload
     * @return lowercase-string
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
