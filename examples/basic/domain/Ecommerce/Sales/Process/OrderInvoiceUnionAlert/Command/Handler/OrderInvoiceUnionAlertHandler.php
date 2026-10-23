<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\OrderInvoiceUnionAlert;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process OrderInvoiceUnionAlert (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Belegt AK2 (Event-Feld-Bindung, Union-Quelle, PRD
 * docs/process-event-fields/PRD.md §5) am End-to-End-Golden-Pfad:
 * subjectRemoval referenziert Order.RemoveOrder | Invoice.RemoveInvoice —
 * zwei Root-Ops verschiedener Aggregate derselben BC mit gleicher
 * Identitaets-Kettenlaenge (Aritaet 1, V-EVT-ARITY-konform). Der
 * Event-Feld-Resolver loest je Zweig die eigene Root-Identitaet auf
 * (match(true)+instanceof, P2 Renderer + P4.5 Multi-Aggregat-Config-Fix).
 */
final class OrderInvoiceUnionAlertHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(OrderInvoiceUnionAlertDefinition::class))();
        /** @var OrderInvoiceUnionAlert $input */
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
