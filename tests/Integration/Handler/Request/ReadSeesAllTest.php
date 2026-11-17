<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Handler\Request\ReadSeesAll;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SeeAllRight;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * "Sees all" of one right: true for the superuser `*` and for the tick entry of exactly that right in
 * `RequestAttribute::RIGHTS`; false for the tick of another right, for ordinary rights and without RIGHTS.
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
}
