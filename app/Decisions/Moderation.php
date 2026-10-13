<?php

namespace App\Decisions;

use MarcReichel\Laya\Attributes\Ask;

/**
 * Hold abusive or spam reviews back before they are published, and record the sentiment.
 */
final readonly class Moderation
{
    public function __construct(
        #[Ask('Does the review contain insults, hate speech or threats?')]
        public bool $abusive,

        #[Ask('Is this spam or an advertisement rather than a real review?')]
        public bool $spam,

        #[Ask('What is the overall sentiment of the review?')]
        public Sentiment $sentiment,
    ) {}
}
