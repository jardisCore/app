<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\CreatePriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\RemovePriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Handler\UpdatePriceTier;
use Ecommerce\Sales\Model\PriceTier\Event\PriceTierEvents;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Sales\Model\PriceTier\Command\PriceTier as CommandPriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\RemovePriceTier as CommandRemovePriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\UpdatePriceTier as CommandUpdatePriceTier;

/**
 * PriceTier Aggregate Facade.
 *
 * Hosts the inline command operations for this aggregate (writes).
 * Domain events are exposed via event() (constants on PriceTierEvents).
 * Query/list operations live on the sibling read facade.
 */
class PriceTier extends EcommerceContext
{
    /**
     * Creates a new PriceTier.
     *
     * @param CommandPriceTier $priceTier
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function createPriceTier(CommandPriceTier $priceTier, string $version = ''): DomainResponseInterface
    {
        return $this->context(CreatePriceTier::class, $priceTier, $version)();
    }

    /**
     * Update PriceTier operation.
     *
     * @param CommandUpdatePriceTier $updatePriceTier
     * @param string $version Optional ClassVersion override (default: caller version)
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updatePriceTier(
        CommandUpdatePriceTier $updatePriceTier,
        string $version = '',
        array $expected = []
    ): DomainResponseInterface {
        return $this->context(UpdatePriceTier::class, $updatePriceTier, $version)($expected);
    }

    /**
     * Removes PriceTier aggregate.
     *
     * @param CommandRemovePriceTier $removePriceTier
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removePriceTier(
        CommandRemovePriceTier $removePriceTier,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemovePriceTier::class, $removePriceTier, $version)();
    }

    /**
     * Returns the Event registry.
     *
     * @return PriceTierEvents
     * @throws Throwable
     */
    public function event(): PriceTierEvents
    {
        return $this->handle(PriceTierEvents::class);
    }
}
