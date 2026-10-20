<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use JardisSupport\Response\Data\InputFindings;
use JardisSupport\Response\Handler\Failure\DescribeFieldFinding;

/**
 * Turns the findings the request readers collected (`field`, `reason`) into the findings of the 400
 * answer ({@see InputFindings} of `jardissupport/response`): each gets its text and its message key.
 *
 * The vocabulary of `missing` and the fallback is that of `response` ({@see DescribeFieldFinding});
 * the two reasons only a request reader knows - `type` and `unknown` - bring their own text and the
 * key `validation.<reason>`. A text map may be handed in; the default is the standard vocabulary.
 *
 * Atomic closure - one entry point.
 */
final class BuildRequestFindings
{
    public const string REASON_MISSING = DescribeFieldFinding::REASON_MISSING;
    public const string REASON_TYPE = 'type';
    public const string REASON_UNKNOWN = 'unknown';

    public const array TEXTS = [
        self::REASON_TYPE => 'Field has an invalid type.',
        self::REASON_UNKNOWN => 'Unknown field',
    ];

    /**
     * @param array<string, string> $texts the texts of the reasons `response` does not know
     */
    public function __construct(
        private readonly array $texts = self::TEXTS,
        private readonly DescribeFieldFinding $describe = new DescribeFieldFinding(),
    ) {
    }

    /**
     * @param list<array{field: string, reason: string}> $fields
     */
    public function __invoke(array $fields): InputFindings
    {
        $described = [];
        foreach ($fields as $finding) {
            $text = $this->texts[$finding['reason']] ?? null;
            $entry = ($this->describe)($finding + ['message' => $text ?? '']);
            if ($text !== null) {
                $entry['messageKey'] = DescribeFieldFinding::KEY_PREFIX . '.' . $finding['reason'];
            }
            $described[] = $entry;
        }

        return new InputFindings($described, []);
    }
}
