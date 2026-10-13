<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laya Playground</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%238b5cf6'/%3E%3Cstop offset='1' stop-color='%2322d3ee'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='9' fill='url(%23g)'/%3E%3Cpath d='M11 8v16h11' stroke='white' stroke-width='3.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen overflow-x-hidden">
<div class="pointer-events-none absolute inset-0 aurora"></div>
<div class="pointer-events-none absolute inset-x-0 top-0 h-[60rem] grid-bg"></div>

<div
    class="relative mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8"
    x-data="playground({ decisions: @js($decisions), presets: @js($presets) })"
    @keydown.window.meta.enter.prevent="run()"
    @keydown.window.ctrl.enter.prevent="run()"
>
    {{-- Header --------------------------------------------------------------------------------------------- --}}
    <header class="flex flex-wrap items-center justify-between gap-4 py-6">
        <a href="/" class="group flex items-center gap-3">
            <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-laya-500 to-glow shadow-lg shadow-laya-600/30">
                <svg viewBox="0 0 32 32" class="size-5"><path d="M11 8v16h11" stroke="white" stroke-width="3.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="leading-tight">
                <span class="block text-[15px] font-semibold tracking-tight text-white">Laya Playground</span>
                <span class="block text-xs text-ink-400">typed text decisions · self-hosted</span>
            </span>
        </a>

        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="tab = 'server'"
                data-testid="health-pill"
                class="flex items-center gap-2 rounded-full border border-white/[0.08] bg-ink-900/70 py-1.5 pr-3.5 pl-3 text-xs font-medium backdrop-blur transition hover:border-white/20"
            >
                <span class="relative flex size-2">
                    <span class="absolute inline-flex size-full animate-ping rounded-full opacity-60"
                          :class="{ 'bg-emerald-400': health.status === 'online', 'bg-amber-400': health.status === 'checking' || health.status === 'degraded', 'bg-rose-500': health.status === 'offline' }"></span>
                    <span class="relative inline-flex size-2 rounded-full"
                          :class="{ 'bg-emerald-400': health.status === 'online', 'bg-amber-400': health.status === 'checking' || health.status === 'degraded', 'bg-rose-500': health.status === 'offline' }"></span>
                </span>
                <span x-text="healthLabel" class="text-ink-200"></span>
                <span x-show="health.status === 'online'" x-cloak class="text-ink-400" x-text="`· ${health.device}`"></span>
            </button>
            <a href="https://github.com/marcreichel/laya-php" target="_blank" rel="noopener" class="btn-ghost" title="laya-php on GitHub">
                <x-icon name="github" /> <span class="hidden sm:inline">laya-php</span>
            </a>
        </div>
    </header>

    {{-- Hero ----------------------------------------------------------------------------------------------- --}}
    <section class="pt-6 pb-10 sm:pt-10">
        <div class="max-w-3xl">
            <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-laya-400/20 bg-laya-500/10 px-3 py-1 text-xs font-medium text-laya-300">
                <x-icon name="sparkles" class="size-3.5" /> No LLM API · 100+ languages · one forward pass
            </p>
            <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                Ask typed questions about any text,
                <span class="bg-gradient-to-r from-laya-300 via-laya-400 to-glow bg-clip-text text-transparent">get typed answers back.</span>
            </h1>
            <p class="mt-4 text-base leading-relaxed text-ink-300">
                A playground for <a class="text-ink-100 underline decoration-laya-500/50 underline-offset-4 hover:decoration-laya-400" href="https://github.com/marcreichel/laya-php" target="_blank" rel="noopener">laya-php</a>,
                talking to a local <code class="rounded bg-white/5 px-1.5 py-0.5 font-mono text-[13px] text-ink-200">laya-serve</code>.
                Turn text into PHP objects with <code class="font-mono text-[13px] text-laya-300">decide()</code>, or build ad-hoc
                <span class="text-ink-100">choice</span>, <span class="text-ink-100">score</span> and <span class="text-ink-100">yes/no</span> questions with <code class="font-mono text-[13px] text-laya-300">predict()</code>.
            </p>
        </div>
    </section>

    {{-- Tabs + options -------------------------------------------------------------------------------------- --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex gap-1 rounded-xl border border-white/[0.07] bg-ink-900/70 p-1 backdrop-blur" role="tablist">
            <button class="tab" role="tab" :aria-selected="tab === 'decide'" @click="tab = 'decide'" data-testid="tab-decide">
                <span class="font-mono text-laya-300">decide()</span> <span class="hidden sm:inline">Decision classes</span>
            </button>
            <button class="tab" role="tab" :aria-selected="tab === 'predict'" @click="tab = 'predict'" data-testid="tab-predict">
                <span class="font-mono text-laya-300">predict()</span> <span class="hidden sm:inline">Question builder</span>
            </button>
            <button class="tab" role="tab" :aria-selected="tab === 'server'" @click="tab = 'server'" data-testid="tab-server">
                Server
            </button>
        </nav>

        <div x-show="tab !== 'server'" class="flex flex-wrap items-center gap-2 text-xs">
            <label class="flex items-center gap-2 rounded-xl border border-white/[0.07] bg-ink-900/70 py-1.5 pr-1.5 pl-3 backdrop-blur">
                <span class="text-ink-400">Model</span>
                <select x-model="model" data-testid="model" class="rounded-lg border-0 bg-white/[0.06] py-1 pr-7 pl-2 text-xs text-ink-100 outline-none focus:ring-2 focus:ring-laya-500/40">
                    <option value="auto">auto (router)</option>
                    <option value="english">english</option>
                    <option value="multilingual">multilingual</option>
                    <option value="typed-decisions">typed-decisions</option>
                </select>
            </label>
            <div class="flex items-center gap-2 rounded-xl border border-white/[0.07] bg-ink-900/70 px-3 py-1.5 backdrop-blur">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" x-model="gate" data-testid="gate" class="size-3.5 accent-laya-500">
                    <span class="text-ink-400">minConfidence</span>
                </label>
                <input type="range" min="0" max="1" step="0.05" x-model="minConfidence" :disabled="!gate" class="w-20 accent-laya-500 disabled:opacity-30">
                <span class="w-8 font-mono text-ink-200" :class="!gate && 'opacity-30'" x-text="Number(minConfidence).toFixed(2)"></span>
            </div>
            <label class="flex items-center gap-2 rounded-xl border border-white/[0.07] bg-ink-900/70 py-1.5 pr-1.5 pl-3 backdrop-blur">
                <span class="text-ink-400">maxLen</span>
                <input type="number" min="16" max="8192" step="64" x-model="maxLen" placeholder="default" class="w-20 rounded-lg border-0 bg-white/[0.06] px-2 py-1 font-mono text-xs text-ink-100 outline-none placeholder:text-ink-400 focus:ring-2 focus:ring-laya-500/40">
            </label>
        </div>
    </div>

    @include('partials.decide')
    @include('partials.predict')
    @include('partials.server')

    <footer class="mt-16 flex flex-wrap items-center justify-between gap-3 border-t border-white/[0.06] pt-6 text-xs text-ink-400">
        <p>
            Built with Laravel {{ app()->version() }} and
            <a class="text-ink-200 hover:text-white" href="https://github.com/marcreichel/laya-php" target="_blank" rel="noopener">laya-php</a> ·
            Model by <a class="text-ink-200 hover:text-white" href="https://github.com/NandhaKishorM/laya" target="_blank" rel="noopener">Laya</a>
        </p>
        <p><kbd class="rounded border border-white/10 bg-white/5 px-1.5 py-0.5 font-mono">⌘/Ctrl ↵</kbd> runs the current tab</p>
    </footer>
</div>
</body>
</html>
