<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Handler\Request\ReadRequestAttributeInt;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * The integer reader: an integer -> the integer, absent or anything else -> `null`, never an exception.
 */
final class ReadRequestAttributeIntTest extends TestCase
{
    public function testIntegerIsReturnedAndEverythingElseIsNull(): void
    {
        $read = new ReadRequestAttributeInt();
        $request = (new Psr17Factory())->createServerRequest('GET', '/projects');

        $this->assertSame(12, $read($request->withAttribute('jardisPersonalGroup', 12), 'jardisPersonalGroup'));
        $this->assertNull($read($request, 'jardisPersonalGroup'));
        foreach (['12', '', 1.5, true, [12]] as $value) {
            $this->assertNull(
                $read($request->withAttribute('jardisPersonalGroup', $value), 'jardisPersonalGroup'),
                var_export($value, true),
            );
        }
    }
}
