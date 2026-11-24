<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Directory;

use JardisCore\App\Handler\Directory\PersonalGroupPerCreator;
use JardisCore\App\Tests\Support\FakeGroupLookup;
use PHPUnit\Framework\TestCase;

/**
 * The translator from the contract question `personalGroupOf` (one person) to the list question of a bulk run
 * (one tenant, many creators).
 */
final class PersonalGroupPerCreatorTest extends TestCase
{
    public function testTwoKnownAndOneUnknownCreatorGiveTwoPairsInTheTenant(): void
    {
        $lookup = new FakeGroupLookup(personalGroups: ['anna' => 11, 'ben' => 12], onlyTenant: 't1');

        $answer = (new PersonalGroupPerCreator($lookup))('t1', ['anna', 'ben', 'ghost']);

        $this->assertSame(['anna' => 11, 'ben' => 12], $answer);
        $this->assertSame(
            [['anna', 't1'], ['ben', 't1'], ['ghost', 't1']],
            $lookup->personalCalls,
        );
    }

    public function testAnotherTenantAnswersNothing(): void
    {
        $lookup = new FakeGroupLookup(personalGroups: ['anna' => 11, 'ben' => 12], onlyTenant: 't1');

        $this->assertSame([], (new PersonalGroupPerCreator($lookup))('t2', ['anna', 'ben']));
    }

    public function testNoTenantAndNoCreatorsAreHandled(): void
    {
        $lookup = new FakeGroupLookup(personalGroups: ['anna' => 11]);
        $translator = new PersonalGroupPerCreator($lookup);

        $this->assertSame(['anna' => 11], $translator(null, ['anna']));
        $this->assertSame([['anna', null]], $lookup->personalCalls);
        $this->assertSame([], $translator('t1', []));
    }

    public function testACreatorListedTwiceIsAskedOnce(): void
    {
        $lookup = new FakeGroupLookup(personalGroups: ['anna' => 11]);

        $answer = (new PersonalGroupPerCreator($lookup))('t1', ['anna', 'anna']);

        $this->assertSame(['anna' => 11], $answer);
        $this->assertCount(1, $lookup->personalCalls);
    }

    public function testThePackageDoesNotKnowTheAccessPackage(): void
    {
        $root = dirname(__DIR__, 4);
        $composer = (string) file_get_contents($root . '/composer.json');
        $this->assertStringNotContainsString('jardissupport/acl', $composer);

        $found = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src')) as $file) {
            $code = $file->isFile() ? (string) file_get_contents($file->getPathname()) : '';
            if (str_contains($code, 'JardisSupport\\Acl')) {
                $found[] = $file->getPathname();
            }
        }
        $this->assertSame([], $found);
    }
}
