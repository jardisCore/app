<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Directory;

use JardisCore\App\Handler\Directory\FallbackGroupPerTenant;
use JardisCore\App\Tests\Support\FakeGroupLookup;
use PHPUnit\Framework\TestCase;

/**
 * The translator from the contract question `fallbackGroupOf` (kind and tenant) to the one-tenant question of a
 * bulk run: the kind is fixed in the constructor.
 */
final class FallbackGroupPerTenantTest extends TestCase
{
    public function testKindWithAFallbackGroupGivesTheNumber(): void
    {
        $lookup = new FakeGroupLookup(fallbackGroups: [7 => 41]);

        $this->assertSame(41, (new FallbackGroupPerTenant($lookup, 7))('t1'));
        $this->assertSame([[7, 't1']], $lookup->fallbackCalls);
    }

    public function testKindWithoutAFallbackGroupGivesNull(): void
    {
        $lookup = new FakeGroupLookup(fallbackGroups: [7 => 41]);

        $this->assertNull((new FallbackGroupPerTenant($lookup, 8))('t1'));
    }

    public function testTheTenantIsPassedOnAlsoWhenAbsent(): void
    {
        $lookup = new FakeGroupLookup(fallbackGroups: [7 => 41]);
        $translator = new FallbackGroupPerTenant($lookup, 7);

        $translator('t1');
        $translator(null);

        $this->assertSame([[7, 't1'], [7, null]], $lookup->fallbackCalls);
    }
}
