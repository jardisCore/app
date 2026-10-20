<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use Closure;
use JardisCore\App\Contract\RouterInterface;
use JardisCore\App\Data\Route;
use JardisCore\App\Data\RouteMatch;
use JardisCore\App\Data\RouteMatchStatus;
use JardisCore\App\Handler\Request\ResolveVersion;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * The request-handling core chain (F1/F3/F8a/F9): the
 * `Closure(ServerRequestInterface): ResponseInterface` App hands to
 * `HandleThrowable` as its inner boundary.
 *
 * Dispatches the request via the injected `RouterInterface` (E3 - FastRoute
 * stays fully swappable), then either resolves a boundary error response
 * (F9: 404/405, the latter carrying the Allow header from
 * `RouteMatch::$allowedMethods`) or, on a Found match, applies the matched
 * path parameters as request attributes (F8a), resolves the requested class
 * version (`X-Jardis-Version` -> attribute `jardisVersion`; an invalid header
 * is answered 400 right here, before any middleware or handler) and only then
 * runs the PSR-15 middleware pipeline (global outer, route inner, E9) around the route's
 * own handler (auto-mapped via `ResolveRouteHandler`, E6).
 */
final class DispatchAndRespond
{
    /** Request attribute carrying the validated class version (`''` = baseline). */
    public const string VERSION_ATTRIBUTE = 'jardisVersion';

    /**
     * Request attribute (strict bool) telling the pipeline that the matched route needs no
     * session. Always written by this class, so a path placeholder of the same name cannot
     * forge it; consumers must compare with `=== true`.
     */
    public const string ANONYMOUS_ATTRIBUTE = 'jardisAnonymousRoute';

    /**
     * @param Closure $applyRouteAttributes {@see ApplyRouteAttributes::__invoke()}
     * @param Closure $resolveRouteHandler {@see ResolveRouteHandler::__invoke()}
     * @param Closure $runMiddlewarePipeline {@see \JardisCore\App\Handler\Pipeline\RunMiddlewarePipeline::__invoke()}
     * @param Closure $buildErrorResponse {@see \JardisCore\App\Handler\Response\BuildErrorResponse::__invoke()}
     * @param Closure $resolveVersion {@see \JardisCore\App\Handler\Request\ResolveVersion::__invoke()}
     * @param list<MiddlewareInterface> $globalMiddleware
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly Closure $applyRouteAttributes,
        private readonly Closure $resolveRouteHandler,
        private readonly Closure $runMiddlewarePipeline,
        private readonly Closure $buildErrorResponse,
        private readonly Closure $resolveVersion,
        private readonly array $globalMiddleware,
        private readonly ValidationPayload $validationPayload = new ValidationPayload(),
        private readonly BuildResponseData $buildResponseData = new BuildResponseData(),
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $match = $this->router->dispatch($request);

        return match ($match->status) {
            RouteMatchStatus::NotFound => ($this->buildErrorResponse)(404),
            // Positional call (not named args): PHPStan cannot map named
            // arguments onto a bare `Closure` type, so the empty
            // data/errors/meta fillers stay positional to reach the
            // allowedMethods slot (BuildErrorResponse::__invoke's 5th param).
            RouteMatchStatus::MethodNotAllowed => ($this->buildErrorResponse)(
                405,
                [],
                [],
                [],
                $match->allowedMethods,
            ),
            RouteMatchStatus::Found => $this->respondToFoundRoute($request, $match),
        };
    }

    private function respondToFoundRoute(ServerRequestInterface $request, RouteMatch $match): ResponseInterface
    {
        /** @var Route $route */
        $route = $match->route;

        $requestWithAttributes = ($this->applyRouteAttributes)($request, $match->parameters);

        $version = ($this->resolveVersion)($requestWithAttributes);
        if ($version === null) {
            return $this->rejectVersion();
        }
        $requestWithAttributes = $requestWithAttributes
            ->withAttribute(self::VERSION_ATTRIBUTE, $version)
            ->withAttribute(self::ANONYMOUS_ATTRIBUTE, $route->anonymous);

        $finalHandler = ($this->resolveRouteHandler)($route->handler);

        return ($this->runMiddlewarePipeline)(
            $this->globalMiddleware,
            $route->middleware,
            $finalHandler,
            $requestWithAttributes,
        );
    }

    /**
     * The one 400 answer for an invalid `X-Jardis-Version` header: the validation
     * form every generated door answers with (`data = {"@type":"validation","fields":
     * [{field, reason, message, messageKey}]}`, `errors` empty), built by
     * `jardissupport/response`; the envelope stays the bare one of this boundary
     * (`meta` empty, no handler has run).
     */
    private function rejectVersion(): ResponseInterface
    {
        $form = ($this->validationPayload)('validation', [[
            'field' => ResolveVersion::HEADER,
            'reason' => 'invalid',
            'message' => 'The requested version is not known.',
            'messageKey' => 'request.versionUnknown',
        ]]);

        return ($this->buildErrorResponse)(
            400,
            ($this->buildResponseData)(ResponseStatus::ValidationError, $form, null, ''),
        );
    }
}
