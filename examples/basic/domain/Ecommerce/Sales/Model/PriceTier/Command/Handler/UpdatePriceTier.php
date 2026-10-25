<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\Action\BuildUpdatePriceTierData;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\Action\BuildUpdatePriceTierExpected;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\UpdatePriceTierValidatorRegistry;
use Ecommerce\Sales\Model\PriceTier\Event\PriceTierUpdated;
use Ecommerce\Sales\Model\PriceTier\Repository\PriceTierRepository;
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
use Ecommerce\Sales\Model\PriceTier\Command\UpdatePriceTier as CommandUpdatePriceTier;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierByIdentifier as QueryPriceTier;

/**
 * Command endpoint: UpdatePriceTier
 *
 * Operation: update PriceTier (root)
 */
class UpdatePriceTier extends EcommerceContext
{
    /**
     * Updates the root PriceTier entity.
     *
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        /** @var CommandUpdatePriceTier $updatePriceTier */
        $updatePriceTier = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdatePriceTierValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updatePriceTier);
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
                QueryPriceTier::class,
                tenant: $updatePriceTier->tenant,
                identifier: $updatePriceTier->priceTierIdentifier
            );
            /** @var PriceTier|null $handler */
            $handler = $this->handle(PriceTierRepository::class)->getPriceTierByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['priceTierIdentifier' => $updatePriceTier->priceTierIdentifier]
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

            $entityData = $this->handle(BuildUpdatePriceTierData::class)($updatePriceTier);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdatePriceTierExpected::class)($expected));
            }

            $handler->setPriceTier($entityData);

            $persistResult = $this->handle(PriceTierRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'priceTier',
                'id' => $handler->getData()->getId(),
                'priceTierIdentifier' => $updatePriceTier->priceTierIdentifier,
            ]);

            $event = $this->handle(
                PriceTierUpdated::class,
                priceTierIdentifier: $handler->getData()->getIdentifier(),
                tenantKey: $handler->getData()->getTenantKey(),
                tierCode: $handler->getData()->getTierCode(),
                minQuantity: $handler->getData()->getMinQuantity(),
                unitPrice: $handler->getData()->getUnitPrice(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'PriceTier');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdatePriceTier');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
