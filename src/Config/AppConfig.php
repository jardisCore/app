<?php

declare(strict_types=1);

namespace JardisCore\App\Config;

/**
 * Application-wide configuration values (readonly VO). Values are always
 * injected by the bootstrap layer — this VO never reads ENV itself (E11).
 *
 * `knownVersions` is the set of class versions a caller may request via the
 * `X-Jardis-Version` header (e.g. `['v2', 'v3']`); the empty default serves
 * the baseline only, any sent header is answered with 400.
 */
final readonly class AppConfig
{
    /**
     * @param list<string> $knownVersions
     */
    public function __construct(
        public bool $debug = false,
        public array $knownVersions = [],
    ) {
    }
}
