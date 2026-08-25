<?php

declare(strict_types=1);
use Composer\Autoload\ClassLoader;

$candidates = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../../vendor/autoload.php',
];

$autoloader = null;
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloader = require $candidate;
        break;
    }
}

if ($autoloader === null) {
    throw new RuntimeException('Composer autoloader not found.');
}

/** @var ClassLoader $autoloader */
$autoloader->addPsr4('AlexKassel\\StableFingerprint\\', __DIR__.'/../src');
$autoloader->addPsr4('AlexKassel\\StableFingerprint\\Tests\\', __DIR__);
