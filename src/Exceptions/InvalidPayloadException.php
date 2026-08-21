<?php

declare(strict_types=1);

namespace AlexKassel\StableFingerprint\Exceptions;

use InvalidArgumentException;

final class InvalidPayloadException extends InvalidArgumentException implements StableFingerprintException
{
}
