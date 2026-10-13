<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The one reader of the `X-Jardis-Version` request header: the caller names
 * the class version it wants served, the delivery layer validates it ONCE
 * against the configured set of known versions, so no route has to read or
 * check the header itself (and none can lose it).
 *
 * Result:
 * - header absent                              -> `''` (the baseline);
 * - exactly one value, exactly (case-sensitive)
 *   in the known versions                      -> that value;
 * - anything else (unknown value, empty value,
 *   repeated header)                           -> `null` (invalid).
 *
 * The set of known versions comes from outside (`AppConfig::$knownVersions`,
 * handed in by the bootstrap layer); this class neither scans directories
 * nor knows classes. With an empty set only the baseline is reachable.
 */
final class ResolveVersion
{
    public const string HEADER = 'X-Jardis-Version';

    /**
     * @param list<string> $knownVersions
     */
    public function __construct(
        private readonly array $knownVersions = [],
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ?string
    {
        $values = $request->getHeader(self::HEADER);

        if ($values === []) {
            return '';
        }

        if (count($values) !== 1) {
            return null;
        }

        $value = $values[0];

        if ($value === '' || !in_array($value, $this->knownVersions, true)) {
            return null;
        }

        return $value;
    }
}
