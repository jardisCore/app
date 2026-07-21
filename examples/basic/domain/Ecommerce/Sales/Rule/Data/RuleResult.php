<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule\Data;

/**
 * Outcome of a single Rule invocation (docs/rules-layer/PRD.md A4,
 * PLAN.md Vertrag 3). Not exception-based — a rejection is a value,
 * carried through the Guard-Closure chain and surfaced as the 422
 * RuleViolation payload `{rule, messageKey, context}` — structure and
 * message key are stable API across Rule ClassVersions (PRD M5).
 */
final class RuleResult
{
    /**
     * @param array<string, mixed> $context
     */
    private function __construct(
        public readonly bool $passed,
        public readonly string $rule = '',
        public readonly string $messageKey = '',
        public readonly array $context = [],
    ) {
    }

    public static function pass(): self
    {
        return new self(true);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function reject(string $rule, string $messageKey, array $context = []): self
    {
        return new self(false, $rule, $messageKey, $context);
    }
}
