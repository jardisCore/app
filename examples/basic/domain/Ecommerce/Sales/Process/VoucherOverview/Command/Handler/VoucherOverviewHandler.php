<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherOverview\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\VoucherOverview\Command\VoucherOverview;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process VoucherOverview (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Antwort mit den Zeilen einer Query in Aussenform (akteur-queries E3-P5, R1058
 * Variante 2): das successOutput-Feld `vouchers` traegt `rowsOf: voucherDigestList`.
 * Die Tuer schneidet jede Zeile auf die Aussenform der Query (fachlicher Schluessel
 * statt id, keine writeOnly-Spalte), auch wenn der Knoten mehr liefert.
 */
final class VoucherOverviewHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(VoucherOverviewDefinition::class))();
        /** @var VoucherOverview $input */
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
