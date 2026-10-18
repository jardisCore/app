<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Model\Order\Command\RemoveOrder as CommandRemoveOrder;

/**
 * Dokumentiert die Stornierungs-Policy in Freitext, damit eine KI
 * den Stub-Rumpf ohne Rueckfrage implementieren kann.
 *
 * Haertungs-Nachweis: ein Kommentar-Terminator * / mitten im
 * Freitext darf den generierten Docblock nicht brechen.
 *
 * Rule: OrderCancellationPolicyDocumented.
 *
 * M9: this Rule may read ONLY its OWN bounded context — no Fremd-BC
 * import, ever. A read of
 * another BC's state belongs in a Prozess-Knoten, not a Rule. The
 * read itself goes over the Kernel-Naht, not the Lese-Fassade — the
 * facade is the BC's API to the outside. Recipe: read a small page of
 * the list and decide via `total`/`items` — for example
 * `$this->context(Get{Agg}ListHandler::class, new {Agg}ListFilter(limit: 1))()`.
 * A scope-bound root (a `tenant` request-attribute column) makes the scope a
 * REQUIRED filter argument — `new {Agg}ListFilter(<scopeField>: $scope, limit: 1)`;
 * a read without it fails with an ArgumentCountError, never counts across scopes.
 * Need a different shape? Declare your own `internal` query
 * (`save_queries`), bind it in this Rule's `reads:` (`save_closures`), then
 * `build` — see Skill `platform-implementation`.
 */
final class OrderCancellationPolicyDocumented extends EcommerceContext
{
    public function __invoke(CommandRemoveOrder $command): RuleResult
    {
        throw new \RuntimeException(
            'Not implemented: write the rule predicate for ' . self::class
        );
    }
}
