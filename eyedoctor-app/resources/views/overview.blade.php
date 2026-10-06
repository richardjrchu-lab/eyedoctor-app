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

    <title>RETINA Web — Overview</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: #1e293b;
        }

        .hero-pattern::before,
        .hero-pattern::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(71, 85, 105, 0.28);
            border-radius: 9999px;
            pointer-events: none;
        }

        .hero-pattern::before {
            width: 290px;
            height: 290px;
            right: -95px;
            top: -95px;
        }

        .hero-pattern::after {
            width: 190px;
            height: 190px;
            right: -25px;
            top: -20px;
        }
    </style>
</head>


<body class="text-slate-100 min-h-screen font-sans">

    {{-- ============================================================= --}}
    {{-- SHARED RETINA HEADER                                          --}}
    {{-- ============================================================= --}}

    @include('partials.app-header')


    {{-- ============================================================= --}}
    {{-- RETINA NAVIGATION                                              --}}
    {{-- ============================================================= --}}

    <nav
        class="border-b border-slate-700/60
               bg-slate-800"
    >

        <div
            class="max-w-7xl mx-auto
                   px-6 py-3
                   flex items-center gap-2
                   overflow-x-auto"
        >

            {{-- OVERVIEW --}}
            <a
                href="{{ route('overview') }}"
                class="px-4 py-2
                       rounded-lg
                       border border-slate-500
                       bg-slate-700
                       text-sm font-semibold
                       text-white
                       whitespace-nowrap"
            >
                Overview
            </a>


            {{-- SCREENING --}}
            <a
                href="{{ route('screening') }}"
                class="px-4 py-2
                       rounded-lg
                       border border-slate-700
                       bg-slate-900
                       text-sm font-semibold
                       text-slate-300
                       hover:bg-slate-800
                       hover:text-white
                       transition
                       whitespace-nowrap"
            >
                Screening
            </a>


            {{-- EVALUATION --}}
            <a
                href="{{ route('evaluation') }}"
                class="px-4 py-2
                       rounded-lg
                       border border-slate-700
                       bg-slate-900
                       text-sm font-semibold
                       text-slate-300
                       hover:bg-slate-800
                       hover:text-white
                       transition
                       whitespace-nowrap"
            >
                Evaluation
            </a>


            {{-- PREDICTION HISTORY --}}
            <button
                type="button"
                disabled
                title="Prediction History will be connected next."
                class="px-4 py-2
                       rounded-lg
                       border border-slate-700
                       bg-slate-900
                       text-sm font-semibold
                       text-slate-500
                       cursor-not-allowed
                       whitespace-nowrap"
            >
                Prediction History
            </button>


            {{-- MOBILE APP --}}
            <button
                type="button"
                disabled
                title="Mobile App page will be connected later."
                class="px-4 py-2
                       rounded-lg
                       border border-slate-700
                       bg-slate-900
                       text-sm font-semibold
                       text-slate-500
                       cursor-not-allowed
                       whitespace-nowrap"
            >
                Mobile App
            </button>

        </div>

    </nav>



    {{-- ============================================================= --}}
    {{-- MAIN OVERVIEW                                                  --}}
    {{-- ============================================================= --}}

    <main
        class="max-w-7xl
               mx-auto
               px-6
               py-10"
    >

        {{-- ========================================================= --}}
        {{-- HERO                                                       --}}
        {{-- ========================================================= --}}

        <section
            class="hero-pattern
                   relative
                   overflow-hidden
                   bg-slate-900
                   border border-slate-700
                   rounded-2xl
                   shadow-xl"
        >

            <div
                class="grid grid-cols-1
                       lg:grid-cols-12
                       gap-10
                       items-center
                       px-8
                       md:px-12
                       py-14"
            >

                {{-- ================================================= --}}
                {{-- LEFT HERO                                         --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-7 relative z-10">

                    <div
                        class="inline-flex
                               items-center
                               gap-2
                               px-3 py-1.5
                               rounded-full
                               border border-slate-700
                               bg-slate-800
                               mb-6"
                    >

                        <span
                            class="w-2 h-2
                                   rounded-full
                                   bg-sky-400"
                        >
                        </span>


                        <span
                            class="text-xs
                                   font-semibold
                                   text-sky-300"
                        >
                            Professional Screening Workspace
                        </span>

                    </div>


                    <h1
                        class="text-4xl
                               sm:text-5xl
                               lg:text-6xl
                               font-extrabold
                               tracking-tight
                               leading-[1.04]
                               text-slate-100"
                    >
                        AI-Assisted
                        <br>

                        <span class="text-slate-200">
                            Diabetic Retinopathy
                        </span>

                        <br>

                        Screening
                    </h1>


                    <p
                        class="mt-7
                               max-w-3xl
                               text-base
                               md:text-lg
                               text-slate-400
                               leading-relaxed"
                    >
                        Analyze retinal fundus photographs, review ICDR
                        severity predictions, assess referral recommendations,
                        and maintain screening results within one professional
                        workspace.
                    </p>


                    <div
                        class="mt-8
                               flex flex-col
                               sm:flex-row
                               gap-3"
                    >

                        <a
                            href="{{ route('screening') }}"
                            class="inline-flex
                                   items-center
                                   justify-center
                                   gap-3
                                   px-6 py-3.5
                                   rounded-lg
                                   bg-slate-700
                                   hover:bg-slate-600
                                   border border-slate-500
                                   text-white
                                   text-sm
                                   font-bold
                                   transition"
                        >
                            Start Screening

                            <span class="text-lg">
                                &rarr;
                            </span>
                        </a>


                        <button
                            type="button"
                            disabled
                            title="Prediction History will be connected next."
                            class="inline-flex
                                   items-center
                                   justify-center
                                   px-6 py-3.5
                                   rounded-lg
                                   bg-slate-900
                                   border border-slate-700
                                   text-slate-500
                                   text-sm
                                   font-bold
                                   cursor-not-allowed"
                        >
                            View Prediction History
                        </button>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- RIGHT SYSTEM CARD                                 --}}
                {{-- ================================================= --}}

                <div
                    class="lg:col-span-5
                           relative z-10"
                >

                    <div
                        class="bg-slate-800/80
                               border border-slate-700
                               rounded-2xl
                               p-7"
                    >

                        <div
                            class="flex
                                   items-start
                                   justify-between
                                   gap-5
                                   mb-6"
                        >

                            <div>

                                <p
                                    class="text-[11px]
                                           uppercase
                                           tracking-[0.18em]
                                           text-slate-500
                                           font-bold"
                                >
                                    RETINA System
                                </p>


                                <h2
                                    class="mt-2
                                           text-xl
                                           font-bold
                                           text-slate-100"
                                >
                                    Screening Overview
                                </h2>

                            </div>


                            <img
                                src="{{ asset('images/retina-logo.png') }}"
                                alt="RETINA"
                                class="w-16 h-auto"
                            >

                        </div>



                        {{-- MODEL --}}
                        <div
                            class="flex
                                   justify-between
                                   items-center
                                   gap-4
                                   py-4
                                   border-b border-slate-700"
                        >

                            <span class="text-sm text-sky-300/80">
                                Model
                            </span>


                            <span
                                class="text-sm
                                       font-bold
                                       text-slate-100"
                            >
                                EfficientNetB4
                            </span>

                        </div>



                        {{-- SEVERITY --}}
                        <div
                            class="flex
                                   justify-between
                                   items-center
                                   gap-4
                                   py-4
                                   border-b border-slate-700"
                        >

                            <span class="text-sm text-sky-300/80">
                                Severity Scale
                            </span>


                            <span
                                class="text-sm
                                       font-semibold
                                       text-slate-100"
                            >
                                ICDR 5-Stage
                            </span>

                        </div>



                        {{-- INPUT VALIDATION --}}
                        <div
                            class="flex
                                   justify-between
                                   items-center
                                   gap-4
                                   py-4
                                   border-b border-slate-700"
                        >

                            <span class="text-sm text-sky-300/80">
                                Input Validation
                            </span>


                            <span
                                class="text-sm
                                       font-semibold
                                       text-slate-100"
                            >
                                Safety Screening
                            </span>

                        </div>



                        {{-- THRESHOLD --}}
                        <div
                            class="flex
                                   justify-between
                                   items-center
                                   gap-4
                                   pt-4"
                        >

                            <span class="text-sm text-sky-300/80">
                                Referral Threshold
                            </span>


                            <span
                                class="text-sm
                                       font-mono
                                       font-bold
                                       text-slate-100"
                            >
                                0.49
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- QUICK WORKFLOW                                             --}}
        {{-- ========================================================= --}}

        <section
            class="mt-6
                   grid grid-cols-1
                   md:grid-cols-3
                   gap-4"
        >

            {{-- SCREENING --}}
            <a
                href="{{ route('screening') }}"
                class="group
                       bg-slate-900
                       border border-slate-700
                       hover:border-slate-500
                       rounded-xl
                       p-5
                       transition"
            >

                <p
                    class="text-[10px]
                           font-mono
                           uppercase
                           tracking-widest
                           text-teal-400"
                >
                    Screening
                </p>


                <h3
                    class="mt-2
                           text-base
                           font-bold
                           text-slate-100"
                >
                    Analyze a Fundus Image
                </h3>


                <p
                    class="mt-2
                           text-xs
                           text-slate-500
                           leading-relaxed"
                >
                    Run the standard RETINA Web screening workflow and review
                    the AI-assisted result.
                </p>


                <span
                    class="inline-block
                           mt-4
                           text-xs
                           font-bold
                           text-sky-300
                           group-hover:text-sky-200"
                >
                    Open Screening &rarr;
                </span>

            </a>



            {{-- EVALUATION --}}
            <a
                href="{{ route('evaluation') }}"
                class="group
                       bg-slate-900
                       border border-slate-700
                       hover:border-slate-500
                       rounded-xl
                       p-5
                       transition"
            >

                <p
                    class="text-[10px]
                           font-mono
                           uppercase
                           tracking-widest
                           text-teal-400"
                >
                    Research Evaluation
                </p>


                <h3
                    class="mt-2
                           text-base
                           font-bold
                           text-slate-100"
                >
                    Formal Evaluation Mode
                </h3>


                <p
                    class="mt-2
                           text-xs
                           text-slate-500
                           leading-relaxed"
                >
                    Use assigned RETINA-EVAL study case IDs during controlled
                    research evaluation.
                </p>


                <span
                    class="inline-block
                           mt-4
                           text-xs
                           font-bold
                           text-sky-300
                           group-hover:text-sky-200"
                >
                    Open Evaluation &rarr;
                </span>

            </a>



            {{-- CLINICAL SAFETY --}}
            <div
                class="bg-slate-900
                       border border-slate-700
                       rounded-xl
                       p-5"
            >

                <p
                    class="text-[10px]
                           font-mono
                           uppercase
                           tracking-widest
                           text-amber-400"
                >
                    Clinical Safety
                </p>


                <h3
                    class="mt-2
                           text-base
                           font-bold
                           text-slate-100"
                >
                    Professional Review Required
                </h3>


                <p
                    class="mt-2
                           text-xs
                           text-slate-500
                           leading-relaxed"
                >
                    RETINA Web provides clinical decision support only.
                    Predictions must be reviewed by a qualified eye-care
                    professional.
                </p>

            </div>

        </section>

    </main>


    {{-- ============================================================= --}}
    {{-- FOOTER                                                        --}}
    {{-- ============================================================= --}}

    <footer
        class="mt-12
               border-t border-slate-700/60"
    >

        <div
            class="max-w-7xl
                   mx-auto
                   px-6
                   py-6
                   flex flex-col
                   sm:flex-row
                   items-center
                   justify-between
                   gap-3
                   text-[11px]
                   text-slate-500"
        >

            <span>
                RETINA Web
            </span>


            <div class="flex items-center gap-4">

                <a
                    href="{{ route('legal.eula') }}"
                    class="hover:text-slate-300"
                >
                    EULA
                </a>


                <a
                    href="{{ route('legal.privacy') }}"
                    class="hover:text-slate-300"
                >
                    Privacy Notice
                </a>


                <span>
                    Version 1.0
                </span>

            </div>

        </div>

    </footer>

</body>

</html>