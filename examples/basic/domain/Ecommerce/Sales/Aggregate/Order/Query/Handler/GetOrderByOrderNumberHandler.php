<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Query\Response\OrderResponse;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrderByOrderNumber;

/**
 * Query endpoint: GetOrderByOrderNumber
 */
class GetOrderByOrderNumberHandler extends EcommerceContext
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
        /** @var QueryOrderByOrderNumber $orderByOrderNumber */
        $orderByOrderNumber = $this->payload();

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(OrderRepository::class)->getOrderByOrderNumber($orderByOrderNumber, false);

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
                'id' => 'id',
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

        /** @var array<int, OrderResponse> $projected */
        $projected = array_map(
            function (array $row) use ($mapEntity): OrderResponse {
                $mapped = $this->handle(FieldMapper::class)->fromAggregate($row, $mapEntity, 'order');
                $orderResponse = new OrderResponse();
                $this->handle(Hydration::class)->hydrateAggregate($orderResponse, $mapped);
                return $orderResponse;
            },
            $result,
        );

        $this->result()->setData(['order' => $projected[0] ?? null]);

        return $this->handle(DomainResponseTransformer::class)->transform($this->result());
    }
}
