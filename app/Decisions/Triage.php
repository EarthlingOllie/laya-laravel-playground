<?php

namespace App\Decisions;

use MarcReichel\Laya\Attributes\Ask;

/**
 * Route an incoming support ticket: who handles it, how fast, and whether the customer might leave.
 */
final readonly class Triage
{
    public function __construct(
        #[Ask('Which department should handle this?')]
        public Department $department,

        #[Ask('How urgent is this?')]
        public Urgency $urgency,

        #[Ask('Does the user threaten to cancel or leave?', yes: 'says they will cancel or switch')]
        public bool $churn,
    ) {}
}
