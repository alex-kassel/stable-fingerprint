<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint\Exceptions;

use InvalidArgumentException;

class UnsupportedAlgorithmException extends InvalidArgumentException implements StableFingerprintException
{
}
