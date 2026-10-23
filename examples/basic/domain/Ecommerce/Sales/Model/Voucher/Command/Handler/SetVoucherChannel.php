<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\Action\BuildSetVoucherChannelData;
use Ecommerce\Sales\Model\Voucher\Command\Validation\SetVoucherChannelValidatorRegistry;
use Ecommerce\Sales\Model\Voucher\Event\VoucherVoucherChannelUpdated;
use Ecommerce\Sales\Model\Voucher\Repository\VoucherRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\SetVoucherChannel as CommandSetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as QueryVoucher;

/**
 * Command endpoint: SetVoucherChannel
 *
 * Operation: set VoucherChannel (one)
 */
class SetVoucherChannel extends EcommerceContext
{
    /**
     * Sets/replaces the VoucherChannel entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandSetVoucherChannel $setVoucherChannel */
        $setVoucherChannel = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(SetVoucherChannelValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($setVoucherChannel);
        if ($findings->fields !== []) {
            foreach ($findings->errors as $error) {
                $this->result()->addError($error);
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))(
                    ResponseStatus::ValidationError,
                    ($this->handle(ValidationPayload::class))('validation', $findings->fields),
                    null,
                    ''
                )
            );

            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
        }
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(
                QueryVoucher::class,
                scopeKey: $setVoucherChannel->scopeKey,
                voucherCode: $setVoucherChannel->voucherCode
            );
            /** @var Voucher|null $handler */
            $handler = $this->handle(VoucherRepository::class)->getVoucherByVoucherCode($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['voucherCode' => $setVoucherChannel->voucherCode]
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildSetVoucherChannelData::class)($setVoucherChannel);

            $handler->setVoucherChannel($entityData);
            $handler->setChannelHook($entityData['channelHook']);

            $persistResult = $this->handle(VoucherRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'voucher',
                'id' => $handler->getData()->getId(),
                'voucherCode' => $setVoucherChannel->voucherCode,
                'voucherChannelIdentifier' => $handler->getData()->getVoucherChannel()?->getIdentifier(),
            ]);

            $event = $this->handle(
                VoucherVoucherChannelUpdated::class,
                voucherCode: $handler->getData()->getVoucherCode(),
                label: $handler->getData()->getVoucherChannel()?->getLabel(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Voucher');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'SetVoucherChannel');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
