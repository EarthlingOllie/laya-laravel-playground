<?php

namespace App\Decisions;

use MarcReichel\Laya\Attributes\Ask;
use MarcReichel\Laya\Attributes\Levels;

/**
 * Score an inbound sales lead. Floats keep the continuous values, so leads sort cleanly.
 */
final readonly class Lead
{
    public function __construct(
        #[Ask('How ready is this person to buy?'), Levels('just browsing', 'researching options', 'comparing vendors', 'ready to buy')]
        public float $intent,

        #[Ask('Does the message mention a budget, a timeline or a team size?')]
        public float $budget,
    ) {}
}
