<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Handler\Request\ReadRequestAttributeList;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The list reader: absent -> `[]`, a list of strings -> the list, anything else -> `null` ("unusable"),
 * never an exception. Requests are real nyholm server requests.
 */
final class ReadRequestAttributeListTest extends TestCase
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
        $read = new ReadRequestAttributeList();

        $this->assertSame([], $read($this->request(), 'jardisRights'));
    }

    public function testListOfStringsIsReturnedAsIs(): void
    {
        $read = new ReadRequestAttributeList();

        $this->assertSame(
            ['sales.order:read', 'sales.order:*', '*'],
            $read($this->request(jardisRights: ['sales.order:read', 'sales.order:*', '*']), 'jardisRights'),
        );
        $this->assertSame([], $read($this->request(jardisRights: []), 'jardisRights'));
    }

    public function testStringInsteadOfListIsUnusable(): void
    {
        $read = new ReadRequestAttributeList();

        $this->assertNull($read($this->request(jardisRights: 'sales.order:read'), 'jardisRights'));
        $this->assertNull($read($this->request(jardisRights: ''), 'jardisRights'));
        $this->assertNull($read($this->request(jardisRights: 42), 'jardisRights'));
    }

    public function testListWithANonStringElementIsUnusable(): void
    {
        $read = new ReadRequestAttributeList();

        foreach ([['a', 1], ['a', null], ['a', ['b']], [true]] as $value) {
            $this->assertNull($read($this->request(jardisRights: $value), 'jardisRights'));
        }
    }

    public function testAssociativeArrayIsUnusable(): void
    {
        $read = new ReadRequestAttributeList();

        $this->assertNull($read($this->request(jardisRights: ['right' => 'sales.order:read']), 'jardisRights'));
        $this->assertNull($read($this->request(jardisRights: [1 => 'a', 2 => 'b']), 'jardisRights'));
    }
}
