<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;
use Ecommerce\Service\CheckStockInCatalog as CheckStockInCatalogService;

/**
 * Same-Domain-Variante (D3): schreibt in die fremde BC Catalog im eigenen
 * Domain Ecommerce ueber deren Prozess UpdateProductInCatalog (G7), nicht
 * direkt ueber das Aggregat. Der Domain-Service uebersetzt die eigene
 * Eingabe (productIdentifier + newPrice) in das fremde Prozess-Input-DTO
 * und mappt die Antwort ins eigene Vokabular zurueck (ACL).
 *
 * Status: onFail
 *
 * Cross-BC-Call: delegiert an den Service CheckStockInCatalog ueber die Kernel-Naht
 * ($this->handle()) — die eigentliche Uebersetzung lebt dort (Dev-Hoheit).
 */
final class CheckStockInCatalog extends EcommerceContext
{
    /**
     * @node-id a1b2c301
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var CrossBcServiceDemo $cmd */
        $cmd = $this->payload();

        $result = $this->logic($cmd, $context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Delegiert an den generierten Service-Stub CheckStockInCatalog.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    protected function logic(CrossBcServiceDemo $cmd, WorkflowContextInterface $context): array
    {
        return $this->handle(CheckStockInCatalogService::class)->__invoke($context);
    }
}
