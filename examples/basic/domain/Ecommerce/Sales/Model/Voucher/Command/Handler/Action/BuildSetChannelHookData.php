<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\SetChannelHook as CommandSetChannelHook;

/**
 * Action: BuildSetChannelHookData
 */
class BuildSetChannelHookData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandSetChannelHook $setChannelHook Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandSetChannelHook $setChannelHook): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($setChannelHook);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["tenant","voucherCode"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->channelHooksColumns(), ['signing_key']);
    }
}
