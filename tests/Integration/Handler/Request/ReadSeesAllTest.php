<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use Closure;
use InvalidArgumentException;
use JardisCore\App\Handler\Request\ReadSeesAll;
use JardisSupport\Auth\Handler\Authorization\HasRight;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SeeAllRight;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * "Sees all" of one right: true for the superuser `*` and for the tick entry of exactly that right in
 * `RequestAttribute::RIGHTS`; false for the tick of another right, for ordinary rights and without RIGHTS.
 *
 * With a rule handed in: the ticks (prefix cut off) go to the rule together with the right of the door - a tick with
 * a wildcard opens the view at every door of its resource; ordinary rights never reach the rule.
 */
final class ReadSeesAllTest extends TestCase
{
    private const RIGHT = 'sales.order:read';

    /**
     * @param mixed $rights null = no RIGHTS attribute
     */
    private function request(mixed $rights = null): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/orders');

        return $rights === null ? $request : $request->withAttribute(RequestAttribute::RIGHTS, $rights);
    }

    public function testSuperuserStarSeesAll(): void
    {
        $this->assertTrue((new ReadSeesAll())($this->request(['*']), self::RIGHT));
    }

    public function testTickOfExactlyThisRightSeesAll(): void
    {
        $tick = SeeAllRight::RESOURCE . ':' . self::RIGHT;

        $this->assertTrue((new ReadSeesAll())($this->request([self::RIGHT, $tick]), self::RIGHT));
        $this->assertTrue((new ReadSeesAll())($this->request([$tick]), self::RIGHT));
    }

    public function testTickOfAnotherRightDoesNotSeeAll(): void
    {
        $other = SeeAllRight::RESOURCE . ':sales.invoice:read';

        $this->assertFalse((new ReadSeesAll())($this->request([self::RIGHT, $other]), self::RIGHT));
    }

    public function testOrdinaryRightsAndWildcardOfTheResourceDoNotSeeAll(): void
    {
        $this->assertFalse((new ReadSeesAll())($this->request([self::RIGHT, 'sales.order:*']), self::RIGHT));
    }

    public function testWithoutRightsAttributeNothingIsSeen(): void
    {
        $this->assertFalse((new ReadSeesAll())($this->request(), self::RIGHT));
        $this->assertFalse((new ReadSeesAll())($this->request([]), self::RIGHT));
    }

    public function testUnusableRightsAttributeFailsClosed(): void
    {
        foreach (['*', 42, ['*', 7], ['right' => '*']] as $unusable) {
            $this->assertFalse((new ReadSeesAll())($this->request($unusable), self::RIGHT), var_export($unusable, true));
        }
    }

    /** Own small rule after the contract: `resource:action`, `resource:*` and `*` among the granted rights. */
    private function rule(): Closure
    {
        return static function (array $rights, string $right): bool {
            [$resource, $action] = explode(':', $right, 2);
            foreach ($rights as $granted) {
                if ($granted === '*' || $granted === $resource . ':*' || $granted === $right) {
                    return true;
                }
                if (substr_count($granted, ':') === 0) {
                    throw new InvalidArgumentException('unreadable right: ' . $granted);
                }
            }

            return false;
        };
    }

    private function tick(string $right): string
    {
        return SeeAllRight::RESOURCE . ':' . $right;
    }

    public function testWithRuleTickWithWildcardSeesAllAtEveryDoorOfTheResource(): void
    {
        $seesAll = new ReadSeesAll($this->rule());
        $request = $this->request([$this->tick('x.y:*')]);

        $this->assertTrue($seesAll($request, 'x.y:read'));
        $this->assertTrue($seesAll($request, 'x.y:write'));
        $this->assertFalse($seesAll($request, 'x.z:read'));
    }

    public function testWithRuleTickOfOneRightSeesAllOnlyAtThatDoor(): void
    {
        $seesAll = new ReadSeesAll($this->rule());
        $request = $this->request([$this->tick('x.y:read')]);

        $this->assertTrue($seesAll($request, 'x.y:read'));
        $this->assertFalse($seesAll($request, 'x.y:write'));
    }

    public function testWithRuleOrdinaryWildcardWithoutTickOpensTheDoorNotTheView(): void
    {
        $seesAll = new ReadSeesAll($this->rule());

        $this->assertFalse($seesAll($this->request(['x.y:*']), 'x.y:read'));
        $this->assertFalse($seesAll($this->request(['x.y:*', 'x.y:read']), 'x.y:read'));
    }

    public function testWithRuleSuperuserStarSeesAllWithoutAskingTheRule(): void
    {
        $asked = 0;
        $seesAll = new ReadSeesAll(static function () use (&$asked): bool {
            $asked++;

            return false;
        });

        $this->assertTrue($seesAll($this->request(['*']), self::RIGHT));
        $this->assertSame(0, $asked);
    }

    public function testWithRuleThatThrowsFailsClosed(): void
    {
        $seesAll = new ReadSeesAll(static function (): bool {
            throw new InvalidArgumentException('unreadable right');
        });

        $this->assertFalse($seesAll($this->request([$this->tick('broken')]), self::RIGHT));
    }

    public function testWithRuleOnlyTheCutTicksAndTheDoorRightAreHandedToTheRule(): void
    {
        $calls = [];
        $seesAll = new ReadSeesAll(static function (array $rights, string $right) use (&$calls): bool {
            $calls[] = [$rights, $right];

            return true;
        });

        $request = $this->request(['x.y:*', $this->tick('a.b:read'), 'c.d:write', $this->tick('e.f:*')]);

        $this->assertTrue($seesAll($request, 'x.y:read'));
        $this->assertSame([[['a.b:read', 'e.f:*'], 'x.y:read']], $calls);
    }

    public function testWithRuleTickOfTheStarOpensEveryDoorAsConsequenceOfTheRule(): void
    {
        $seesAll = new ReadSeesAll($this->rule());
        $request = $this->request([$this->tick('*')]);

        $this->assertTrue($seesAll($request, 'x.y:read'));
        $this->assertTrue($seesAll($request, 'a.b:write'));
    }

    public function testWithRuleUnusableRightsFailClosed(): void
    {
        $seesAll = new ReadSeesAll($this->rule());

        foreach ([42, ['*', 7], ['right' => '*'], [$this->tick('x.y:*'), 7]] as $unusable) {
            $this->assertFalse($seesAll($this->request($unusable), 'x.y:read'), var_export($unusable, true));
        }
        $this->assertFalse($seesAll($this->request(), 'x.y:read'));
    }

    public function testWithTheAuthRuleTickWithWildcardSeesAllAtEveryDoorOfTheResource(): void
    {
        $seesAll = new ReadSeesAll((new HasRight())->__invoke(...));
        $request = $this->request([$this->tick('x.y:*')]);

        $this->assertTrue($seesAll($request, 'x.y:read'));
        $this->assertFalse($seesAll($request, 'x.z:read'));
        $this->assertFalse($seesAll($this->request(['x.y:*']), 'x.y:read'));
    }
}
