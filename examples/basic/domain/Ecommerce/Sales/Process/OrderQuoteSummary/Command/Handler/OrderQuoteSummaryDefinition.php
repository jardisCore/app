<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler;

use Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\Action\CollectQuoteLines;
use Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\Action\TotalQuote;
use Ecommerce\Sales\Process\OrderQuoteSummary\Command\OrderQuoteSummary;
use Ecommerce\Sales\Process\OrderQuoteSummary\Response\CollectQuoteLinesResponse;
use Ecommerce\Sales\Process\OrderQuoteSummary\Response\TotalQuoteResponse;
use JardisSupport\Workflow\Data\Answer;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process OrderQuoteSummary: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class OrderQuoteSummaryDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(OrderQuoteSummary::class, 'OrderQuoteSummary')
            ->context(OrderQuoteSummaryContext::class)
            ->transactional(true)
            ->answer(Answer::Merged)
            ->node(CollectQuoteLines::class)
            ->onTrue(TotalQuote::class)
            ->response(CollectQuoteLinesResponse::class, 'currency', 'lineCount')
            ->node(TotalQuote::class)
            ->terminal(Route::OnTrue)
            ->response(TotalQuoteResponse::class, 'lineCount', 'total')
            ->build();
    }
}
