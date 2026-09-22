<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        RETINA — AI-Assisted Diabetic Retinopathy Screening System
    </title>

    <meta
        name="description"
        content="RETINA is a research-based AI-assisted diabetic retinopathy screening system designed to support retinal fundus image assessment and referral decision support."
    >

    <meta
        name="robots"
        content="index, follow"
    >

    <link
        rel="canonical"
        href="{{ url('/about') }}"
    >

    <meta
        property="og:type"
        content="website"
    >

    <meta
        property="og:title"
        content="RETINA — AI-Assisted Diabetic Retinopathy Screening System"
    >

    <meta
        property="og:description"
        content="Learn about RETINA, a research-based AI-assisted system for diabetic retinopathy screening using retinal fundus photographs."
    >

    <meta
        property="og:url"
        content="{{ url('/about') }}"
    >

    <meta
        property="og:image"
        content="{{ asset('images/retina-logo.png') }}"
    >

    <meta
        name="twitter:card"
        content="summary"
    >

    <script src="https://cdn.tailwindcss.com"></script>
</head>


<body
    class="min-h-screen
           bg-[#0f172a]
           text-[#f1f5f9]
           font-sans"
>

    {{-- ============================================================= --}}
    {{-- HEADER                                                        --}}
    {{-- ============================================================= --}}

    <header
        class="border-b
               border-[#334155]
               bg-[#111827]"
    >

        <div
            class="mx-auto flex
                   max-w-6xl
                   items-center
                   justify-between
                   px-6 py-4"
        >

            <a
                href="{{ route('public.about') }}"
                class="flex items-center gap-3"
            >

                <img
                    src="{{ asset('images/retina-logo.png') }}"
                    alt="RETINA logo"
                    class="h-10 w-auto"
                >

                <div>

                    <div
                        class="text-lg
                               font-extrabold
                               tracking-[0.22em]"
                    >
                        RETINA
                    </div>

                    <div
                        class="text-[11px]
                               text-[#94a3b8]"
                    >
                        Diabetic Retinopathy Detection System
                    </div>

                </div>

            </a>


            <a
                href="{{ route('login') }}"
                class="rounded-lg
                       border border-[#475569]
                       bg-[#1e293b]
                       px-4 py-2
                       text-sm font-semibold
                       text-[#e2e8f0]
                       transition
                       hover:border-[#64748b]
                       hover:bg-[#334155]"
            >
                Professional Login
            </a>

        </div>

    </header>



    {{-- ============================================================= --}}
    {{-- HERO                                                          --}}
    {{-- ============================================================= --}}

    <main>

        <section
            class="mx-auto grid
                   max-w-6xl
                   grid-cols-1
                   gap-10
                   px-6 py-16
                   lg:grid-cols-2
                   lg:items-center
                   lg:py-24"
        >

            <div>

                <div
                    class="mb-4 inline-flex
                           rounded-full
                           border border-[#334155]
                           bg-[#1e293b]
                           px-3 py-1
                           text-xs font-semibold
                           uppercase
                           tracking-[0.16em]
                           text-[#94a3b8]"
                >
                    Research-Based Clinical Decision Support
                </div>


                <h1
                    class="max-w-3xl
                           text-4xl
                           font-extrabold
                           leading-tight
                           tracking-tight
                           sm:text-5xl"
                >
                    AI-Assisted Diabetic Retinopathy Screening
                </h1>


                <p
                    class="mt-6
                           max-w-2xl
                           text-lg
                           leading-8
                           text-[#cbd5e1]"
                >
                    RETINA is an artificial intelligence-assisted system
                    developed to support diabetic retinopathy screening
                    from color retinal fundus photographs.
                </p>


                <p
                    class="mt-4
                           max-w-2xl
                           leading-7
                           text-[#94a3b8]"
                >
                    The platform analyzes retinal images and provides
                    an ICDR severity-stage prediction, class probability
                    information, and a referral decision to assist qualified
                    eye care professionals during image review.
                </p>


                <div
                    class="mt-8 flex
                           flex-wrap gap-3"
                >

                    <a
                        href="#about-retina"
                        class="rounded-lg
                               bg-[#2dd4bf]
                               px-5 py-3
                               text-sm font-bold
                               text-[#0f172a]
                               transition
                               hover:bg-[#5eead4]"
                    >
                        Learn About RETINA
                    </a>


                    <a
                        href="{{ route('login') }}"
                        class="rounded-lg
                               border border-[#475569]
                               px-5 py-3
                               text-sm font-semibold
                               text-[#e2e8f0]
                               transition
                               hover:border-[#64748b]
                               hover:bg-[#1e293b]"
                    >
                        Authorized Professional Access
                    </a>

                </div>

            </div>



            <div
                class="rounded-2xl
                       border border-[#334155]
                       bg-[#1e293b]
                       p-8
                       shadow-2xl
                       shadow-black/20"
            >

                <img
                    src="{{ asset('images/retina-logo.png') }}"
                    alt="RETINA AI-assisted diabetic retinopathy screening project logo"
                    class="mx-auto
                           w-full
                           max-w-sm
                           object-contain"
                >


                <div
                    class="mt-8
                           grid grid-cols-1
                           gap-3
                           sm:grid-cols-3"
                >

                    <div
                        class="rounded-xl
                               border border-[#334155]
                               bg-[#0f172a]
                               p-4"
                    >

                        <div
                            class="text-xs
                                   uppercase
                                   tracking-wider
                                   text-[#64748b]"
                        >
                            Input
                        </div>

                        <div
                            class="mt-1
                                   font-semibold
                                   text-[#e2e8f0]"
                        >
                            Fundus Image
                        </div>

                    </div>


                    <div
                        class="rounded-xl
                               border border-[#334155]
                               bg-[#0f172a]
                               p-4"
                    >

                        <div
                            class="text-xs
                                   uppercase
                                   tracking-wider
                                   text-[#64748b]"
                        >
                            Output
                        </div>

                        <div
                            class="mt-1
                                   font-semibold
                                   text-[#e2e8f0]"
                        >
                            ICDR Stage
                        </div>

                    </div>


                    <div
                        class="rounded-xl
                               border border-[#334155]
                               bg-[#0f172a]
                               p-4"
                    >

                        <div
                            class="text-xs
                                   uppercase
                                   tracking-wider
                                   text-[#64748b]"
                        >
                            Purpose
                        </div>

                        <div
                            class="mt-1
                                   font-semibold
                                   text-[#e2e8f0]"
                        >
                            Decision Support
                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- ABOUT                                                     --}}
        {{-- ========================================================= --}}

        <section
            id="about-retina"
            class="border-y
                   border-[#334155]
                   bg-[#111827]"
        >

            <div
                class="mx-auto
                       max-w-6xl
                       px-6 py-16"
            >

                <h2
                    class="text-3xl
                           font-bold
                           tracking-tight"
                >
                    What is RETINA?
                </h2>


                <div
                    class="mt-6
                           grid grid-cols-1
                           gap-8
                           lg:grid-cols-2"
                >

                    <p
                        class="leading-8
                               text-[#cbd5e1]"
                    >
                        RETINA is a research project focused on the use
                        of artificial intelligence for diabetic retinopathy
                        screening. The system evaluates color fundus
                        photographs and categorizes diabetic retinopathy
                        severity according to the International Clinical
                        Diabetic Retinopathy classification framework.
                    </p>


                    <p
                        class="leading-8
                               text-[#cbd5e1]"
                    >
                        The project is intended to explore how AI-assisted
                        retinal image analysis may support clinical review,
                        particularly by presenting structured predictions,
                        class probabilities, and referral information to
                        authorized professionals.
                    </p>

                </div>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- DR EXPLANATION                                            --}}
        {{-- ========================================================= --}}

        <section
            class="mx-auto
                   max-w-6xl
                   px-6 py-16"
        >

            <h2
                class="text-3xl
                       font-bold
                       tracking-tight"
            >
                Diabetic Retinopathy Screening
            </h2>


            <p
                class="mt-5
                       max-w-4xl
                       leading-8
                       text-[#cbd5e1]"
            >
                Diabetic retinopathy is a diabetes-related retinal condition
                that can affect vision. Retinal fundus photography allows
                clinicians to examine visible retinal changes associated
                with different stages of the disease.
            </p>


            <p
                class="mt-4
                       max-w-4xl
                       leading-8
                       text-[#94a3b8]"
            >
                RETINA is designed to assist with image-based screening
                and classification. It does not replace a comprehensive
                eye examination or the professional judgment of an
                ophthalmologist or other qualified eye care professional.
            </p>

        </section>



        {{-- ========================================================= --}}
        {{-- SAFETY / RESEARCH NOTICE                                  --}}
        {{-- ========================================================= --}}

        <section
            class="border-t
                   border-[#334155]
                   bg-[#111827]"
        >

            <div
                class="mx-auto
                       max-w-6xl
                       px-6 py-12"
            >

                <div
                    class="rounded-xl
                           border border-[#475569]
                           bg-[#1e293b]
                           p-6"
                >

                    <h2
                        class="text-lg
                               font-bold"
                    >
                        Clinical and Research Notice
                    </h2>


                    <p
                        class="mt-3
                               leading-7
                               text-[#94a3b8]"
                    >
                        RETINA is a clinical decision-support and research
                        system. Its outputs are not a standalone medical
                        diagnosis and must be interpreted together with
                        clinical assessment by a qualified eye care
                        professional.
                    </p>


                    <p
                        class="mt-3
                               leading-7
                               text-[#64748b]"
                    >
                        Patient records, retinal images, predictions,
                        clinician history, and formal evaluation records
                        are not publicly accessible through this page.
                        Professional features require authenticated,
                        authorized access.
                    </p>

                </div>

            </div>

        </section>

    </main>



    {{-- ============================================================= --}}
    {{-- FOOTER                                                        --}}
    {{-- ============================================================= --}}

    <footer
        class="border-t
               border-[#334155]
               bg-[#0b1120]"
    >

        <div
            class="mx-auto flex
                   max-w-6xl
                   flex-col
                   gap-3
                   px-6 py-8
                   text-sm
                   text-[#64748b]
                   sm:flex-row
                   sm:items-center
                   sm:justify-between"
        >

            <div>
                RETINA — Diabetic Retinopathy Detection System
            </div>


            <div>
                AI-assisted screening research project
            </div>

        </div>

    </footer>

</body>

</html>