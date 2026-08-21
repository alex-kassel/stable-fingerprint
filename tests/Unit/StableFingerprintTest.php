<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint\Tests\Unit;

use AlexKassel\StableFingerprint\Exceptions\InvalidPayloadException;
use AlexKassel\StableFingerprint\StableFingerprint;
use PHPUnit\Framework\TestCase;

enum SampleEnum: string
{
    case FOO = 'foo';
}

class SampleObject
{
    public string $name = 'test';
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
        $payload1 = ['b' => 2, 'a' => 1];
        $payload2 = ['a' => 1, 'b' => 2];

        $this->assertSame($this->fingerprint->hash($payload1), $this->fingerprint->hash($payload2));
    }

    public function testListOrderSensitivity(): void
    {
        $payload1 = ['apple', 'banana'];
        $payload2 = ['banana', 'apple'];

        $this->assertNotSame($this->fingerprint->hash($payload1), $this->fingerprint->hash($payload2));
    }

    public function testRecursiveSorting(): void
    {
        $payload1 = [
            'z' => ['b' => 2, 'a' => 1],
            'a' => [10, 20],
        ];

        $payload2 = [
            'a' => [10, 20],
            'z' => ['a' => 1, 'b' => 2],
        ];

        $this->assertSame($this->fingerprint->hash($payload1), $this->fingerprint->hash($payload2));
    }

    public function testTypeDistinction(): void
    {
        $hashInt = $this->fingerprint->hash(1);
        $hashString = $this->fingerprint->hash('1');
        $hashBool = $this->fingerprint->hash(true);

        $this->assertNotSame($hashInt, $hashString);
        $this->assertNotSame($hashInt, $hashBool);
        $this->assertNotSame($hashString, $hashBool);
    }

    public function testKnownSha256Vectors(): void
    {
        $this->assertSame(
            '74234e98afe7498fb5daf1f36ac2d78acc339464f950703b8c019892f982b90b',
            $this->fingerprint->hash(null),
        );
        $this->assertSame(
            '43258cff783fe7036d8a43033f830adfc60ec037382473548ac742b888292777',
            $this->fingerprint->hash(['b' => 2, 'a' => 1]),
        );
        $this->assertSame(
            'f5ca319099f6b777b72517eb1fd6c40d5fd45f43acd86c0ce687aed7b8a7a0f9',
            $this->fingerprint->hash(['first', 'second']),
        );
    }

    public function testNoUnicodeNormalization(): void
    {
        // NFC (precomposed e-acute: U+00E9) vs NFD (decomposed e + combining acute accent: U+0065 U+0301)
        $nfc = "\u{00E9}";
        $nfd = "e\u{0301}";

        $this->assertNotSame($nfc, $nfd);
        $this->assertNotSame($this->fingerprint->hash($nfc), $this->fingerprint->hash($nfd));
    }

    public function testRejectionOfFloat(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash(1.23);
    }

    public function testRejectionOfObject(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash(new SampleObject());
    }

    public function testRejectionOfEnum(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash(SampleEnum::FOO);
    }

    public function testRejectionOfResource(): void
    {
        $resource = fopen('php://memory', 'rb');

        try {
            $this->expectException(InvalidPayloadException::class);
            $this->fingerprint->hash($resource);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    public function testRejectionOfInvalidUtf8(): void
    {
        $invalidUtf8 = "\xC3\x28";

        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash($invalidUtf8);
    }

    public function testRejectionOfInvalidUtf8AssociativeKey(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash(["\xC3\x28" => 'value']);
    }

    public function testRejectionOfRecursiveArray(): void
    {
        $recursive = [];
        $recursive['self'] = &$recursive;

        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash($recursive);
    }

    public function testRejectionOfMixedAndNonSequentialKeys(): void
    {
        // Non-sequential integer keys
        $nonSeq = [0 => 'a', 2 => 'b'];

        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash($nonSeq);
    }

    public function testRejectionOfMixedKeys(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->fingerprint->hash(['name' => 'Alex', 0 => 'unexpected']);
    }

    public function testHashAndBinaryCorrespondence(): void
    {
        $payload = ['name' => 'Alex', 'age' => 30, 'roles' => ['admin', 'user']];

        $hashHex = $this->fingerprint->hash($payload);
        $binaryRaw = $this->fingerprint->binary($payload);

        $this->assertSame(64, strlen($hashHex));
        $this->assertSame(32, strlen($binaryRaw));
        $this->assertSame($hashHex, bin2hex($binaryRaw));
    }

    public function testInputArrayNotMutated(): void
    {
        $original = [
            'z' => 10,
            'a' => 5,
            'nested' => [
                'y' => 100,
                'x' => 50,
            ],
        ];

        $originalCopy = $original;

        $this->fingerprint->hash($original);

        $this->assertSame($originalCopy, $original);
        $this->assertSame(array_keys($original), ['z', 'a', 'nested']);
        $this->assertSame(array_keys($original['nested']), ['y', 'x']);
    }
}
