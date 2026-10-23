<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Guard\GuardUpdateVoucher;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\Action\BuildUpdateVoucherData;
use Ecommerce\Sales\Model\Voucher\Command\Handler\Action\BuildUpdateVoucherExpected;
use Ecommerce\Sales\Model\Voucher\Command\Validation\UpdateVoucherValidatorRegistry;
use Ecommerce\Sales\Model\Voucher\Event\VoucherUpdated;
use Ecommerce\Sales\Model\Voucher\Repository\VoucherRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Data\RuleResult;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\RuleViolationPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\UpdateVoucher as CommandUpdateVoucher;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as QueryVoucher;

/**
 * Command endpoint: UpdateVoucher
 *
 * Operation: update Voucher (root)
 */
class UpdateVoucher extends EcommerceContext
{
    /**
     * Updates the root Voucher entity.
     *
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        /** @var CommandUpdateVoucher $updateVoucher */
        $updateVoucher = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateVoucherValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateVoucher);
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
                scopeKey: $updateVoucher->scopeKey,
                voucherCode: $updateVoucher->voucherCode
            );
            /** @var Voucher|null $handler */
            $handler = $this->handle(VoucherRepository::class)->getVoucherByVoucherCode($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['voucherCode' => $updateVoucher->voucherCode]
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

            /** @var RuleResult $guardResult */
            $guardResult = ($this->handle(GuardUpdateVoucher::class))($updateVoucher);
            if (!$guardResult->passed) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::RuleViolation,
                        ($this->handle(RuleViolationPayload::class))(
                            $guardResult->rule,
                            $guardResult->messageKey,
                            $guardResult->context
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::RuleViolation
                );
            }

            $entityData = $this->handle(BuildUpdateVoucherData::class)($updateVoucher);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateVoucherExpected::class)($expected));
            }

            $handler->setVoucher($entityData);

            $persistResult = $this->handle(VoucherRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'voucher',
                'id' => $handler->getData()->getId(),
                'voucherCode' => $updateVoucher->voucherCode,
            ]);

            $event = $this->handle(
                VoucherUpdated::class,
                voucherCode: $handler->getData()->getVoucherCode(),
                scopeKey: $handler->getData()->getScopeKey(),
                amount: $handler->getData()->getAmount(),
                note: $handler->getData()->getNote(),
                issuedBy: $handler->getData()->getIssuedBy(),
                issuerId: $handler->getData()->getIssuerId(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Voucher');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateVoucher');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
