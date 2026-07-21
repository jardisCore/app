<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Invoice\Query\Response\InvoiceResponse;
use Ecommerce\Sales\Aggregate\Invoice\Repository\InvoiceRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIds as QueryInvoiceByIds;

/**
 * Query endpoint: GetInvoiceByIds
 */
class GetInvoiceByIdsHandler extends EcommerceContext
{
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

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(InvoiceRepository::class)->getInvoiceByIds($invoiceByIds, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'invoice' => [
                'id' => 'id',
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

        /** @var array<int, InvoiceResponse> $projected */
        $projected = array_map(
            function (array $row) use ($mapEntity): InvoiceResponse {
                $mapped = $this->handle(FieldMapper::class)->fromAggregate($row, $mapEntity, 'invoice');
                $invoiceResponse = new InvoiceResponse();
                $this->handle(Hydration::class)->hydrateAggregate($invoiceResponse, $mapped);
                return $invoiceResponse;
            },
            $result,
        );

        $this->result()->setData(['invoice' => $projected]);

        return $this->handle(DomainResponseTransformer::class)->transform($this->result());
    }
}
