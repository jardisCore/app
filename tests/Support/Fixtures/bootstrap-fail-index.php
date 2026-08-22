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

// jardiscore/kernel v2.0.0 (env-konfiguration, R2) always derives
// "<projectRoot>/config/env" and mkdir()s it if missing (G1) - an empty
// string no longer reaches DomainKernel's own "projectRoot must not be
// empty" guard deterministically, because whether mkdir('/config/env')
// itself succeeds now depends on the host/container's filesystem
// permissions at "/". __FILE__ is a regular file, so appending
// "/config/env" and mkdir()'ing it fails on every POSIX filesystem
// regardless of user/permissions (ENOTDIR) - the same deterministic,
// environment-independent bootstrap failure the empty string used to give,
// just via the packer's own RuntimeException instead of DomainKernel's.
(new BuildDomainKernelFromEnv())(__FILE__);
