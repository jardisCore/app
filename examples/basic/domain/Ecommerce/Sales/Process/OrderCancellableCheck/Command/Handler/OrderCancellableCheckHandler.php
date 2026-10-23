<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderCancellableCheck\Command\OrderCancellableCheck;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process OrderCancellableCheck (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Kleinste Fixture-Ergaenzung fuer den notFound-Beweis am Rule-Knoten
 * (rules-neuverortung PRD I1/Sec8.3, PLAN.md Phase 1.3 Zusage 6): ein
 * eigener, winziger Prozess statt eines zweiten Knotens in
 * RuleGuardedOrderIntake, um die vier bestehenden RuleGuardedOrderIntakeTest-
 * Faelle nicht anzufassen. Der einzige Knoten referenziert die
 * `aggregate`-subject Katalog-Rule OrderIsCancellable (Einsatz, zusaetzlich
 * zu ihrem Wirkort an UpdateOrder, M7 Doppelbindung) mit einem skalaren
 * Payload-Feld als Wurzel-Identifier. Existiert der Order nicht, laedt der
 * emittierte Load-Closure null -> ON_FAIL mit dem festen
 * `{bc}.{agg}.notFound`-messageKey (Q6), nie eine echte Rule-Ablehnung.
 */
final class OrderCancellableCheckHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(OrderCancellableCheckDefinition::class))();
        /** @var OrderCancellableCheck $input */
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
