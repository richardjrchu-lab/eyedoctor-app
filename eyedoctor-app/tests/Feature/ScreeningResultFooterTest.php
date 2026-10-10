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
 * Run the screening page's own result-state functions in Node against a
 * stub DOM and return the displayed state after each scenario step.
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
    'let modelResult = null;',
    extract('const stagesList', '[', ']') + ';',
    extract('const stageColors', '[', ']') + ';',
    extract('function setUIDiagnosis(', '{', '}'),
    extract('function renderModelFooter(', '{', '}'),
    extract('function overrideDiagnosis(', '{', '}'),
    'return { setModelResult: r => { modelResult = r; }, getModelResult: () => modelResult,',
    '  setUIDiagnosis, renderModelFooter, overrideDiagnosis };',
].join('\n');

const el = () => ({ innerText: '', className: '', value: '' });
const els = Object.fromEntries(
    ['stage-label', 'stage-badge', 'desc-box', 'doctor-override', 'model-val', 'conf-val']
        .map(id => [id, el()])
);
const document = { getElementById: id => els[id] };
const ui = new Function('document', source)(document);

const snapshot = () => ({
    label: els['stage-label'].innerText,
    badge: els['stage-badge'].innerText,
    desc: els['desc-box'].innerText,
    model: els['model-val'].innerText,
    confidence: els['conf-val'].innerText,
    modelResult: ui.getModelResult(),
});

const out = {};

// Same order as the success branch of runInference().
ui.setModelResult({ label: 'Moderate NPDR', confidencePct: 64 });
ui.setUIDiagnosis(2, 'Classified as Moderate NPDR with 64% confidence.');
ui.renderModelFooter();
out.success = snapshot();

ui.overrideDiagnosis('3');
out.overrideStage = snapshot();

ui.overrideDiagnosis('invalid');
out.overrideInvalid = snapshot();

// Same order as the error branch of runInference().
ui.setModelResult(null);
ui.setUIDiagnosis('invalid', 'This does not appear to be a color fundus photograph.');
ui.renderModelFooter();
out.rejected = snapshot();

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

function retinaFooterText(string $html, string $id): string
{
    preg_match('/id="'.$id.'"[^>]*>\s*(.*?)\s*</s', $html, $matches);

    return trim($matches[1] ?? '');
}

test('initial screening footer shows classification as not performed', function () {
    $html = retinaFooterScreeningHtml();

    expect(retinaFooterText($html, 'model-val'))->toBe('Classification: Not performed')
        ->and(retinaFooterText($html, 'conf-val'))->toBe('N/A');
});

test('successful prediction shows the B4 model and its actual confidence', function () {
    $state = retinaFooterRunScenarios(retinaFooterScreeningHtml())['success'];

    expect($state['label'])->toBe('Predicted ICDR Stage:')
        ->and($state['badge'])->toBe('Stage 2: Moderate Non-Proliferative DR')
        ->and($state['model'])->toBe('Model: EfficientNetB4 + TTA')
        ->and($state['confidence'])->toBe('64%');
});

test('rejected or failed screening shows classification as not performed', function () {
    $state = retinaFooterRunScenarios(retinaFooterScreeningHtml())['rejected'];

    expect($state['badge'])->toBe('Cannot Determine Stage')
        ->and($state['model'])->toBe('Classification: Not performed')
        ->and($state['confidence'])->toBe('N/A')
        ->and($state['modelResult'])->toBeNull();
});

test('clinician stage override does not replace model confidence with 100%', function () {
    $state = retinaFooterRunScenarios(retinaFooterScreeningHtml())['overrideStage'];

    expect($state['label'])->toBe('Clinician-Selected Stage:')
        ->and($state['badge'])->toBe('Stage 3: Severe Non-Proliferative DR')
        ->and($state['desc'])->toStartWith('Clinician selection: Stage 3')
        ->and($state['model'])->toBe('Model: EfficientNetB4 + TTA')
        ->and($state['confidence'])->toBe('64%')
        ->not->toBe('100%');
});

test('clinician cannot determine stage selection does not replace model confidence with 0%', function () {
    $state = retinaFooterRunScenarios(retinaFooterScreeningHtml())['overrideInvalid'];

    expect($state['label'])->toBe('Clinician-Selected Stage:')
        ->and($state['badge'])->toBe('Cannot Determine Stage')
        ->and($state['desc'])->toContain('cannot be saved as a correction')
        ->and($state['model'])->toBe('Model: EfficientNetB4 + TTA')
        ->and($state['confidence'])->toBe('64%')
        ->not->toBe('0%');
});

test('successful model metadata remains intact after clinician overrides', function () {
    $states = retinaFooterRunScenarios(retinaFooterScreeningHtml());

    $original = ['label' => 'Moderate NPDR', 'confidencePct' => 64];

    expect($states['success']['modelResult'])->toBe($original)
        ->and($states['overrideStage']['modelResult'])->toBe($original)
        ->and($states['overrideInvalid']['modelResult'])->toBe($original)
        ->and($states['overrideInvalid']['desc'])
        ->toContain('The model result (Moderate NPDR, 64% confidence) is unchanged.');
});

test('screening script records the model result only on success and clears it on error', function () {
    $html = retinaFooterScreeningHtml();

    expect($html)
        ->toMatch('/modelResult = \{\s*label: data\.predicted_label,\s*confidencePct: confidencePct\s*\};/')
        ->toMatch('/\.catch\(err => \{.*?modelResult = null;.*?renderModelFooter\(\);/s');

    // No caller may hand setUIDiagnosis a fabricated confidence value.
    expect($html)->not->toMatch('/setUIDiagnosis\([^;]*,\s*(0|100)\s*\)/s');
});
