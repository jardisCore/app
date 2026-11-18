<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Session;

use JardisCore\App\Handler\Session\ApplyFallbackGroups;
use JardisSupport\Contract\Auth\RequestAttribute;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The only writer of `RequestAttribute::FALLBACK_GROUPS`: removes first, sets only a non-empty mapping.
 */
final class ApplyFallbackGroupsTest extends TestCase
{
    private function request(): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest('GET', '/projects');
    }

    public function testNonEmptyMappingIsSetWithoutTheNullAnswers(): void
    {
        $result = (new ApplyFallbackGroups())($this->request(), [3 => 41, 4 => null, 5 => 42]);

        $this->assertSame([3 => 41, 5 => 42], $result->getAttribute(RequestAttribute::FALLBACK_GROUPS));
    }

    public function testOnlyNullAnswersAndNoAnswersLeaveTheAttributeAbsent(): void
    {
        foreach ([[], [3 => null, 4 => null]] as $answers) {
            $result = (new ApplyFallbackGroups())($this->request(), $answers);

            $this->assertArrayNotHasKey(RequestAttribute::FALLBACK_GROUPS, $result->getAttributes());
        }
    }

    public function testEarlierValueIsRemovedFirst(): void
    {
        $forged = $this->request()->withAttribute(RequestAttribute::FALLBACK_GROUPS, [3 => 666]);

        $empty = (new ApplyFallbackGroups())($forged, []);
        $replaced = (new ApplyFallbackGroups())($forged, [3 => 41]);

        $this->assertArrayNotHasKey(RequestAttribute::FALLBACK_GROUPS, $empty->getAttributes());
        $this->assertSame([3 => 41], $replaced->getAttribute(RequestAttribute::FALLBACK_GROUPS));
    }
}
