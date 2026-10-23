<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherOverview\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\VoucherOverview\Command\VoucherOverview;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Repository\Data\OwnedResult;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Repository\Handler\Transaction\RunOwnedTransaction;
use JardisSupport\Response\Handler\Response\ProjectRows;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\ProcessOutcome;
use JardisSupport\Workflow\ProcessRunner;
use Throwable;

/**
 * Run step of the process VoucherOverview (hermetic): runs the process in its transaction bracket (the definition says
 * whether it has one).
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
        VoucherOverview $input,
        int $depth
    ): OwnedResult {
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $run = fn(): ProcessOutcome => $this->projectWire(
            $this->projectRows(
                ($this->handle(ProcessRunner::class))->run(
                    $definition,
                    $input,
                    fn(string $class): object => $this->handle($class),
                    $depth
                )
            )
        );

        return ($this->handle(RunOwnedTransaction::class))(
            $connection,
            $run,
            $depth,
            transactional: $definition->isTransactional()
        );
    }

    /**
     * The rows of a query in the answer of a success, cut to their outer form (ProjectRows); a reject passes unchanged.
     * @throws Throwable
     */
    private function projectRows(ProcessOutcome $outcome): ProcessOutcome
    {
        if ($outcome->isFailed()) {
            return $outcome;
        }
        $cuts = [
            'vouchers' => ['voucherCode', 'amount', 'note', 'issuedBy', 'issuerId', 'createdAt'],
        ];
        $payload = get_object_vars($outcome->response());
        $projectRows = $this->handle(ProjectRows::class);
        $projected = [];
        foreach ($cuts as $field => $names) {
            if (isset($payload[$field])) {
                $projected[$field] = $projectRows($payload[$field], ...$names);
            }
        }

        return $projected === [] ? $outcome : $outcome->withResponse((object) array_replace($payload, $projected));
    }

    /**
     * The typed answer of a success on its wire form (dates as strings); a reject passes unchanged.
     * @throws Throwable
     */
    private function projectWire(ProcessOutcome $outcome): ProcessOutcome
    {
        if ($outcome->isFailed()) {
            return $outcome;
        }
        $wireTypes = [
            'vouchers' => [
                'createdAt' => 'date-time',
            ],
        ];
        $answer = (object) ($this->handle(ProjectWire::class))(get_object_vars($outcome->response()), $wireTypes);

        return $outcome->withResponse($answer);
    }
}
