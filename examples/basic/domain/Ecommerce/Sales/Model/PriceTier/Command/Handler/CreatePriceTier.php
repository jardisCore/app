<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\Action\HydrateCreatePriceTierEntities;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\ValidateCreatePriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\PriceTier\Event\PriceTierCreated;
use Ecommerce\Sales\Model\PriceTier\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\PriceTier\Repository\PriceTierRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandPriceTier $priceTier */
            $priceTier = $this->payload();

            /** @var PriceTier $handler */
            $handler = $this->handle(PriceTierRepository::class)->createNew();
            $this->handle(ValidateCreatePriceTier::class)($priceTier);
            $this->handle(HydrateCreatePriceTierEntities::class)($handler, $priceTier);

            $persistResult = $this->handle(PriceTierRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'priceTier',
                'priceTierIdentifier' => $handler->getData()->getIdentifier(),
            ]);

            $event = $this->handle(
                PriceTierCreated::class,
                priceTierIdentifier: $handler->getData()->getIdentifier(),
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
