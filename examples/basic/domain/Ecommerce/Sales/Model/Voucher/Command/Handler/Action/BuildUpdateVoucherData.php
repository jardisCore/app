<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\UpdateVoucher as CommandUpdateVoucher;

/**
 * Action: BuildUpdateVoucherData
 */
class BuildUpdateVoucherData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateVoucher $updateVoucher Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateVoucher $updateVoucher): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateVoucher);

        $columns = $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["tenant","voucherCode","user"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->vouchersColumns(), ['secret_digest']);
        $columns['issuer_id'] = $updateVoucher->user;
        return $columns;
    }
}
