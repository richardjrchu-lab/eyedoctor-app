<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prediction #{{ $prediction->id }} — DR Detection System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-800 text-slate-100 min-h-screen font-mono">

@php
    $stages = [
        0 => 'No DR',
        1 => 'Stage 1: Mild Non-Proliferative DR',
        2 => 'Stage 2: Moderate Non-Proliferative DR',
        3 => 'Stage 3: Severe Non-Proliferative DR',
        4 => 'Stage 4: Proliferative DR',
    ];
    $stageColors = [
        0 => 'border-slate-500 text-slate-200',
        1 => 'border-yellow-600 text-yellow-300',
        2 => 'border-amber-600 text-amber-300',
        3 => 'border-orange-600 text-orange-300',
        4 => 'border-red-600 text-red-300',
    ];
    $probs = $prediction->probabilities ?? [];
$referralProb = $prediction->referable_probability;
@endphp

<header class="bg-slate-900 border-b border-slate-700 p-5 text-center">
    <h1 class="text-lg font-bold tracking-wide">Prediction Record #{{ $prediction->id }}</h1>
    <a href="{{ route('history') }}" class="text-xs text-slate-400 hover:text-slate-200 uppercase tracking-widest">
        &larr; Back to history
    </a>
</header>

<main class="max-w-6xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Image --}}
    <section class="bg-slate-900 border border-slate-700 rounded p-4">
        <p class="text-xs uppercase tracking-widest text-slate-400 mb-3">Fundus Image</p>
        <img src="{{ route('images.file', $image) }}"
             alt="Anonymized fundus image"
             class="w-full rounded border border-slate-700 bg-black">
        <dl class="mt-4 text-xs space-y-1 text-slate-400">
            <div class="flex justify-between">
                <dt>Anonymized filename</dt>
                <dd class="text-slate-200">{{ $image->anonymized_filename }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Validation status</dt>
                <dd class="text-slate-200">{{ $image->validation_status }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Uploaded</dt>
                <dd class="text-slate-200">{{ $image->created_at->format('M j, Y g:i A') }}</dd>
            </div>
            @if ($isAdmin)
            <div class="flex justify-between border-t border-slate-700 pt-1 mt-2">
                <dt>Uploaded by</dt>
                <dd class="text-slate-200">{{ $image->user->name ?? 'Unknown' }}</dd>
            </div>
            @endif
        </dl>
    </section>

    {{-- Result --}}
    <section class="space-y-4">

        {{-- Referral verdict --}}
        @if ($prediction->referral_flag)
        <div class="border-2 border-red-600 bg-red-950/40 rounded p-4">
            <p class="font-bold text-red-300 tracking-wide">REFER TO SPECIALIST</p>
            <p class="text-xs text-slate-300 mt-1">
                This case meets the referral threshold and warrants specialist follow-up.
            </p>
        </div>
        @else
        <div class="border-2 border-emerald-700 bg-emerald-950/40 rounded p-4">
            <p class="font-bold text-emerald-300 tracking-wide">NO REFERRAL INDICATED</p>
            <p class="text-xs text-slate-300 mt-1">
                This case falls below the referral threshold.
            </p>
        </div>
        @endif

        {{-- Threshold scale --}}
        <div class="bg-slate-900 border border-slate-700 rounded p-4">
            <p class="text-xs uppercase tracking-widest text-slate-400 mb-2">Referral probability</p>
            <div class="relative h-2 bg-slate-700 rounded">
                <div class="absolute inset-y-0 left-0 rounded {{ $prediction->referral_flag ? 'bg-red-600' : 'bg-emerald-600' }}"
 style="width: {{ min(100, max(0, ($referralProb ?? 0) * 100)) }}%"></div>
                 <div class="absolute inset-y-0 w-px bg-slate-100" style="left: 48%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
  <span>{{ $referralProb === null ? 'P = not recorded' : 'P = ' . number_format($referralProb, 3) }}</span>
                  <span>Threshold = 0.48</span>
            </div>
        </div>

        {{-- ICDR grade --}}
        <div>
            <p class="text-xs uppercase tracking-widest text-slate-400 mb-2">Predicted ICDR stage</p>
            <div class="border-2 rounded p-3 {{ $stageColors[$prediction->predicted_class] ?? 'border-slate-500' }}">
                {{ $stages[$prediction->predicted_class] ?? 'Unknown' }}
            </div>
        </div>

        {{-- Probabilities --}}
        @if (!empty($probs))
        <div class="bg-slate-900 border border-slate-700 rounded p-4">
            <p class="text-xs uppercase tracking-widest text-slate-400 mb-3">Class probability distribution</p>
 @foreach ($probs as $row)
                @php
                    $label = $row['label'] ?? '—';
                    $value = (float) ($row['probability'] ?? 0);
                @endphp
                <div class="mb-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-300">{{ $label }}</span>
                        <span class="text-slate-200">{{ number_format($value * 100, 1) }}%</span>
                    </div>
                    <div class="h-1 bg-slate-700 rounded mt-1">
                        <div class="h-1 bg-slate-400 rounded" style="width: {{ min(100, $value * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
            <p class="text-[10px] text-slate-500 mt-3 leading-relaxed">
                Mild NPDR precision is limited (~47%) — when the grade reads "Mild", it is often
                actually Moderate. The referral decision is unaffected by this boundary; the grade
                itself is least reliable here.
            </p>
        </div>
        @endif

        {{-- Correction --}}
        @if ($prediction->correction)
        <div class="bg-slate-900 border border-slate-600 rounded p-4">
            <p class="text-xs uppercase tracking-widest text-slate-400 mb-2">Clinician correction</p>
            <p class="text-sm text-slate-100">
                {{ $stages[$prediction->correction->corrected_class] ?? 'Unknown' }}
            </p>
            @if ($prediction->correction->note)
                <p class="text-xs text-slate-300 mt-2 border-l-2 border-slate-600 pl-3">
                    {{ $prediction->correction->note }}
                </p>
            @endif
            <p class="text-[10px] text-slate-500 mt-3">
                Corrected by {{ $prediction->correction->correctedBy->name ?? 'Unknown' }}
                on {{ $prediction->correction->created_at->format('M j, Y g:i A') }}
            </p>
        </div>
        @else
        <p class="text-xs text-slate-500 italic">No clinician correction recorded.</p>
        @endif

        {{-- Meta --}}
        <div class="text-[10px] text-slate-500 space-y-1 pt-2 border-t border-slate-700">
            <p>Model: {{ $prediction->model_version }}</p>
            <p>Confidence: {{ number_format($prediction->confidence_score * 100, 1) }}%</p>
            <p>Predicted: {{ $prediction->created_at->format('M j, Y g:i A') }}</p>
        </div>

        <p class="text-[10px] text-red-400 leading-relaxed">
            For clinical decision support only. Predictions must be reviewed by a qualified eye care
            professional and are not a substitute for clinical diagnosis. This is a read-only archive
            view; corrections are made on the prediction page.
        </p>

    </section>
</main>

</body>
</html>