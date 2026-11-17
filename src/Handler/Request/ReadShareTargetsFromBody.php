<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use JardisCore\App\Data\ShareTargetRequest;
use JardisCore\App\Exception\InvalidJsonBody;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads the share targets of "share" from the JSON body fields `groups` (`list<int>`), `person` (bare subject
 * id, string) and `level` (ONE level word for every target).
 *
 * - body empty, not valid JSON, not an object        -> `null` (no indication; the handler answers the 400);
 * - neither `groups` (non-empty) nor `person` named  -> `null`;
 * - `groups` not a list of integers, `person` not a string -> `false` (unusable);
 * - otherwise                                        -> the groups and the person, each with the word `level`.
 *
 * A field that is absent or `null` counts as not named. `level` absent or not a string -> the word `''`. The
 * word is carried unchanged and never checked (the door does that). The closure never throws (but `\Error`).
 */
final class ReadShareTargetsFromBody
{
    public function __construct(private readonly ParseJsonBody $parseJsonBody = new ParseJsonBody())
    {
    }

    public function __invoke(ServerRequestInterface $request): ShareTargetRequest|false|null
    {
        try {
            $body = ($this->parseJsonBody)($request);
        } catch (InvalidJsonBody) {
            return null;
        }

        $groups = $body['groups'] ?? null;
        $person = $body['person'] ?? null;
        $word = is_string($body['level'] ?? null) ? $body['level'] : '';

        if (($groups === null || $groups === []) && $person === null) {
            return null;
        }

        if ($groups !== null && !$this->isIntList($groups)) {
            return false;
        }

        if ($person !== null && !is_string($person)) {
            return false;
        }

        return new ShareTargetRequest(
            array_fill_keys($groups ?? [], $word),
            $person,
            $word,
        );
    }

    /**
     * @phpstan-assert-if-true list<int> $value
     */
    private function isIntList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && array_filter($value, is_int(...)) === $value;
    }
}
