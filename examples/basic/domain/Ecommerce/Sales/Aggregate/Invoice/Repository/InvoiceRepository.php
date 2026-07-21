<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity\Invoice as InvoiceAggregate;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice as InvoiceHandler;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceById as InvoiceByIdQuery;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIdentifier as InvoiceByIdentifierQuery;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIds as InvoiceByIdsQuery;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceLineListFilter;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceListFilter;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;

/**
 * Repository for Invoice aggregate.
 *
 * Orchestrates Query → Transform → Handler process.
 * Optionally uses Hydration for entity hydration.
 */
class InvoiceRepository extends EcommerceContext
{
    /**
     * Get Invoice aggregate.
     *
     * @param InvoiceByIdQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return InvoiceHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getInvoiceById(InvoiceByIdQuery $query, bool $asHandler = true): InvoiceHandler|array|null
    {
        $rootContainer = $this->handle(QueryInvoiceById::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Invoice aggregate.
     *
     * @param InvoiceByIdentifierQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return InvoiceHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getInvoiceByIdentifier(InvoiceByIdentifierQuery $query, bool $asHandler = true): InvoiceHandler|array|null
    {
        $rootContainer = $this->handle(QueryInvoiceByIdentifier::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Invoice aggregate.
     *
     * @param InvoiceByIdsQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return InvoiceHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getInvoiceByIds(InvoiceByIdsQuery $query, bool $asHandler = true): InvoiceHandler|array|null
    {
        $rootContainer = $this->handle(QueryInvoiceByIds::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Build Invoice aggregate from container data.
     *
     * @param array $rootContainer Root container with root entity data
     * @param bool $asHandler If true, return handler; if false, return array
     * @return InvoiceHandler|array|null
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function buildAggregate(array $rootContainer, bool $asHandler = true): InvoiceHandler|array|null
    {
        $container = $this->handle(QueryInvoiceAdditions::class)($rootContainer);
        $aggregateArray = $this->handle(TransformInvoice::class)($container);

        if (empty($aggregateArray)) {
            return $asHandler ? null : [];
        }

        if (!$asHandler) {
            return $aggregateArray;
        }

        $hydration = $this->handle(Hydration::class);
        $aggregateEntity = $hydration->hydrateAggregate(
            $this->handle(InvoiceAggregate::class),
            $aggregateArray[0]
        );

        return $this->handle(InvoiceHandler::class, $aggregateEntity);
    }

    /**
     * Creates a new empty Invoice aggregate handler.
     *
     * Use this for CREATE operations where no existing aggregate exists.
     *
     * @return InvoiceHandler Handler with empty aggregate
     * @throws Throwable
     */
    public function createNew(): InvoiceHandler
    {
        $aggregateEntity = $this->handle(InvoiceAggregate::class);

        return $this->handle(InvoiceHandler::class, $aggregateEntity);
    }
    /**
     * Persists the Invoice aggregate.
     *
     * Validates all OWNED entities before persistence.
     *
     * @param InvoiceHandler $handler The aggregate handler
     * @return ContextResponseInterface Response with events
     * @throws Throwable
     */
    public function persist(InvoiceHandler $handler): ContextResponseInterface
    {
        $this->handle(ValidateInvoice::class)($handler);

        return $this->handle(PersistInvoice::class)($handler);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     */
    protected function listResponse(array $rows, int $limit, int $offset): array
    {
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function invoiceLineList(InvoiceLineListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryInvoiceLineList::class)($filter), $filter->limit, $filter->offset);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function invoiceList(InvoiceListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryInvoiceList::class)($filter), $filter->limit, $filter->offset);
    }
}
