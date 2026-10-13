@php($t = $target)
@php($views = $t === 'decide'
    ? ['answers' => 'Result', 'source' => 'Class source', 'raw' => 'Raw JSON', 'php' => 'PHP']
    : ['answers' => 'Result', 'raw' => 'Raw JSON', 'php' => 'PHP'])

<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <div class="flex gap-1 rounded-lg bg-white/[0.03] p-0.5">
        @foreach ($views as $view => $label)
            <button type="button" class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                    data-testid="{{ $t }}-view-{{ $view }}"
                    :class="{{ $t }}.view === '{{ $view }}' ? 'bg-white/10 text-white' : 'text-ink-400 hover:text-ink-100'"
                    @click="{{ $t }}.view = '{{ $view }}'">{{ $label }}</button>
        @endforeach
    </div>
    <template x-if="{{ $t }}.result && {{ $t }}.view === 'answers'">
        <div class="flex flex-wrap items-center gap-1.5 font-mono text-[11px] text-ink-400" data-testid="{{ $t }}-meta">
            <span class="rounded-md bg-white/[0.04] px-2 py-1" title="Routed checkpoint">⇢ <span class="text-ink-200" x-text="{{ $t }}.result.meta.routed_model ?? 'n/a'"></span></span>
            <span class="rounded-md bg-white/[0.04] px-2 py-1"><span class="text-ink-200" x-text="{{ $t }}.result.meta.input_tokens"></span> tok</span>
            <span class="rounded-md bg-white/[0.04] px-2 py-1"><span class="text-ink-200" x-text="{{ $t }}.result.meta.duration_ms"></span> ms</span>
            <span x-show="{{ $t }}.result.meta.truncated" class="rounded-md bg-amber-400/10 px-2 py-1 text-amber-200">truncated</span>
        </div>
    </template>
</div>

{{-- Class source is always available, so it sits outside the result states. --}}
@if ($t === 'decide')
    <div x-show="decide.view === 'source'" class="flex flex-1 flex-col">
        <div class="mb-2 flex gap-1">
            <template x-for="(file, i) in decision.files" :key="file.name">
                <button type="button" class="rounded-md px-2 py-1 font-mono text-[11px] transition"
                        :class="decide.file === i ? 'bg-laya-500/15 text-laya-300' : 'text-ink-400 hover:text-ink-100'"
                        @click="decide.file = i" x-text="file.name"></button>
            </template>
        </div>
        <pre class="code flex-1"><code x-html="highlighted(decision.files[decide.file]?.source ?? '')"></code></pre>
    </div>
@endif

<div x-show="{{ $t }}.view === 'php'" class="relative flex flex-1 flex-col">
    <button type="button" class="btn-ghost absolute top-2 right-2 z-10 bg-ink-900/80" @click="copy({{ $t }}Snippet)">
        <span x-show="!copied" class="flex items-center gap-1.5"><x-icon name="copy" class="size-3.5" /> Copy</span>
        <span x-show="copied" class="flex items-center gap-1.5 text-emerald-300"><x-icon name="check" class="size-3.5" /> Copied</span>
    </button>
    <pre class="code flex-1"><code x-html="highlighted({{ $t }}Snippet)"></code></pre>
    <p class="mt-2 text-[11px] text-ink-400">The call this tab makes, with your current text and options. Paste it into any Laravel app with laya-php installed.</p>
</div>

<div x-show="['answers', 'raw'].includes({{ $t }}.view)" class="flex flex-1 flex-col">
    {{-- Running --}}
    <template x-if="{{ $t }}.running">
        <div class="space-y-3" data-testid="{{ $t }}-loading">
            <div class="shimmer h-20 rounded-xl"></div>
            <div class="shimmer h-14 rounded-xl"></div>
            <div class="shimmer h-14 rounded-xl"></div>
            <p x-show="elapsed > 4" x-transition class="pt-2 text-center text-xs text-ink-400">
                Still working… the first request to a checkpoint downloads its weights (about 1 GB) and loads them.
            </p>
        </div>
    </template>

    {{-- Error --}}
    <template x-if="!{{ $t }}.running && {{ $t }}.error">
        <div class="rounded-xl border border-rose-500/25 bg-rose-500/[0.07] p-4" data-testid="{{ $t }}-error">
            <div class="flex items-start gap-3">
                <x-icon name="alert" class="mt-0.5 size-4 text-rose-300" />
                <div class="min-w-0 space-y-1.5">
                    <p class="text-sm font-semibold text-rose-200" x-text="{{ $t }}.error.title"></p>
                    <p class="text-sm break-words text-rose-100/80" x-text="{{ $t }}.error.message"></p>
                    <ul class="list-disc space-y-0.5 pl-4 text-xs text-rose-100/70" x-show="{{ $t }}.error.details.length > 1">
                        <template x-for="detail in {{ $t }}.error.details"><li x-text="detail"></li></template>
                    </ul>
                    <p class="text-xs text-ink-300" x-show="{{ $t }}.error.hint" x-text="{{ $t }}.error.hint"></p>
                </div>
            </div>
        </div>
    </template>

    {{-- Empty --}}
    <template x-if="!{{ $t }}.running && !{{ $t }}.error && !{{ $t }}.result">
        <div class="flex flex-1 flex-col items-center justify-center py-16 text-center" data-testid="{{ $t }}-empty">
            <div class="relative mb-5">
                <div class="absolute inset-0 rounded-full bg-laya-500/30 blur-2xl"></div>
                <div class="relative grid size-14 place-items-center rounded-2xl border border-white/10 bg-ink-850">
                    <x-icon name="sparkles" class="size-6 text-laya-300" />
                </div>
            </div>
            <p class="text-sm font-medium text-ink-100">{{ $t === 'decide' ? 'Run a decision class' : 'Ask your questions' }}</p>
            <p class="mt-1 max-w-xs text-xs leading-relaxed text-ink-400">
                {{ $t === 'decide'
                    ? 'Pick a class, choose a sample or paste your own text, and hit Decide.'
                    : 'Edit the questions on the left, then hit Predict to see every option\'s probability.' }}
            </p>
        </div>
    </template>

    {{-- Raw JSON --}}
    <template x-if="!{{ $t }}.running && {{ $t }}.result && {{ $t }}.view === 'raw'">
        <pre class="code max-h-[40rem] flex-1"><code x-html="rawJson({{ $t }}.result)"></code></pre>
    </template>

    {{-- Result --}}
    <template x-if="!{{ $t }}.running && {{ $t }}.result && {{ $t }}.view === 'answers'">
        <div class="space-y-4" data-testid="{{ $t }}-result">
            @if ($t === 'decide')
                {{-- The hydrated object, printed like PHP would. --}}
                <div class="overflow-hidden rounded-xl border border-laya-400/20 bg-gradient-to-br from-laya-500/[0.08] to-glow/[0.03]" data-testid="decide-object">
                    <div class="flex items-center justify-between border-b border-white/[0.06] px-4 py-2">
                        <span class="font-mono text-xs text-ink-300">$decision->value</span>
                        <span class="rounded-md bg-laya-500/15 px-2 py-0.5 font-mono text-[11px] text-laya-300" x-text="`App\\Decisions\\${decide.result.class}`"></span>
                    </div>
                    <div class="px-4 py-3 font-mono text-[13px] leading-7">
                        <span class="text-cyan-300" x-text="decide.result.class"></span> <span class="text-ink-400">{</span>
                        <template x-for="row in decide.result.value" :key="row.name">
                            <div class="flex flex-wrap items-baseline gap-x-2 pl-5">
                                <span class="text-ink-200" x-text="row.name"></span><span class="text-ink-400">:</span>
                                <span :class="valueTone(row.kind)" x-text="row.php"></span>
                                <span class="ml-auto text-[11px] text-ink-400" x-text="row.type"></span>
                            </div>
                        </template>
                        <span class="text-ink-400">}</span>
                    </div>
                </div>
            @endif

            {{-- One card per answer --}}
            <template x-for="answer in {{ $t }}.result.answers" :key="answer.id">
                <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4" :data-answer="answer.id">
                    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-laya-300" x-text="answer.id"></span>
                                <span class="rounded bg-white/[0.05] px-1.5 py-px font-mono text-[10px] tracking-wide text-ink-400 uppercase"
                                      x-text="answer.type === 'yesno' ? 'yes/no' : answer.type"></span>
                            </div>
                            <p class="mt-1 text-sm text-ink-200" x-text="answer.instructions"></p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span x-show="answer.abstention" class="rounded-full px-2 py-0.5 text-[11px] font-medium ring-1"
                                  :class="answer.abstention === 'passed' ? 'text-emerald-300 bg-emerald-400/10 ring-emerald-400/20' : 'text-amber-200 bg-amber-400/10 ring-amber-400/20'"
                                  :title="`abstention threshold ${answer.abstention_threshold}`"
                                  x-text="answer.abstention"></span>
                            <span class="rounded-full px-2 py-0.5 font-mono text-[11px] ring-1" :class="confidenceTone(answer.answer_confidence)"
                                  title="answerConfidence: calibrated, comparable across question types"
                                  x-text="`conf ${pct(answer.answer_confidence)}`"></span>
                        </div>
                    </div>

                    <div class="mb-3 flex items-baseline gap-2">
                        <span class="text-lg font-semibold text-white" x-text="answer.headline"></span>
                        <template x-if="answer.type === 'score'">
                            <span class="font-mono text-xs text-ink-400" x-text="`expected level ${answer.score.toFixed(2)} of ${answer.max_level}`"></span>
                        </template>
                        <template x-if="answer.type === 'yesno'">
                            <span class="font-mono text-xs text-ink-400" x-text="`P(yes) = ${answer.probability.toFixed(3)}`"></span>
                        </template>
                    </div>

                    <template x-if="answer.type === 'score'">
                        <div class="relative mb-4 h-1.5 rounded-full bg-gradient-to-r from-emerald-400/30 via-amber-400/30 to-rose-400/40">
                            <span class="absolute top-1/2 size-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-laya-500 shadow-lg shadow-laya-600/50 transition-[left] duration-700"
                                  :style="`left: ${(answer.score / answer.max_level) * 100}%`"></span>
                        </div>
                    </template>

                    <div class="space-y-1.5">
                        <template x-for="option in answer.options" :key="option.label">
                            <div class="grid grid-cols-[minmax(0,9rem)_1fr_3.5rem] items-center gap-3 text-xs">
                                <span class="truncate" :class="option.top ? 'font-medium text-white' : 'text-ink-400'" x-text="option.label" :title="option.label"></span>
                                <div class="bar-track"><div class="bar-fill" :class="option.top && 'top'" :style="`width: ${Math.max(option.p * 100, 0.5)}%`"></div></div>
                                <span class="text-right font-mono" :class="option.top ? 'text-white' : 'text-ink-400'" x-text="pct(option.p)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
