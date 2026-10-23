<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler;

use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use Ecommerce\EcommerceContext;
use JardisSupport\Repository\Data\OwnedResult;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Repository\Handler\Transaction\RunOwnedTransaction;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\ProcessOutcome;
use JardisSupport\Workflow\ProcessRunner;
use Throwable;

/**
 * Run step of the process UpdateProductInCatalog (hermetic): runs the process in its transaction bracket (the
 * definition says whether it has one).
 *
 * Hands back the OwnedResult: the outcome of the run, who owned the transaction, the failure the run ended with. The
 * door answers from it.
 */
final class RunProcess extends EcommerceContext
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        ProcessDefinition $definition,
        UpdateProductInCatalog $input,
        int $depth
    ): OwnedResult {
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $run = fn(): ProcessOutcome => ($this->handle(ProcessRunner::class))->run(
            $definition,
            $input,
            fn(string $class): object => $this->handle($class),
            $depth
        );

        return ($this->handle(RunOwnedTransaction::class))(
            $connection,
            $run,
            $depth,
            transactional: $definition->isTransactional()
        );
    }
}
