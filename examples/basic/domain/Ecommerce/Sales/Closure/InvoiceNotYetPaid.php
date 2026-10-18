<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Model\Invoice\Command\Invoice as CommandInvoice;

/**
 * Test-fixture predicate (docs/rules-layer/PLAN.md P4 integration
 * tests): exists solely to prove that a Create command CAN be
 * Rule-guarded on a second aggregate in the same BC. Deliberately,
 * permanently a no-op — hand-confirmed pass, not a forgotten stub.
 *
 * Rule: InvoiceNotYetPaid.
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
final class InvoiceNotYetPaid extends EcommerceContext
{
    /**
     * Always passes — see the class DocBlock above for why.
     */
    public function __invoke(CommandInvoice $command): RuleResult
    {
        return RuleResult::pass();
    }
}
