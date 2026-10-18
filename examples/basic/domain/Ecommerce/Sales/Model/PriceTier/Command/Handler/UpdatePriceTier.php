<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\Action\BuildUpdatePriceTierData;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\Action\BuildUpdatePriceTierExpected;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\ValidateUpdatePriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\PriceTier\Event\PriceTierUpdated;
use Ecommerce\Sales\Model\PriceTier\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\PriceTier\Repository\PriceTierRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\NotFoundPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        try {
            /** @var CommandUpdatePriceTier $updatePriceTier */
            $updatePriceTier = $this->payload();

            $this->handle(ValidateUpdatePriceTier::class)($updatePriceTier);

            $query = $this->handle(QueryPriceTier::class, identifier: $updatePriceTier->priceTierIdentifier);
            /** @var PriceTier|null $handler */
            $handler = $this->handle(PriceTierRepository::class)->getPriceTierByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['priceTierIdentifier' => $updatePriceTier->priceTierIdentifier]
                        )
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
                'priceTierIdentifier' => $updatePriceTier->priceTierIdentifier,
            ]);

            $event = $this->handle(
                PriceTierUpdated::class,
                priceTierIdentifier: $handler->getData()->getIdentifier(),
                tierCode: $handler->getData()->getTierCode(),
                minQuantity: $handler->getData()->getMinQuantity(),
                unitPrice: $handler->getData()->getUnitPrice(),
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
