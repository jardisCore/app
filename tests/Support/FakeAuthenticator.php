<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use JardisSupport\Contract\Auth\AuthenticatorInterface;
use JardisSupport\Contract\Auth\AuthResultInterface;
use JardisSupport\Contract\Auth\CredentialInterface;
use JardisSupport\Contract\Auth\CredentialType;
use JardisSupport\Contract\Auth\SessionAwareAuthResultInterface;
use JardisSupport\Contract\Auth\TenantAwareAuthResultInterface;
use RuntimeException;

/**
 * Test fake for the AuthenticatorInterface port: a fixed token table
 * (token => [subject, tenant]); every other token fails with a reason.
 * Records the credentials it was asked about. A token of `boom` throws.
 *
 * A session entry may carry a third and fourth element (subjectId, session reference):
 * then the result is session-aware, otherwise it is a plain tenant-aware result.
 */
final class FakeAuthenticator implements AuthenticatorInterface
{
    /** @var list<CredentialInterface> */
    public array $seen = [];

    /**
     * @param array<string, array{0: ?string, 1: ?string, 2?: ?string, 3?: ?string}> $sessions token => [subject, tenant, subjectId, session]
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

        $entry = $this->sessions[$token];

        if (array_key_exists(2, $entry)) {
            return $this->sessionResult($entry[0], $entry[1], $entry[2], $entry[3] ?? null);
        }

        return $this->result(true, $entry[0], $entry[1], null);
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

    private function sessionResult(?string $subject, ?string $tenant, ?string $subjectId, ?string $session): SessionAwareAuthResultInterface
    {
        return new class ($subject, $tenant, $subjectId, $session) implements SessionAwareAuthResultInterface {
            public function __construct(
                private readonly ?string $subject,
                private readonly ?string $tenant,
                private readonly ?string $subjectId,
                private readonly ?string $session,
            ) {
            }

            public function isSuccess(): bool
            {
                return true;
            }

            public function getSubject(): ?string
            {
                return $this->subject;
            }

            public function getReason(): ?string
            {
                return null;
            }

            public function getTenant(): ?string
            {
                return $this->tenant;
            }

            public function getSubjectId(): ?string
            {
                return $this->subjectId;
            }

            public function getSessionReference(): ?string
            {
                return $this->session;
            }
        };
    }
}
