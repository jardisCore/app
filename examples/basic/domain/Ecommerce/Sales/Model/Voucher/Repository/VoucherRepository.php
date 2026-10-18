<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\Voucher as VoucherAggregate;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher as VoucherHandler;
use Ecommerce\Sales\Model\Voucher\Query\VoucherById as VoucherByIdQuery;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByIds as VoucherByIdsQuery;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as VoucherByVoucherCodeQuery;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCodes as VoucherByVoucherCodesQuery;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;

/**
 * Repository for Voucher aggregate.
 *
 * Orchestrates Query → Transform → Handler process.
 * Optionally uses Hydration for entity hydration.
 */
class VoucherRepository extends EcommerceContext
{
    /**
     * Get Voucher aggregate.
     *
     * @param VoucherByIdQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return VoucherHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getVoucherById(VoucherByIdQuery $query, bool $asHandler = true): VoucherHandler|array|null
    {
        $rootContainer = $this->handle(QueryVoucherById::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Voucher aggregate.
     *
     * @param VoucherByIdsQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return VoucherHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getVoucherByIds(VoucherByIdsQuery $query, bool $asHandler = true): VoucherHandler|array|null
    {
        $rootContainer = $this->handle(QueryVoucherByIds::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Voucher aggregate.
     *
     * @param VoucherByVoucherCodeQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return VoucherHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getVoucherByVoucherCode(
        VoucherByVoucherCodeQuery $query,
        bool $asHandler = true
    ): VoucherHandler|array|null {
        $rootContainer = $this->handle(QueryVoucherByVoucherCode::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Voucher aggregate.
     *
     * @param VoucherByVoucherCodesQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return VoucherHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getVoucherByVoucherCodes(
        VoucherByVoucherCodesQuery $query,
        bool $asHandler = true
    ): VoucherHandler|array|null {
        $rootContainer = $this->handle(QueryVoucherByVoucherCodes::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Build Voucher aggregate from container data.
     *
     * @param array $rootContainer Root container with root entity data
     * @param bool $asHandler If true, return handler; if false, return array
     * @return VoucherHandler|array|null
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function buildAggregate(array $rootContainer, bool $asHandler = true): VoucherHandler|array|null
    {
        $container = $this->handle(QueryVoucherAdditions::class)($rootContainer);
        $aggregateArray = $this->handle(TransformVoucher::class)($container);

        if (empty($aggregateArray)) {
            return $asHandler ? null : [];
        }

        if (!$asHandler) {
            return $aggregateArray;
        }

        $hydration = $this->handle(Hydration::class);
        $aggregateEntity = $hydration->hydrateAggregate(
            $this->handle(VoucherAggregate::class),
            $aggregateArray[0]
        );

        return $this->handle(VoucherHandler::class, $aggregateEntity);
    }

    /**
     * Creates a new empty Voucher aggregate handler.
     *
     * Use this for CREATE operations where no existing aggregate exists.
     *
     * @return VoucherHandler Handler with empty aggregate
     * @throws Throwable
     */
    public function createNew(): VoucherHandler
    {
        $aggregateEntity = $this->handle(VoucherAggregate::class);

        return $this->handle(VoucherHandler::class, $aggregateEntity);
    }
    /**
     * Persists the Voucher aggregate.
     *
     * Validates all OWNED entities before persistence.
     *
     * @param VoucherHandler $handler The aggregate handler
     * @return ContextResponseInterface Response with events
     * @throws Throwable
     */
    public function persist(VoucherHandler $handler): ContextResponseInterface
    {
        $this->handle(ValidateVoucher::class)($handler);

        return $this->handle(PersistVoucher::class)($handler);
    }
}
