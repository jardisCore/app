<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler;

use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\CallPricingApiBc;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\CallPricingApiDomain;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\CallPricingApiProcess;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action\ExternalCallServiceDemoFinalize;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process ExternalCallServiceDemo: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class ExternalCallServiceDemoDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(ExternalCallServiceDemo::class, 'ExternalCallServiceDemo')
            ->context(ExternalCallServiceDemoContext::class)
            ->transactional(true)
            ->validate(ExternalCallServiceDemoValidator::class)
            ->node(CallPricingApiProcess::class)
            ->onTrue(CallPricingApiBc::class)
            ->node(CallPricingApiBc::class)
            ->onTrue(CallPricingApiDomain::class)
            ->node(CallPricingApiDomain::class)
            ->onTrue(ExternalCallServiceDemoFinalize::class)
            ->node(ExternalCallServiceDemoFinalize::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}
