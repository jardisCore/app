<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use JardisSupport\Contract\Auth\HashedTokenInterface;
use JardisSupport\Contract\Auth\TokenStoreInterface;

/**
 * Test fake for the TokenStoreInterface port: keeps hashed tokens in memory.
 */
final class InMemoryTokenStore implements TokenStoreInterface
{
    /** @var array<string, HashedTokenInterface> */
    private array $tokens = [];

    public function store(HashedTokenInterface $token): void
    {
        $this->tokens[$token->getHash()] = $token;
    }

    public function find(string $hash): ?HashedTokenInterface
    {
        return $this->tokens[$hash] ?? null;
    }

    public function revoke(string $hash): void
    {
        unset($this->tokens[$hash]);
    }

    public function revokeAllForSubject(string $subject): void
    {
        foreach ($this->tokens as $hash => $token) {
            if ($token->getSubject() === $subject) {
                unset($this->tokens[$hash]);
            }
        }
    }

    public function deleteExpired(): int
    {
        $removed = 0;
        foreach ($this->tokens as $hash => $token) {
            if ($token->isExpired()) {
                unset($this->tokens[$hash]);
                $removed++;
            }
        }

        return $removed;
    }
}
