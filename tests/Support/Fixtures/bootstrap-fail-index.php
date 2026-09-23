<?php

declare(strict_types=1);

// Fixture for the "Bootstrap-Fehlschlag" sub-process test (F9, P6 AK): a
// minimal stand-in for a real public/index.php (docs/getting-started.md),
// deliberately calling the ENV-Packer with an invalid project root so it
// throws BEFORE any router/pipeline exists to catch it - there is nothing
// downstream of this to produce a client-facing envelope, only PHP's own
// error handling (display_errors=Off + this shutdown-function net).
require dirname(__DIR__, 3) . '/vendor/autoload.php';

use JardisCore\Kernel\Bootstrap\BuildDomainKernelFromEnv;

register_shutdown_function(static function (): void {
    $error = error_get_last();
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    if ($error !== null && in_array($error['type'], $fatal, true) && !headers_sent()) {
        http_response_code(500);
    }
});

// jardiscore/kernel v2.2.0 dropped the "<projectRoot>/config/env" mkdir()
// convention this fixture used to rely on (G1) - the packer no longer
// creates any directory, so the previous "__FILE__ as project root makes
// mkdir() fail with ENOTDIR" trick stopped throwing once this repo resolved
// kernel >=2.2.0 (composer.lock is not committed here, so every fresh
// install picks the newest ^2.0 release).
//
// The $envContent (string) mode is the new deterministic, filesystem- and
// permission-independent failure path: loadPrivateFromString() has no
// file-system context to resolve a load()/load?() directive against, so it
// throws IncludeNotSupportedException unconditionally (jardissupport/dotenv,
// a required - not optional - kernel dependency, so this path needs no
// adapter package installed).
(new BuildDomainKernelFromEnv())(dirname(__DIR__, 3), 'load(.env.database)');
