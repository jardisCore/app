<?php

declare(strict_types=1);

namespace Ecommerce;

use JardisSupport\ClassVersion\ClassVersion;
use JardisSupport\ClassVersion\Data\ClassVersionConfig;
use JardisSupport\ClassVersion\Reader\LoadClassFromProxy;
use JardisSupport\ClassVersion\Reader\LoadClassFromSubDirectory;
use JardisSupport\ClassVersion\Support\ClassResolutionCache;
use JardisSupport\Contract\ClassVersion\ClassVersionInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Contract\Kernel\GeneratedContextInterface;
use JardisSupport\Factory\Factory;
use LogicException;
use Throwable;
use JardisSupport\Response\Data\Response\ContextResponse;
use JardisSupport\Response\Handler\Response\TransformResponse;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Response\Data\Failure\FailureForm;
use JardisSupport\Scheduling\Clock\SystemClock;
use Psr\Clock\ClockInterface;
use JardisSupport\Response\Data\ReadResult;

/**
 * Ecommerce Domain Context.
 *
 * All bounded context classes in this domain extend this class.
 * 1:1-structural port of the former jardiscore/kernel BoundedContext
 * (Kernel-Entkopplung P4) — handle()/context() are family-internal
 * only (protected); the generated facade's own accessor methods are
 * the sole outer door.
 */
class EcommerceContext implements GeneratedContextInterface
{
    /**
     * Container-Schlüssel der Test-Stellvertreter-Liste (I3):
     * array<class-string, object> — Klassenname → Stellvertreter.
     * Nur Tests legen sie ab; ohne Eintrag verhält sich alles wie heute
     * (keine Betriebssperre — Bescheid Rolf 2026-09-26, Punkt b).
     */
    public const CLOSURE_STAND_INS = 'jardis.test.closureStandIns';

    private DomainKernelInterface $domainKernel;
    private mixed $payload;
    private string $version;
    private int $depth;
    private bool $entry;
    private ?ContextResponseInterface $result = null;
    private ?ClassVersionInterface $classVersionInstance = null;

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload,
        string $version,
        int $depth = 0,
        bool $entry = true,
    ) {
        $this->domainKernel = $domainKernel;
        $this->payload = $payload;
        $this->version = $version;
        $this->depth = $depth;
        $this->entry = $entry;
    }

    /**
     * Resolves and instantiates a class while inheriting the current
     * payload+version from this Context. Family-internal only.
     *
     * @template T
     * @param class-string<T> $className
     * @return T
     * @throws Throwable
     * @throws LogicException
     */
    protected function handle(string $className, mixed ...$parameters): mixed
    {
        return $this->resolve(
            $className,
            $this->payload,
            $this->version,
            $parameters,
            requireGeneratedContext: false,
            entry: $this->entry
        );
    }

    /**
     * Starts a fresh context for the given generated-context subclass,
     * setting payload+version explicitly (and the nesting depth of a sub-process
     * call: the savepoint name of its door); an empty version is the caller's own.
     * The kernel of the run is inherited.
     * Family-internal only; rejects non-GeneratedContextInterface targets.
     *
     * @template T of GeneratedContextInterface
     * @param class-string<T> $className
     * @return T
     * @throws Throwable
     * @throws LogicException
     */
    protected function context(
        string $className,
        mixed $payload,
        string $version = '',
        int $depth = 0,
    ): mixed {
        // An absent version is the caller's own: a facade reached through the kernel seam
        // (a node calling a process or aggregate facade) keeps the class version of the run.
        $version = $version !== '' ? $version : $this->version;

        return $this->resolve(
            $className,
            $payload,
            $version,
            [],
            requireGeneratedContext: true,
            depth: $depth,
            entry: false
        );
    }

    /**
     * @param class-string $className
     * @param array<int|string, mixed> $parameters
     * @throws Throwable
     * @throws LogicException
     */
    private function resolve(
        string $className,
        mixed $payload,
        string $version,
        array $parameters,
        bool $requireGeneratedContext,
        bool $entry,
        int $depth = 0,
    ): mixed {
        try {
            $container = $this->resource()->container();
            $factory = $container instanceof Factory ? $container : new Factory($container);

            $resolved = $this->resolveClassName($className, $version);

            // The entry mark always travels on (named, so no construction can lose it); a nesting
            // depth only where there is one.
            $named = ['entry' => $entry] + ($depth === 0 ? [] : ['depth' => $depth]);

            if (is_object($resolved)) {
                return $resolved;
            }

            return match (true) {
                is_subclass_of($resolved, GeneratedContextInterface::class)
                    => $factory->create($resolved, $this->domainKernel, $payload, $version, ...$parameters, ...$named),
                $requireGeneratedContext
                    => throw new LogicException(sprintf(
                        'context() requires a %s subclass; %s does not implement it.',
                        GeneratedContextInterface::class,
                        $resolved,
                    )),
                !empty($parameters)
                    => $factory->create($resolved, ...$parameters),
                default
                    => $container->get($resolved),
            };
        } catch (Throwable $e) {
            $logger = $this->resource()->logger();
            if ($logger !== null) {
                $logger->error($e->getMessage(), ['exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Reads through an own-BC query handler: starts the handler's context with
     * the given filter as payload (the class version of this run) and answers
     * its ReadResult. The read door of a process node (own BC only).
     *
     * @template T of GeneratedContextInterface
     * @param class-string<T> $handler
     * @return ReadResult<array<string, mixed>>
     * @throws LogicException when the handler lies outside the own bounded context (M9)
     * @throws Throwable
     */
    protected function read(string $handler, object $filter): ReadResult
    {
        $own = $this->ownBoundedContext();
        if (!str_starts_with($handler, $own)) {
            throw new LogicException(sprintf(
                'read() reads the own bounded context (%s) only; %s lies outside it. '
                . 'Reach a foreign bounded context through its process() or its {Agg}Read facade.',
                $own,
                $handler,
            ));
        }

        /** @var callable(): ReadResult<array<string, mixed>> $query */
        $query = $this->context($handler, $filter, $this->version);

        return $query();
    }

    /**
     * The namespace prefix `{Domain}\{BC}\` of the bounded context this run belongs to: the
     * first class of the caller's lineage below the domain namespace (a test harness that
     * extends a generated class belongs to the BC of that class).
     * @throws LogicException
     */
    private function ownBoundedContext(): string
    {
        $domain = __NAMESPACE__ . '\\';
        foreach ([static::class, ...array_values(class_parents($this))] as $class) {
            if (!str_starts_with($class, $domain)) {
                continue;
            }
            $segments = explode('\\', substr($class, strlen($domain)));
            if (count($segments) > 1) {
                return $domain . $segments[0] . '\\';
            }
        }

        throw new LogicException(sprintf('%s belongs to no bounded context of %s.', static::class, $domain));
    }

    protected function resource(): DomainKernelInterface
    {
        return $this->domainKernel;
    }

    protected function payload(): mixed
    {
        return $this->payload;
    }

    protected function version(): string
    {
        return $this->version;
    }

    /**
     * The nesting depth of this run: 0 at the outermost door, the depth of the calling
     * run plus one for a sub-process. The process door names its savepoint after it.
     */
    protected function depth(): int
    {
        return $this->depth;
    }

    /**
     * Whether this run entered the domain from outside (the entry mark): true for a context built
     * by a door (routes.php, App/, CLI), false for every context the run starts itself - a handler
     * (context()) - and for everything those construct through the kernel seam.
     */
    protected function entry(): bool
    {
        return $this->entry;
    }

    protected function result(): ContextResponseInterface
    {
        if ($this->result === null) {
            $context = basename(str_replace('\\', '/', get_class($this)));
            $this->result = new ContextResponse($context);
        }

        return $this->result;
    }

    /**
     * The envelope builder of this run (the package's TransformResponse),
     * stamped with the run's class version (V10): `meta.version` names the
     * version the caller asked for, also when that version has no class of its
     * own and the baseline served the call. The ONE place a generated class
     * obtains its transformer - every handler, process and node answers through
     * it, none builds its own.
     * @throws LogicException
     * @throws Throwable
     */
    protected function transformer(): TransformResponse
    {
        return new TransformResponse($this->clock(), $this->version);
    }

    /**
     * The clock of this run (PSR-20): the one source of every instant the
     * generat stamps - the envelope's timestamp and duration, the `auto`
     * columns of a persist. Resolved through the kernel seam, so a test
     * replaces it with a FixedClock via CLOSURE_STAND_INS under SystemClock.
     * @throws LogicException
     * @throws Throwable
     */
    protected function clock(): ClockInterface
    {
        return $this->handle(SystemClock::class);
    }

    /**
     * The wire data of a failure form - the package's one mapping from a
     * FailureForm to the `data` of the envelope (BuildResponseData).
     *
     * @return array<string, mixed>
     * @throws LogicException
     * @throws Throwable
     */
    protected function failureData(FailureForm $form): array
    {
        return $this->handle(BuildResponseData::class)($form->status(), $form, null, '');
    }

    /**
     * Resolves a class name through ClassVersion.
     *
     * @param class-string $className
     * @return class-string|object Resolved class name or proxy object
     */
    private function resolveClassName(string $className, string $version): string|object
    {
        $classVersion = $this->classVersionInstance ??= $this->classVersion();
        $resolved = $classVersion($className, $version);

        return $resolved ?? $className;
    }

    /**
     * Override to configure ClassVersion labels and fallback chains.
     */
    protected function classVersionConfig(): ClassVersionConfig
    {
        return new ClassVersionConfig();
    }

    /**
     * Resolves ClassVersion overrides from per-class subdirectories.
     *
     * The generated aggregate tree is Platform-free; version overrides
     * (e.g. v2) live as an immediate subdirectory next to the baseline
     * class (…/Handler/v2/Create{Aggregate}), resolved by
     * LoadClassFromSubDirectory. Override only for a custom setup.
     */
    protected function classVersion(): ClassVersionInterface
    {
        $config = $this->classVersionConfig();
        $container = $this->resource()->container();

        // Test-Stellvertreter (I3): nur mit Container-Eintrag —
        // sonst null, ClassVersion verhält sich exakt wie heute.
        $standIns = null;
        if ($container->has(self::CLOSURE_STAND_INS)) {
            $standIns = new LoadClassFromProxy($config);
            foreach ($container->get(self::CLOSURE_STAND_INS) as $className => $standIn) {
                $standIns->addProxy($className, $standIn);
            }
        }

        return new ClassVersion(
            $config,
            new LoadClassFromSubDirectory($config),
            proxyClassFinder: $standIns,
            cache: new ClassResolutionCache(),
        );
    }
}
