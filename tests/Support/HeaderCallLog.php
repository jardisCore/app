<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

/**
 * Test fake: records every `(string $line, bool $replace)` call an
 * EmitResponse instance makes through its injected `$sendHeader` closure -
 * used because CLI SAPI's real `header()` is a silent no-op with nothing
 * observable via `headers_list()` (see EmitResponseTest's class docblock).
 */
final class HeaderCallLog
{
    /** @var list<array{string, bool}> */
    public array $calls = [];

    public function record(string $line, bool $replace): void
    {
        $this->calls[] = [$line, $replace];
    }
}
