<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\CallLog;
use JardisCore\App\Tests\Support\DomainResponse;
use JardisCore\App\Tests\Support\FixedResponseHandler;
use JardisCore\App\Tests\Support\LoggerSpy;
use JardisCore\App\Tests\Support\OrderRecordingMiddleware;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * End-to-end tests for the App orchestrator (E5, P6): the full wiring from
 * a Routes collector through Router/pipeline/mapper/error-boundary, using
 * the real collaborator classes throughout (only the DomainKernel's
 * optional services are test fakes, and only where a case needs one -
 * this is the P6 recipient of the F9/F8a/E6/E10 AK-Matrix rows).
 */
final class AppTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function kernel(?LoggerInterface $logger = null): DomainKernel
    {
        return new DomainKernel(domainRoot: '/tmp/jardiscore-app-test', logger: $logger);
    }

    private function request(string $method, string $path): ServerRequestInterface
    {
        return $this->factory->createServerRequest($method, $path);
    }

    public function testHandleIsAPureFunctionAcrossRepeatedCallsOnTheSameInstance(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/echo/{value}', static function (ServerRequestInterface $request) {
            $factory = new Psr17Factory();
            $response = $factory->createResponse(200);
            $response->getBody()->write((string) $request->getAttribute('value'));

            return $response;
        });
        $app = new App($routes, $this->kernel(), new AppConfig());

        $first = $app->handle($this->request('GET', '/echo/first'));
        $second = $app->handle($this->request('GET', '/echo/second'));
        $firstAgain = $app->handle($this->request('GET', '/echo/first'));

        $this->assertSame('first', (string) $first->getBody());
        $this->assertSame('second', (string) $second->getBody());
        $this->assertSame('first', (string) $firstAgain->getBody());
    }

    public function testHandleRebuildsTheMiddlewareChainFreshForEachRequestViaMarkerMiddleware(): void
    {
        $log = new CallLog();
        $routes = new Routes($this->factory);
        $routes->middleware(new OrderRecordingMiddleware($log, 'global'));
        $routes->get('/echo/{value}', static function (ServerRequestInterface $request) use ($log) {
            $log->record('handler:' . $request->getAttribute('value'));
            $factory = new Psr17Factory();
            $response = $factory->createResponse(200);
            $response->getBody()->write((string) $request->getAttribute('value'));

            return $response;
        });
        $app = new App($routes, $this->kernel(), new AppConfig());

        $app->handle($this->request('GET', '/echo/alpha'));
        $app->handle($this->request('GET', '/echo/beta'));

        $this->assertSame([
            'global:request',
            'handler:alpha',
            'global:response',
            'global:request',
            'handler:beta',
            'global:response',
        ], $log->entries);
    }

    public function testPathParametersAreAvailableAsRequestAttributesEndToEnd(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders/{id}', static function (ServerRequestInterface $request) {
            $factory = new Psr17Factory();
            $response = $factory->createResponse(200);
            $response->getBody()->write('order:' . $request->getAttribute('id'));

            return $response;
        });
        $app = new App($routes, $this->kernel(), new AppConfig());

        $response = $app->handle($this->request('GET', '/orders/42'));

        $this->assertSame('order:42', (string) $response->getBody());
    }

    public function testHealthEndpointRespondsViaTheRealApp(): void
    {
        $routes = new Routes($this->factory);
        $routes->health('/health');
        $app = new App($routes, $this->kernel(), new AppConfig());

        $response = $app->handle($this->request('GET', '/health'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"status":200}', (string) $response->getBody());
    }

    public function testUnknownRouteRespondsWithFourOhFourEnvelope(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders', static fn () => null);
        $app = new App($routes, $this->kernel(), new AppConfig());

        $response = $app->handle($this->request('GET', '/unknown'));

        $this->assertSame(404, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(404, $body['status']);
    }

    public function testUnsupportedMethodRespondsWithFourOhFiveAndAllowHeader(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders', static fn () => null);
        $routes->post('/orders', static fn () => null);
        $app = new App($routes, $this->kernel(), new AppConfig());

        $response = $app->handle($this->request('DELETE', '/orders'));

        $this->assertSame(405, $response->getStatusCode());
        $allow = array_map('trim', explode(',', $response->getHeaderLine('Allow')));
        $this->assertEqualsCanonicalizing(['GET', 'HEAD', 'POST'], $allow);
    }

    public function testDomainResponseReturnedFromHandlerIsMappedToTheCanonicalEnvelope(): void
    {
        $routes = new Routes($this->factory);
        $routes->post('/orders', static fn () => new DomainResponse(
            status: ResponseStatus::Created->value,
            data: ['Sales' => ['id' => '7']],
        ));
        $app = new App($routes, $this->kernel(), new AppConfig());

        $response = $app->handle($this->request('POST', '/orders'));

        $this->assertSame(201, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(['Sales' => ['id' => '7']], $body['data']);
    }

    public function testResponseInterfaceReturnedFromHandlerIsPassedThroughUnchanged(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/raw', static function () {
            $factory = new Psr17Factory();
            $response = $factory->createResponse(200)->withHeader('X-Custom', 'yes');
            $response->getBody()->write('raw-body');

            return $response;
        });
        $app = new App($routes, $this->kernel(), new AppConfig());

        $response = $app->handle($this->request('GET', '/raw'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('yes', $response->getHeaderLine('X-Custom'));
        $this->assertSame('raw-body', (string) $response->getBody());
    }

    public function testRouteRegisteredWithARequestHandlerInterfaceWorksEndToEnd(): void
    {
        $handler = new FixedResponseHandler($this->factory->createResponse(204));
        $routes = new Routes($this->factory);
        $routes->get('/fixed', $handler);
        $app = new App($routes, $this->kernel(), new AppConfig());

        $response = $app->handle($this->request('GET', '/fixed'));

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testExceptionInHandlerIsMappedToAGenericFiveHundredAndLogged(): void
    {
        $logger = new LoggerSpy();
        $routes = new Routes($this->factory);
        $routes->get('/boom', static function (): never {
            throw new \RuntimeException('sensitive internal detail');
        });
        $app = new App($routes, $this->kernel($logger), new AppConfig());

        $response = $app->handle($this->request('GET', '/boom'));

        $this->assertSame(500, $response->getStatusCode());
        $json = (string) $response->getBody();
        $this->assertStringNotContainsString('sensitive internal detail', $json);
        $this->assertCount(1, $logger->records);
        $this->assertSame('sensitive internal detail', $logger->records[0]['message']);
    }

    public function testCustomPsr17FactoriesAreAcceptedAndUsedByTheConstructor(): void
    {
        $factory = new Psr17Factory();
        $routes = new Routes($factory);
        $routes->health('/health');
        $app = new App($routes, $this->kernel(), new AppConfig(), $factory, $factory, $factory, $factory, $factory);

        $response = $app->handle($this->request('GET', '/health'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRunBuildsTheRequestFromSapiGlobalsHandlesItAndEmitsTheResponse(): void
    {
        $routes = new Routes($this->factory);
        $routes->health('/health');
        $app = new App($routes, $this->kernel(), new AppConfig());

        $originalServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/health';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

        try {
            ob_start();
            $app->run();
            $output = ob_get_clean();
        } finally {
            $_SERVER = $originalServer;
        }

        $this->assertSame('{"status":200}', $output);
    }
}
