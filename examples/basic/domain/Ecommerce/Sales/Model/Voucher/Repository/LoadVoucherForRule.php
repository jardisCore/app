<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\Voucher;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher as VoucherHandler;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as QueryVoucher;

/**
 * Rule-Knoten-Wurzel-Lader fuer Voucher (rules-neuverortung PRD I1/§8.3):
 * laedt ueber denselben Repository-Weg wie der generierte
 * Command-Handler (Query + Repository-Getter) — nie ueber die
 * Lesefassade. Generiert (ForceOverwrite), eine Instanz je Aggregat.
 */
final class LoadVoucherForRule extends EcommerceContext
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $tenant, string $voucherCode): ?Voucher
    {
        $query = $this->handle(QueryVoucher::class, tenant: $tenant, voucherCode: $voucherCode);
        /** @var VoucherHandler|null $handler */
        $handler = $this->handle(VoucherRepository::class)->getVoucherByVoucherCode($query);

        return $handler?->getData();
    }
}
