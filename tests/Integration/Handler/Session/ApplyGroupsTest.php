<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Session;

use JardisCore\App\Handler\Session\ApplyGroups;
use JardisCore\App\Tests\Support\FakeGroupLookup;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SubjectGroupsInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ApplyGroups: sets GROUPS and PERSONAL_GROUP from the groups of the subject, removes both first, and leaves
 * an attribute absent (never `[]`, never null) when there is nothing to set.
 */
final class ApplyGroupsTest extends TestCase
{
    private function request(): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest('GET', '/orders');
    }

    /**
     * @param list<int> $ids
     */
    private function subjectGroups(array $ids, ?int $personal): SubjectGroupsInterface
    {
        return (new FakeGroupLookup($ids, $personal))->groupsOf('42', null);
    }

    public function testGroupsAndPersonalGroupAreSetAsIntegers(): void
    {
        $result = (new ApplyGroups())($this->request(), $this->subjectGroups([7, 9], 9));

        $this->assertSame([7, 9], $result->getAttribute(RequestAttribute::GROUPS));
        $this->assertSame(9, $result->getAttribute(RequestAttribute::PERSONAL_GROUP));
    }

    public function testEmptyGroupsLeaveGroupsAbsentNeverEmptyList(): void
    {
        $result = (new ApplyGroups())($this->request(), $this->subjectGroups([], null));

        $this->assertArrayNotHasKey(RequestAttribute::GROUPS, $result->getAttributes());
        $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $result->getAttributes());
    }

    public function testNoPersonalGroupLeavesOnlyThatAttributeAbsent(): void
    {
        $result = (new ApplyGroups())($this->request(), $this->subjectGroups([7], null));

        $this->assertSame([7], $result->getAttribute(RequestAttribute::GROUPS));
        $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $result->getAttributes());
    }

    public function testEarlierValuesAreRemovedFirstWhateverTheResult(): void
    {
        $forged = $this->request()
            ->withAttribute(RequestAttribute::GROUPS, [666])
            ->withAttribute(RequestAttribute::PERSONAL_GROUP, 666);

        $anonymous = (new ApplyGroups())($forged, null);
        $empty = (new ApplyGroups())($forged, $this->subjectGroups([], null));
        $replaced = (new ApplyGroups())($forged, $this->subjectGroups([7], 7));

        $this->assertArrayNotHasKey(RequestAttribute::GROUPS, $anonymous->getAttributes());
        $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $anonymous->getAttributes());
        $this->assertArrayNotHasKey(RequestAttribute::GROUPS, $empty->getAttributes());
        $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $empty->getAttributes());
        $this->assertSame([7], $replaced->getAttribute(RequestAttribute::GROUPS));
        $this->assertSame(7, $replaced->getAttribute(RequestAttribute::PERSONAL_GROUP));
    }
}
