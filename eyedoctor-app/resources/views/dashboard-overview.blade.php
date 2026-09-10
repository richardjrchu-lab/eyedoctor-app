<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        RETINA Overview
    </title>


    <link
        rel="preconnect"
        href="https://fonts.bunny.net"
    >


    <link
        href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
        rel="stylesheet"
    >


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
    {{-- MAIN DASHBOARD                                                --}}
    {{-- ============================================================= --}}

    <main
        class="mx-auto w-full max-w-7xl
               px-5 py-8
               sm:px-6 sm:py-10"
    >



        {{-- ========================================================= --}}
        {{-- HERO                                                      --}}
        {{-- ========================================================= --}}

        <section
            class="relative overflow-hidden
                   rounded-3xl
                   border border-[#334155]
                   bg-[#0f172a]"
        >

            {{-- Background decoration --}}
            <div
                class="pointer-events-none
                       absolute -right-32 -top-32
                       h-80 w-80 rounded-full
                       border border-[#334155]
                       opacity-60"
            >
            </div>


            <div
                class="pointer-events-none
                       absolute -right-12 -top-12
                       h-48 w-48 rounded-full
                       border border-[#475569]
                       opacity-40"
            >
            </div>



            <div
                class="relative grid
                       grid-cols-1 gap-10
                       px-7 py-10
                       sm:px-10 sm:py-14
                       lg:grid-cols-5
                       lg:items-center
                       lg:px-14 lg:py-16"
            >


                {{-- ================================================= --}}
                {{-- HERO TEXT                                         --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-3">

                    <div
                        class="inline-flex
                               items-center gap-2
                               rounded-full
                               border border-[#334155]
                               bg-[#1e293b]
                               px-3 py-1.5
                               text-xs font-semibold
                               text-[#94a3b8]"
                    >

                        <span
                            class="h-2 w-2
                                   rounded-full
                                   bg-[#38bdf8]"
                        >
                        </span>

                        Professional Screening Workspace

                    </div>



                    <h1
                        class="mt-6 max-w-4xl
                               text-4xl font-extrabold
                               leading-[1.08]
                               tracking-tight
                               text-[#f1f5f9]
                               sm:text-5xl
                               lg:text-6xl"
                    >
                        AI-Assisted

                        <span
                            class="block text-[#cbd5e1]"
                        >
                            Diabetic Retinopathy
                        </span>

                        <span
                            class="block"
                        >
                            Screening
                        </span>
                    </h1>



                    <p
                        class="mt-6 max-w-2xl
                               text-base leading-7
                               text-[#94a3b8]
                               sm:text-lg"
                    >
                        Analyze retinal fundus photographs, review ICDR
                        severity predictions, assess referral recommendations,
                        and maintain screening history within one professional
                        workspace.
                    </p>



                    <div
                        class="mt-8 flex flex-col
                               gap-3 sm:flex-row"
                    >

                        <a
                            href="{{ route('screening') }}"
                            class="inline-flex
                                   items-center justify-center
                                   gap-2 rounded-lg
                                   border border-[#64748b]
                                   bg-[#334155]
                                   px-6 py-3
                                   text-sm font-bold
                                   text-[#f1f5f9]
                                   transition
                                   hover:bg-[#475569]"
                        >

                            Start Screening

                            <svg
                                viewBox="0 0 24 24"
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M5 12h14" />
                                <path d="m13 6 6 6-6 6" />
                            </svg>

                        </a>



                        <a
                            href="{{ route('history') }}"
                            class="inline-flex
                                   items-center justify-center
                                   rounded-lg
                                   border border-[#334155]
                                   bg-[#0f172a]
                                   px-6 py-3
                                   text-sm font-semibold
                                   text-[#cbd5e1]
                                   transition
                                   hover:border-[#64748b]
                                   hover:bg-[#172033]"
                        >
                            View Prediction History
                        </a>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- SYSTEM SUMMARY                                    --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-2">

                    <div
                        class="rounded-2xl
                               border border-[#334155]
                               bg-[#111c30]
                               p-6"
                    >

                        <div
                            class="flex items-center
                                   justify-between"
                        >

                            <div>

                                <div
                                    class="text-xs font-semibold
                                           uppercase tracking-widest
                                           text-[#64748b]"
                                >
                                    RETINA System
                                </div>

                                <div
                                    class="mt-1 text-lg
                                           font-bold
                                           text-[#f1f5f9]"
                                >
                                    Screening Overview
                                </div>

                            </div>


                            <img
                                src="{{ asset('images/retina-logo.png') }}"
                                alt=""
                                class="w-16 object-contain"
                            >

                        </div>



                        <div
                            class="mt-6 space-y-4"
                        >

                            <div
                                class="flex items-center
                                       justify-between
                                       border-b border-[#334155]
                                       pb-3"
                            >
                                <span class="text-sm text-[#94a3b8]">
                                    Model
                                </span>

                                <span
                                    class="text-sm font-semibold
                                           text-[#cbd5e1]"
                                >
                                    EfficientNetB4
                                </span>
                            </div>


                            <div
                                class="flex items-center
                                       justify-between
                                       border-b border-[#334155]
                                       pb-3"
                            >
                                <span class="text-sm text-[#94a3b8]">
                                    Severity Scale
                                </span>

                                <span
                                    class="text-sm font-semibold
                                           text-[#cbd5e1]"
                                >
                                    ICDR 5-Stage
                                </span>
                            </div>


                            <div
                                class="flex items-center
                                       justify-between
                                       border-b border-[#334155]
                                       pb-3"
                            >
                                <span class="text-sm text-[#94a3b8]">
                                    Input Validation
                                </span>

                                <span
                                    class="text-sm font-semibold
                                           text-[#cbd5e1]"
                                >
                                    Fundus Gate
                                </span>
                            </div>


                            <div
                                class="flex items-center
                                       justify-between"
                            >
                                <span class="text-sm text-[#94a3b8]">
                                    Referral Threshold
                                </span>

                                <span
                                    class="font-mono text-sm
                                           font-semibold
                                           text-[#cbd5e1]"
                                >
                                    0.48
                                </span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- STATISTICS                                                --}}
        {{-- ========================================================= --}}

        <section
            class="mt-6 grid
                   grid-cols-2 gap-4
                   lg:grid-cols-4"
        >


            {{-- TOTAL UPLOADS --}}
            <div
                class="rounded-2xl
                       border border-[#334155]
                       bg-[#0f172a]
                       p-5"
            >

                <div
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-[#64748b]"
                >
                    Total Uploads
                </div>


                <div
                    class="mt-2 text-3xl
                           font-extrabold
                           text-[#f1f5f9]"
                >
                    {{ $totalUploads }}
                </div>


                <div
                    class="mt-1 text-xs
                           text-[#64748b]"
                >
                    Your submitted images
                </div>

            </div>



            {{-- COMPLETED --}}
            <div
                class="rounded-2xl
                       border border-[#334155]
                       bg-[#0f172a]
                       p-5"
            >

                <div
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-[#64748b]"
                >
                    Screened
                </div>


                <div
                    class="mt-2 text-3xl
                           font-extrabold
                           text-[#f1f5f9]"
                >
                    {{ $completedScreenings }}
                </div>


                <div
                    class="mt-1 text-xs
                           text-[#64748b]"
                >
                    Completed predictions
                </div>

            </div>



            {{-- REFERRALS --}}
            <div
                class="rounded-2xl
                       border border-[#334155]
                       bg-[#0f172a]
                       p-5"
            >

                <div
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-[#64748b]"
                >
                    Referral Cases
                </div>


                <div
                    class="mt-2 text-3xl
                           font-extrabold
                           text-[#fca5a5]"
                >
                    {{ $referralCount }}
                </div>


                <div
                    class="mt-1 text-xs
                           text-[#64748b]"
                >
                    Above referral threshold
                </div>

            </div>



            {{-- REJECTED --}}
            <div
                class="rounded-2xl
                       border border-[#334155]
                       bg-[#0f172a]
                       p-5"
            >

                <div
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-[#64748b]"
                >
                    Rejected Inputs
                </div>


                <div
                    class="mt-2 text-3xl
                           font-extrabold
                           text-[#f1f5f9]"
                >
                    {{ $rejectedUploads }}
                </div>


                <div
                    class="mt-1 text-xs
                           text-[#64748b]"
                >
                    Non-fundus images
                </div>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- WORKSPACE SHORTCUTS                                       --}}
        {{-- ========================================================= --}}

        <section class="mt-10">

            <div class="mb-5">

                <h2
                    class="text-xl font-bold
                           text-[#f1f5f9]"
                >
                    Workspace
                </h2>


                <p
                    class="mt-1 text-sm
                           text-[#94a3b8]"
                >
                    Access the primary RETINA tools.
                </p>

            </div>



            <div
                class="grid grid-cols-1
                       gap-4
                       md:grid-cols-3"
            >


                {{-- SCREENING CARD --}}
                <a
                    href="{{ route('screening') }}"
                    class="group rounded-2xl
                           border border-[#334155]
                           bg-[#0f172a]
                           p-6 transition
                           hover:-translate-y-0.5
                           hover:border-[#64748b]"
                >

                    <div
                        class="flex h-11 w-11
                               items-center justify-center
                               rounded-xl
                               border border-[#334155]
                               bg-[#1e293b]
                               text-[#94a3b8]
                               transition
                               group-hover:text-[#f1f5f9]"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M4 5h16v14H4z" />
                            <circle cx="12" cy="12" r="3" />
                            <path d="M8 5 9 3h6l1 2" />
                        </svg>

                    </div>


                    <h3
                        class="mt-5 text-lg font-bold
                               text-[#f1f5f9]"
                    >
                        Screening
                    </h3>


                    <p
                        class="mt-2 text-sm
                               leading-6
                               text-[#94a3b8]"
                    >
                        Upload a retinal fundus photograph and run
                        AI-assisted diabetic retinopathy screening.
                    </p>


                    <div
                        class="mt-5 text-sm
                               font-semibold
                               text-[#cbd5e1]"
                    >
                        Start Screening &rarr;
                    </div>

                </a>



                {{-- HISTORY CARD --}}
                <a
                    href="{{ route('history') }}"
                    class="group rounded-2xl
                           border border-[#334155]
                           bg-[#0f172a]
                           p-6 transition
                           hover:-translate-y-0.5
                           hover:border-[#64748b]"
                >

                    <div
                        class="flex h-11 w-11
                               items-center justify-center
                               rounded-xl
                               border border-[#334155]
                               bg-[#1e293b]
                               text-[#94a3b8]
                               transition
                               group-hover:text-[#f1f5f9]"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M5 4h14v16H5z" />
                            <path d="M8 8h8" />
                            <path d="M8 12h8" />
                            <path d="M8 16h5" />
                        </svg>

                    </div>


                    <h3
                        class="mt-5 text-lg font-bold
                               text-[#f1f5f9]"
                    >
                        Prediction History
                    </h3>


                    <p
                        class="mt-2 text-sm
                               leading-6
                               text-[#94a3b8]"
                    >
                        Review previous screening results, referral
                        decisions, and clinician corrections.
                    </p>


                    <div
                        class="mt-5 text-sm
                               font-semibold
                               text-[#cbd5e1]"
                    >
                        View History &rarr;
                    </div>

                </a>



                {{-- MOBILE APP CARD --}}
                <a
                    href="{{ route('mobile-app') }}"
                    class="group rounded-2xl
                           border border-[#334155]
                           bg-[#0f172a]
                           p-6 transition
                           hover:-translate-y-0.5
                           hover:border-[#64748b]"
                >

                    <div
                        class="flex h-11 w-11
                               items-center justify-center
                               rounded-xl
                               border border-[#334155]
                               bg-[#1e293b]
                               text-[#94a3b8]
                               transition
                               group-hover:text-[#f1f5f9]"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <rect
                                x="7"
                                y="2"
                                width="10"
                                height="20"
                                rx="2"
                            />

                            <path d="M10 18h4" />
                        </svg>

                    </div>


                    <h3
                        class="mt-5 text-lg font-bold
                               text-[#f1f5f9]"
                    >
                        Mobile App
                    </h3>


                    <p
                        class="mt-2 text-sm
                               leading-6
                               text-[#94a3b8]"
                    >
                        Access the protected RETINA Android application
                        release and installation information.
                    </p>


                    <div
                        class="mt-5 text-sm
                               font-semibold
                               text-[#cbd5e1]"
                    >
                        Open Mobile App &rarr;
                    </div>

                </a>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- RECENT ACTIVITY                                           --}}
        {{-- ========================================================= --}}

        <section class="mt-10">

            <div
                class="mb-5 flex
                       items-end justify-between
                       gap-4"
            >

                <div>

                    <h2
                        class="text-xl font-bold
                               text-[#f1f5f9]"
                    >
                        Recent Screenings
                    </h2>


                    <p
                        class="mt-1 text-sm
                               text-[#94a3b8]"
                    >
                        Your latest retinal screening activity.
                    </p>

                </div>


                <a
                    href="{{ route('history') }}"
                    class="hidden text-sm
                           font-semibold
                           text-[#94a3b8]
                           transition
                           hover:text-[#f1f5f9]
                           sm:block"
                >
                    View all &rarr;
                </a>

            </div>



            <div
                class="overflow-hidden
                       rounded-2xl
                       border border-[#334155]
                       bg-[#0f172a]"
            >

                @forelse ($recentImages as $image)

                    <div
                        class="border-b border-[#334155]
                               last:border-b-0"
                    >

                        @if ($image->prediction)

                            <a
                                href="{{ route('predictions.show', $image->prediction) }}"
                                class="flex flex-col
                                       gap-4 px-5 py-4
                                       transition
                                       hover:bg-[#111c30]
                                       sm:flex-row
                                       sm:items-center
                                       sm:justify-between"
                            >

                                <div class="min-w-0">

                                    <div
                                        class="truncate
                                               font-mono text-sm
                                               text-[#cbd5e1]"
                                    >
                                        {{ $image->anonymized_filename }}
                                    </div>


                                    <div
                                        class="mt-1 text-xs
                                               text-[#64748b]"
                                    >
                                        {{ $image->created_at->format('M d, Y g:i A') }}
                                    </div>

                                </div>


                                <div
                                    class="flex flex-wrap
                                           items-center gap-3"
                                >

                                    @if ($image->prediction->referral_flag)

                                        <span
                                            class="rounded-full
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
                                            class="rounded-full
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
                                        class="text-sm font-semibold
                                               text-[#cbd5e1]"
                                    >
                                        Stage {{ $image->prediction->predicted_class }}
                                    </span>


                                    <span
                                        class="text-xs
                                               text-[#64748b]"
                                    >
                                        {{ round($image->prediction->confidence_score * 100) }}%
                                    </span>

                                </div>

                            </a>


                        @else

                            <div
                                class="flex flex-col
                                       gap-4 px-5 py-4
                                       sm:flex-row
                                       sm:items-center
                                       sm:justify-between"
                            >

                                <div class="min-w-0">

                                    <div
                                        class="truncate
                                               font-mono text-sm
                                               text-[#cbd5e1]"
                                    >
                                        {{ $image->anonymized_filename }}
                                    </div>


                                    <div
                                        class="mt-1 text-xs
                                               text-[#64748b]"
                                    >
                                        {{ $image->created_at->format('M d, Y g:i A') }}
                                    </div>

                                </div>


                                @if ($image->validation_status === 'rejected_not_fundus')

                                    <span
                                        class="w-fit rounded-full
                                               border border-rose-800
                                               bg-rose-950/30
                                               px-3 py-1
                                               text-xs font-bold
                                               text-rose-300"
                                    >
                                        REJECTED INPUT
                                    </span>

                                @else

                                    <span
                                        class="w-fit rounded-full
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

                        @endif

                    </div>

                @empty

                    <div
                        class="px-6 py-14
                               text-center"
                    >

                        <div
                            class="mx-auto flex
                                   h-12 w-12
                                   items-center justify-center
                                   rounded-xl
                                   border border-[#334155]
                                   bg-[#1e293b]
                                   text-[#64748b]"
                        >

                            <svg
                                viewBox="0 0 24 24"
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M4 5h16v14H4z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>

                        </div>


                        <h3
                            class="mt-4 text-sm
                                   font-semibold
                                   text-[#cbd5e1]"
                        >
                            No screening activity yet
                        </h3>


                        <p
                            class="mt-1 text-sm
                                   text-[#64748b]"
                        >
                            Start your first RETINA screening to see
                            activity here.
                        </p>


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

                    </div>

                @endforelse

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- CLINICAL NOTICE                                           --}}
        {{-- ========================================================= --}}

        <section
            class="mt-8
                   rounded-2xl
                   border border-[#334155]
                   bg-[#0f172a]
                   px-6 py-5"
        >

            <div
                class="flex items-start gap-4"
            >

                <div
                    class="flex h-10 w-10
                           shrink-0 items-center
                           justify-center
                           rounded-lg
                           border border-[#475569]
                           bg-[#1e293b]
                           text-[#94a3b8]"
                >

                    <svg
                        viewBox="0 0 24 24"
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path
                            d="M12 3 4.5 6v5.5
                               c0 4.6 3.1 7.8
                               7.5 9.5
                               4.4-1.7 7.5-4.9
                               7.5-9.5V6L12 3Z"
                        />

                        <path
                            d="M9.5 12
                               11 13.5
                               14.5 10"
                        />
                    </svg>

                </div>


                <div>

                    <h3
                        class="text-sm font-bold
                               text-[#cbd5e1]"
                    >
                        Clinical Decision Support
                    </h3>


                    <p
                        class="mt-1 max-w-4xl
                               text-xs leading-5
                               text-[#64748b]"
                    >
                        RETINA provides AI-assisted screening information.
                        Model predictions and referral recommendations must
                        be reviewed alongside appropriate clinical assessment
                        and professional judgment.
                    </p>

                </div>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- FOOTER                                                    --}}
        {{-- ========================================================= --}}

        <footer
            class="mt-8
                   border-t border-[#334155]
                   pt-5 text-center"
        >

            <p
                class="text-xs
                       text-[#64748b]"
            >
                RETINA &mdash;
                Diabetic Retinopathy Detection System
            </p>

        </footer>

    </main>

</body>

</html>