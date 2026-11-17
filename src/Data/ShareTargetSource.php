<?php

declare(strict_types=1);

namespace JardisCore\App\Data;

/**
 * Where {@see \JardisCore\App\Middleware\ResolveShareTargets} reads the share targets of a request: the header
 * `X-Jardis-Share-With` (create with "share with") or the body fields `groups` / `person` / `level` (share).
 */
enum ShareTargetSource
{
    case Header;
    case Body;
}
