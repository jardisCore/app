<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Query\Response\InvoiceResponse;
use Ecommerce\Sales\Model\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIds as QueryInvoiceByIds;

/**
 * Query endpoint: GetInvoiceByIds
 */
class GetInvoiceByIdsHandler extends EcommerceContext
{
    private const BULK_WARN_ABOVE = 100;

    /**
     * Executes the Query endpoint operation.
     *
     * @return DomainResponseInterface
     * @throws Throwable
     * @throws Exception
     * @throws ReflectionException
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var QueryInvoiceByIds $invoiceByIds */
        $invoiceByIds = $this->payload();

        $logger = $this->resource()->logger();
        if ($logger !== null) {
            $count = count($invoiceByIds->ids);
            if ($count > self::BULK_WARN_ABOVE) {
                $logger->warning('Bulk read {handler} received {count} keys (threshold {threshold}).', [
                    'handler' => self::class,
                    'count' => $count,
                    'threshold' => self::BULK_WARN_ABOVE,
                ]);
            }
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(InvoiceRepository::class)->getInvoiceByIds($invoiceByIds, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'invoice' => [
                'identifier' => 'identifier',
                'invoiceNumber' => 'invoice_number',
                'orderNumber' => 'order_number',
                'customerIdentifier' => 'customer_identifier',
                'status' => 'status',
                'paymentMethod' => 'payment_method',
                'totalNet' => 'total_net',
                'taxRate' => 'tax_rate',
                'totalGross' => 'total_gross',
                'currency' => 'currency',
                'note' => 'note',
                'issuedAt' => 'issued_at',
                'dueAt' => 'due_at',
                'paidAt' => 'paid_at',
                'createdAt' => 'created_at',
            ],
            'invoiceLine' => [
                'identifier' => 'identifier',
                'position' => 'position',
                'description' => 'description',
                'quantity' => 'quantity',
                'unit' => 'unit',
                'unitPrice' => 'unit_price',
                'discountPercent' => 'discount_percent',
                'lineTotal' => 'line_total',
                'taxIncluded' => 'tax_included',
                'createdAt' => 'created_at',
            ],
        ];
        $mapEntity = static fn(string $entity): array => $readMaps[$entity] ?? [];

        $wireTypes = [
            'issuedAt' => 'date-time',
            'dueAt' => 'date-time',
            'paidAt' => 'date-time',
            'createdAt' => 'date-time',
            'invoiceLine' => [
                'createdAt' => 'date-time',
            ],
        ];

        $projectWire = $this->handle(ProjectWire::class);
        $fieldMapper = $this->handle(FieldMapper::class);
        $hydration = $this->handle(Hydration::class);

        /** @var array<int, InvoiceResponse> $projected */
        $projected = array_map(
            function (array $row) use (
                $mapEntity,
                $fieldMapper,
                $hydration,
                $projectWire,
                $wireTypes
            ): InvoiceResponse {
                $mapped = $fieldMapper->fromAggregate($row, $mapEntity, 'invoice');
                $mapped = $projectWire($mapped, $wireTypes);
                $invoiceResponse = new InvoiceResponse();
                $hydration->hydrateAggregate($invoiceResponse, $mapped);
                return $invoiceResponse;
            },
            $result,
        );

        $this->result()->setData(['@type' => 'invoiceList', 'items' => $projected]);

        return ($this->transformer())($this->result());
    }
}
