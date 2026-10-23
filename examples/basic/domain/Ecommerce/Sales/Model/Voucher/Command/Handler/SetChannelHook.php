<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\Action\BuildSetChannelHookData;
use Ecommerce\Sales\Model\Voucher\Command\Validation\SetChannelHookValidatorRegistry;
use Ecommerce\Sales\Model\Voucher\Event\VoucherChannelHookUpdated;
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
use Ecommerce\Sales\Model\Voucher\Command\SetChannelHook as CommandSetChannelHook;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as QueryVoucher;

/**
 * Command endpoint: SetChannelHook
 *
 * Operation: set ChannelHook (one)
 */
class SetChannelHook extends EcommerceContext
{
    /**
     * Sets/replaces the ChannelHook entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandSetChannelHook $setChannelHook */
        $setChannelHook = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(SetChannelHookValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($setChannelHook);
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
                scopeKey: $setChannelHook->scopeKey,
                voucherCode: $setChannelHook->voucherCode
            );
            /** @var Voucher|null $handler */
            $handler = $this->handle(VoucherRepository::class)->getVoucherByVoucherCode($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['voucherCode' => $setChannelHook->voucherCode]
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

            $entityData = $this->handle(BuildSetChannelHookData::class)($setChannelHook);

            $miss = $handler->setChannelHook($entityData);
            if ($miss !== null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['voucherCode' => $setChannelHook->voucherCode]
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

            $persistResult = $this->handle(VoucherRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'voucher',
                'id' => $handler->getData()->getId(),
                'voucherCode' => $setChannelHook->voucherCode,
                'channelHookId' => $handler->getData()->getVoucherChannel()->getChannelHook()?->getId(),
            ]);

            $event = $this->handle(
                VoucherChannelHookUpdated::class,
                voucherCode: $handler->getData()->getVoucherCode(),
                targetUrl: $handler->getData()->getVoucherChannel()->getChannelHook()?->getTargetUrl(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Voucher');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'SetChannelHook');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
