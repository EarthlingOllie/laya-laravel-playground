<section x-show="tab === 'server'" x-cloak class="grid gap-5 lg:grid-cols-2" data-testid="server-panel">
    <div class="panel p-6">
        <div class="mb-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-white/[0.06] text-ink-200"><x-icon name="server" /></span>
                <div>
                    <p class="text-sm font-semibold text-white">laya-serve</p>
                    <p class="font-mono text-xs text-ink-400" x-text="health.url ?? @js($layaUrl)"></p>
                </div>
            </div>
            <button type="button" class="btn-ghost" @click="health.status = 'checking'; checkHealth()" data-testid="refresh-health">
                <x-icon name="refresh" class="size-3.5" /> Refresh
            </button>
        </div>

        <dl class="divide-y divide-white/[0.06] text-sm">
            <div class="flex items-center justify-between py-3">
                <dt class="text-ink-400">Status</dt>
                <dd class="flex items-center gap-2 font-medium" data-testid="health-status"
                    :class="{ 'text-emerald-300': health.status === 'online', 'text-amber-200': health.status === 'checking' || health.status === 'degraded', 'text-rose-300': health.status === 'offline' }"
                    x-text="healthLabel"></dd>
            </div>
            <template x-if="health.online">
                <div>
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-ink-400">Device</dt>
                        <dd class="font-mono text-ink-100" x-text="health.device"></dd>
                    </div>
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-ink-400">Health latency</dt>
                        <dd class="font-mono text-ink-100" x-text="`${health.latency_ms} ms`"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-ink-400">Loaded checkpoints</dt>
                        <dd class="flex flex-wrap justify-end gap-1.5">
                            <template x-for="name in health.loaded"><span class="rounded-md bg-laya-500/15 px-2 py-0.5 font-mono text-xs text-laya-300" x-text="name"></span></template>
                            <span x-show="!health.loaded?.length" class="text-right text-xs text-ink-400">none yet: the first request loads one</span>
                        </dd>
                    </div>
                    <template x-for="(sha, name) in health.revisions" :key="name">
                        <div class="flex items-center justify-between py-3">
                            <dt class="font-mono text-xs text-ink-400" x-text="`${name} revision`"></dt>
                            <dd class="font-mono text-xs text-ink-200" x-text="sha ? sha.slice(0, 12) : 'unpinned'"></dd>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="health.status === 'offline'">
                <div class="py-3 text-sm text-ink-300">
                    <p class="text-rose-200/90" x-text="health.error"></p>
                    <p class="mt-3 text-xs leading-relaxed text-ink-400">
                        The playground retries every few seconds. On a fresh <code class="font-mono text-ink-200">docker compose up</code>,
                        laya-serve may still be building or starting. Follow it with
                        <code class="font-mono text-ink-200">docker compose logs -f laya</code>.
                    </p>
                </div>
            </template>
        </dl>
    </div>

    <div class="panel p-6">
        <p class="mb-4 text-sm font-semibold text-white">How the pieces fit</p>
        <div class="flex flex-col items-stretch gap-2 font-mono text-xs sm:flex-row sm:items-center">
            <div class="flex-1 rounded-xl border border-white/[0.08] bg-white/[0.03] p-3 text-center">
                <p class="text-ink-100">browser</p><p class="mt-0.5 text-ink-400">Alpine.js</p>
            </div>
            <span class="text-center text-ink-400">→</span>
            <div class="flex-1 rounded-xl border border-laya-400/30 bg-laya-500/10 p-3 text-center">
                <p class="text-laya-300">app</p><p class="mt-0.5 text-ink-400">Laravel + laya-php</p>
            </div>
            <span class="text-center text-ink-400">→</span>
            <div class="flex-1 rounded-xl border border-glow/30 bg-glow/10 p-3 text-center">
                <p class="text-cyan-300">laya</p><p class="mt-0.5 text-ink-400">laya-serve · PyTorch</p>
            </div>
        </div>
        <ul class="mt-5 space-y-3 text-sm leading-relaxed text-ink-300">
            <li><span class="font-mono text-xs text-laya-300">POST /api/decide</span>: <code class="font-mono text-xs">$laya->decideWithResult()</code> on a class in <code class="font-mono text-xs">app/Decisions</code>.</li>
            <li><span class="font-mono text-xs text-laya-300">POST /api/predict</span>: builds <code class="font-mono text-xs">Question::choice/score/yesNo</code> from the form and calls <code class="font-mono text-xs">$laya->predict()</code>.</li>
            <li><span class="font-mono text-xs text-laya-300">GET /api/health</span>: <code class="font-mono text-xs">$laya->health()</code>, polled by the status pill.</li>
            <li>laya-php sends <span class="font-mono text-xs text-cyan-300">POST /v1/systemone</span> to laya-serve, which routes each text to a checkpoint (English or multilingual) and answers every question in one forward pass.</li>
        </ul>
        <p class="mt-5 rounded-xl border border-white/[0.06] bg-white/[0.02] p-3 text-xs leading-relaxed text-ink-400">
            Weights are cached in the <code class="font-mono text-ink-200">laya-models</code> Docker volume, so only the first request per checkpoint is slow.
            Set <code class="font-mono text-ink-200">LAYA_PRELOAD=1</code> to download and load them at startup instead.
        </p>
    </div>
</section>
