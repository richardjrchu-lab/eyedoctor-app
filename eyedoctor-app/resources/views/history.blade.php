<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Prediction History — RETINA</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>


<body
    class="min-h-screen
           bg-[#1e293b]
           font-sans
           text-[#f1f5f9]
           antialiased"
>

    {{-- ============================================================= --}}
    {{-- SHARED RETINA HEADER                                          --}}
    {{-- ============================================================= --}}

    @include('partials.app-header')



    {{-- ============================================================= --}}
    {{-- MAIN CONTENT                                                  --}}
    {{-- ============================================================= --}}

    <main
        class="mx-auto w-full max-w-5xl
               px-5 py-8 sm:px-6"
    >

        {{-- ========================================================= --}}
        {{-- PAGE HEADING                                              --}}
        {{-- ========================================================= --}}

        <div
            class="mb-6 flex flex-col
                   gap-2 sm:flex-row
                   sm:items-end
                   sm:justify-between"
        >

            <div>

                <h1
                    class="text-2xl font-bold
                           tracking-tight
                           text-[#f1f5f9]"
                >
                    Prediction History
                </h1>


                <p
                    class="mt-1 text-sm
                           text-[#94a3b8]"
                >
                    Review previous retinal image screenings and model results.
                </p>


                @role('admin')

                    <div
                        class="mt-2 inline-flex
                               items-center rounded-full
                               border border-amber-800/60
                               bg-amber-950/30
                               px-3 py-1
                               text-[11px] font-semibold
                               uppercase tracking-wider
                               text-amber-300"
                    >
                        Administrator View — All Doctors
                    </div>

                @endrole

            </div>

        </div>



        {{-- ========================================================= --}}
        {{-- HISTORY LIST                                              --}}
        {{-- ========================================================= --}}

        <div class="space-y-3">

            @forelse ($images as $image)

                @php
                    $link = $image->prediction
                        ? route('predictions.show', $image->prediction)
                        : null;
                @endphp


                @if ($link)

                    <a
                        href="{{ $link }}"
                        class="block rounded-xl
                               border border-[#334155]
                               bg-[#0f172a]
                               p-5
                               transition
                               hover:border-[#64748b]
                               hover:bg-[#111c30]"
                    >

                        <div
                            class="flex flex-col
                                   gap-4
                                   sm:flex-row
                                   sm:items-center
                                   sm:justify-between"
                        >

                            {{-- LEFT SIDE --}}
                            <div class="min-w-0">

                                <span
                                    class="block break-all
                                           font-mono text-sm
                                           text-[#cbd5e1]"
                                >
                                    {{ $image->anonymized_filename }}
                                </span>


                                <span
                                    class="mt-1 block
                                           text-xs
                                           text-[#64748b]"
                                >
                                    {{ $image->created_at->format('M d, Y g:i A') }}
                                </span>


                                @role('admin')

                                    <span
                                        class="mt-1 block
                                               text-xs
                                               text-[#94a3b8]"
                                    >
                                        Doctor:
                                        {{ $image->user->name ?? 'Unknown' }}
                                    </span>

                                @endrole

                            </div>



                            {{-- RIGHT SIDE --}}
                            <div
                                class="shrink-0
                                       sm:text-right"
                            >

                                @if ($image->prediction->referral_flag)

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               border border-red-700
                                               bg-red-950/40
                                               px-3 py-1
                                               text-xs font-bold
                                               text-red-300"
                                    >
                                        REFER
                                    </span>

                                @else

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               border border-emerald-800
                                               bg-emerald-950/30
                                               px-3 py-1
                                               text-xs font-bold
                                               text-emerald-300"
                                    >
                                        NO REFERRAL
                                    </span>

                                @endif


                                <span
                                    class="mt-2 block
                                           text-sm font-medium
                                           text-[#cbd5e1]"
                                >
                                    Stage {{ $image->prediction->predicted_class }}

                                    <span class="text-[#64748b]">
                                        (
                                        {{ round($image->prediction->confidence_score * 100) }}%
                                        confidence
                                        )
                                    </span>
                                </span>


                                @if ($image->prediction->correction)

                                    <span
                                        class="mt-1 block
                                               text-xs
                                               text-amber-300"
                                    >
                                        Corrected to Stage
                                        {{ $image->prediction->correction->corrected_class }}
                                    </span>

                                @endif


                                <span
                                    class="mt-2 block
                                           text-xs font-medium
                                           text-[#94a3b8]"
                                >
                                    View details &rarr;
                                </span>

                            </div>

                        </div>

                    </a>


                @else

                    {{-- ================================================= --}}
                    {{-- RECORD WITHOUT A PREDICTION                       --}}
                    {{-- ================================================= --}}

                    <div
                        class="rounded-xl
                               border border-[#334155]
                               bg-[#0f172a]
                               p-5"
                    >

                        <div
                            class="flex flex-col
                                   gap-4
                                   sm:flex-row
                                   sm:items-center
                                   sm:justify-between"
                        >

                            {{-- LEFT --}}
                            <div class="min-w-0">

                                <span
                                    class="block break-all
                                           font-mono text-sm
                                           text-[#cbd5e1]"
                                >
                                    {{ $image->anonymized_filename }}
                                </span>


                                <span
                                    class="mt-1 block
                                           text-xs
                                           text-[#64748b]"
                                >
                                    {{ $image->created_at->format('M d, Y g:i A') }}
                                </span>


                                @role('admin')

                                    <span
                                        class="mt-1 block
                                               text-xs
                                               text-[#94a3b8]"
                                    >
                                        Doctor:
                                        {{ $image->user->name ?? 'Unknown' }}
                                    </span>

                                @endrole

                            </div>



                            {{-- RIGHT --}}
                            <div
                                class="shrink-0
                                       sm:text-right"
                            >

                                @if ($image->validation_status === 'rejected_not_fundus')

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               border border-rose-800
                                               bg-rose-950/30
                                               px-3 py-1
                                               text-xs font-bold
                                               text-rose-300"
                                    >
                                        REJECTED
                                    </span>


                                    <span
                                        class="mt-2 block
                                               text-xs
                                               text-[#94a3b8]"
                                    >
                                        Not a valid fundus image
                                    </span>


                                    <span
                                        class="mt-1 block
                                               text-xs
                                               text-[#64748b]"
                                    >
                                        No DR classification performed
                                    </span>


                                @elseif ($image->validation_status === 'error')

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               border border-amber-800
                                               bg-amber-950/30
                                               px-3 py-1
                                               text-xs font-bold
                                               text-amber-300"
                                    >
                                        PREDICTION UNAVAILABLE
                                    </span>


                                    <span
                                        class="mt-2 block
                                               text-xs
                                               text-[#64748b]"
                                    >
                                        Model service error — no classification recorded
                                    </span>


                                @else

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               border border-[#475569]
                                               bg-[#1e293b]
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-[#94a3b8]"
                                    >
                                        NO PREDICTION
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>

                @endif

            @empty

                {{-- ================================================= --}}
                {{-- EMPTY STATE                                       --}}
                {{-- ================================================= --}}

                <div
                    class="rounded-2xl
                           border border-[#334155]
                           bg-[#0f172a]
                           px-6 py-16
                           text-center"
                >

                    <svg
                        viewBox="0 0 24 24"
                        class="mx-auto h-10 w-10
                               text-[#64748b]"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        aria-hidden="true"
                    >
                        <path d="M4 5h16v14H4z" />
                        <path d="M8 9h8" />
                        <path d="M8 13h5" />
                    </svg>


                    <h2
                        class="mt-4 text-base
                               font-semibold
                               text-[#cbd5e1]"
                    >
                        No screening records yet
                    </h2>


                    <p
                        class="mt-1 text-sm
                               text-[#64748b]"
                    >
                        Completed screenings will appear here.
                    </p>


                    @role('doctor')

                        <a
                            href="{{ route('screening') }}"
                            class="mt-5 inline-flex
                                   rounded-lg
                                   border border-[#475569]
                                   bg-[#334155]
                                   px-4 py-2
                                   text-sm font-semibold
                                   text-[#f1f5f9]
                                   transition
                                   hover:bg-[#475569]"
                        >
                            Start Screening
                        </a>

                    @endrole

                </div>

            @endforelse

        </div>



        {{-- ========================================================= --}}
        {{-- PAGINATION                                                --}}
        {{-- ========================================================= --}}

        @if ($images->hasPages())

            <div class="mt-7">

                {{ $images->links() }}

            </div>

        @endif



        {{-- ========================================================= --}}
        {{-- FOOTER                                                    --}}
        {{-- ========================================================= --}}

        <footer
            class="mt-10 border-t
                   border-[#334155]
                   pt-5 text-center"
        >

            <p
                class="text-xs
                       text-[#64748b]"
            >
                RETINA &mdash; Diabetic Retinopathy Detection System
            </p>

        </footer>

    </main>

</body>

</html>