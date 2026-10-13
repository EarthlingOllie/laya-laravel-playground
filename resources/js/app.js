import Alpine from 'alpinejs';
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import json from 'highlight.js/lib/languages/json';

hljs.registerLanguage('php', php);
hljs.registerLanguage('json', json);

const blankQuestion = (n) => ({
    id: `question_${n}`,
    type: 'yesno',
    instructions: '',
    options: [
        { label: '', description: '' },
        { label: '', description: '' },
    ],
    levels: ['low', 'medium', 'high'],
    yes: '',
    no: '',
});

const clone = (value) => JSON.parse(JSON.stringify(value));

const phpString = (s) => `'${String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;

function highlight(code, language) {
    return hljs.highlight(code, { language }).value;
}

Alpine.data('playground', ({ decisions, presets }) => ({
    decisions,
    presets,
    tab: 'decide',

    // Shared request options.
    model: 'auto',
    gate: false,
    minConfidence: 0.7,
    maxLen: '',

    health: { status: 'checking' },

    decide: {
        key: 'triage',
        state: decisions.triage.samples[0].text,
        running: false,
        result: null,
        error: null,
        view: 'answers',
        file: 0,
    },

    predict: {
        preset: 'support',
        state: presets.support.state,
        questions: clone(presets.support.questions),
        running: false,
        result: null,
        error: null,
        view: 'answers',
    },

    elapsed: 0,
    timer: null,

    init() {
        this.checkHealth();
        const hash = window.location.hash.replace('#', '');
        if (['decide', 'predict', 'server'].includes(hash)) {
            this.tab = hash;
        }
        this.$watch('tab', (value) => history.replaceState(null, '', `#${value}`));
    },

    // ---- Server health --------------------------------------------------------------

    async checkHealth() {
        try {
            const response = await fetch('/api/health', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            this.health = { ...data, status: data.online ? (data.ok ? 'online' : 'degraded') : 'offline', checkedAt: new Date() };
        } catch (e) {
            this.health = { status: 'offline', error: String(e), checkedAt: new Date() };
        }
        clearTimeout(this._healthTimer);
        this._healthTimer = setTimeout(() => this.checkHealth(), this.health.status === 'online' ? 15000 : 4000);
    },

    get healthLabel() {
        return {
            checking: 'Connecting…',
            online: 'laya-serve online',
            degraded: 'laya-serve degraded',
            offline: 'laya-serve offline',
        }[this.health.status];
    },

    // ---- Running ---------------------------------------------------------------------

    options() {
        return {
            model: this.model,
            min_confidence: this.gate ? Number(this.minConfidence) : null,
            max_len: this.maxLen ? Number(this.maxLen) : null,
        };
    },

    async call(target, url, body) {
        target.running = true;
        target.error = null;
        this.elapsed = 0;
        const started = performance.now();
        this.timer = setInterval(() => (this.elapsed = (performance.now() - started) / 1000), 100);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({ error: `HTTP ${response.status}` }));

            if (!response.ok) {
                target.error = {
                    title: data.exception ?? (response.status === 422 ? 'Check your input' : `HTTP ${response.status}`),
                    message: data.error ?? data.message ?? 'Something went wrong.',
                    hint: data.hint ?? null,
                    details: data.errors ? Object.values(data.errors).flat() : [],
                };
                return;
            }

            target.result = data;
            this.checkHealth();
        } catch (e) {
            target.error = { title: 'Network error', message: String(e), details: [] };
        } finally {
            clearInterval(this.timer);
            target.running = false;
        }
    },

    runDecide() {
        if (this.decide.running || !this.decide.state.trim()) return;
        this.call(this.decide, '/api/decide', { decision: this.decide.key, state: this.decide.state, ...this.options() });
    },

    runPredict() {
        if (this.predict.running || !this.predict.state.trim()) return;
        this.call(this.predict, '/api/predict', { state: this.predict.state, questions: this.wireQuestions(), ...this.options() });
    },

    run() {
        this.tab === 'predict' ? this.runPredict() : this.runDecide();
    },

    // ---- Decide tab ------------------------------------------------------------------

    get decision() {
        return this.decisions[this.decide.key];
    },

    pickDecision(key) {
        if (this.decide.key === key) return;
        this.decide.key = key;
        this.decide.state = this.decisions[key].samples[0].text;
        this.decide.result = null;
        this.decide.error = null;
        this.decide.file = 0;
    },

    // ---- Predict tab -----------------------------------------------------------------

    loadPreset(key) {
        this.predict.preset = key;
        this.predict.state = this.presets[key].state;
        this.predict.questions = clone(this.presets[key].questions);
        this.predict.result = null;
        this.predict.error = null;
    },

    addQuestion() {
        let n = this.predict.questions.length + 1;
        while (this.predict.questions.some((q) => q.id === `question_${n}`)) n++;
        this.predict.questions.push(blankQuestion(n));
    },

    removeQuestion(index) {
        this.predict.questions.splice(index, 1);
    },

    setType(question, type) {
        question.type = type;
        question.options ??= [{ label: '', description: '' }, { label: '', description: '' }];
        question.levels ??= ['low', 'medium', 'high'];
    },

    wireQuestions() {
        return this.predict.questions.map((q) => ({
            id: q.id.trim(),
            type: q.type,
            instructions: q.instructions,
            ...(q.type === 'choice' ? { options: q.options.filter((o) => o.label.trim() !== '') } : {}),
            ...(q.type === 'score' ? { levels: q.levels.filter((l) => l.trim() !== '') } : {}),
            ...(q.type === 'yesno' ? { yes: q.yes || null, no: q.no || null } : {}),
        }));
    },

    // ---- Results ---------------------------------------------------------------------

    get active() {
        return this.tab === 'predict' ? this.predict : this.decide;
    },

    pct(p) {
        return `${(Number(p) * 100).toFixed(p >= 0.995 || p < 0.001 ? 0 : 1)}%`;
    },

    confidenceTone(c) {
        if (c >= 0.8) return 'text-emerald-300 bg-emerald-400/10 ring-emerald-400/20';
        if (c >= 0.6) return 'text-amber-200 bg-amber-400/10 ring-amber-400/20';
        return 'text-rose-300 bg-rose-400/10 ring-rose-400/20';
    },

    valueTone(kind) {
        return {
            enum: 'text-laya-300',
            true: 'text-emerald-300',
            false: 'text-rose-300',
            number: 'text-orange-300',
            list: 'text-cyan-300',
            null: 'text-ink-400 italic',
        }[kind];
    },

    highlighted(code, language = 'php') {
        return highlight(code, language);
    },

    rawJson(result) {
        return highlight(JSON.stringify(result?.raw ?? {}, null, 2), 'json');
    },

    // ---- PHP snippets ----------------------------------------------------------------

    phpOptions() {
        const args = [];
        if (this.model !== 'auto') {
            const name = { english: 'English', multilingual: 'Multilingual', 'typed-decisions': 'TypedDecisions' }[this.model];
            args.push(`model: Model::${name}`);
        }
        if (this.gate) args.push(`minConfidence: ${Number(this.minConfidence)}`);
        if (this.maxLen) args.push(`maxLen: ${Number(this.maxLen)}`);
        return args;
    },

    get decideSnippet() {
        const d = this.decision;
        const args = [phpString(this.decide.state), `${d.short}::class`, ...this.phpOptions()];
        const uses = ['MarcReichel\\Laya\\Laya', `App\\Decisions\\${d.short}`];
        if (this.model !== 'auto') uses.push('MarcReichel\\Laya\\Model');

        return `${uses.map((u) => `use ${u};`).join('\n')}

$laya = app(Laya::class);

$decision = $laya->decideWithResult(
    ${args.join(',\n    ')},
);

$decision->value;   // ${d.short}
$decision->result;  // the raw Result, with every probability`;
    },

    get predictSnippet() {
        const questions = this.wireQuestions().map((q) => {
            let factory;
            if (q.type === 'choice') {
                const opts = q.options
                    .map((o) => (o.description ? `        ${phpString(o.label)} => ${phpString(o.description)},` : `        ${phpString(o.label)} => null,`))
                    .join('\n');
                factory = `Question::choice(${phpString(q.instructions)}, [\n${opts}\n    ])`;
            } else if (q.type === 'score') {
                factory = `Question::score(${phpString(q.instructions)}, [${q.levels.map(phpString).join(', ')}])`;
            } else {
                const extra = [q.yes ? `yes: ${phpString(q.yes)}` : null, q.no ? `no: ${phpString(q.no)}` : null].filter(Boolean);
                factory = `Question::yesNo(${[phpString(q.instructions), ...extra].join(', ')})`;
            }
            return `    ${phpString(q.id)} => ${factory},`;
        });
        const uses = ['MarcReichel\\Laya\\Laya', 'MarcReichel\\Laya\\Question'];
        if (this.model !== 'auto') uses.push('MarcReichel\\Laya\\Model');
        const opts = this.phpOptions();

        return `${uses.map((u) => `use ${u};`).join('\n')}

$laya = app(Laya::class);

$result = $laya->predict(${phpString(this.predict.state)}, [
${questions.join('\n')}
]${opts.length ? `, ${opts.join(', ')}` : ''});

$result->routedModel; // the checkpoint laya picked`;
    },

    copied: false,

    async copy(text) {
        await navigator.clipboard?.writeText(text);
        this.copied = true;
        setTimeout(() => (this.copied = false), 1500);
    },
}));

window.Alpine = Alpine;
Alpine.start();
