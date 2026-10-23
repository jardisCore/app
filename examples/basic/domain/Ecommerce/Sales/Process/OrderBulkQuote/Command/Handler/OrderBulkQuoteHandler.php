<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderBulkQuote\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderBulkQuote\Command\OrderBulkQuote;
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
 * Door of the process OrderBulkQuote (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Strukturierter Prozess-Eingang (FIX3, R942): eine Skalarliste, eine
 * nullbare Werteliste-Liste, ein Objekt und eine Objektliste, deren Elemente
 * ein nullbares Objekt (Ebene 2) und eine nullbare Skalarliste tragen. Jedes
 * Element ist typisiert; ein falsches Element antwortet 400 mit seinem
 * Index-Pfad, nie 500.
 */
final class OrderBulkQuoteHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(OrderBulkQuoteDefinition::class))();
        /** @var OrderBulkQuote $input */
        $input = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(OrderBulkQuoteValidator::class))(),
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
