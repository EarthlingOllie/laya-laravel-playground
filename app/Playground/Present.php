<?php

namespace App\Playground;

use MarcReichel\Laya\Answers\Answer;
use MarcReichel\Laya\Answers\ChoiceAnswer;
use MarcReichel\Laya\Answers\ScoreAnswer;
use MarcReichel\Laya\Answers\YesNoAnswer;
use MarcReichel\Laya\Question;
use MarcReichel\Laya\Result;

/**
 * Turns the SDK's typed objects into plain arrays for the browser.
 */
final class Present
{
    /**
     * @param  array<string, Question>  $questions
     * @return array<string, mixed>
     */
    public static function result(Result $result, array $questions, float $ms): array
    {
        $answers = [];
        foreach ($result as $id => $answer) {
            $answers[] = self::answer((string) $id, $answer, $questions[$id] ?? null);
        }

        return [
            'answers' => $answers,
            'meta' => [
                'routed_model' => $result->routedModel,
                'input_tokens' => $result->inputTokens,
                'truncated' => $result->truncated,
                'state_tokens' => $result->stateTokens,
                'duration_ms' => round($ms),
            ],
            'raw' => $result->raw,
        ];
    }

    /** @return array<string, mixed> */
    public static function answer(string $id, Answer $answer, ?Question $question): array
    {
        $common = [
            'id' => $id,
            'instructions' => $question?->instructions,
            'confidence' => $answer->confidence,
            'answer_confidence' => $answer->answerConfidence,
            'abstention' => $answer->abstention?->value,
            'abstention_threshold' => $answer->abstentionThreshold,
            'low_confidence' => $answer->lowConfidence,
        ];

        return $common + match (true) {
            $answer instanceof ChoiceAnswer => [
                'type' => 'choice',
                'headline' => (string) $answer->choice,
                'options' => array_map(
                    fn ($label, $p) => ['label' => (string) $label, 'p' => $p, 'top' => (string) $label === (string) $answer->choice],
                    array_keys($answer->probabilities), $answer->probabilities,
                ),
            ],
            $answer instanceof ScoreAnswer => [
                'type' => 'score',
                'headline' => $answer->label() ?? (string) $answer->level(),
                'score' => $answer->score,
                'level' => $answer->level(),
                'max_level' => max(count($answer->probabilities) - 1, 1),
                'options' => array_map(
                    fn ($i, $p) => ['label' => $answer->legend[$i] ?? "level {$i}", 'p' => $p, 'top' => $i === $answer->level()],
                    array_keys($answer->probabilities), $answer->probabilities,
                ),
            ],
            $answer instanceof YesNoAnswer => [
                'type' => 'yesno',
                'headline' => $answer->yes() ? 'yes' : 'no',
                'probability' => $answer->probability,
                'options' => [
                    ['label' => 'yes', 'p' => $answer->probability, 'top' => $answer->yes()],
                    ['label' => 'no', 'p' => 1 - $answer->probability, 'top' => $answer->no()],
                ],
            ],
        };
    }

    /**
     * A decision instance as rows: parameter, PHP type, the value as PHP would print it, and a short display value.
     *
     * @return list<array{name: string, type: string, php: string, display: string, kind: string}>
     */
    public static function decision(object $value): array
    {
        $rows = [];
        $constructor = new \ReflectionMethod($value, '__construct');

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $v = $value->{$name};
            $rows[] = [
                'name' => $name,
                'type' => (string) $parameter->getType(),
                'php' => self::php($v),
                'display' => self::display($v),
                'kind' => match (true) {
                    $v === null => 'null',
                    is_bool($v) => $v ? 'true' : 'false',
                    $v instanceof \UnitEnum => 'enum',
                    is_array($v) => 'list',
                    default => 'number',
                },
            ];
        }

        return $rows;
    }

    private static function php(mixed $v): string
    {
        return match (true) {
            $v === null => 'null',
            is_bool($v) => $v ? 'true' : 'false',
            $v instanceof \UnitEnum => class_basename($v::class).'::'.$v->name,
            is_array($v) => '['.implode(', ', array_map(self::php(...), $v)).']',
            is_float($v) => rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.') ?: '0',
            default => var_export($v, true),
        };
    }

    private static function display(mixed $v): string
    {
        return match (true) {
            $v === null => 'unsure',
            is_bool($v) => $v ? 'yes' : 'no',
            $v instanceof \BackedEnum => (string) $v->value,
            $v instanceof \UnitEnum => $v->name,
            is_array($v) => $v === [] ? 'none' : implode(', ', array_map(self::display(...), $v)),
            is_float($v) => number_format($v, 2),
            default => (string) $v,
        };
    }
}
