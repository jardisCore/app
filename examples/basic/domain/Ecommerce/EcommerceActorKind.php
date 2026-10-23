<?php

declare(strict_types=1);

namespace Ecommerce;

/**
 * EcommerceActorKind: the kind of actor of one run of the Ecommerce domain.
 *
 * anonymous - no session; user - a signed-in subject; system - an entry outside HTTP
 * (CLI, queue, cron). Hermetic - the kind is data, never compared by generated code.
 */
enum EcommerceActorKind: string
{
    case Anonymous = 'anonymous';
    case User = 'user';
    case System = 'system';
}
