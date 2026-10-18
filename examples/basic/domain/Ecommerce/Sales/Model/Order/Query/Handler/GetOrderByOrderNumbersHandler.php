<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Query\Response\OrderResponse;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumbers as QueryOrderByOrderNumbers;

/**
 * Query endpoint: GetOrderByOrderNumbers
 */
class GetOrderByOrderNumbersHandler extends EcommerceContext
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
        /** @var QueryOrderByOrderNumbers $orderByOrderNumbers */
        $orderByOrderNumbers = $this->payload();

        $logger = $this->resource()->logger();
        if ($logger !== null) {
            $count = count($orderByOrderNumbers->orderNumbers);
            if ($count > self::BULK_WARN_ABOVE) {
                $logger->warning('Bulk read {handler} received {count} keys (threshold {threshold}).', [
                    'handler' => self::class,
                    'count' => $count,
                    'threshold' => self::BULK_WARN_ABOVE,
                ]);
            }
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(OrderRepository::class)->getOrderByOrderNumbers($orderByOrderNumbers, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'address' => [
                'street' => 'street',
                'city' => 'city',
                'postalCode' => 'postal_code',
                'country' => 'country',
            ],
            'customer' => [
                'identifier' => 'identifier',
                'email' => 'email',
                'customerName' => 'name',
                'phone' => 'phone',
                'createdAt' => 'created_at',
            ],
            'itemDiscount' => [
                'id' => 'id',
                'discountCode' => 'discount_code',
                'amount' => 'amount',
            ],
            'order' => [
                'orderNumber' => 'order_number',
                'totalAmount' => 'total_amount',
                'status' => 'status',
                'createdAt' => 'created_at',
            ],
            'orderItem' => [
                'identifier' => 'identifier',
                'productIdentifier' => 'product_identifier',
                'quantity' => 'quantity',
                'unitPrice' => 'unit_price',
                'subtotal' => 'subtotal',
            ],
        ];
        $mapEntity = static fn(string $entity): array => $readMaps[$entity] ?? [];

        $wireTypes = [
            'createdAt' => 'date-time',
            'customer' => [
                'createdAt' => 'date-time',
            ],
        ];

        $projectWire = $this->handle(ProjectWire::class);
        $fieldMapper = $this->handle(FieldMapper::class);
        $hydration = $this->handle(Hydration::class);

        /** @var array<int, OrderResponse> $projected */
        $projected = array_map(
            function (array $row) use ($mapEntity, $fieldMapper, $hydration, $projectWire, $wireTypes): OrderResponse {
                $mapped = $fieldMapper->fromAggregate($row, $mapEntity, 'order');
                $mapped = $projectWire($mapped, $wireTypes);
                $orderResponse = new OrderResponse();
                $hydration->hydrateAggregate($orderResponse, $mapped);
                return $orderResponse;
            },
            $result,
        );

        $this->result()->setData(['@type' => 'orderList', 'items' => $projected]);

        return ($this->transformer())($this->result());
    }
}
