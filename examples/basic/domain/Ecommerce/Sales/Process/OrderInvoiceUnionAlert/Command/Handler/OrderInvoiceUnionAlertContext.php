<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler;

use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action\EvaluateSubjectRemoval;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action\SubjectRemovalAlertedNode;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\OrderInvoiceUnionAlert;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process OrderInvoiceUnionAlert: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class OrderInvoiceUnionAlertContext extends ProcessContext
{
    public function input(): OrderInvoiceUnionAlert
    {
        /** @var OrderInvoiceUnionAlert $input */
        $input = parent::input();

        return $input;
    }

    public function evaluateSubjectRemoval(): ?NodeResult
    {
        return $this->latest(EvaluateSubjectRemoval::class);
    }

    public function subjectRemovalAlertedNode(): ?NodeResult
    {
        return $this->latest(SubjectRemovalAlertedNode::class);
    }
}
