<?php

namespace App\Playground;

use App\Decisions\Department;
use App\Decisions\Lead;
use App\Decisions\Moderation;
use App\Decisions\Sentiment;
use App\Decisions\Tagging;
use App\Decisions\Topic;
use App\Decisions\Triage;
use App\Decisions\Urgency;

/**
 * Everything the playground offers: the decision classes you can run, and ready-made question sets.
 */
final class Catalog
{
    /**
     * Decision classes, with the files that define them (shown as source) and sample texts.
     *
     * @return array<string, array{class: class-string, title: string, blurb: string, icon: string, files: list<class-string>, samples: list<array{label: string, text: string}>}>
     */
    public static function decisions(): array
    {
        return [
            'triage' => [
                'class' => Triage::class,
                'title' => 'Ticket triage',
                'blurb' => 'Route a support ticket: department (choice), urgency (#[Scale] enum) and churn risk (yes/no).',
                'icon' => 'inbox',
                'files' => [Triage::class, Department::class, Urgency::class],
                'samples' => [
                    ['label' => 'Double billing', 'text' => 'Hi, we were billed twice for March. Refund it today or we cancel.'],
                    ['label' => 'Outage', 'text' => 'Since this morning the dashboard returns a 500 error for our whole team. We cannot work at all.'],
                    ['label' => 'German', 'text' => 'Ich kann mich seit gestern nicht mehr einloggen, das Passwort-Zurücksetzen funktioniert auch nicht.'],
                    ['label' => 'Spanish', 'text' => '¿Tienen modo oscuro? Solo es curiosidad, no hay prisa.'],
                ],
            ],
            'moderation' => [
                'class' => Moderation::class,
                'title' => 'Review moderation',
                'blurb' => 'Two yes/no gates (abusive, spam) and a sentiment choice, in one forward pass.',
                'icon' => 'shield',
                'files' => [Moderation::class, Sentiment::class],
                'samples' => [
                    ['label' => 'Happy', 'text' => 'Arrived quickly and works perfectly. The battery lasts for days. Would buy again!'],
                    ['label' => 'Spam', 'text' => 'BEST PRICES on watches!!! Visit cheap-watches-4u dot com and use code SAVE80 today'],
                    ['label' => 'Abusive', 'text' => 'Whoever designed this is an idiot and the support staff are useless morons.'],
                    ['label' => 'French', 'text' => 'Produit correct, mais la livraison a pris trois semaines. Bof.'],
                ],
            ],
            'lead' => [
                'class' => Lead::class,
                'title' => 'Lead scoring',
                'blurb' => 'Float parameters: the expected level of a score question, and P(yes) of a yes/no question.',
                'icon' => 'trending',
                'files' => [Lead::class],
                'samples' => [
                    ['label' => 'Hot lead', 'text' => 'We are a team of 40 and need to roll this out before Q3. Budget is approved, can we get a quote this week?'],
                    ['label' => 'Browsing', 'text' => 'Just looking around, what does your product do exactly?'],
                    ['label' => 'Comparing', 'text' => 'We are evaluating you against two other vendors. How does your pricing compare for SSO?'],
                ],
            ],
            'tagging' => [
                'class' => Tagging::class,
                'title' => 'Multi-label tagging',
                'blurb' => 'An array with #[Of(Topic::class)]: one yes/no question per enum case, threshold 0.3.',
                'icon' => 'tags',
                'files' => [Tagging::class, Topic::class],
                'samples' => [
                    ['label' => 'Two topics', 'text' => 'My parcel never arrived and I was still charged for express shipping. Please refund the difference.'],
                    ['label' => 'Login bug', 'text' => 'The 2FA code screen just spins forever after the last update, so I cannot log in.'],
                    ['label' => 'Italian', 'text' => 'Il pacco è arrivato danneggiato, come posso fare il reso?'],
                ],
            ],
        ];
    }

    /**
     * Question sets for the free-form predict() tab. Options are label => description; descriptions may be empty.
     *
     * @return array<string, array{title: string, state: string, questions: list<array<string, mixed>>}>
     */
    public static function presets(): array
    {
        return [
            'support' => [
                'title' => 'Support inbox',
                'state' => 'Hi, we were billed twice for March. Refund it today or we cancel.',
                'questions' => [
                    ['id' => 'department', 'type' => 'choice', 'instructions' => 'Which department should handle this?', 'options' => [
                        ['label' => 'billing', 'description' => 'invoices, payments, refunds'],
                        ['label' => 'technical', 'description' => 'bugs, outages, system errors'],
                        ['label' => 'other', 'description' => 'everything else'],
                    ]],
                    ['id' => 'urgency', 'type' => 'score', 'instructions' => 'How urgent is this?', 'levels' => ['not urgent', 'soon', 'blocking']],
                    ['id' => 'churn_risk', 'type' => 'yesno', 'instructions' => 'Does the user threaten to cancel or leave?', 'yes' => '', 'no' => ''],
                ],
            ],
            'email' => [
                'title' => 'Email routing',
                'state' => "Subject: Quote for 50 seats\n\nHello, we'd like pricing for 50 seats on the annual plan, ideally with a discount for nonprofits. Thanks, Priya",
                'questions' => [
                    ['id' => 'team', 'type' => 'choice', 'instructions' => 'Which team should answer this email?', 'options' => [
                        ['label' => 'sales', 'description' => 'pricing, quotes, new contracts'],
                        ['label' => 'support', 'description' => 'problems using the product'],
                        ['label' => 'billing', 'description' => 'invoices, payments, refunds'],
                        ['label' => 'spam', 'description' => 'newsletters, cold outreach, phishing'],
                    ]],
                    ['id' => 'reply_needed', 'type' => 'yesno', 'instructions' => 'Does the sender expect a reply?', 'yes' => '', 'no' => ''],
                ],
            ],
            'contract' => [
                'title' => 'Contract fields',
                'state' => 'Who do we sue if things go wrong, and under which law?',
                'questions' => [
                    ['id' => 'main_clause', 'type' => 'choice', 'instructions' => 'Which contract clause do you need to answer this question?', 'options' => [
                        ['label' => 'notice_period', 'description' => 'how far in advance either side must give notice to end the contract'],
                        ['label' => 'auto_renewal', 'description' => 'whether and for how long the contract renews automatically'],
                        ['label' => 'governing_law', 'description' => "which country's law applies"],
                        ['label' => 'jurisdiction', 'description' => 'which court handles disputes'],
                    ]],
                    ['id' => 'governing_law', 'type' => 'yesno', 'instructions' => "Do you need to know the contract's clause on which country's law applies to answer this question?", 'yes' => '', 'no' => ''],
                ],
            ],
            'blank' => [
                'title' => 'Blank canvas',
                'state' => '',
                'questions' => [
                    ['id' => 'question_1', 'type' => 'yesno', 'instructions' => '', 'yes' => '', 'no' => ''],
                ],
            ],
        ];
    }

    /** @return class-string|null */
    public static function decisionClass(string $key): ?string
    {
        return self::decisions()[$key]['class'] ?? null;
    }
}
