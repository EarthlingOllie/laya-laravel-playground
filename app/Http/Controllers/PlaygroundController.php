<?php

namespace App\Http\Controllers;

use App\Playground\Catalog;
use App\Playground\Present;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use MarcReichel\Laya\Exceptions\AuthenticationException;
use MarcReichel\Laya\Exceptions\InvalidOptionException;
use MarcReichel\Laya\Exceptions\InvalidQuestionException;
use MarcReichel\Laya\Exceptions\LayaException;
use MarcReichel\Laya\Exceptions\ServerBusyException;
use MarcReichel\Laya\Exceptions\TransportException;
use MarcReichel\Laya\Exceptions\ValidationException;
use MarcReichel\Laya\Laya;
use MarcReichel\Laya\Model;
use MarcReichel\Laya\Question;

class PlaygroundController extends Controller
{
    public function index(): View
    {
        $decisions = [];
        foreach (Catalog::decisions() as $key => $decision) {
            $decisions[$key] = [
                'key' => $key,
                'class' => $decision['class'],
                'short' => class_basename($decision['class']),
                'title' => $decision['title'],
                'blurb' => $decision['blurb'],
                'icon' => $decision['icon'],
                'samples' => $decision['samples'],
                'files' => array_map(fn (string $class) => [
                    'name' => class_basename($class).'.php',
                    'source' => self::source($class),
                ], $decision['files']),
            ];
        }

        return view('playground', [
            'decisions' => $decisions,
            'presets' => Catalog::presets(),
            'layaUrl' => config('laya.url'),
        ]);
    }

    public function health(Laya $laya): JsonResponse
    {
        try {
            $started = hrtime(true);
            $health = $laya->health();

            return response()->json([
                'online' => true,
                'ok' => $health->ok,
                'device' => $health->device,
                'loaded' => $health->loaded,
                'revisions' => $health->revisions,
                'idle_unload_seconds' => $health->idleUnloadSeconds,
                'latency_ms' => round((hrtime(true) - $started) / 1e6),
                'url' => config('laya.url'),
            ]);
        } catch (LayaException $e) {
            return response()->json(['online' => false, 'ok' => false, 'error' => $e->getMessage(), 'url' => config('laya.url')]);
        }
    }

    public function predict(Request $request, Laya $laya): JsonResponse
    {
        $data = $request->validate([
            'state' => ['required', 'string', 'max:50000'],
            'questions' => ['required', 'array', 'min:1', 'max:64'],
            'questions.*.id' => ['required', 'string', 'distinct', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'questions.*.type' => ['required', Rule::in(['choice', 'score', 'yesno'])],
            'questions.*.instructions' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['array'],
            'questions.*.options.*.label' => ['required', 'string', 'max:200'],
            'questions.*.options.*.description' => ['nullable', 'string', 'max:500'],
            'questions.*.levels' => ['array'],
            'questions.*.levels.*' => ['required', 'string', 'max:200'],
            'questions.*.yes' => ['nullable', 'string', 'max:500'],
            'questions.*.no' => ['nullable', 'string', 'max:500'],
            ...self::optionRules(),
        ], [
            'questions.*.instructions.required' => 'Every question needs instructions.',
            'questions.*.id.distinct' => 'Question ids must be unique.',
            'questions.*.id.regex' => 'Question ids may only contain letters, numbers, _, - and .',
        ]);

        return $this->attempt(function () use ($data, $laya) {
            $questions = [];
            foreach ($data['questions'] as $q) {
                $questions[$q['id']] = match ($q['type']) {
                    'choice' => Question::choice($q['instructions'], collect($q['options'] ?? [])
                        ->mapWithKeys(fn ($o) => [$o['label'] => filled($o['description'] ?? null) ? $o['description'] : null])
                        ->all()),
                    'score' => Question::score($q['instructions'], array_values($q['levels'] ?? [])),
                    'yesno' => Question::yesNo($q['instructions'],
                        yes: filled($q['yes'] ?? null) ? $q['yes'] : null,
                        no: filled($q['no'] ?? null) ? $q['no'] : null),
                };
            }

            $started = hrtime(true);
            $result = $laya->predict($data['state'], $questions, ...self::options($data));

            return Present::result($result, $questions, (hrtime(true) - $started) / 1e6);
        });
    }

    public function decide(Request $request, Laya $laya): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(array_keys(Catalog::decisions()))],
            'state' => ['required', 'string', 'max:50000'],
            ...self::optionRules(),
        ]);

        return $this->attempt(function () use ($data, $laya) {
            $class = Catalog::decisionClass($data['decision']);

            $started = hrtime(true);
            $decision = $laya->decideWithResult($data['state'], $class, ...self::options($data));
            $ms = (hrtime(true) - $started) / 1e6;

            return Present::result($decision->result, Laya::questions($class), $ms) + [
                'class' => class_basename($class),
                'value' => Present::decision($decision->value),
            ];
        });
    }

    /** @return array<string, list<mixed>> */
    private static function optionRules(): array
    {
        return [
            'model' => ['nullable', Rule::in(['auto', ...array_column(Model::cases(), 'value')])],
            'min_confidence' => ['nullable', 'numeric', 'between:0,1'],
            'max_len' => ['nullable', 'integer', 'min:16', 'max:8192'],
        ];
    }

    /**
     * The optional arguments shared by predict() and decide().
     *
     * @param  array<string, mixed>  $data
     * @return array{model: ?Model, minConfidence: ?float, maxLen: ?int}
     */
    private static function options(array $data): array
    {
        return [
            'model' => Model::tryFrom($data['model'] ?? ''),
            'minConfidence' => isset($data['min_confidence']) ? (float) $data['min_confidence'] : null,
            'maxLen' => isset($data['max_len']) ? (int) $data['max_len'] : null,
        ];
    }

    /** Runs a laya call and turns its exceptions into JSON the UI can show. */
    private function attempt(callable $call): JsonResponse
    {
        try {
            return response()->json($call());
        } catch (LayaException $e) {
            [$status, $hint] = match (true) {
                $e instanceof TransportException => [503, 'laya-serve is unreachable. Is the laya container running? Try: docker compose logs laya'],
                $e instanceof ServerBusyException => [503, 'laya-serve is busy with another request. Try again in a moment.'],
                $e instanceof AuthenticationException => [502, 'laya-serve rejected the API key. Check LAYA_API_KEY in both services.'],
                $e instanceof ValidationException, $e instanceof InvalidQuestionException, $e instanceof InvalidOptionException => [422, null],
                default => [502, 'laya-serve could not answer. Its log says why: docker compose logs laya. On a first run, check it can reach huggingface.co to download the model.'],
            };

            return response()->json([
                'error' => $e->getMessage(),
                'exception' => Str::afterLast($e::class, '\\'),
                'hint' => $hint,
            ], $status);
        }
    }

    /** The source of a class file, without the opening tag, for display. */
    private static function source(string $class): string
    {
        $file = (new \ReflectionClass($class))->getFileName();

        return trim(Str::after(file_get_contents($file), '<?php'));
    }
}
