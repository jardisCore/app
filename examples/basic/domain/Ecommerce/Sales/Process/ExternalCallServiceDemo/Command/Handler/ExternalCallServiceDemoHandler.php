<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
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
 * Door of the process ExternalCallServiceDemo (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Externer-Call-Fixture (docs/cross-bc-process-access P4, AK2): drei
 * Externer-Call-Knoten, jeder mit eigenem Service; alle Stubs landen unter
 * {Domain}/{BC}/Service/ — CallPricingApiProcess, CallPricingApiBc,
 * CallPricingApiDomain. Jeder Knoten deklariert einen Service mit demselben
 * Namen wie der Knoten selbst (Alias-Guard-Beleg, analog CrossBcServiceDemo P3).
 */
final class ExternalCallServiceDemoHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(ExternalCallServiceDemoDefinition::class))();
        /** @var ExternalCallServiceDemo $input */
        $input = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(ExternalCallServiceDemoValidator::class))(),
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
