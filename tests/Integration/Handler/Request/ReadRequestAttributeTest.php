<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Config\AppConfig;
use JardisCore\App\Exception\RequestAttributeMissing;
use JardisCore\App\Handler\Error\HandleThrowable;
use JardisCore\App\Handler\Request\ReadRequestAttribute;
use JardisCore\App\Handler\Request\ReadRequestAttributeStrict;
use JardisCore\App\Tests\Support\ErrorLogRedirect;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Integration tests for the two request-attribute readers: the lenient one
 * (`''` when absent / not a string) and the strict one (fails closed, the error
 * boundary answers the generic 500). Requests are real nyholm server requests.
 */
final class ReadRequestAttributeTest extends TestCase
{
    private ErrorLogRedirect $errorLog;

    protected function setUp(): void
    {
        $this->errorLog = new ErrorLogRedirect();
        $this->errorLog->start();
    }

    protected function tearDown(): void
    {
        $this->errorLog->stop();
    }

    private function request(mixed ...$attributes): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/projects');
        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute((string) $name, $value);
        }

        return $request;
    }

    public function testLenientReaderReturnsTheStringAttribute(): void
    {
        $read = new ReadRequestAttribute();

        $this->assertSame('t-1', $read($this->request(jardisTenant: 't-1'), 'jardisTenant'));
    }

    public function testLenientReaderReturnsEmptyStringForAbsentAttribute(): void
    {
        $read = new ReadRequestAttribute();

        $this->assertSame('', $read($this->request(), 'jardisTenant'));
    }

    public function testLenientReaderReturnsEmptyStringForAttributeThatIsNotAString(): void
    {
        $read = new ReadRequestAttribute();

        foreach ([42, true, null, ['a'], 1.5] as $value) {
            $this->assertSame('', $read($this->request(jardisTenant: $value), 'jardisTenant'));
        }
    }

    public function testLenientReaderPassesAnEmptyStringThrough(): void
    {
        $read = new ReadRequestAttribute();

        $this->assertSame('', $read($this->request(jardisTenant: ''), 'jardisTenant'));
    }

    public function testStrictReaderReturnsTheStringAttribute(): void
    {
        $read = new ReadRequestAttributeStrict();

        $this->assertSame('t-1', $read($this->request(jardisTenant: 't-1'), 'jardisTenant'));
    }

    public function testStrictReaderFailsWithTheExactMessageWhenAbsentEmptyOrNotAString(): void
    {
        $read = new ReadRequestAttributeStrict();

        foreach ([[], ['jardisTenant' => ''], ['jardisTenant' => 42], ['jardisTenant' => null]] as $attributes) {
            try {
                $read($this->request(...$attributes), 'jardisTenant');
                $this->fail('A missing attribute must throw.');
            } catch (RuntimeException $e) {
                $this->assertInstanceOf(RequestAttributeMissing::class, $e);
                $this->assertSame(
                    'Request attribute jardisTenant is not set - the scoped route needs the session middleware.',
                    $e->getMessage(),
                );
            }
        }
    }

    public function testStrictFailureIsAnsweredAsTheGeneric500ByTheErrorBoundary(): void
    {
        $factory = new Psr17Factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());
        $read = new ReadRequestAttributeStrict();

        $request = $factory->createServerRequest('GET', '/p');

        $response = $handle(
            static function () use ($read, $request, $factory) {
                return $factory->createResponse(200)->withBody($factory->createStream($read($request, 'jardisTenant')));
            },
            $request,
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('jardisTenant', (string) $response->getBody());
    }
}
