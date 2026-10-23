<?php

declare(strict_types=1);

namespace Ecommerce;

use JardisSupport\Auth\Handler\Authorization\HasRight;

/**
 * EcommerceCheckAccess: the one access check of the Ecommerce domain.
 *
 * Input: the entry mark of the run, the actor, the right of the endpoint. Output: null (let in) or
 * the denial EcommerceAccessDenied. The doors call it before they run and throw what it returns.
 * Hermetic - the rights test is HasRight of the auth package, nothing else decides.
 */
final class EcommerceCheckAccess
{
    public function __construct(private readonly HasRight $hasRight = new HasRight())
    {
    }

    /**
     * @param bool $entry whether the run entered the domain from outside (false = internal call)
     * @param string $right a right of the rights catalog of the domain
     */
    public function __invoke(bool $entry, EcommerceActor $actor, string $right): ?EcommerceAccessDenied
    {
        if (!$entry) {
            return null;
        }

        if ($actor->kind === EcommerceActorKind::Anonymous) {
            return new EcommerceAccessDenied('actor.kindNotAllowed', ['kind' => $actor->kind->value]);
        }

        if (!($this->hasRight)($actor->rights, $right)) {
            return new EcommerceAccessDenied('actor.rightMissing', ['right' => $right]);
        }

        return null;
    }
}
