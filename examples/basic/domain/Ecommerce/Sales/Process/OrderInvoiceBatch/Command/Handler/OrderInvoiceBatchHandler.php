<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\ApplyBatch;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\OrderInvoiceBatchEntry;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\OrderInvoiceBatchFinalize;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Workflow\WorkflowConfigInterface;
use JardisSupport\Factory\Factory;
use JardisSupport\Workflow\Workflow;
use JardisSupport\Workflow\WorkflowConfig;
use JardisSupport\Workflow\WorkflowResult;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Workflow orchestrator for OrderInvoiceBatchHandler.
 *
 * Orchestration-Full-Graph:
 * the whole graph IS the workflow — config() registers every node, the
 * engine runs the painted edges; domain events are harvested from the chain.
 */
final class OrderInvoiceBatchHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();

        try {
            if ($ownsTransaction) {
                $writer->beginTransaction();
            }

            /** @var OrderInvoiceBatch $cmd */
            $cmd = $this->payload();

            /** @var Factory $container */
            $container = $this->resource()->container();

            $workflow = $container->create(
                Workflow::class,
                /** @phpstan-ignore return.type */
                fn(string $cls, mixed $data = null): object => $data !== null
                    /** @phpstan-ignore argument.type */
                    ? $this->context($cls, $data)
                    : $this->handle($cls),
            );

            $ctx = $workflow($this->config(), $cmd);

            foreach ($ctx->getChain() as $nodeResult) {
                $data = $nodeResult->getData();
                foreach (is_array($data) ? ($data[EventScope::Domain->value] ?? []) : [] as $event) {
                    $this->result()->addEvent($event, EventScope::Domain);
                }
            }

            if ($ownsTransaction) {
                $writer->commit();
            }

            return $this->handle(DomainResponseTransformer::class)
                ->transform($this->result());
        } catch (Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            }

            $this->result()->addError($e->getMessage());

            return $this->handle(DomainResponseTransformer::class)
                ->transform($this->result(), ResponseStatus::InternalError);
        }
    }

    private function config(): WorkflowConfigInterface
    {
        return (new WorkflowConfig())
            ->addNode(OrderInvoiceBatchEntry::class, [
                WorkflowResult::ON_SUCCESS => ApplyBatch::class,
            ])
            ->addNode(ApplyBatch::class, [
                WorkflowResult::ON_SUCCESS => OrderInvoiceBatchFinalize::class,
            ])
            ->addNode(OrderInvoiceBatchFinalize::class, []);
    }

    /**
     * Resolves the database connection to a PDO instance.
     *
     * Handles ConnectionPoolInterface (read/write splitting) and plain PDO.
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        $connection = $this->resource()->dbConnection();

        if ($connection instanceof \PDO) {
            return $connection;
        }

        if ($connection instanceof ConnectionPoolInterface) {
            return $connection->getWriter()->pdo();
        }

        throw new \RuntimeException('No database connection configured');
    }
}
