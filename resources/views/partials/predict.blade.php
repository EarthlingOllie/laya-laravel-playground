<section x-show="tab === 'predict'" x-cloak class="grid gap-5 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
    {{-- Input --}}
    <div class="space-y-5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-semibold tracking-[0.12em] text-ink-400 uppercase">Presets</span>
            <template x-for="(preset, key) in presets" :key="key">
                <button type="button" class="chip" @click="loadPreset(key)" x-text="preset.title" :data-testid="`preset-${key}`"
                        :class="predict.preset === key && '!border-laya-400/50 !bg-laya-500/15 !text-white'"></button>
            </template>
        </div>

        <div class="panel p-5">
            <div class="mb-2 flex items-center justify-between">
                <label for="predict-state" class="label !mb-0">State <span class="normal-case tracking-normal text-ink-400/70">(the text laya reads)</span></label>
                <span class="font-mono text-[11px] text-ink-400" x-text="`${predict.state.length} chars`"></span>
            </div>
            <textarea id="predict-state" x-model="predict.state" rows="4" data-testid="predict-state"
                      class="field resize-y leading-relaxed" placeholder="Any text: a ticket, an email, a chat message…"></textarea>
        </div>

        <div class="space-y-3">
            <template x-for="(q, index) in predict.questions" :key="index">
                <div class="panel p-4" :data-testid="`question-${index}`">
                    <div class="mb-3 flex items-center gap-2">
                        <input x-model="q.id" class="field !w-40 !rounded-lg !px-2.5 !py-1.5 font-mono !text-xs !text-laya-300" placeholder="question_id" aria-label="Question id">
                        <div class="flex gap-0.5 rounded-lg bg-white/[0.04] p-0.5">
                            <template x-for="type in [['choice', 'Choice'], ['score', 'Score'], ['yesno', 'Yes / no']]">
                                <button type="button" class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                                        :class="q.type === type[0] ? 'bg-laya-500/80 text-white shadow' : 'text-ink-400 hover:text-ink-100'"
                                        @click="setType(q, type[0])" x-text="type[1]"></button>
                            </template>
                        </div>
                        <button type="button" class="btn-ghost ml-auto !p-1.5" @click="removeQuestion(index)" x-show="predict.questions.length > 1" title="Remove question">
                            <x-icon name="x" class="size-3.5" />
                        </button>
                    </div>

                    <input x-model="q.instructions" class="field" placeholder="The question, e.g. Does the user threaten to cancel?" aria-label="Instructions">

                    {{-- Choice: label => description --}}
                    <div x-show="q.type === 'choice'" class="mt-3 space-y-1.5">
                        <p class="label">Options <span class="normal-case tracking-normal text-ink-400/70">label → optional description</span></p>
                        <template x-for="(option, i) in q.options" :key="i">
                            <div class="flex items-center gap-1.5">
                                <input x-model="option.label" class="field !w-36 !py-1.5 font-mono !text-xs" placeholder="label">
                                <input x-model="option.description" class="field !py-1.5 !text-xs" placeholder="what this option means">
                                <button type="button" class="btn-ghost !p-1.5" @click="q.options.splice(i, 1)" x-show="q.options.length > 2"><x-icon name="x" class="size-3" /></button>
                            </div>
                        </template>
                        <button type="button" class="btn-ghost" @click="q.options.push({ label: '', description: '' })"><x-icon name="plus" class="size-3" /> option</button>
                    </div>

                    {{-- Score: ordered levels --}}
                    <div x-show="q.type === 'score'" class="mt-3 space-y-1.5">
                        <p class="label">Levels <span class="normal-case tracking-normal text-ink-400/70">lowest first</span></p>
                        <template x-for="(level, i) in q.levels" :key="i">
                            <div class="flex items-center gap-1.5">
                                <span class="w-5 text-center font-mono text-[11px] text-ink-400" x-text="i"></span>
                                <input x-model="q.levels[i]" class="field !py-1.5 !text-xs" placeholder="level description">
                                <button type="button" class="btn-ghost !p-1.5" @click="q.levels.splice(i, 1)" x-show="q.levels.length > 2"><x-icon name="x" class="size-3" /></button>
                            </div>
                        </template>
                        <button type="button" class="btn-ghost" @click="q.levels.push('')"><x-icon name="plus" class="size-3" /> level</button>
                    </div>

                    {{-- Yes / no: optional descriptions --}}
                    <div x-show="q.type === 'yesno'" class="mt-3 grid grid-cols-2 gap-1.5">
                        <input x-model="q.yes" class="field !py-1.5 !text-xs" placeholder="yes means… (optional)">
                        <input x-model="q.no" class="field !py-1.5 !text-xs" placeholder="no means… (optional)">
                    </div>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-between gap-3">
            <button type="button" class="btn-ghost !text-sm" @click="addQuestion()" data-testid="add-question"><x-icon name="plus" /> Add question</button>
            <button type="button" class="btn-primary" @click="runPredict()" :disabled="predict.running || !predict.state.trim()" data-testid="run-predict">
                <template x-if="!predict.running"><span class="flex items-center gap-2"><x-icon name="play" class="size-3.5 fill-current" /> Predict</span></template>
                <template x-if="predict.running"><span class="flex items-center gap-2"><span class="size-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> <span x-text="`${elapsed.toFixed(1)}s`"></span></span></template>
            </button>
        </div>
    </div>

    {{-- Output --}}
    <div class="panel flex min-h-[32rem] flex-col self-start p-5 lg:sticky lg:top-5" data-testid="predict-output">
        @include('partials.result', ['target' => 'predict'])
    </div>
</section>
