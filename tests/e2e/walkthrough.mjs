// End-to-end walkthrough of the playground in a real browser, with a screenshot per step.
//
//   docker compose up -d --wait
//   cd tests/e2e && npm install && npx playwright install chromium
//   node walkthrough.mjs                       # BASE_URL=http://localhost:8080, SHOTS_DIR=screenshots
//
// Every step asserts on what the page shows, so a failed step exits non-zero.

import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import assert from 'node:assert/strict';

const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8080';
const SHOTS_DIR = process.env.SHOTS_DIR ?? 'screenshots';
const TIMEOUT = Number(process.env.RUN_TIMEOUT ?? 180_000); // a cold checkpoint can take a while

mkdirSync(SHOTS_DIR, { recursive: true });

const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 2 });
const problems = [];
page.on('pageerror', (e) => problems.push(`page error: ${e.message}`));
// Failed requests are checked by each step (one step expects a 422), so only script errors count here.
page.on('console', (m) => m.type() === 'error' && !m.text().startsWith('Failed to load resource') && problems.push(`console: ${m.text()}`));

let n = 0;
async function step(name, fn) {
    n++;
    const label = `${String(n).padStart(2, '0')}-${name}`;
    process.stdout.write(`• ${label} … `);
    await fn();
    await page.waitForTimeout(400); // let the probability bars finish animating
    await page.screenshot({ path: `${SHOTS_DIR}/${label}.png`, fullPage: true });
    console.log('ok');
}

const testId = (id) => page.getByTestId(id);

async function runAndWait(target) {
    await testId(`run-${target}`).click();
    await testId(`${target}-result`).or(testId(`${target}-error`)).waitFor({ timeout: TIMEOUT });
    if (await testId(`${target}-error`).isVisible()) {
        throw new Error(`${target} failed: ${await testId(`${target}-error`).innerText()}`);
    }
}

try {
    await step('home', async () => {
        await page.goto(BASE_URL);
        await assert.doesNotReject(testId('health-pill').getByText('laya-serve online').waitFor({ timeout: 30_000 }));
        await testId('decide-empty').waitFor();
    });

    // ---- decide() ------------------------------------------------------------------------------

    await step('decide-triage', async () => {
        await page.getByRole('button', { name: 'Double billing' }).click();
        await runAndWait('decide');
        const object = await testId('decide-object').innerText();
        assert.match(object, /department\s*:\s*Department::Billing/);
        assert.match(object, /urgency\s*:\s*Urgency::\w+/);
        assert.match(object, /churn\s*:\s*(true|false)/);
        assert.equal(await page.locator('[data-testid="decide-result"] [data-answer]').count(), 3);
        assert.match(await testId('decide-meta').innerText(), /tok/);
    });

    await step('decide-triage-min-confidence', async () => {
        await testId('gate').check();
        await runAndWait('decide');
        const badges = await testId('decide-result').getByText(/^(passed|abstained)$/).count();
        assert.equal(badges, 3, 'every answer reports how the abstention gate decided it');
        await testId('gate').uncheck();
    });

    await step('decide-class-source', async () => {
        await testId('decide-view-source').click();
        await page.getByText('final readonly class Triage').waitFor();
        await page.getByRole('button', { name: 'Urgency.php' }).click();
        await page.getByText('#[Scale]').first().waitFor();
    });

    await step('decide-php-snippet', async () => {
        await testId('decide-view-php').click();
        await page.getByText('decideWithResult(').first().waitFor();
    });

    await step('decide-raw-json', async () => {
        await testId('decide-view-raw').click();
        await page.locator('pre code').filter({ hasText: '"answers"' }).waitFor();
        await testId('decide-view-answers').click();
    });

    await step('decide-moderation', async () => {
        await testId('decision-moderation').click();
        await page.getByRole('button', { name: 'Spam', exact: true }).click();
        await runAndWait('decide');
        assert.match(await testId('decide-object').innerText(), /sentiment\s*:\s*Sentiment::\w+/);
    });

    await step('decide-lead-scoring', async () => {
        await testId('decision-lead').click();
        await runAndWait('decide');
        assert.match(await testId('decide-object').innerText(), /intent\s*:\s*[\d.]+/);
    });

    await step('decide-tagging-italian', async () => {
        await testId('decision-tagging').click();
        await page.getByRole('button', { name: 'Italian' }).click();
        await runAndWait('decide');
        assert.match(await testId('decide-object').innerText(), /topics\s*:\s*\[/);
        assert.equal(await page.locator('[data-testid="decide-result"] [data-answer^="topics."]').count(), 4);
    });

    // ---- predict() -----------------------------------------------------------------------------

    await step('predict-support-preset', async () => {
        await testId('tab-predict').click();
        await testId('predict-empty').waitFor();
        await runAndWait('predict');
        assert.equal(await page.locator('[data-testid="predict-result"] [data-answer]').count(), 3);
    });

    await step('predict-email-preset', async () => {
        await testId('preset-email').click();
        await runAndWait('predict');
        assert.ok(await page.locator('[data-answer="team"]').isVisible());
    });

    await step('predict-custom-questions', async () => {
        await testId('preset-blank').click();
        await testId('predict-state').fill('Mi pedido llegó roto y nadie responde a mis correos desde hace una semana.');
        const q1 = testId('question-0');
        await q1.getByLabel('Question id').fill('topic');
        await q1.getByRole('button', { name: 'Choice' }).click();
        await q1.getByLabel('Instructions').fill('What is the customer writing about?');
        const labels = q1.getByPlaceholder('label');
        await labels.nth(0).fill('damaged_item');
        await labels.nth(1).fill('late_delivery');
        await q1.getByRole('button', { name: 'option' }).click();
        await labels.nth(2).fill('billing');

        await testId('add-question').click();
        const q2 = testId('question-1');
        await q2.getByLabel('Question id').fill('frustration');
        await q2.getByRole('button', { name: 'Score' }).click();
        await q2.getByLabel('Instructions').fill('How frustrated is the customer?');

        await testId('model').selectOption('multilingual');
        await runAndWait('predict');
        assert.equal(await page.locator('[data-testid="predict-result"] [data-answer]').count(), 2);
        assert.match(await testId('predict-meta').innerText(), /multilingual/);
    });

    await step('predict-php-snippet', async () => {
        await testId('predict-view-php').click();
        await page.getByText("Question::choice('What is the customer writing about?'").waitFor();
        await testId('predict-view-answers').click();
    });

    await step('predict-validation-error', async () => {
        await testId('add-question').click();
        await testId('run-predict').click();
        await testId('predict-error').waitFor();
        const error = await testId('predict-error').innerText();
        assert.equal(error.split('Every question needs instructions.').length - 1, 1, 'the message shows once');
    });

    // ---- Server --------------------------------------------------------------------------------

    await step('server', async () => {
        await testId('tab-server').click();
        await testId('health-status').getByText('laya-serve online').waitFor();
        await testId('refresh-health').click();
        await testId('health-status').getByText('laya-serve online').waitFor();
    });

    await step('mobile', async () => {
        await page.setViewportSize({ width: 390, height: 844 });
        await testId('tab-decide').click();
        await testId('decision-triage').click();
        await runAndWait('decide');
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        assert.ok(overflow <= 0, `page scrolls sideways by ${overflow}px on a phone`);
    });

    assert.deepEqual(problems, [], 'the browser reported errors');
    console.log(`\nAll ${n} steps passed. Screenshots in ${SHOTS_DIR}/`);
} catch (error) {
    await page.screenshot({ path: `${SHOTS_DIR}/failure.png`, fullPage: true }).catch(() => {});
    console.error('\n', error);
    process.exitCode = 1;
} finally {
    await browser.close();
}
