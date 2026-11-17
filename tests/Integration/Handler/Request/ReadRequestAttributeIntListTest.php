<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Handler\Request\ReadRequestAttributeIntList;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The integer list reader: absent -> `[]`, a list of integers -> the list, anything else -> `null` ("unusable"),
 * never an exception. Requests are real nyholm server requests.
 */
final class ReadRequestAttributeIntListTest extends TestCase
{
    private function request(mixed ...$attributes): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/projects');
        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute((string) $name, $value);
        }

        return $request;
    }

    public function testAbsentAttributeReadsAsEmptyList(): void
    {
        $read = new ReadRequestAttributeIntList();

        $this->assertSame([], $read($this->request(), 'jardisGroups'));
    }

    public function testListOfIntegersIsReturnedAsIs(): void
    {
        $read = new ReadRequestAttributeIntList();

        $this->assertSame([7, 12, 3], $read($this->request(jardisGroups: [7, 12, 3]), 'jardisGroups'));
        $this->assertSame([], $read($this->request(jardisGroups: []), 'jardisGroups'));
    }

    public function testScalarInsteadOfListIsUnusable(): void
    {
        $read = new ReadRequestAttributeIntList();

        foreach (['7', '', 7, true, 1.5] as $value) {
            $this->assertNull($read($this->request(jardisGroups: $value), 'jardisGroups'), var_export($value, true));
        }
    }

    public function testListWithANonIntegerElementIsUnusable(): void
    {
        $read = new ReadRequestAttributeIntList();

        foreach ([[7, '8'], [7, null], [7, 1.5], [7, [8]], [true], ['a']] as $value) {
            $this->assertNull($read($this->request(jardisGroups: $value), 'jardisGroups'), var_export($value, true));
        }
    }

    public function testAssociativeArrayIsUnusable(): void
    {
        $read = new ReadRequestAttributeIntList();

        $this->assertNull($read($this->request(jardisGroups: ['group' => 7]), 'jardisGroups'));
        $this->assertNull($read($this->request(jardisGroups: [1 => 7, 2 => 8]), 'jardisGroups'));
    }
}
