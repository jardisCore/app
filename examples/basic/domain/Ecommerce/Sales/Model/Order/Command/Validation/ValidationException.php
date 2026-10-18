<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Validation;

use JardisSupport\Contract\Validation\ValidationResult;

/**
 * Thrown by generated Validate classes (Command-level field
 * validation, Repository-level entity validation) when incoming data
 * fails structural checks. The generated CommandHandler dispatch
 * catches this BEFORE the generic \Throwable branch and maps it to a
 * 400 response — a field-validation failure is a client input error
 * (400), not an unexpected internal fault (500)
 * (docs/rules-layer/PRD.md A5, PLAN.md Vertrag 4).
 *
 * The failure travels typed in `fields` — one entry per finding, the one
 * 400 form every door of the domain answers with (route guard, aggregate
 * command, process): `{field, reason, message}`. `field` is the outer name
 * (the DTO property, never the column) as a dotted path; an element of a list keeps its
 * index (`{child}[1].{field}`, the same form the route guard answers),
 * `reason` is `missing` for an absent value and `invalid` for every other
 * rule — the kind comes from the validator (ValidationResult::getKinds()), never
 * from the text — `message` is the validator text (the standard text of the reason when the
 * validator delivers none), `messageKey` the stable
 * `validation.<reason>` key (B13). The message of the exception stays
 * the JSON of the raw validator tree for backward compatibility.
 */
final class ValidationException extends \RuntimeException
{
    /** @var list<array{field: string, reason: string, message: string, messageKey: string}> */
    public readonly array $fields;

    /**
     * `$errors` is the raw validator tree. Without `$root` it is the Command-level
     * ObjectValidator tree `[dtoClass => [field => [message]]]`: the DTO's own level
     * carries no path segment, nested objects extend the path. With `$root` it is the
     * entity-level tree `[entityName => [field => [message]]]`: the entity named
     * `$root` is the aggregate root and carries no prefix, every other entity prefixes
     * its fields with its own name (a list element keeps its `[i]`).
     *
     * `$kinds` is the structurally equal tree of validator kinds
     * (ValidationResult::getKinds()): it decides `missing` / `invalid`; a finding
     * without a kind is `invalid`. Path, reason and text of every finding come from
     * ValidationResult::toFields(); this class only adds the answer vocabulary.
     *
     * @param array<array-key, mixed> $errors
     * @param array<array-key, mixed> $kinds
     */
    public function __construct(array $errors, array $kinds = [], ?string $root = null)
    {
        // A value json_encode cannot represent must never turn the 400 into a 500.
        $json = json_encode($errors, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        parent::__construct(is_string($json) ? $json : 'Validation failed');

        $fields = [];
        foreach ($errors as $name => $tree) {
            $result = new ValidationResult([$name => $tree], [$name => $kinds[$name] ?? []]);
            // Without `$root` the top level of the tree carries no prefix; with it only the root does.
            foreach ($result->toFields($root ?? (string) $name) as $finding) {
                $fields[] = self::describe($finding);
            }
        }
        $this->fields = $fields;
    }

    /**
     * Adds the answer vocabulary to one finding of ValidationResult::toFields(): the standard
     * text of the reason when the validator delivered none, and the stable `messageKey`.
     *
     * @param array{field: string, reason: string, message: string} $finding
     * @return array{field: string, reason: string, message: string, messageKey: string}
     */
    private static function describe(array $finding): array
    {
        $missing = $finding['reason'] === ValidationResult::REASON_MISSING;

        return [
            'field' => $finding['field'],
            'reason' => $finding['reason'],
            'message' => $finding['message'] !== ''
                ? $finding['message']
                : ($missing ? 'Field is required.' : 'Field is invalid.'),
            'messageKey' => $missing ? 'validation.missing' : 'validation.invalid',
        ];
    }
}
