<?php

declare(strict_types=1);

namespace JardisCore\App\Data;

/**
 * What a request names as share targets, as read from its source and not yet resolved: groups with their level
 * word, optionally one person (to be mapped to her personal group) with her level word. The words are carried
 * unchanged and never checked here.
 */
final readonly class ShareTargetRequest
{
    /**
     * @param array<int, string> $groups group id => level word
     * @param string|null $person bare subject id of the person to share with, `null` = no person named
     * @param string $personWord level word of the person's personal group
     */
    public function __construct(
        public array $groups,
        public ?string $person = null,
        public string $personWord = '',
    ) {
    }
}
