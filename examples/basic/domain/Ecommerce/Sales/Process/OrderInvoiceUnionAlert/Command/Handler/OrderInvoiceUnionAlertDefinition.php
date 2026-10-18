<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler;

use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action\EvaluateSubjectRemoval;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action\SubjectRemovalAlertedNode;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\OrderInvoiceUnionAlert;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process OrderInvoiceUnionAlert: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class OrderInvoiceUnionAlertDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(OrderInvoiceUnionAlert::class, 'OrderInvoiceUnionAlert')
            ->context(OrderInvoiceUnionAlertContext::class)
            ->transactional(true)
            ->validate(OrderInvoiceUnionAlertValidator::class)
            ->node(EvaluateSubjectRemoval::class)
            ->onTrue(SubjectRemovalAlertedNode::class)
            ->node(SubjectRemovalAlertedNode::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}
