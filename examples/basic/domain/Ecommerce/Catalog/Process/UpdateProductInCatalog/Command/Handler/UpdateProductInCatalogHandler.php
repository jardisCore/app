<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler;

use Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\UpdateProductInCatalog;
use Ecommerce\EcommerceContext;
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
 * Door of the process UpdateProductInCatalog (hermetic): input check, run in its bracket (RunProcess), answer.
 *
 * Prozess-Ziel für Fremd-Writes auf Ecommerce.Catalog.Product
 * (bc-facade-layering PRD.md G7, P3): kapselt den Aggregat-Command
 * UpdateProduct hinter einem Prozess, damit CrossBcServiceDemo
 * (Ecommerce/Sales) über process() statt direkt über das fremde Aggregat
 * schreibt. Minimal autoriert (P3) — die echte DTO-Übersetzung im
 * Demo-Service (CheckStockInCatalog) folgt P4.
 */
final class UpdateProductInCatalogHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(UpdateProductInCatalogDefinition::class))();
        /** @var UpdateProductInCatalog $input */
        $input = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateProductInCatalogValidator::class))(),
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
