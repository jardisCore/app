<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderQuoteSummary\Command\OrderQuoteSummary;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process OrderQuoteSummary (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Antwortform `merged` (tuer-und-response E4 P5b): zwei Action-Knoten, die
 * Antwort sind die Daten ALLER Knoten in Laufreihenfolge, bei gleichem Feldnamen
 * gewinnt der spaetere. n1 liefert `currency` und `lineCount`, n2 liefert
 * `lineCount` (ueberschreibt n1) und `total`. Kein `successOutput`.
 */
final class OrderQuoteSummaryHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(OrderQuoteSummaryDefinition::class))();
        /** @var OrderQuoteSummary $input */
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
