<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
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
 * Door of the process RuleGuardedOrderIntake (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Rule-Knoten-/Vertrag-5-Fixture (docs/rules-layer/PLAN.md P5, A6;
 * Set-Knoten-Golden seit rule-sets PLAN.md E1-P2, S9):
 * demonstriert BEIDE Rules-Layer-Einsatzorte im selben Prozess.
 *
 * (1) Ein Rule-Knoten prueft frueh im Flow (vor teurer Arbeit, PRD §5
 * Frage 6) das Rule-Set OrderIntakeChecks (Mitglieder StockAvailable,
 * OrderEligibleForProcessing) — DASSELBE Set, das auch die Order-Kette
 * bindet (Rules.json) — AND-Kette mit Kurzschluss ueber den generierten
 * Adapter (alle Mitglieder passed -> onSuccess, erstes rejected ->
 * onFail mit additivem context.set).
 *
 * (2) Erst danach ruft ein Custom-Knoten (Arbeits-Body, K2) den
 * Order-Aggregat-Command Order (createOrder) auf, dessen EIGENE
 * Rules.json-Kette (OrderIntakeChecks, OrderWithinCreditLimit) real
 * 422/500 liefern kann — Vertrag 5: 422 -> onFail (gemalte Kante), 5xx ->
 * der Exception-Pfad der Engine (keine onFail-Kante, kein stiller
 * Erfolg).
 */
final class RuleGuardedOrderIntakeHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(RuleGuardedOrderIntakeDefinition::class))();
        /** @var RuleGuardedOrderIntake $input */
        $input = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(RuleGuardedOrderIntakeValidator::class))(),
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
