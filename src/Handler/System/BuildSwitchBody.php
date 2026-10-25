<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\System;

/**
 * Builds the JSON body of the tenant switch: `{"<tenantField>": "<tenant>"}`.
 */
final class BuildSwitchBody
{
    public function __invoke(string $tenantField, string $tenant): string
    {
        return json_encode([$tenantField => $tenant], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
