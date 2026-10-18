<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Query\Response\VoucherResponse;
use Ecommerce\Sales\Model\Voucher\Repository\VoucherRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as QueryVoucherByVoucherCode;

/**
 * Query endpoint: GetVoucherByVoucherCode
 */
class GetVoucherByVoucherCodeHandler extends EcommerceContext
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
        /** @var QueryVoucherByVoucherCode $voucherByVoucherCode */
        $voucherByVoucherCode = $this->payload();

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(VoucherRepository::class)->getVoucherByVoucherCode($voucherByVoucherCode, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'channelHook' => [
                'id' => 'id',
                'targetUrl' => 'target_url',
            ],
            'voucher' => [
                'voucherCode' => 'voucher_code',
                'amount' => 'amount',
                'note' => 'note',
                'issuedBy' => 'issued_by',
                'issuerId' => 'issuer_id',
                'createdAt' => 'created_at',
            ],
            'voucherChannel' => [
                'identifier' => 'identifier',
                'label' => 'label',
            ],
        ];
        $mapEntity = static fn(string $entity): array => $readMaps[$entity] ?? [];

        $wireTypes = [
            'createdAt' => 'date-time',
        ];

        $projectWire = $this->handle(ProjectWire::class);
        $fieldMapper = $this->handle(FieldMapper::class);
        $hydration = $this->handle(Hydration::class);

        /** @var array<int, VoucherResponse> $projected */
        $projected = array_map(
            function (array $row) use (
                $mapEntity,
                $fieldMapper,
                $hydration,
                $projectWire,
                $wireTypes
            ): VoucherResponse {
                $mapped = $fieldMapper->fromAggregate($row, $mapEntity, 'voucher');
                $mapped = $projectWire($mapped, $wireTypes);
                $voucherResponse = new VoucherResponse();
                $hydration->hydrateAggregate($voucherResponse, $mapped);
                return $voucherResponse;
            },
            $result,
        );

        $found = $projected[0] ?? null;
        $this->result()->setData($found === null ? [] : ['@type' => 'voucher'] + get_object_vars($found));

        return ($this->transformer())($this->result());
    }
}
