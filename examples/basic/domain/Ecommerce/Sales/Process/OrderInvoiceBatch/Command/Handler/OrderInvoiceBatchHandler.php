<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerFindings;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogFailure;
use JardisSupport\Validation\ObjectValidator;
use Throwable;

/**
 * Door of the process OrderInvoiceBatch (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Typisierter Batch-Eingang (R3): das Input-DTO komponiert Aggregat-Commands
 * seines BC als Union — ein Einzel-Command-Feld und ein Listen-Command-Feld,
 * beide cross-aggregat (Order + Invoice). Skalar-Felder gemischt; die
 * Dispatch-Schleife bleibt Dev-Code im Knoten (R7), nicht Generat.
 */
final class OrderInvoiceBatchHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(OrderInvoiceBatchDefinition::class))();
        /** @var OrderInvoiceBatch $input */
        $input = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(OrderInvoiceBatchValidator::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($input);
        if ($findings->failed()) {
            return ($this->handle(AnswerFindings::class))($this->result(), $this->transformer(), $findings);
        }
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
