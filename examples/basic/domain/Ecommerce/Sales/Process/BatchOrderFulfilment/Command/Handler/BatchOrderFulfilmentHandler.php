<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\BatchOrderFulfilment;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process BatchOrderFulfilment (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Caller-Fixture fuer R10: ruft OrderInvoiceBatch als Sub-Prozess auf.
 * Belegt R4 (Einzel-Command-Feld primaryCommand: UpdateOrder|UpdateInvoice),
 * R5 (Listen-Command-Feld lineCommands: array<AddOrderItem|AddInvoiceLine>)
 * und R1 (Skalare requestedBy: string, note: string).
 */
final class BatchOrderFulfilmentHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(BatchOrderFulfilmentDefinition::class))();
        /** @var BatchOrderFulfilment $input */
        $input = $this->payload();
        $run = ($this->handle(RunProcess::class))($definition, $input, $this->depth());
        if ($run->failure !== null) {
            $answer = ($this->handle(AnswerThrowable::class))(
                $this->result(),
                $this->transformer(),
                $run->failure,
                $run->owner,
                $definition->getName()
            );
            ($this->handle(LogFailure::class, logger: $this->resource()->logger()))(
                $run->failure,
                $definition->getName(),
                $run->owner,
                $answer
            );

            return $answer;
        }

        return ($this->handle(AnswerOutcome::class))(
            $this->result(),
            $this->transformer(),
            $run->result,
            $definition->getName()
        );
    }
}
