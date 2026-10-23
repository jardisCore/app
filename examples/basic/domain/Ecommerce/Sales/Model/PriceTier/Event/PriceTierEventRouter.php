<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the PriceTier aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener; listeners and transport
 * are wired in the App delivery layer, never in this generated file.
 *
 * Channel keys:
 *   ecommerce.sales.price-tier.created
 *   ecommerce.sales.price-tier.removed
 *   ecommerce.sales.price-tier.updated
 */
class PriceTierEventRouter
{
    public function __invoke(EventListenerRegistryInterface $registry): void
    {
        $this->onPriceTierCreated($registry);
        $this->onPriceTierRemoved($registry);
        $this->onPriceTierUpdated($registry);
    }

    // ecommerce.sales.price-tier.created
    protected function onPriceTierCreated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(PriceTierCreated::class, function (PriceTierCreated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.price-tier.removed
    protected function onPriceTierRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(PriceTierRemoved::class, function (PriceTierRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.sales.price-tier.updated
    protected function onPriceTierUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(PriceTierUpdated::class, function (PriceTierUpdated $event) {
            // configure transport
        });
    }
}
