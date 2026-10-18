<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\Action\HydrateCreateVoucherEntities;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ValidateCreateVoucher;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Voucher\Event\VoucherCreated;
use Ecommerce\Sales\Model\Voucher\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\Voucher\Repository\VoucherRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\Voucher as CommandVoucher;

/**
 * Command endpoint: CreateVoucher
 *
 * Creates a new Voucher aggregate
 */
class CreateVoucher extends EcommerceContext
{
    /**
     * Creates a new Voucher.
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
            /** @var CommandVoucher $voucher */
            $voucher = $this->payload();

            /** @var Voucher $handler */
            $handler = $this->handle(VoucherRepository::class)->createNew();
            $this->handle(ValidateCreateVoucher::class)($voucher);
            $this->handle(HydrateCreateVoucherEntities::class)($handler, $voucher);

            $persistResult = $this->handle(VoucherRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'voucher',
                'voucherCode' => $handler->getData()->getVoucherCode(),
                'voucherChannelIdentifier' => $handler->getData()->getVoucherChannel()?->getIdentifier(),
            ]);

            $event = $this->handle(
                VoucherCreated::class,
                voucherCode: $handler->getData()->getVoucherCode(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result(), ResponseStatus::Created);
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
