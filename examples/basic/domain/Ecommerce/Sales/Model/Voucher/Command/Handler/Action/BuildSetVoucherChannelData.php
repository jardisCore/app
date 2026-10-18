<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\SetVoucherChannel as CommandSetVoucherChannel;

/**
 * Action: BuildSetVoucherChannelData
 */
class BuildSetVoucherChannelData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandSetVoucherChannel $setVoucherChannel Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandSetVoucherChannel $setVoucherChannel): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($setVoucherChannel);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["scopeKey","voucherCode","channelHook"], true),
                ARRAY_FILTER_USE_KEY
            ),
            'channelHook' => $setVoucherChannel->channelHook !== null
                ? $this->handle(FieldMapper::class)->toColumns(
                    get_object_vars($setVoucherChannel->channelHook),
                    $fieldMap->channelHooksColumns(),
                    ['signing_key']
                ) : null,
        ], $fieldMap->voucherChannelsColumns(), ['access_key']);
    }
}
