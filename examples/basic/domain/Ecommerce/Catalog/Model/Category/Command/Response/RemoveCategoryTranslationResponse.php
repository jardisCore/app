<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Response;

/**
 * Response contract of the RemoveCategoryTranslation command.
 *
 * Carries the reference values the command handler writes into
 * DomainResponse::setData() — the aggregate's public identifier plus the
 * affected child reference. Never a state copy: the caller reads the new
 * state back through the read facade.
 */
class RemoveCategoryTranslationResponse
{
    public string $categoryIdentifier = '';
}
