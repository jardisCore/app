<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler;

use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\CallPricingApiBc;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\CallPricingApiDomain;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\CallPricingApiProcess;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\ExternalCallServiceDemoFinalize;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process ExternalCallServiceDemo: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class ExternalCallServiceDemoContext extends ProcessContext
{
    public function input(): ExternalCallServiceDemo
    {
        /** @var ExternalCallServiceDemo $input */
        $input = parent::input();

        return $input;
    }

    public function callPricingApiProcess(): ?NodeResult
    {
        return $this->latest(CallPricingApiProcess::class);
    }

    public function callPricingApiBc(): ?NodeResult
    {
        return $this->latest(CallPricingApiBc::class);
    }

    public function callPricingApiDomain(): ?NodeResult
    {
        return $this->latest(CallPricingApiDomain::class);
    }

    public function externalCallServiceDemoFinalize(): ?NodeResult
    {
        return $this->latest(ExternalCallServiceDemoFinalize::class);
    }
}
