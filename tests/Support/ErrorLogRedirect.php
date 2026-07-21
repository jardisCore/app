<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use RuntimeException;

/**
 * Test helper: redirects the `error_log` ini directive to a temporary file
 * for the duration of a test, and restores it afterwards - used to assert
 * on HandleThrowable/LogThrowable's `error_log` fallback (F9c) without
 * polluting real stderr/stdout during a test run.
 */
final class ErrorLogRedirect
{
    private string $file = '';
    private string $original = '';

    public function start(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'app_error_log_');
        if ($file === false) {
            throw new RuntimeException('Could not create a temporary file for error_log redirection.');
        }

        $this->file = $file;
        $this->original = (string) ini_get('error_log');
        ini_set('error_log', $this->file);
    }

    public function stop(): void
    {
        ini_set('error_log', $this->original);
        @unlink($this->file);
    }

    public function contents(): string
    {
        $contents = file_get_contents($this->file);

        return $contents === false ? '' : $contents;
    }
}
