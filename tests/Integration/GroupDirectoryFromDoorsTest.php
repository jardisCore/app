<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration;

use Closure;
use JardisCore\App\GroupDirectoryFromDoors;
use JardisSupport\Contract\Auth\GroupLookupInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

/**
 * GroupDirectoryFromDoors fulfils the four questions of the contract over injected door calls, without logic of
 * its own; the structure test pins that each question has exactly one closure of its own.
 */
final class GroupDirectoryFromDoorsTest extends TestCase
{
    private const NAMESPACE = 'JardisCore\\App\\Handler\\Directory\\';

    /** @var list<array{0: string, 1: list<mixed>}> */
    private array $calls = [];

    protected function setUp(): void
    {
        $this->calls = [];
    }

    public function testItFulfilsTheContract(): void
    {
        $this->assertInstanceOf(GroupLookupInterface::class, $this->directory());
    }

    public function testGroupsOfAsksTheDoorAndBuildsTheAnswerFromTheRawArray(): void
    {
        $groups = $this->directory()->groupsOf('42', 'acme');

        $this->assertSame([7, 12], $groups->getGroups());
        $this->assertSame(12, $groups->getPersonalGroup());
        $this->assertSame([['a', ['42', 'acme']]], $this->calls);
    }

    public function testGroupsOfPassesNoTenantAndNoPersonalGroupThrough(): void
    {
        $directory = new GroupDirectoryFromDoors(
            static fn (string $subject, ?string $tenant): array => ['groups' => [], 'personal' => null],
            $this->unused(...),
            $this->unused(...),
            $this->unused(...),
        );

        $groups = $directory->groupsOf('42', null);

        $this->assertSame([], $groups->getGroups());
        $this->assertNull($groups->getPersonalGroup());
    }

    public function testPersonalGroupOfPassesTheDoorAnswerThrough(): void
    {
        $this->assertSame(12, $this->directory()->personalGroupOf('42', 'acme'));
        $this->assertSame([['b', ['42', 'acme']]], $this->calls);
    }

    public function testExistingGroupsPassesTheDoorAnswerThrough(): void
    {
        $this->assertSame([7], $this->directory()->existingGroups([7, 999], null));
        $this->assertSame([['c', [[7, 999], null]]], $this->calls);
    }

    public function testFallbackGroupOfPassesTheDoorAnswerThrough(): void
    {
        $this->assertSame(3, $this->directory()->fallbackGroupOf(5, 'acme'));
        $this->assertNull($this->directory(fallback: null)->fallbackGroupOf(5, 'acme'));
        $this->assertSame([['d', [5, 'acme']]], array_slice($this->calls, 0, 1));
    }

    public function testWhatADoorCallThrowsIsNotCaught(): void
    {
        $directory = new GroupDirectoryFromDoors(
            $this->unused(...),
            static fn (): never => throw new RuntimeException('door down'),
            $this->unused(...),
            $this->unused(...),
        );

        $this->expectException(RuntimeException::class);
        $directory->personalGroupOf('42', 'acme');
    }

    public function testEachQuestionOfTheContractHasExactlyOneClosureOfItsOwn(): void
    {
        $questions = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(GroupLookupInterface::class))->getMethods(),
        );
        sort($questions);
        $this->assertSame(['existingGroups', 'fallbackGroupOf', 'groupsOf', 'personalGroupOf'], $questions);

        $directory = $this->directory();
        $classes = [];

        foreach ($questions as $question) {
            $class = self::NAMESPACE . 'Answer' . ucfirst($question);
            $this->assertTrue(class_exists($class), $class . ' is missing');

            $reflection = new ReflectionClass($class);
            $this->assertTrue($reflection->isFinal(), $class . ' must be final');
            $own = array_map(
                static fn (ReflectionMethod $method): string => $method->getName(),
                array_filter(
                    $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                    static fn (ReflectionMethod $method): bool => $method->getName() !== '__construct',
                ),
            );
            $this->assertSame(['__invoke'], array_values($own), $class . ' has exactly one entry point');

            $held = $this->closureHolding($directory, $question);
            $this->assertInstanceOf($class, (new ReflectionFunction($held))->getClosureThis());
            $classes[] = $class;
        }

        $this->assertCount(4, array_unique($classes), 'no closure is shared between two questions');
    }

    public function testTheDirectoryHoldsNoStateButTheFourClosures(): void
    {
        $properties = array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(GroupDirectoryFromDoors::class))->getProperties(),
        );
        sort($properties);

        $this->assertSame(['existingGroups', 'fallbackGroupOf', 'groupsOf', 'personalGroupOf'], $properties);
    }

    private function closureHolding(GroupDirectoryFromDoors $directory, string $question): Closure
    {
        $value = (new ReflectionProperty($directory, $question))->getValue($directory);
        $this->assertInstanceOf(Closure::class, $value);

        return $value;
    }

    private function directory(?int $fallback = 3): GroupDirectoryFromDoors
    {
        return new GroupDirectoryFromDoors(
            function (string $subject, ?string $tenant): array {
                $this->calls[] = ['a', [$subject, $tenant]];

                return ['groups' => [7, 12], 'personal' => 12];
            },
            function (string $subject, ?string $tenant): int {
                $this->calls[] = ['b', [$subject, $tenant]];

                return 12;
            },
            function (array $groups, ?string $tenant): array {
                $this->calls[] = ['c', [$groups, $tenant]];

                return [7];
            },
            function (int $kind, ?string $tenant) use ($fallback): ?int {
                $this->calls[] = ['d', [$kind, $tenant]];

                return $fallback;
            },
        );
    }

    private function unused(): never
    {
        throw new RuntimeException('this door must not be asked');
    }
}
