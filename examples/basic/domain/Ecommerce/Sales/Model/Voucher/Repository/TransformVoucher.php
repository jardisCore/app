<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;

/**
 * Transforms flat query results into nested Voucher structure.
 *
 * Input: Arrays grouped by entity name (from query results)
 * Output: Nested array structure matching aggregate definition
 */
class TransformVoucher extends EcommerceContext
{
    /**
     * Transform flat query results into nested aggregate structure.
     *
     * @param array<string, array<int, mixed>> $container Query results grouped by entity
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     */
    public function __invoke(array $container = []): array
    {
        $result = [];
        $dto = [];
        $processedIds = [];

        foreach ($container['voucher'] as $index => $voucher) {
            if (is_numeric($index) && !isset($processedIds[$voucher['id']])) {
                $processedIds[$voucher['id']] = true;
                $parent = $dto['voucher'] = $voucher;

                $this->transformVoucherVoucherChannel($parent, $dto, $container);

                $result[] = $parent;
            }
        }

        return $result;
    }

    /**
     * Transform voucherChannel entities (ERM: one).
     * Relates: voucher_id = voucher.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     * @throws \RuntimeException
     */
    protected function transformVoucherVoucherChannel(array &$parent, array $dto, array &$container): void
    {
        $matchCount = 0;
        $matchedVoucherChannel = null;

        foreach ($container['voucherChannel'] as $index => $voucherChannel) {
            if (is_numeric($index) && $voucherChannel['voucher_id'] == $dto['voucher']['id']) {
                $matchCount++;
                $matchedVoucherChannel = $voucherChannel;
            }
        }

        if ($matchCount > 1) {
            throw new \RuntimeException(
                "Integrity violation: erm:one relation 'voucher.voucherChannel' expects at most one row per parent, "
                . "found "
                . $matchCount
                . " for parent id '" . (string) $dto['voucher']['id'] . "'."
            );
        }

        if ($matchCount === 1) {
            $parent['voucherChannel'] = $dto['voucherChannel'] = $matchedVoucherChannel;

            $this->transformVoucherChannelChannelHook($parent['voucherChannel'], $dto, $container);
        }
    }
    /**
     * Transform channelHook entities (ERM: one).
     * Relates: channel_id = voucherChannel.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     * @throws \RuntimeException
     */
    protected function transformVoucherChannelChannelHook(array &$parent, array $dto, array &$container): void
    {
        $matchCount = 0;
        $matchedChannelHook = null;

        foreach ($container['channelHook'] as $index => $channelHook) {
            if (is_numeric($index) && $channelHook['channel_id'] == $dto['voucherChannel']['id']) {
                $matchCount++;
                $matchedChannelHook = $channelHook;
            }
        }

        if ($matchCount > 1) {
            throw new \RuntimeException(
                "Integrity violation: erm:one relation 'voucher.voucherChannel.channelHook' expects at most one row "
                . "per parent, found "
                . $matchCount
                . " for parent id '" . (string) $dto['voucherChannel']['id'] . "'."
            );
        }

        if ($matchCount === 1) {
            $parent['channelHook'] = $matchedChannelHook;
        }
    }
}
