<?php

declare(strict_types=1);

namespace ExampleApp\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * F6-Architektur-Regel (PRD §5, PLAN P7 Block C): the example app's route
 * handlers (`examples/basic/app/`) may only reach the generated domain
 * through its legitimate outer doors — a BC facade (`Sales`), a read
 * facade (`OrderRead`), the process facade (`SalesProcess`) plus its
 * process DTOs, or a Command/Query DTO. Anything that reaches PAST the
 * outer door — the aggregate WRITE facade (`Order`), a Command/Query
 * Handler, a Repository, or a Rule class — is flagged.
 *
 * Mechanism (deliberately the simplest tragfähige option, PLAN P7 Block C):
 * a custom PHPStan rule inspecting `use` import statements textually
 * against a small deny-list of `Ecommerce\...` namespace shapes. This
 * needs no Scope-based type resolution (so it works even against the
 * negative fixture, which is intentionally outside the Composer
 * autoload map — PHPStan can still parse and match import names without
 * autoloading the referenced class) and needs no extra Composer
 * dependency (ruled out `forbiddenFunctionCalls`-style config: PHPStan
 * core has no stock "forbidden class" check; a third-party rule package
 * would add a dependency for a check this small rule expresses directly).
 *
 * Known, accepted scope limit: only catches `use`-imported references,
 * not a fully-qualified inline `new \Ecommerce\...\Order(...)` written
 * without a `use` statement. Every real route handler in this example
 * (and the negative fixture demonstrating the bypass) imports via `use`,
 * so this covers the demonstrated case; a stricter rule (also matching
 * `Node\Expr\New_`/`Node\Expr\StaticCall` inline names) would be the next
 * step if this project ever needs to harden further.
 *
 * @implements Rule<Use_>
 */
final class ForbidDomainInternalsRule implements Rule
{
    /**
     * Denies reaching PAST the outer door into generated-domain internals:
     * Command/Query Handlers, Repositories, Rule classes, and the
     * aggregate WRITE facade itself (`Ecommerce\{Bc}\Aggregate\{Agg}\{Agg}`
     * — the write facade class is always named identically to its
     * enclosing `Aggregate\{Agg}\` segment, e.g.
     * `Ecommerce\Sales\Aggregate\Order\Order`).
     *
     * @var list<string>
     */
    private const DENY_PATTERNS = [
        '/^Ecommerce\\\\.*\\\\Command\\\\Handler\\\\/',
        '/^Ecommerce\\\\.*\\\\Query\\\\Handler\\\\/',
        '/^Ecommerce\\\\.*\\\\Repository\\\\/',
        '/^Ecommerce\\\\.*\\\\Rule\\\\/',
        '/^Ecommerce\\\\.*\\\\Entity\\\\/',
        '/^Ecommerce\\\\[^\\\\]+\\\\Aggregate\\\\([^\\\\]+)\\\\\1$/',
    ];

    public function getNodeType(): string
    {
        return Use_::class;
    }

    /**
     * @param Use_ $node
     * @return list<\PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->uses as $use) {
            if (!$use instanceof UseUse) {
                continue;
            }

            $fqcn = $use->name->toString();

            foreach (self::DENY_PATTERNS as $pattern) {
                if (preg_match($pattern, $fqcn) === 1) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'F6: handler code may only use BC facades, DTOs and the App-Layer API — '
                            . '"%s" reaches past the outer door into generated-domain internals '
                            . '(aggregate write facade / Command-Handler / Query-Handler / Repository / Rule).',
                        $fqcn,
                    ))->identifier('jardis.f6OuterDoorOnly')->build();

                    break;
                }
            }
        }

        return $errors;
    }
}
