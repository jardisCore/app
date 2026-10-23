<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\CrossBcReadDemo;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process CrossBcReadDemo (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Cross-BC-Lese-Fixture (crm-nachlauf E3.9): ein Lese-Knoten mit consumedCalls
 * auf die Lese-Fassade einer fremden BC (Ecommerce.Sales -> Ecommerce.Catalog)
 * OHNE ausdruecklichen crossBcCall. Die Knoten-Art ist BC-bewusst: der Knoten ist
 * ein Cross-BC-Knoten, sein Service-Name wird aus dem Ziel abgeleitet (Stub unter {BC}/Service/), die
 * Action importiert nichts aus Catalog und delegiert ueber die Kernel-Naht an das
 * Service-Scaffold, das die Lese-Fassade des fremden BC verdrahtet.
 */
final class CrossBcReadDemoHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(CrossBcReadDemoDefinition::class))();
        /** @var CrossBcReadDemo $input */
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
