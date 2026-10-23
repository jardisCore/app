<?php

declare(strict_types=1);

namespace Ecommerce;

/**
 * EcommerceActor: the actor of one run of the Ecommerce domain (V1).
 *
 * Built once per request by routes.php (anonymous or user), by a CLI, queue or cron entry
 * with system(name); carried by the domain context, inherited by every sub-run. Data only.
 */
final readonly class EcommerceActor
{
    /**
     * @param list<string> $rights rights of the role: 'resource:action', 'resource:*' or '*'
     */
    private function __construct(
        public EcommerceActorKind $kind,
        public string $id,
        public string $tenant,
        public string $role,
        public array $rights,
    ) {
    }

    /**
     * No session: the empty values.
     */
    public static function anonymous(): self
    {
        return new self(EcommerceActorKind::Anonymous, '', '', '', []);
    }

    /**
     * A signed-in subject: its id, tenant ('' = none), role and the rights of that role.
     *
     * @param list<string> $rights
     */
    public static function user(string $id, string $tenant, string $role, array $rights): self
    {
        return new self(EcommerceActorKind::User, $id, $tenant, $role, $rights);
    }

    /**
     * An entry outside HTTP (CLI, queue, cron): named by the entry, role 'system', every right.
     */
    public static function system(string $name): self
    {
        return new self(EcommerceActorKind::System, $name, '', 'system', ['*']);
    }
}
