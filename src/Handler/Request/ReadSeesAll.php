<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use Closure;
use InvalidArgumentException;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SeeAllRight;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Answers whether the session of the request "sees all" for one right. The route then passes `groups = null` to the
 * access filter.
 *
 * Without a rule (`new ReadSeesAll()`): true when `RequestAttribute::RIGHTS` holds the superuser `*` (the owner) or
 * the "sees all" entry of exactly this right (`SeeAllRight::RESOURCE . ':' . $right`, e.g. `seeAll:sales.order:read`),
 * compared literally.
 *
 * With a rule (`$hasRight`, the same rule the door asks, e.g. `HasRight` of `jardissupport/auth`, handed in - never
 * rebuilt here): true for `*`; otherwise the "sees all" entries are taken out of `RIGHTS`, their prefix
 * `SeeAllRight::RESOURCE . ':'` is cut off and the widened rights found that way are handed to the rule together
 * with the right of the door: `$hasRight($widenedRights, $right)`. So `seeAll:x.y:*` opens the view at every door of
 * `x.y`, exactly where `x.y:*` would open that door. Ordinary rights never go to the rule: `x.y:*` without a "sees
 * all" entry opens the door, never the view. This class compares no right with a right itself; the entry form is the
 * contract constant.
 *
 * False for the entry of another right, for a RIGHTS attribute that is absent and for one that is not a list of
 * strings (unusable). Fail-closed: a rule that cannot read a right (`InvalidArgumentException`) answers false, as
 * `RequireRight` answers 403. It only reads and never decides on access; it throws nothing of its own (but
 * `\Error`), a rule that throws anything other than `InvalidArgumentException` propagates.
 */
final class ReadSeesAll
{
    private readonly Closure $readRights;

    /**
     * @param Closure(list<string>, string): bool|null $hasRight true when the right is among the given rights (the
     *        door's rule, handed in); null = literal comparison of the "sees all" entry
     */
    public function __construct(private readonly ?Closure $hasRight = null)
    {
        $this->readRights = (new ReadRequestAttributeList())->__invoke(...);
    }

    public function __invoke(ServerRequestInterface $request, string $right): bool
    {
        $rights = ($this->readRights)($request, RequestAttribute::RIGHTS);
        if ($rights === null) {
            return false;
        }

        if (in_array('*', $rights, true)) {
            return true;
        }

        if ($this->hasRight === null) {
            return in_array(SeeAllRight::RESOURCE . ':' . $right, $rights, true);
        }

        $widened = $this->widenedRights($rights);
        if ($widened === []) {
            return false;
        }

        try {
            return ($this->hasRight)($widened, $right);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @param list<string> $rights
     * @return list<string> the widened rights of the "sees all" entries, prefix cut off
     */
    private function widenedRights(array $rights): array
    {
        $prefix = SeeAllRight::RESOURCE . ':';
        $widened = [];
        foreach ($rights as $entry) {
            if (str_starts_with($entry, $prefix)) {
                $widened[] = substr($entry, strlen($prefix));
            }
        }

        return $widened;
    }
}
