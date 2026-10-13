<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use JardisSupport\Contract\Auth\AuthenticatorInterface;
use JardisSupport\Contract\Auth\AuthResultInterface;
use JardisSupport\Contract\Auth\CredentialInterface;
use JardisSupport\Contract\Auth\CredentialType;
use JardisSupport\Contract\Auth\TenantAwareAuthResultInterface;
use RuntimeException;

/**
 * Test fake for the AuthenticatorInterface port: a fixed token table
 * (token => [subject, tenant]); every other token fails with a reason.
 * Records the credentials it was asked about. A token of `boom` throws.
 */
final class FakeAuthenticator implements AuthenticatorInterface
{
    /** @var list<CredentialInterface> */
    public array $seen = [];

    /**
     * @param array<string, array{0: ?string, 1: ?string}> $sessions token => [subject, tenant]
     */
    public function __construct(private readonly array $sessions)
    {
    }

    public function authenticate(CredentialInterface $credential): AuthResultInterface
    {
        $this->seen[] = $credential;
        $token = $credential->getValue();

        if ($token === 'boom') {
            throw new RuntimeException('token store unreachable');
        }

        if (!isset($this->sessions[$token])) {
            return $this->result(false, null, null, 'revoked');
        }

        [$subject, $tenant] = $this->sessions[$token];

        return $this->result(true, $subject, $tenant, null);
    }

    public static function credentialFor(string $token): CredentialInterface
    {
        return new class ($token) implements CredentialInterface {
            public function __construct(private readonly string $token)
            {
            }

            public function getType(): CredentialType
            {
                return CredentialType::Token;
            }

            public function getValue(): string
            {
                return $this->token;
            }

            public function getIdentifier(): string
            {
                return $this->token;
            }
        };
    }

    private function result(bool $success, ?string $subject, ?string $tenant, ?string $reason): TenantAwareAuthResultInterface
    {
        return new class ($success, $subject, $tenant, $reason) implements TenantAwareAuthResultInterface {
            public function __construct(
                private readonly bool $success,
                private readonly ?string $subject,
                private readonly ?string $tenant,
                private readonly ?string $reason,
            ) {
            }

            public function isSuccess(): bool
            {
                return $this->success;
            }

            public function getSubject(): ?string
            {
                return $this->subject;
            }

            public function getReason(): ?string
            {
                return $this->reason;
            }

            public function getTenant(): ?string
            {
                return $this->tenant;
            }
        };
    }
}
