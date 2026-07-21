<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

/**
 * Test fake: records a call sequence as an ordered list of labels - used to
 * prove pipeline/middleware execution order (E9) in both request and
 * response direction.
 */
final class CallLog
{
    /** @var list<string> */
    public array $entries = [];

    public function record(string $entry): void
    {
        $this->entries[] = $entry;
    }
}
