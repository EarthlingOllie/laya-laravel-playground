<?php

namespace App\Decisions;

use MarcReichel\Laya\Attributes\Ask;
use MarcReichel\Laya\Attributes\Of;

/**
 * Multi-label: one yes/no question per Topic case, so several tags can apply at once.
 */
final readonly class Tagging
{
    public function __construct(
        /** @var list<Topic> */
        #[Ask('Does this message mention {case}?', threshold: 0.3), Of(Topic::class)]
        public array $topics,
    ) {}
}
