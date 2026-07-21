<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Exception;

use JardisCore\App\Exception\AppException;
use JardisCore\App\Exception\InvalidJsonBody;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the exception hierarchy (AppException base + InvalidJsonBody).
 */
final class InvalidJsonBodyTest extends TestCase
{
    public function testExtendsAppExceptionAndRuntimeException(): void
    {
        $exception = new InvalidJsonBody('bad body');

        $this->assertInstanceOf(AppException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('bad body', $exception->getMessage());
    }

    public function testCarriesPreviousException(): void
    {
        $previous = new RuntimeException('syntax error');
        $exception = new InvalidJsonBody('bad body', previous: $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
