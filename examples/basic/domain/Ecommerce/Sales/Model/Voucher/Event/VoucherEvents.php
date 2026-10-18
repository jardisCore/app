<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

/**
 * Voucher Event Registry.
 *
 * Provides access to all Voucher domain events.
 * Use constants for event subscriptions and getAll() for bulk registration.
 */
class VoucherEvents
{
    public const VOUCHER_CREATED = VoucherCreated::class;
    public const VOUCHER_REMOVED = VoucherRemoved::class;
    public const VOUCHER_UPDATED = VoucherUpdated::class;
    public const VOUCHER_VOUCHER_CHANNEL_UPDATED = VoucherVoucherChannelUpdated::class;
    public const VOUCHER_VOUCHER_CHANNEL_REMOVED = VoucherVoucherChannelRemoved::class;
    public const VOUCHER_CHANNEL_HOOK_UPDATED = VoucherChannelHookUpdated::class;
    public const VOUCHER_CHANNEL_HOOK_REMOVED = VoucherChannelHookRemoved::class;

    public static function getAll(): array
    {
        return [
            'VoucherCreated' => self::VOUCHER_CREATED,
            'VoucherRemoved' => self::VOUCHER_REMOVED,
            'VoucherUpdated' => self::VOUCHER_UPDATED,
            'VoucherVoucherChannelUpdated' => self::VOUCHER_VOUCHER_CHANNEL_UPDATED,
            'VoucherVoucherChannelRemoved' => self::VOUCHER_VOUCHER_CHANNEL_REMOVED,
            'VoucherChannelHookUpdated' => self::VOUCHER_CHANNEL_HOOK_UPDATED,
            'VoucherChannelHookRemoved' => self::VOUCHER_CHANNEL_HOOK_REMOVED,
        ];
    }
}
