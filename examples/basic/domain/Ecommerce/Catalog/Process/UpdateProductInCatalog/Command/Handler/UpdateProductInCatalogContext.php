<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler;

use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler\Action\ApplyProductUpdate;
use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process UpdateProductInCatalog: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class UpdateProductInCatalogContext extends ProcessContext
{
    public function input(): UpdateProductInCatalog
    {
        /** @var UpdateProductInCatalog $input */
        $input = parent::input();

        return $input;
    }

    public function applyProductUpdate(): ?NodeResult
    {
        return $this->latest(ApplyProductUpdate::class);
    }
}
