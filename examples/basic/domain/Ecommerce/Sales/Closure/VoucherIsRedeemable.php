<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\Voucher;

/**
 * Test-fixture predicate (pm-tool FIX1-G1b): `aggregate`-subject Rule on the
 * tenant-scoped Voucher root. The Rule-Knoten adapter loads the root through
 * LoadVoucherForRule within the tenant of the process context; a voucher
 * whose amount is 0.00 is not redeemable.
 *
 * Rule: VoucherIsRedeemable.
 *
 * Message key: sales.voucherNotRedeemable.
 * The 422 answer carries this catalog key, not the key passed to reject().
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
final class VoucherIsRedeemable extends EcommerceContext
{
    public function __invoke(Voucher $root): RuleResult
    {
        // Test-fixture predicate (pm-tool FIX1-G1b): a voucher with a zero
        // amount is not redeemable; the root arrives already loaded within
        // the tenant of the process context (LoadVoucherForRule).
        if ((float) $root->getAmount() <= 0.0) {
            return RuleResult::reject(
                'VoucherIsRedeemable',
                'sales.voucherNotRedeemable',
                ['amount' => $root->getAmount()],
            );
        }

        return RuleResult::pass();
    }
}
