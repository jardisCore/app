<?php

declare(strict_types=1);

namespace Ecommerce;

use JardisSupport\ClassVersion\ClassVersion;
use JardisSupport\ClassVersion\Data\ClassVersionConfig;
use JardisSupport\ClassVersion\Reader\LoadClassFromSubDirectory;
use JardisSupport\ClassVersion\Support\ClassResolutionCache;
use JardisSupport\Contract\ClassVersion\ClassVersionInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Contract\Kernel\GeneratedContextInterface;
use JardisSupport\Factory\Factory;
use LogicException;
use Throwable;
use Ecommerce\Response\ContextResponse;

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
    private DomainKernelInterface $domainKernel;
    private mixed $payload;
    private string $version;
    private ?ContextResponseInterface $result = null;
    private ?ClassVersionInterface $classVersionInstance = null;

    public function __construct(DomainKernelInterface $domainKernel, mixed $payload = null, string $version = '')
    {
        $this->domainKernel = $domainKernel;
        $this->payload = $payload;
        $this->version = $version;
    }

    /**
     * Resolves and instantiates a class while inheriting the current
     * payload+version from this Context. Family-internal only.
     *
     * @template T
     * @param class-string<T> $className
     * @return T|null
     * @throws Throwable
     * @throws LogicException
     */
    protected function handle(string $className, mixed ...$parameters): mixed
    {
        return $this->resolve($className, $this->payload, $this->version, $parameters, requireGeneratedContext: false);
    }

    /**
     * Starts a fresh context for the given generated-context subclass,
     * setting payload+version explicitly. The kernel is inherited.
     * Family-internal only; rejects non-GeneratedContextInterface targets.
     *
     * @template T of GeneratedContextInterface
     * @param class-string<T> $className
     * @return T
     * @throws Throwable
     * @throws LogicException
     */
    protected function context(string $className, mixed $payload, string $version = ''): mixed
    {
        return $this->resolve($className, $payload, $version, [], requireGeneratedContext: true);
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
    ): mixed {
        try {
            $container = $this->resource()->container();
            $factory = $container instanceof Factory ? $container : new Factory($container);

            $resolved = $this->resolveClassName($className, $version);

            if (is_object($resolved)) {
                return $resolved;
            }

            return match (true) {
                is_subclass_of($resolved, GeneratedContextInterface::class)
                    => $factory->create($resolved, $this->domainKernel, $payload, $version, ...$parameters),
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

    protected function result(): ContextResponseInterface
    {
        if ($this->result === null) {
            $context = basename(str_replace('\\', '/', get_class($this)));
            $this->result = new ContextResponse($context);
        }

        return $this->result;
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
     * class (…/Handler/v2/CreateCounter), resolved by
     * LoadClassFromSubDirectory. Override only for a custom setup.
     */
    protected function classVersion(): ClassVersionInterface
    {
        $config = $this->classVersionConfig();

        return new ClassVersion(
            $config,
            new LoadClassFromSubDirectory($config),
            cache: new ClassResolutionCache(),
        );
    }
}
