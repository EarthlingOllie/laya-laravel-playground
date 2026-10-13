<?php

namespace Tests\Feature;

use App\Decisions\Department;
use App\Decisions\Lead;
use App\Decisions\Moderation;
use App\Decisions\Tagging;
use App\Decisions\Topic;
use App\Decisions\Triage;
use App\Decisions\Urgency;
use MarcReichel\Laya\Laya;
use Tests\TestCase;

class PlaygroundTest extends TestCase
{
    public function test_the_page_renders(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Laya Playground')
            ->assertSee('Triage::class');
    }

    public function test_every_decision_class_is_valid(): void
    {
        foreach ([Triage::class, Moderation::class, Lead::class, Tagging::class] as $class) {
            $this->assertNotEmpty(Laya::questions($class), $class);
        }
    }

    public function test_decide_returns_the_hydrated_object_and_probabilities(): void
    {
        $laya = Laya::fake(new Triage(Department::Billing, Urgency::Blocking, churn: true));

        $this->postJson('/api/decide', ['decision' => 'triage', 'state' => 'Refund me or I cancel.', 'model' => 'auto'])
            ->assertOk()
            ->assertJsonPath('class', 'Triage')
            ->assertJsonPath('value.0.php', 'Department::Billing')
            ->assertJsonPath('value.1.php', 'Urgency::Blocking')
            ->assertJsonPath('value.2.php', 'true')
            ->assertJsonPath('answers.0.headline', 'billing')
            ->assertJsonPath('answers.1.type', 'score');

        $laya->assertDecided(Triage::class);
    }

    public function test_multi_label_decisions_list_their_cases(): void
    {
        Laya::fake(['topics' => [Topic::Billing, Topic::Shipping]]);

        $this->postJson('/api/decide', ['decision' => 'tagging', 'state' => 'Charged for shipping that never came.'])
            ->assertOk()
            ->assertJsonPath('value.0.php', '[Topic::Billing, Topic::Shipping]')
            ->assertJsonCount(4, 'answers');
    }

    public function test_predict_builds_questions_from_the_form(): void
    {
        $laya = Laya::fake(['team' => 'sales', 'urgency' => 1, 'reply' => 0.9]);

        $this->postJson('/api/predict', [
            'state' => 'Can I get a quote for 50 seats?',
            'model' => 'multilingual',
            'min_confidence' => 0.5,
            'questions' => [
                ['id' => 'team', 'type' => 'choice', 'instructions' => 'Which team?', 'options' => [
                    ['label' => 'sales', 'description' => 'quotes'],
                    ['label' => 'support', 'description' => ''],
                ]],
                ['id' => 'urgency', 'type' => 'score', 'instructions' => 'How urgent?', 'levels' => ['low', 'medium', 'high']],
                ['id' => 'reply', 'type' => 'yesno', 'instructions' => 'Is a reply expected?'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('answers.0.headline', 'sales')
            ->assertJsonPath('answers.1.headline', 'medium')
            ->assertJsonPath('answers.2.headline', 'yes')
            ->assertJsonPath('answers.2.abstention', 'passed')
            ->assertJsonPath('meta.routed_model', 'multilingual');

        $laya->assertPredicted(fn ($state, array $questions, ?string $model) => $model === 'multilingual'
            && $questions['team']['criteria'] === ['sales' => 'quotes', 'support' => null]);
    }

    public function test_predict_validates_the_form(): void
    {
        Laya::fake();

        $this->postJson('/api/predict', [
            'state' => 'Hello',
            'questions' => [['id' => 'q', 'type' => 'yesno', 'instructions' => '']],
        ])->assertUnprocessable()->assertJsonValidationErrors('questions.0.instructions');
    }

    public function test_an_unreachable_server_is_reported_not_thrown(): void
    {
        config(['laya.url' => 'http://127.0.0.1:1']);
        $this->app->forgetInstance(Laya::class);

        $this->getJson('/api/health')->assertOk()->assertJsonPath('online', false);

        $this->postJson('/api/decide', ['decision' => 'triage', 'state' => 'Hi'])
            ->assertServiceUnavailable()
            ->assertJsonPath('exception', 'TransportException');
    }
}
