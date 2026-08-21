<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint;

use AlexKassel\StableFingerprint\Contracts\StableFingerprintInterface;
use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;

final class StableFingerprint implements StableFingerprintInterface
{
    /**
     * Calculate a deterministic SHA-256 hex hash (64 lowercase characters) from the payload.
     *
     * @param null|bool|int|string|array<mixed> $payload
     * @return lowercase-string
     * @throws InvalidPayloadException
     */
    public function hash(mixed $payload): string
    {
        $json = $this->canonicalizeToJson($payload);

        return hash('sha256', $json);
    }

    /**
     * Calculate a deterministic raw binary SHA-256 hash (32 bytes) from the payload.
     *
     * @param null|bool|int|string|array<mixed> $payload
     * @return non-empty-string Exact 32 raw SHA-256 bytes.
     * @throws InvalidPayloadException
     */
    public function binary(mixed $payload): string
    {
        $json = $this->canonicalizeToJson($payload);

        return hash('sha256', $json, true);
    }

    /**
     * Validate and convert the payload into a canonical JSON string.
     *
     * @throws InvalidPayloadException
     */
    private function canonicalizeToJson(mixed $payload): string
    {
        $canonical = $this->canonicalize($payload);

        return json_encode(
            $canonical,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Recursively validate types and canonicalize payload structures.
     *
     * @throws InvalidPayloadException
     */
    private function canonicalize(mixed $data): mixed
    {
        if ($data === null || is_bool($data) || is_int($data)) {
            return $data;
        }

        if (is_string($data)) {
            if (!mb_check_encoding($data, 'UTF-8')) {
                throw new InvalidPayloadException('Payload string contains invalid UTF-8 byte sequences.');
            }

            return $data;
        }

        if (is_array($data)) {
            if (array_is_list($data)) {
                $list = [];
                foreach ($data as $item) {
                    $list[] = $this->canonicalize($item);
                }

                return $list;
            }

            foreach (array_keys($data) as $key) {
                if (!is_string($key)) {
                    throw new InvalidPayloadException('Associative array contains non-string key or non-sequential integer key.');
                }
            }

            $copy = $data;
            ksort($copy, SORT_STRING);

            $assoc = [];
            foreach ($copy as $key => $value) {
                $assoc[$key] = $this->canonicalize($value);
            }

            return $assoc;
        }

        if (is_float($data)) {
            throw new InvalidPayloadException('Float values are not allowed in payload.');
        }

        if (is_object($data)) {
            throw new InvalidPayloadException('Object values are not allowed in payload.');
        }

        if (is_resource($data) || gettype($data) === 'resource (closed)') {
            throw new InvalidPayloadException('Resource values are not allowed in payload.');
        }

        throw new InvalidPayloadException(sprintf('Unsupported payload element type [%s].', get_debug_type($data)));
    }
}
