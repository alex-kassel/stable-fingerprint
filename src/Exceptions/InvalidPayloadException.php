<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint\Exceptions;

use InvalidArgumentException;

class InvalidPayloadException extends InvalidArgumentException implements StableFingerprintException
{
}
