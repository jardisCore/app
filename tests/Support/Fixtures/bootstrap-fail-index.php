<?php

declare(strict_types=1);

// Fixture for the "Bootstrap-Fehlschlag" sub-process test (F9, P6 AK): a
// minimal stand-in for a real public/index.php (docs/getting-started.md),
// deliberately calling the ENV-Packer with an invalid config path so it
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

// An empty config path is rejected by DomainKernel's own constructor guard
// (domainRoot must not be empty) - a deterministic, filesystem-independent
// way to force the packer to fail during bootstrap, before any request
// handling exists.
(new BuildDomainKernelFromEnv())('');
