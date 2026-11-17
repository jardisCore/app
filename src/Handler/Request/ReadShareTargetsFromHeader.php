<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use JardisCore\App\Data\ShareTargetRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads the share targets of "create with share with" from the request header `X-Jardis-Share-With`:
 * comma-separated pairs `group=level`, white space around an entry is allowed (`12=view, 15=edit`).
 *
 * - header absent or blank                       -> `null` (no indication);
 * - an entry without `=` (`12`)                  -> that group with the word `''`;
 * - a group that is not a whole number           -> `false` (unusable);
 * - otherwise                                    -> the groups with their words, never a person.
 *
 * The level word is carried unchanged and never checked (the door does that). Empty entries (`12=view,,`)
 * are skipped; a repeated group keeps its last word. The closure only reads and never throws (but `\Error`).
 */
final class ReadShareTargetsFromHeader
{
    public const string HEADER = 'X-Jardis-Share-With';

    public function __invoke(ServerRequestInterface $request): ShareTargetRequest|false|null
    {
        $groups = [];

        foreach (explode(',', $request->getHeaderLine(self::HEADER)) as $entry) {
            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            [$group, $word] = array_pad(explode('=', $entry, 2), 2, '');
            $group = trim($group);

            if (preg_match('/^\d+$/', $group) !== 1 || filter_var($group, FILTER_VALIDATE_INT) === false) {
                return false;
            }

            $groups[(int) $group] = trim($word);
        }

        return $groups === [] ? null : new ShareTargetRequest($groups);
    }
}
