<?php

use App\Models\User;
use Illuminate\Support\Facades\Process;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\ExecutableFinder;

function retinaFooterDoctor(): User
{
    app(PermissionRegistrar::class)
        ->forgetCachedPermissions();

    Role::findOrCreate('doctor', 'web');

    $user = User::factory()->create();

    $user->assignRole('doctor');

    acceptCurrentRetinaLegalDocuments($user);

    return $user;
}

function retinaFooterScreeningHtml(): string
{
    return test()
        ->actingAs(retinaFooterDoctor())
        ->get(route('screening'))
        ->assertOk()
        ->getContent();
}

/**
 * Run the screening page's own runInference() and result-state functions
 * in Node against a stub DOM and a stubbed fetch, and return the displayed
 * state after each step of the upload sequences.
 */
function retinaFooterRunScenarios(string $html): array
{
    $node = (new ExecutableFinder)->find('node');

    if ($node === null) {
        throw new RuntimeException('Node.js is required for the screening UI state tests.');
    }

    $dir = sys_get_temp_dir().'/retina-footer-'.bin2hex(random_bytes(6));
    mkdir($dir);

    $htmlPath = $dir.'/screening.html';
    $harnessPath = $dir.'/harness.mjs';

    file_put_contents($htmlPath, $html);
    file_put_contents($harnessPath, <<<'JS'
import { readFileSync } from 'node:fs';

const html = readFileSync(process.argv[2], 'utf8');

// Extract a declaration by matching its opening bracket to the closing one.
function extract(startToken, open, close) {
    const start = html.indexOf(startToken);
    if (start === -1) throw new Error(`Missing ${startToken}`);
    let depth = 0;
    for (let i = html.indexOf(open, start); i < html.length; i++) {
        if (html[i] === open) depth++;
        if (html[i] === close && --depth === 0) return html.slice(start, i + 1);
    }
    throw new Error(`Unterminated ${startToken}`);
}

const source = [
    'let currentPredictionId = null;',
    'let modelResult = null;',
    'const EVALUATION_MODE = false;',
    "const API_ENDPOINT = '/predict';",
    'const STUDY_CASE_PATTERN = /^$/;',
    'const activeImage = {};',
    'function startLoader() {}',
    'function stopLoader() {}',
    'function drawCanvas() {}',
    'function syncEvaluationUploadState() {}',
    extract('const stagesList', '[', ']') + ';',
    extract('const labelToStageIndex', '{', '}') + ';',
    extract('const stageColors', '[', ']') + ';',
    extract('function runInference(', '{', '}'),
    extract('function renderReferralDecision(', '{', '}'),
    extract('function renderProbabilityBars(', '{', '}'),
    extract('function setUIDiagnosis(', '{', '}'),
    extract('function renderModelFooter(', '{', '}'),
    extract('function clearPredictionResultState(', '{', '}'),
    extract('function overrideDiagnosis(', '{', '}'),
    'return { runInference, overrideDiagnosis,',
    '  getModelResult: () => modelResult, getPredictionId: () => currentPredictionId };',
].join('\n');

function makeElement(hidden) {
    const classes = new Set(hidden ? ['hidden'] : []);
    let children = [];
    return {
        innerText: '', value: '', style: {},
        classList: {
            add: c => classes.add(c),
            remove: c => classes.delete(c),
            contains: c => classes.has(c),
        },
        get className() { return [...classes].join(' '); },
        set className(v) { classes.clear(); v.split(/\s+/).filter(Boolean).forEach(c => classes.add(c)); },
        get innerHTML() { return ''; },
        set innerHTML(v) { if (v === '') children = []; },
        appendChild(child) { children.push(child); },
        get childCount() { return children.length; },
        get hidden() { return classes.has('hidden'); },
    };
}

// Elements that start hidden in the page markup.
const initiallyHidden = [
    'referral-box', 'review-flag', 'atypical-flag', 'scope-warning',
    'probability-panel', 'doctor-panel', 'correction-status', 'reset-btn', 'preview-box',
];

function createPage() {
    const els = {};
    const document = {
        getElementById: id => (els[id] ??= makeElement(initiallyHidden.includes(id))),
        createElement: () => makeElement(false),
        querySelector: () => ({ getAttribute: () => 'csrf-token' }),
    };
    class FileReader { readAsDataURL() {} }
    let nextResponse = null;
    const fetch = async () => ({
        ok: nextResponse.status < 400,
        status: nextResponse.status,
        json: async () => nextResponse.body,
    });
    const ui = new Function('document', 'FileReader', 'fetch', source)(document, FileReader, fetch);
    const el = id => document.getElementById(id);

    const snapshot = () => ({
        label: el('stage-label').innerText,
        badge: el('stage-badge').innerText,
        desc: el('desc-box').innerText,
        model: el('model-val').innerText,
        confidence: el('conf-val').innerText,
        latency: el('latency-val').innerText,
        referralHeadline: el('referral-headline').innerText,
        probabilityRows: el('prob-bars').childCount,
        correctionNote: el('correction-note').value,
        correctionStatus: el('correction-status').innerText,
        reviewText: el('review-flag-text').innerText,
        atypicalText: el('atypical-flag-text').innerText,
        hidden: Object.fromEntries(
            ['referral-box', 'review-flag', 'atypical-flag', 'scope-warning',
             'probability-panel', 'doctor-panel', 'correction-status']
                .map(id => [id, el(id).hidden])
        ),
        modelResult: ui.getModelResult(),
        predictionId: ui.getPredictionId(),
    });

    async function upload(response) {
        nextResponse = response;
        ui.runInference({ target: { files: [{ name: 'upload.png' }], value: '' } });
        const pending = snapshot();
        await new Promise(resolve => setTimeout(resolve, 0));
        return { pending, settled: snapshot() };
    }

    return { ui, el, snapshot, upload };
}

const success = {
    status: 200,
    body: {
        is_valid_fundus_image: true,
        predicted_label: 'Moderate NPDR',
        confidence: 0.64,
        referable: false,
        referable_probability: 0.21,
        referral_threshold: 0.49,
        flagged_for_review: true,
        any_dr_probability: 0.3,
        atypical_fundus_image: true,
        fundus_signature_score: 0.5,
        scope_warning: true,
        master12_score: 0.1,
        master12_threshold: 0.2,
        class_probabilities: [
            { label: 'No DR', probability: 0.2 },
            { label: 'Mild NPDR', probability: 0.1 },
            { label: 'Moderate NPDR', probability: 0.64 },
            { label: 'Severe NPDR', probability: 0.04 },
            { label: 'PDR', probability: 0.02 },
        ],
        inference_time_ms: 321,
        prediction_id: 42,
    },
};

const rejected = {
    status: 422,
    body: { detail: 'This does not appear to be a color fundus photograph. No prediction was recorded.' },
};

const out = {};

// Sequence A: success, clinician overrides, then a rejected second upload.
{
    const page = createPage();
    out.success = (await page.upload(success)).settled;

    page.ui.overrideDiagnosis('3');
    out.overrideStage = page.snapshot();

    page.ui.overrideDiagnosis('invalid');
    out.overrideInvalid = page.snapshot();

    // Leave correction UI state from the first prediction behind.
    page.el('correction-note').value = 'Note about the first image';
    page.el('correction-status').innerText = 'Correction saved.';
    page.el('correction-status').classList.remove('hidden');

    const second = await page.upload(rejected);
    out.pendingAfterSuccess = second.pending;
    out.rejectedAfterSuccess = second.settled;
}

// Sequence B: rejected first upload, then a successful one.
{
    const page = createPage();
    out.rejectedFirst = (await page.upload(rejected)).settled;
    out.successAfterRejection = (await page.upload(success)).settled;
}

console.log(JSON.stringify(out));
JS);

    try {
        $result = Process::run([$node, $harnessPath, $htmlPath]);

        if (! $result->successful()) {
            throw new RuntimeException('Screening UI harness failed: '.$result->errorOutput());
        }

        return json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
    } finally {
        @unlink($htmlPath);
        @unlink($harnessPath);
        @rmdir($dir);
    }
}

function retinaFooterScenarios(): array
{
    return retinaFooterRunScenarios(retinaFooterScreeningHtml());
}

function retinaFooterText(string $html, string $id): string
{
    preg_match('/id="'.$id.'"[^>]*>\s*(.*?)\s*</s', $html, $matches);

    return trim($matches[1] ?? '');
}

/**
 * Assert that a state shows a fully populated successful prediction.
 */
function retinaFooterExpectPopulatedSuccess(array $state): void
{
    expect($state['label'])->toBe('Predicted ICDR Stage:')
        ->and($state['badge'])->toBe('Stage 2: Moderate Non-Proliferative DR')
        ->and($state['model'])->toBe('Model: EfficientNetB4 + TTA')
        ->and($state['confidence'])->toBe('64%')
        ->and($state['latency'])->toBe('321 ms')
        ->and($state['referralHeadline'])->toBe('No Referral Indicated')
        ->and($state['probabilityRows'])->toBe(5)
        ->and($state['modelResult'])->toBe(['label' => 'Moderate NPDR', 'confidencePct' => 64])
        ->and($state['predictionId'])->toBe(42)
        ->and($state['hidden'])->toBe([
            'referral-box' => false,
            'review-flag' => false,
            'atypical-flag' => false,
            'scope-warning' => false,
            'probability-panel' => false,
            'doctor-panel' => false,
            'correction-status' => true,
        ]);
}

/**
 * Assert that a state shows only the failed input and no model output.
 */
function retinaFooterExpectNoModelOutput(array $state): void
{
    expect($state['modelResult'])->toBeNull()
        ->and($state['predictionId'])->toBeNull()
        ->and($state['model'])->toBe('Classification: Not performed')
        ->and($state['confidence'])->toBe('N/A')
        ->and($state['latency'])->toBe('—')
        ->and($state['probabilityRows'])->toBe(0)
        ->and($state['correctionNote'])->toBe('')
        ->and($state['correctionStatus'])->toBe('')
        ->and($state['hidden'])->toBe([
            'referral-box' => true,
            'review-flag' => true,
            'atypical-flag' => true,
            'scope-warning' => true,
            'probability-panel' => true,
            'doctor-panel' => true,
            'correction-status' => true,
        ]);
}

test('initial screening footer shows classification as not performed', function () {
    $html = retinaFooterScreeningHtml();

    expect(retinaFooterText($html, 'model-val'))->toBe('Classification: Not performed')
        ->and(retinaFooterText($html, 'conf-val'))->toBe('N/A');
});

test('initial screening labels do not presume a fundus image or an ICDR prediction', function () {
    $html = retinaFooterScreeningHtml();

    expect(retinaFooterText($html, 'screen-label'))->toBe('Uploaded Image')
        ->and(retinaFooterText($html, 'stage-label'))->toBe('Screening Result:')
        ->and($html)->not->toContain('Uploaded Fundus Image');
});

test('successful prediction shows the B4 model, actual confidence, and all result panels', function () {
    retinaFooterExpectPopulatedSuccess(retinaFooterScenarios()['success']);
});

test('rejected first upload shows classification as not performed', function () {
    $state = retinaFooterScenarios()['rejectedFirst'];

    expect($state['label'])->toBe('Screening Result:')
        ->and($state['badge'])->toBe('Cannot Determine Stage')
        ->and($state['desc'])->toBe('This does not appear to be a color fundus photograph. No prediction was recorded.');

    retinaFooterExpectNoModelOutput($state);
});

test('rejected upload after a successful one leaves no stale prediction output', function () {
    $states = retinaFooterScenarios();

    // While the second request is pending, the first result is already gone.
    retinaFooterExpectNoModelOutput($states['pendingAfterSuccess']);

    expect($states['pendingAfterSuccess']['label'])->toBe('Screening Result:')
        ->and($states['pendingAfterSuccess']['badge'])->toBe('Awaiting Result...');

    // After the rejection only the failed input's own information remains,
    // and nothing labels it as an ICDR prediction.
    $state = $states['rejectedAfterSuccess'];

    expect($state['label'])->toBe('Screening Result:')
        ->and($state['badge'])->toBe('Cannot Determine Stage')
        ->and($state['desc'])->toBe('This does not appear to be a color fundus photograph. No prediction was recorded.');

    retinaFooterExpectNoModelOutput($state);
});

test('successful upload after a rejection repopulates the full result state', function () {
    retinaFooterExpectPopulatedSuccess(retinaFooterScenarios()['successAfterRejection']);
});

test('clinician stage override does not replace model confidence with 100%', function () {
    $state = retinaFooterScenarios()['overrideStage'];

    expect($state['label'])->toBe('Clinician-Selected Stage:')
        ->and($state['badge'])->toBe('Stage 3: Severe Non-Proliferative DR')
        ->and($state['desc'])->toStartWith('Clinician selection: Stage 3')
        ->and($state['model'])->toBe('Model: EfficientNetB4 + TTA')
        ->and($state['confidence'])->toBe('64%')
        ->not->toBe('100%');
});

test('clinician cannot determine stage selection does not replace model confidence with 0%', function () {
    $state = retinaFooterScenarios()['overrideInvalid'];

    expect($state['label'])->toBe('Clinician-Selected Stage:')
        ->and($state['badge'])->toBe('Cannot Determine Stage')
        ->and($state['desc'])->toContain('cannot be saved as a correction')
        ->and($state['model'])->toBe('Model: EfficientNetB4 + TTA')
        ->and($state['confidence'])->toBe('64%')
        ->not->toBe('0%');
});

test('successful model metadata remains intact after clinician overrides', function () {
    $states = retinaFooterScenarios();

    $original = ['label' => 'Moderate NPDR', 'confidencePct' => 64];

    foreach (['overrideStage', 'overrideInvalid'] as $step) {
        expect($states[$step]['modelResult'])->toBe($original)
            ->and($states[$step]['predictionId'])->toBe(42)
            ->and($states[$step]['referralHeadline'])->toBe('No Referral Indicated')
            ->and($states[$step]['probabilityRows'])->toBe(5)
            ->and($states[$step]['hidden']['referral-box'])->toBeFalse()
            ->and($states[$step]['hidden']['probability-panel'])->toBeFalse();
    }

    expect($states['overrideInvalid']['desc'])
        ->toContain('The model result (Moderate NPDR, 64% confidence) is unchanged.');
});

test('screening script clears prior results before each attempt and on error', function () {
    $html = retinaFooterScreeningHtml();

    expect($html)
        ->toMatch('/clearPredictionResultState\(\);\s*startLoader\(\);/')
        ->toMatch('/\.catch\(err => \{.*?clearPredictionResultState\(\);.*?setUIDiagnosis\(\s*"invalid"/s');

    // No caller may hand setUIDiagnosis a fabricated confidence value.
    expect($html)->not->toMatch('/setUIDiagnosis\([^;]*,\s*(0|100)\s*\)/s');
});

test('review and atypical flags describe model outputs without clinical causal claims', function () {
    $state = retinaFooterScenarios()['success'];

    expect($state['reviewText'])
        ->toBe(
            'The referral threshold was not met, but the model assigns 30.0% combined probability '
            .'across the DR stages. The categorical stage and the model\'s other outputs indicate '
            .'that additional clinician review is appropriate before recording the screening result.'
        )
        ->and($state['atypicalText'])
        ->toBe(
            'This image is atypical relative to the fundus-image patterns represented by RETINA '
            .'(signature score 0.50). Interpret the automated result cautiously and review the '
            .'image clinically.'
        );

    foreach (['reviewText', 'atypicalText'] as $text) {
        expect(strtolower($state[$text]))
            ->not->toContain('microaneurysm')
            ->not->toContain('more often severe')
            ->not->toContain('proliferative')
            ->not->toContain('haemorrhage')
            ->not->toContain('weigh the grade');
    }
});
