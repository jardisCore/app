<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\OrderLimitCheck;
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
 * Door of the process OrderLimitCheck (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Access L7 - Rule-Knoten belegt JEDEN Eingang der Closure (`ruleArgs`):
 * Knoten n1 (handgeschrieben, `outputShape` mit `orderNumber`) loest die
 * Bestellnummer auf; der Rule-Knoten n2 ruft OrderTotalWithinLimit(root:
 * aggregate Order, limit: scalar decimal) - die Wurzel wird aus dem Ergebnis
 * von n1 geladen (`latest`, Feld `orderNumber`), das Limit kommt aus dem
 * Prozess-Eingang (`input`). Existiert die Order nicht -> ON_FAIL notFound,
 * ueberschreitet sie das Limit -> ON_FAIL 422 `sales.orderOverLimit`.
 */
final class OrderLimitCheckHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(OrderLimitCheckDefinition::class))();
        /** @var OrderLimitCheck $input */
        $input = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(OrderLimitCheckValidator::class))(),
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
