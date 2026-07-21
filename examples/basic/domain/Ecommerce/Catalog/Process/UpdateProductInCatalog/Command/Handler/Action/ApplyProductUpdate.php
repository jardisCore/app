<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler\Action;

use Ecommerce\Catalog\Aggregate\Product\Product;
use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use RuntimeException;
use Throwable;

/**
 * Persistiert die Product-Aenderung ueber die Aggregat-API
 * (updateProduct). onFail bei fachlichem Misserfolg der
 * DomainResponse.
 *
 * Status: onFail
 *
 * Hilfen aus dem Workflow-Context (siehe WorkflowContextInterface):
 *  - $cmd       = $this->payload();                       // Initial-Params (Command-DTO)
 *  - $prev      = $context->getPrevious();                // direkter Vorgaenger (Status + getData())
 *  - $latest    = $context->getLatest(NodeFqcn::class);   // juengste Invocation eines Knotens
 *  - $allCalls  = $context->getAll(NodeFqcn::class);      // History (Iteration-Counter)
 */
final class ApplyProductUpdate extends EcommerceContext
{
    /**
     * @node-id cp1a0f01
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var UpdateProductInCatalog $cmd */
        $cmd = $this->payload();

        $result = $this->logic($cmd, $context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Ruft den Aggregat-Command direkt ueber die Kernel-Naht auf
     * (familieninterner Zugriff auf die Aggregat-Fassade) und leitet den
     * Routing-Status aus der DomainResponse ab. Vom Generator vorgebackener
     * Default-Body — gegen die Knoten-Requirements pruefen.
     *
     * Statustreppe (docs/rules-layer/PLAN.md Vertrag 5): eine 422-Ablehnung
     * (RuleViolation, fachlicher Fehlschlag) routet ueber die gemalte
     * onFail-Kante — ein 5xx (technischer Fehler) NIE: er wirft, damit der
     * Prozess in den Exception-Pfad der Engine faellt.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function logic(UpdateProductInCatalog $cmd, WorkflowContextInterface $context): array
    {
        // Generierter Default-Body — gegen die Knoten-Requirements pruefen,
        // erweitern oder ersetzen.
        /** @var DomainResponseInterface $response */
        $response = $this->handle(Product::class)->updateProduct($cmd->update);

        if ($response->getStatus() >= ResponseStatus::InternalError->value) {
            throw new \RuntimeException(sprintf('Technischer Fehler bei updateProduct (Status %d).', $response->getStatus()));
        }

        return [
            'status' => $response->isSuccess() ? WorkflowResult::ON_SUCCESS : WorkflowResult::ON_FAIL,
            'data'   => $response->getData(),
        ];
    }
}
