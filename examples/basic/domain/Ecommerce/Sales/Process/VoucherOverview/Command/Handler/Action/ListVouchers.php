<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherOverview\Command\Handler\Action;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\VoucherOverview\Command\Handler\VoucherOverviewContext;
use Ecommerce\Sales\Process\VoucherOverview\Response\ListVouchersResponse;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node ListVouchers of the process VoucherOverview (action).
 *
 * Liefert die Zeilen der Query voucherDigestList so, wie die interne Lesung sie kennt; ein unbekannter Scope antwortet
 * 404.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: VoucherOverview
 *     Antwort mit den Zeilen einer Query in Aussenform (akteur-queries E3-P5, R1058
 *     Variante 2): das successOutput-Feld `vouchers` traegt `rowsOf: voucherDigestList`.
 *     Die Tuer schneidet jede Zeile auf die Aussenform der Query (fachlicher Schluessel
 *     statt id, keine writeOnly-Spalte), auch wenn der Knoten mehr liefert.
 * Knoten: ListVouchers
 */
final class ListVouchers extends EcommerceContext
{
    /**
     * @node-id n1
     */
    public function __invoke(VoucherOverviewContext $ctx): NodeResult
    {
        // Dev body (golden): `unknown` is the declared 404; `drift` hands rows on that lack a key of the
        // outer row (the door fails loudly: 500); every other scope hands the rows as the internal read
        // knows them - with the internal id, the write-only digest and the scope column next to the
        // outer row. The door cuts them off.
        $scope = $ctx->input()->scope;
        if ($scope === 'unknown') {
            return NodeResult::reject(ResponseStatus::NotFound, 'sales.voucherScope.notFound', ['scope' => $scope]);
        }
        if ($scope === 'drift') {
            return NodeResult::ok(new ListVouchersResponse(vouchers: [['id' => 7, 'voucherCode' => 'V-100']]));
        }

        return NodeResult::ok(new ListVouchersResponse(vouchers: [
            [
                'id' => 7,
                'scopeKey' => 'scope-a',
                'voucherCode' => 'V-100',
                'amount' => '25.00',
                'note' => null,
                'issuedBy' => 'sales',
                'issuerId' => 'u-1',
                'createdAt' => new DateTimeImmutable('2026-01-02T03:04:05+00:00'),
                'secretDigest' => 'sha256:internal-only',
            ],
            [
                'id' => 8,
                'scopeKey' => 'scope-a',
                'voucherCode' => 'V-101',
                'amount' => '5.50',
                'note' => 'welcome',
                'issuedBy' => 'sales',
                'issuerId' => 'u-2',
                'createdAt' => null,
                'secretDigest' => 'sha256:internal-only-2',
            ],
        ]));
    }
}
