<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler;

use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler\Action\ApplyProductUpdate;
use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process UpdateProductInCatalog: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class UpdateProductInCatalogDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(UpdateProductInCatalog::class, 'UpdateProductInCatalog')
            ->context(UpdateProductInCatalogContext::class)
            ->transactional(true)
            ->validate(UpdateProductInCatalogValidator::class)
            ->node(ApplyProductUpdate::class)
            ->input('update')
            ->terminal(Route::OnTrue)
            ->rejects(ResponseStatus::Conflict, 'catalog.product_update_failed')
            ->build();
    }
}
