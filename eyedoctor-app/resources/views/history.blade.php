<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction History</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-800 text-slate-100 min-h-screen font-sans">
    <header class="bg-slate-900 border-b border-slate-700 p-5 flex justify-between items-center">
        <div>
            <h1 class="text-lg font-bold text-slate-100 font-mono">Prediction History</h1>
            @role('admin')
                <span class="text-[10px] font-mono text-amber-400 uppercase tracking-widest">
                    Admin view — all doctors
                </span>
            @endrole
        </div>
        @role('doctor')
            <a href="{{ route('welcome') }}" class="text-xs font-mono text-slate-400 hover:text-slate-200 uppercase">Back to Upload</a>
        @endrole
    </header>
    <main class="max-w-5xl mx-auto p-6">
        <div class="space-y-3">
            @forelse ($images as $image)
                @php $link = $image->prediction ? route('predictions.show', $image->prediction) : null; @endphp
                <{{ $link ? 'a' : 'div' }}
                    @if ($link) href="{{ $link }}" @endif
                    class="bg-slate-900 border border-slate-700 rounded-lg p-4 flex justify-between items-center {{ $link ? 'hover:border-slate-500 transition-colors' : '' }}">
                    <div>
                        <span class="text-xs font-mono text-slate-300 block">{{ $image->anonymized_filename }}</span>
                        <span class="text-[10px] font-mono text-slate-500">{{ $image->created_at->format('M d, Y g:i A') }}</span>
                        @role('admin')
                            <span class="text-[10px] font-mono text-slate-400 block">{{ $image->user->name ?? 'Unknown' }}</span>
                        @endrole
                    </div>
                    <div class="text-right">
                        @if ($image->prediction)
                            <span class="text-xs font-mono {{ $image->prediction->referral_flag ? 'text-red-400' : 'text-emerald-400' }} block">
                                {{ $image->prediction->referral_flag ? 'REFER' : 'NO REFERRAL' }}
                            </span>
                            <span class="text-xs font-mono text-slate-300 block">
                                Stage {{ $image->prediction->predicted_class }}
                                ({{ round($image->prediction->confidence_score * 100) }}% confidence)
                            </span>
                            @if ($image->prediction->correction)
                                <span class="text-[10px] font-mono text-amber-400 block">
                                    Corrected to Stage {{ $image->prediction->correction->corrected_class }}
                                </span>
                            @endif
                            <span class="text-[10px] font-mono text-slate-500 block mt-1">View details &rarr;</span>
                        @else
                            <span class="text-xs font-mono text-slate-500">No prediction recorded</span>
                        @endif
                    </div>
                </{{ $link ? 'a' : 'div' }}>
            @empty
                <p class="text-sm font-mono text-slate-500 text-center py-10">No uploads yet.</p>
            @endforelse
        </div>
        <div class="mt-6">
            {{ $images->links() }}
        </div>
    </main>
</body>
</html>