<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\OrderLimitAfterPlace;
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
 * Door of the process OrderLimitAfterPlace (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Access L7 - Zweitfall: die Quelle `latest` ist ein COMMAND-Knoten (Vorbild
 * Event-Feld-Identitaetskette). n1 legt die Order an (K2-Arbeits-Body auf
 * Order.Order); der Rule-Knoten n2 laedt die Wurzel aus dem Identitaetsfeld
 * `orderNumber` im Ergebnis von n1 (`facade`+`method` benennen den Aufruf) und
 * prueft sie gegen das Limit aus dem Prozess-Eingang.
 */
final class OrderLimitAfterPlaceHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(OrderLimitAfterPlaceDefinition::class))();
        /** @var OrderLimitAfterPlace $input */
        $input = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(OrderLimitAfterPlaceValidator::class))(),
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
