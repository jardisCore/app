<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler;

use Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\Action\CollectQuoteLines;
use Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\Action\TotalQuote;
use Ecommerce\Sales\Process\OrderQuoteSummary\Command\OrderQuoteSummary;
use Ecommerce\Sales\Process\OrderQuoteSummary\Response\CollectQuoteLinesResponse;
use Ecommerce\Sales\Process\OrderQuoteSummary\Response\TotalQuoteResponse;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process OrderQuoteSummary: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class OrderQuoteSummaryContext extends ProcessContext
{
    public function input(): OrderQuoteSummary
    {
        /** @var OrderQuoteSummary $input */
        $input = parent::input();

        return $input;
    }

    public function collectQuoteLines(): ?CollectQuoteLinesResponse
    {
        $data = $this->latest(CollectQuoteLines::class)?->data();

        return $data instanceof CollectQuoteLinesResponse ? $data : null;
    }

    public function totalQuote(): ?TotalQuoteResponse
    {
        $data = $this->latest(TotalQuote::class)?->data();

        return $data instanceof TotalQuoteResponse ? $data : null;
    }
}
