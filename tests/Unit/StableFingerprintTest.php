<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint\Tests\Unit;

use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;
use AlexKassel\StableFingerprint\Exceptions\UnsupportedAlgorithmException;
use AlexKassel\StableFingerprint\StableFingerprint;
use ArrayObject;
use DateTime;
use DateTimeZone;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

enum SampleBackedEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum SampleUnitEnum
{
    case PENDING;
}

class SampleDto
{
    public string $name = 'Alex';
    public int $age = 30;
    protected string $secret = 'hidden';
    private int $id = 42;
}

class SampleJsonSerializable implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return ['custom' => 'payload'];
    }
}

class StableFingerprintTest extends TestCase
{
    private StableFingerprint $fingerprint;

    protected function setUp(): void
    {
        $this->fingerprint = new StableFingerprint();
    }

    public function testKeyOrderInvariance(): void
    {
        $payload1 = ['b' => 2, 'a' => 1, 'nested' => ['z' => 10, 'x' => 5]];
        $payload2 = ['a' => 1, 'b' => 2, 'nested' => ['x' => 5, 'z' => 10]];

        $this->assertSame($this->fingerprint->hash($payload1), $this->fingerprint->hash($payload2));
    }

    public function testListOrderSensitivity(): void
    {
        $payload1 = ['items' => ['apple', 'banana']];
        $payload2 = ['items' => ['banana', 'apple']];

        $this->assertNotSame($this->fingerprint->hash($payload1), $this->fingerprint->hash($payload2));
    }

    public function testListIntegrityAfterPathExclusion(): void
    {
        $payload = [
            'category' => 'test',
            'items' => ['a', 'b', 'c']
        ];

        // Path exclusion of items.1 (removes 'b') -> array_values re-indexing guarantees list ["a", "c"]
        $hashWithExclusion = $this->fingerprint->hash($payload, ['items.1']);
        $expectedPayload = [
            'category' => 'test',
            'items' => ['a', 'c']
        ];

        $this->assertSame($this->fingerprint->hash($expectedPayload), $hashWithExclusion);
    }

    public function testWildcardPathExclusion(): void
    {
        $payload1 = [
            'nonce' => '12345',
            'category' => 'electronics',
            'items' => [
                ['name' => 'Phone', 'created_at' => '2026-08-17 10:00:00'],
                ['name' => 'Laptop', 'created_at' => '2026-08-17 10:05:00'],
            ]
        ];

        $payload2 = [
            'category' => 'electronics',
            'items' => [
                ['name' => 'Phone'],
                ['name' => 'Laptop'],
            ]
        ];

        $hash = $this->fingerprint->hash($payload1, ['items.*.created_at', '*.nonce']);
        $this->assertSame($this->fingerprint->hash($payload2), $hash);
    }

    public function testNonMutatingDateTimeUtcUniformity(): void
    {
        $timezoneBerlin = new DateTimeZone('Europe/Berlin');
        $dt = new DateTime('2026-08-17 20:00:00', $timezoneBerlin);

        $hash = $this->fingerprint->hash(['timestamp' => $dt]);

        // Original object timezone must NOT be mutated in memory
        $this->assertSame('Europe/Berlin', $dt->getTimezone()->getName());
        $this->assertSame('2026-08-17 20:00:00', $dt->format('Y-m-d H:i:s'));

        // UTC equivalent datetime object
        $utcDt = new DateTime('2026-08-17 18:00:00', new DateTimeZone('UTC'));
        $this->assertSame($this->fingerprint->hash(['timestamp' => $utcDt]), $hash);
    }

    public function testDtoAndEnumNormalization(): void
    {
        $dto = new SampleDto();
        $payload = [
            'user' => $dto,
            'status' => SampleBackedEnum::ACTIVE,
            'unit' => SampleUnitEnum::PENDING,
        ];

        $expected = [
            'user' => ['name' => 'Alex', 'age' => 30],
            'status' => 'active',
            'unit' => 'PENDING',
        ];

        $this->assertSame($this->fingerprint->hash($expected), $this->fingerprint->hash($payload));
    }

    public function testJsonSerializableAndTraversableHandling(): void
    {
        $jsonSer = new SampleJsonSerializable();
        $traversable = new ArrayObject(['first' => 1, 'second' => 2]);

        $payload = [
            'data' => $jsonSer,
            'collection' => $traversable,
        ];

        $expected = [
            'data' => ['custom' => 'payload'],
            'collection' => ['first' => 1, 'second' => 2],
        ];

        $this->assertSame($this->fingerprint->hash($expected), $this->fingerprint->hash($payload));
    }

    public function testCircularReferenceProtection(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $a->b = $b;
        $b->a = $a;

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('Circular reference detected');

        $this->fingerprint->hash($a);
    }

    public function testFloatJcsStabilityAndSpecialFloatRejection(): void
    {
        $floatVal = 1.2345678901234;
        $hash1 = $this->fingerprint->hash(['val' => $floatVal]);

        ini_set('serialize_precision', '14');
        $hash2 = $this->fingerprint->hash(['val' => $floatVal]);

        $this->assertSame($hash1, $hash2);

        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash(['nan' => NAN]);
    }

    public function testAlgorithmChoiceAndUnsupportedAlgorithm(): void
    {
        $payload = ['foo' => 'bar'];

        $sha256 = $this->fingerprint->hash($payload, [], 'sha256');
        $this->assertSame(64, strlen($sha256));

        $md5 = $this->fingerprint->hash($payload, [], 'md5');
        $this->assertSame(32, strlen($md5));

        $this->expectException(UnsupportedAlgorithmException::class);
        $this->fingerprint->hash($payload, [], 'invalid_algo_name');
    }
}
