<section x-show="tab === 'decide'" class="grid gap-5 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
    {{-- Input --}}
    <div class="space-y-5">
        <div class="grid grid-cols-2 gap-3">
            @foreach ($decisions as $key => $decision)
                <button
                    type="button"
                    @click="pickDecision('{{ $key }}')"
                    data-testid="decision-{{ $key }}"
                    class="group relative overflow-hidden rounded-2xl border p-4 text-left transition"
                    :class="decide.key === '{{ $key }}'
                        ? 'border-laya-400/50 bg-laya-500/[0.12] shadow-lg shadow-laya-600/10'
                        : 'border-white/[0.07] bg-ink-900/60 hover:border-white/15 hover:bg-ink-850'"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <span class="grid size-7 place-items-center rounded-lg transition"
                              :class="decide.key === '{{ $key }}' ? 'bg-laya-500 text-white' : 'bg-white/[0.06] text-ink-300 group-hover:text-white'">
                            <x-icon :name="$decision['icon']" class="size-3.5" />
                        </span>
                        <span class="text-sm font-semibold text-white">{{ $decision['title'] }}</span>
                    </div>
                    <p class="font-mono text-[11px] text-laya-300/90">{{ $decision['short'] }}::class</p>
                    <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-ink-400">{{ $decision['blurb'] }}</p>
                </button>
            @endforeach
        </div>

        <div class="panel p-5">
            <div class="mb-2 flex items-center justify-between">
                <label for="decide-state" class="label !mb-0">Text to classify</label>
                <span class="font-mono text-[11px] text-ink-400" x-text="`${decide.state.length} chars`"></span>
            </div>
            <textarea id="decide-state" x-model="decide.state" rows="6" data-testid="decide-state"
                      class="field resize-y leading-relaxed" placeholder="Paste a support ticket, review, email…"></textarea>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="text-[11px] text-ink-400">Try:</span>
                <template x-for="sample in decision.samples" :key="sample.label">
                    <button type="button" class="chip" @click="decide.state = sample.text" x-text="sample.label"
                            :class="decide.state === sample.text && '!border-laya-400/50 !bg-laya-500/15 !text-white'"></button>
                </template>
            </div>

            <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/[0.06] pt-4">
                <p class="text-xs text-ink-400">
                    <span class="font-mono text-ink-300">$laya->decideWithResult($text, <span class="text-laya-300" x-text="decision.short"></span>::class)</span>
                </p>
                <button type="button" class="btn-primary" @click="runDecide()" :disabled="decide.running || !decide.state.trim()" data-testid="run-decide">
                    <template x-if="!decide.running"><span class="flex items-center gap-2"><x-icon name="play" class="size-3.5 fill-current" /> Decide</span></template>
                    <template x-if="decide.running"><span class="flex items-center gap-2"><span class="size-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> <span x-text="`${elapsed.toFixed(1)}s`"></span></span></template>
                </button>
            </div>
        </div>
    </div>

    {{-- Output --}}
    <div class="panel flex min-h-[32rem] flex-col p-5" data-testid="decide-output">
        @include('partials.result', ['target' => 'decide'])
    </div>
</section>
