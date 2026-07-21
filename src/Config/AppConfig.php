<?php

declare(strict_types=1);

namespace JardisCore\App\Config;

/**
 * Application-wide configuration values (readonly VO). Values are always
 * injected by the bootstrap layer — this VO never reads ENV itself (E11).
 */
final readonly class AppConfig
{
    public function __construct(
        public bool $debug = false,
    ) {
    }
}
