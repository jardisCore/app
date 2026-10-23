<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherRedeemCheck\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\VoucherRedeemCheck\Command\VoucherRedeemCheck;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process VoucherRedeemCheck (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Request-Attribut-Feld an der Prozess-Tuer: nur `session` (Sitzungsreferenz) kommt als Prozess-Input aus dem
 * Request-Attribut, nie vom Client - es fehlt im Body und im OpenAPI-Request. Mandant und Akteur traegt der
 * Domain-Akteur (`$this->actor()`), kein Prozess-Input deklariert sie. Der Rule-Knoten laedt die mandanten-gescopte
 * Voucher-Wurzel ueber LoadVoucherForRule im Mandanten des Akteurs; der Akteur ist nur Wert, nie Lesefilter (B4).
 */
final class VoucherRedeemCheckHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(VoucherRedeemCheckDefinition::class))();
        /** @var VoucherRedeemCheck $input */
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
