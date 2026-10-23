<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\Action\HydrateCreatePriceTierEntities;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\CreatePriceTierValidatorRegistry;
use Ecommerce\Sales\Model\PriceTier\Event\PriceTierCreated;
use Ecommerce\Sales\Model\PriceTier\Repository\PriceTierRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
use Throwable;
use Ecommerce\Sales\Model\PriceTier\Command\PriceTier as CommandPriceTier;

/**
 * Command endpoint: CreatePriceTier
 *
 * Creates a new PriceTier aggregate
 */
class CreatePriceTier extends EcommerceContext
{
    /**
     * Creates a new PriceTier.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandPriceTier $priceTier */
        $priceTier = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(CreatePriceTierValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($priceTier);
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
            /** @var PriceTier $handler */
            $handler = $this->handle(PriceTierRepository::class)->createNew();
            $this->handle(HydrateCreatePriceTierEntities::class)($handler, $priceTier);

            $persistResult = $this->handle(PriceTierRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'priceTier',
                'id' => $handler->getData()->getId(),
                'priceTierIdentifier' => $handler->getData()->getIdentifier(),
            ]);

            $event = $this->handle(
                PriceTierCreated::class,
                priceTierIdentifier: $handler->getData()->getIdentifier(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result(), ResponseStatus::Created);
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'PriceTier');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'CreatePriceTier');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
