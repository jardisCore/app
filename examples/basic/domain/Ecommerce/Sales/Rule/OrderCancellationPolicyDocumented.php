<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;

/**
 * Dokumentiert die Stornierungs-Policy in Freitext, damit eine KI
 * den Stub-Rumpf ohne Rueckfrage implementieren kann.
 *
 * Haertungs-Nachweis: ein Kommentar-Terminator * / mitten im
 * Freitext darf den generierten Docblock nicht brechen.
 *
 * Rule: OrderCancellationPolicyDocumented.
 *
 * M9: this Rule may read ONLY the Lese-Fassade of its OWN bounded
 * context (`$this->handle(SelfBc::class)->{agg}()`) — no Fremd-BC
 * import, ever (docs/rules-layer/PRD.md §5 Frage 3+7). A read of
 * another BC's state belongs in a Prozess-Knoten, not a Rule.
 *
 * A12 (Kombi-/OR-Rules): compose atomic catalog Rules via
 * `$this->handle(OtherRule::class)($command)` (ClassVersion-fähig) —
 * never `new OtherRule()`.
 */
final class OrderCancellationPolicyDocumented extends EcommerceContext
{
    public function __invoke(object $command): RuleResult
    {
        // TODO: implement the rule predicate.
        return RuleResult::pass();
    }
}
