<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint;

use AlexKassel\StableFingerprint\Contracts\StableFingerprintInterface;
use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;
use AlexKassel\StableFingerprint\Exceptions\UnsupportedAlgorithmException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use Normalizer;
use SplObjectStorage;
use Traversable;

final class StableFingerprint implements StableFingerprintInterface
{
    /**
     * Calculate a deterministic canonical hash from the input payload.
     */
    public function hash(mixed $payload, array $excludePaths = [], string $algo = 'md5'): string
    {
        if (!in_array($algo, hash_algos(), true)) {
            throw new UnsupportedAlgorithmException(sprintf('Unsupported hashing algorithm [%s]. Available algorithms: %s', $algo, implode(', ', hash_algos())));
        }

        // Set float serialize precision for RFC 8785 JCS compliance across environments
        $originalPrecision = ini_get('serialize_precision');
        ini_set('serialize_precision', '-1');

        try {
            $objectStack = new SplObjectStorage();
            $normalizedPayload = $this->normalizeAndCanonicalize($payload, $excludePaths, '', $objectStack);

            $json = json_encode(
                $normalizedPayload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            return hash($algo, $json);
        } finally {
            if ($originalPrecision !== false) {
                ini_set('serialize_precision', (string) $originalPrecision);
            }
        }
    }

    /**
     * Recursive normalization and canonicalization pass.
     */
    private function normalizeAndCanonicalize(mixed $data, array $excludePaths, string $currentPath, SplObjectStorage $objectStack): mixed
    {
        // 1. Check for unhashable primitive types
        if (is_resource($data) || $data instanceof \Closure) {
            throw new InvalidPayloadException(sprintf('Unhashable payload element of type [%s] encountered.', get_debug_type($data)));
        }

        // 2. Objects handling & Circular Reference Protection
        if (is_object($data)) {
            if ($objectStack->contains($data)) {
                throw new InvalidPayloadException('Circular reference detected in payload object graph.');
            }

            $objectStack->attach($data);

            try {
                if ($data instanceof DateTimeInterface) {
                    $utcDt = DateTimeImmutable::createFromInterface($data)->setTimezone(new DateTimeZone('UTC'));
                    return $utcDt->format('Y-m-d\TH:i:s.u\Z');
                }

                if ($data instanceof \BackedEnum) {
                    return $data->value;
                }

                if ($data instanceof \UnitEnum) {
                    return $data->name;
                }

                if ($data instanceof JsonSerializable) {
                    $serialized = $data->jsonSerialize();
                    return $this->normalizeAndCanonicalize($serialized, $excludePaths, $currentPath, $objectStack);
                }

                if ($data instanceof Traversable) {
                    $array = iterator_to_array($data);
                    return $this->normalizeAndCanonicalize($array, $excludePaths, $currentPath, $objectStack);
                }

                // DTO / Generic Object: strictly get_object_vars (public non-static properties)
                $array = get_object_vars($data);
                return $this->normalizeAndCanonicalize($array, $excludePaths, $currentPath, $objectStack);
            } finally {
                $objectStack->detach($data);
            }
        }

        // 3. Array handling (Path exclusion + Re-indexing + ksort)
        if (is_array($data)) {
            $isListBefore = array_is_list($data);
            $filtered = [];
            $wasModified = false;

            foreach ($data as $key => $value) {
                $segmentKey = (string) $key;
                $nextPath = $currentPath === '' ? $segmentKey : $currentPath . '.' . $segmentKey;

                if ($this->isPathExcluded($nextPath, $excludePaths)) {
                    $wasModified = true;
                    continue;
                }

                $filtered[$key] = $this->normalizeAndCanonicalize($value, $excludePaths, $nextPath, $objectStack);
            }

            // List Re-indexing Guarantee (RFC 8785)
            if ($isListBefore) {
                if ($wasModified) {
                    $filtered = array_values($filtered);
                }
                // Indexed sequential array: preserve element order
                return $filtered;
            }

            // Associative array: sort keys alphabetically
            ksort($filtered, SORT_STRING);
            return $filtered;
        }

        // 4. Scalar types normalization
        if (is_float($data)) {
            if (is_nan($data) || is_infinite($data)) {
                throw new InvalidPayloadException(sprintf('Special floating-point value [%s] cannot be canonicalized into JSON.', $data));
            }
            return $data;
        }

        if (is_string($data)) {
            if (!mb_check_encoding($data, 'UTF-8')) {
                throw new InvalidPayloadException('Malformed non-UTF-8 string encountered in payload.');
            }
            if (class_exists(Normalizer::class)) {
                $normalized = Normalizer::normalize($data, Normalizer::FORM_C);
                return $normalized !== false ? $normalized : $data;
            }
            return $data;
        }

        return $data;
    }

    /**
     * Evaluates segment-based dot-notation wildcard patterns in $excludePaths.
     */
    private function isPathExcluded(string $path, array $excludePaths): bool
    {
        if (empty($excludePaths)) {
            return false;
        }

        $pathSegments = explode('.', $path);
        $pathLength = count($pathSegments);

        foreach ($excludePaths as $pattern) {
            $patternSegments = explode('.', $pattern);

            // Leading wildcard matching (e.g. *.nonce)
            if ($patternSegments[0] === '*' && count($patternSegments) === 2) {
                $targetKey = $patternSegments[1];
                $lastKey = end($pathSegments);
                if (fnmatch($targetKey, (string) $lastKey)) {
                    return true;
                }
            }

            // Exact segment length match
            if (count($patternSegments) === $pathLength) {
                $matches = true;
                for ($i = 0; $i < $pathLength; $i++) {
                    if (!fnmatch($patternSegments[$i], $pathSegments[$i])) {
                        $matches = false;
                        break;
                    }
                }
                if ($matches) {
                    return true;
                }
            }
        }

        return false;
    }
}
