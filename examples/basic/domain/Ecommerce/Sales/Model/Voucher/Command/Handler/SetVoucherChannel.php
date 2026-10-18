<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\Action\BuildSetVoucherChannelData;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ValidateSetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Voucher\Event\VoucherVoucherChannelUpdated;
use Ecommerce\Sales\Model\Voucher\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\Voucher\Repository\VoucherRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\NotFoundPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandSetVoucherChannel $setVoucherChannel */
            $setVoucherChannel = $this->payload();

            $this->handle(ValidateSetVoucherChannel::class)($setVoucherChannel);

            $query = $this->handle(
                QueryVoucher::class,
                scopeKey: $setVoucherChannel->scopeKey,
                voucherCode: $setVoucherChannel->voucherCode
            );
            /** @var Voucher|null $handler */
            $handler = $this->handle(VoucherRepository::class)->getVoucherByVoucherCode($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['voucherCode' => $setVoucherChannel->voucherCode]
                        )
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
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            $this->result()->setData(
                $this->failureData(($this->handle(ValidationPayload::class))('validation', $e->fields))
            );

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::ValidationError
            );
        } catch (ConcurrencyConflictException $e) {
            $this->result()->setData(
                $this->failureData(
                    ($this->handle(ConcurrencyConflictPayload::class))(
                        'concurrencyConflict',
                        $e->getReason(),
                        $e->getAggregate(),
                        $e->getContext(),
                        $e->getViolated()
                    )
                )
            );

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::Conflict
            );
        } catch (TransientConflictException $transient) {
            throw $transient;
        } catch (Throwable $e) {
            $this->result()->addError($e->getMessage());

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::InternalError
            );
        }
    }
}
