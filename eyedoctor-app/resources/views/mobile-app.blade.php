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
        RETINA Mobile App
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
    {{-- MAIN CONTENT                                                  --}}
    {{-- ============================================================= --}}

    <main
        class="mx-auto w-full max-w-6xl
               px-5 py-8
               sm:px-6 sm:py-10"
    >


        {{-- ========================================================= --}}
        {{-- PAGE HEADING                                              --}}
        {{-- ========================================================= --}}

        <div class="mb-8 max-w-3xl">

            <div
                class="mb-3 inline-flex
                       items-center gap-2
                       rounded-full
                       border border-[#334155]
                       bg-[#0f172a]
                       px-3 py-1.5
                       text-xs font-semibold
                       text-[#94a3b8]"
            >

                <svg
                    viewBox="0 0 24 24"
                    class="h-4 w-4"
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

                Android Application

            </div>


            <h1
                class="text-3xl font-extrabold
                       tracking-tight
                       text-[#f1f5f9]
                       sm:text-4xl"
            >
                RETINA Mobile
            </h1>


            <p
                class="mt-3 text-sm
                       leading-6
                       text-[#94a3b8]
                       sm:text-base"
            >
                Access the Android version of the RETINA Diabetic
                Retinopathy Detection System for authorized professional,
                research, and approved clinical screening workflows.
            </p>

        </div>



        {{-- ========================================================= --}}
        {{-- APK ERROR                                                 --}}
        {{-- ========================================================= --}}

        @if (session('apk_error'))

            <div
                class="mb-6 rounded-xl
                       border border-amber-800/60
                       bg-amber-950/30
                       px-4 py-3"
            >

                <div class="flex items-start gap-3">

                    <span
                        class="mt-0.5
                               text-amber-400"
                    >
                        &#9888;
                    </span>


                    <p
                        class="text-sm
                               leading-6
                               text-amber-200"
                    >
                        {{ session('apk_error') }}
                    </p>

                </div>

            </div>

        @endif



        {{-- ========================================================= --}}
        {{-- MAIN GRID                                                 --}}
        {{-- ========================================================= --}}

        <div
            class="grid grid-cols-1
                   gap-6
                   lg:grid-cols-5"
        >


            {{-- ===================================================== --}}
            {{-- DOWNLOAD CARD                                         --}}
            {{-- ===================================================== --}}

            <section
                class="rounded-2xl
                       border border-[#334155]
                       bg-[#0f172a]
                       p-6
                       shadow-xl shadow-black/10
                       sm:p-8
                       lg:col-span-3"
            >

                <div
                    class="flex flex-col gap-6
                           sm:flex-row
                           sm:items-start
                           sm:justify-between"
                >

                    <div>

                        <div
                            class="flex h-14 w-14
                                   items-center justify-center
                                   rounded-xl
                                   border border-[#334155]
                                   bg-[#1e293b]
                                   text-[#cbd5e1]"
                        >

                            <svg
                                viewBox="0 0 24 24"
                                class="h-7 w-7"
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


                        <h2
                            class="mt-5
                                   text-xl font-bold
                                   text-[#f1f5f9]"
                        >
                            RETINA for Android
                        </h2>


                        <p
                            class="mt-2 max-w-xl
                                   text-sm leading-6
                                   text-[#94a3b8]"
                        >
                            Download the approved Android release of the
                            RETINA screening application. The installation
                            package is available only to authenticated RETINA
                            users.
                        </p>

                    </div>



                    {{-- AVAILABILITY STATUS --}}

                    @if ($apkAvailable)

                        <span
                            class="inline-flex w-fit
                                   items-center gap-2
                                   rounded-full
                                   border border-emerald-800
                                   bg-emerald-950/30
                                   px-3 py-1.5
                                   text-xs font-semibold
                                   text-emerald-300"
                        >

                            <span
                                class="h-2 w-2
                                       rounded-full
                                       bg-emerald-400"
                            >
                            </span>

                            Available

                        </span>

                    @else

                        <span
                            class="inline-flex w-fit
                                   items-center gap-2
                                   rounded-full
                                   border border-[#475569]
                                   bg-[#1e293b]
                                   px-3 py-1.5
                                   text-xs font-semibold
                                   text-[#94a3b8]"
                        >

                            <span
                                class="h-2 w-2
                                       rounded-full
                                       bg-[#64748b]"
                            >
                            </span>

                            Pending Release

                        </span>

                    @endif

                </div>



                {{-- ================================================= --}}
                {{-- APP INFORMATION                                   --}}
                {{-- ================================================= --}}

                <div
                    class="mt-7 grid
                           grid-cols-1 gap-3
                           sm:grid-cols-3"
                >

                    {{-- VERSION --}}
                    <div
                        class="rounded-xl
                               border border-[#334155]
                               bg-[#1e293b]
                               p-4"
                    >

                        <div
                            class="text-[10px]
                                   font-semibold uppercase
                                   tracking-widest
                                   text-[#64748b]"
                        >
                            Version
                        </div>


                        <div
                            class="mt-1
                                   text-sm font-semibold
                                   text-[#cbd5e1]"
                        >
                            {{ $appVersion ?: 'Pending release' }}
                        </div>

                    </div>



                    {{-- VERSION CODE --}}
                    <div
                        class="rounded-xl
                               border border-[#334155]
                               bg-[#1e293b]
                               p-4"
                    >

                        <div
                            class="text-[10px]
                                   font-semibold uppercase
                                   tracking-widest
                                   text-[#64748b]"
                        >
                            Version Code
                        </div>


                        <div
                            class="mt-1
                                   text-sm font-semibold
                                   text-[#cbd5e1]"
                        >
                            {{ $versionCode ?: 'Pending' }}
                        </div>

                    </div>



                    {{-- PLATFORM --}}
                    <div
                        class="rounded-xl
                               border border-[#334155]
                               bg-[#1e293b]
                               p-4"
                    >

                        <div
                            class="text-[10px]
                                   font-semibold uppercase
                                   tracking-widest
                                   text-[#64748b]"
                        >
                            Platform
                        </div>


                        <div
                            class="mt-1
                                   text-sm font-semibold
                                   text-[#cbd5e1]"
                        >
                            Android
                        </div>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- APPLICATION ID                                    --}}
                {{-- ================================================= --}}

                <div
                    class="mt-3 rounded-xl
                           border border-[#334155]
                           bg-[#1e293b]
                           p-4"
                >

                    <div
                        class="text-[10px]
                               font-semibold uppercase
                               tracking-widest
                               text-[#64748b]"
                    >
                        Application ID
                    </div>


                    <div
                        class="mt-1 break-all
                               font-mono text-sm
                               text-[#cbd5e1]"
                    >
                        {{ $packageId ?: 'Pending' }}
                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- DOWNLOAD AREA                                     --}}
                {{-- ================================================= --}}

                <div
                    class="mt-7
                           border-t border-[#334155]
                           pt-6"
                >

                    @if ($apkAvailable)

                        <a
                            href="{{ route('mobile-app.download') }}"
                            class="inline-flex w-full
                                   items-center justify-center
                                   gap-2 rounded-lg
                                   border border-[#64748b]
                                   bg-[#334155]
                                   px-5 py-3
                                   text-sm font-bold
                                   text-[#f1f5f9]
                                   shadow-sm
                                   transition
                                   hover:bg-[#475569]
                                   focus:outline-none
                                   focus:ring-2
                                   focus:ring-[#94a3b8]
                                   focus:ring-offset-2
                                   focus:ring-offset-[#0f172a]
                                   sm:w-auto"
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
                                <path d="M12 3v12" />
                                <path d="m7 10 5 5 5-5" />
                                <path d="M5 21h14" />
                            </svg>

                            Download Android APK

                        </a>

                    @else

                        <button
                            type="button"
                            disabled
                            class="inline-flex w-full
                                   cursor-not-allowed
                                   items-center justify-center
                                   gap-2 rounded-lg
                                   border border-[#334155]
                                   bg-[#1e293b]
                                   px-5 py-3
                                   text-sm font-semibold
                                   text-[#64748b]
                                   sm:w-auto"
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
                                <path d="M12 3v12" />
                                <path d="m7 10 5 5 5-5" />
                                <path d="M5 21h14" />
                            </svg>

                            Package Not Yet Available

                        </button>

                    @endif


                    <p
                        class="mt-3
                               text-xs leading-5
                               text-[#64748b]"
                    >
                        Authentication is required for every download.
                        The Android installation package is stored in
                        private application storage and is not exposed
                        through a public URL.
                    </p>

                </div>

            </section>



            {{-- ===================================================== --}}
            {{-- INSTALLATION GUIDE                                    --}}
            {{-- ===================================================== --}}

            <aside
                class="rounded-2xl
                       border border-[#334155]
                       bg-[#0f172a]
                       p-6
                       shadow-xl shadow-black/10
                       lg:col-span-2"
            >

                <h2
                    class="text-lg font-bold
                           text-[#f1f5f9]"
                >
                    Installation Guide
                </h2>


                <p
                    class="mt-1
                           text-xs leading-5
                           text-[#64748b]"
                >
                    Follow these steps after the approved release
                    becomes available.
                </p>



                <div class="mt-6 space-y-6">


                    {{-- STEP 1 --}}
                    <div class="flex gap-4">

                        <div
                            class="flex h-8 w-8
                                   shrink-0 items-center
                                   justify-center
                                   rounded-full
                                   border border-[#475569]
                                   bg-[#1e293b]
                                   text-xs font-bold
                                   text-[#cbd5e1]"
                        >
                            1
                        </div>


                        <div>

                            <div
                                class="text-sm font-semibold
                                       text-[#cbd5e1]"
                            >
                                Download
                            </div>


                            <p
                                class="mt-1
                                       text-xs leading-5
                                       text-[#64748b]"
                            >
                                Sign in to your RETINA account and download
                                the approved Android APK from this page.
                            </p>

                        </div>

                    </div>



                    {{-- STEP 2 --}}
                    <div class="flex gap-4">

                        <div
                            class="flex h-8 w-8
                                   shrink-0 items-center
                                   justify-center
                                   rounded-full
                                   border border-[#475569]
                                   bg-[#1e293b]
                                   text-xs font-bold
                                   text-[#cbd5e1]"
                        >
                            2
                        </div>


                        <div>

                            <div
                                class="text-sm font-semibold
                                       text-[#cbd5e1]"
                            >
                                Allow Installation
                            </div>


                            <p
                                class="mt-1
                                       text-xs leading-5
                                       text-[#64748b]"
                            >
                                Android may request permission to install
                                applications from the browser or file manager
                                used for the download.
                            </p>

                        </div>

                    </div>



                    {{-- STEP 3 --}}
                    <div class="flex gap-4">

                        <div
                            class="flex h-8 w-8
                                   shrink-0 items-center
                                   justify-center
                                   rounded-full
                                   border border-[#475569]
                                   bg-[#1e293b]
                                   text-xs font-bold
                                   text-[#cbd5e1]"
                        >
                            3
                        </div>


                        <div>

                            <div
                                class="text-sm font-semibold
                                       text-[#cbd5e1]"
                            >
                                Install RETINA
                            </div>


                            <p
                                class="mt-1
                                       text-xs leading-5
                                       text-[#64748b]"
                            >
                                Open the downloaded APK and complete the
                                Android installation process.
                            </p>

                        </div>

                    </div>



                    {{-- STEP 4 --}}
                    <div class="flex gap-4">

                        <div
                            class="flex h-8 w-8
                                   shrink-0 items-center
                                   justify-center
                                   rounded-full
                                   border border-[#475569]
                                   bg-[#1e293b]
                                   text-xs font-bold
                                   text-[#cbd5e1]"
                        >
                            4
                        </div>


                        <div>

                            <div
                                class="text-sm font-semibold
                                       text-[#cbd5e1]"
                            >
                                Verify the Release
                            </div>


                            <p
                                class="mt-1
                                       text-xs leading-5
                                       text-[#64748b]"
                            >
                                Confirm that the app name, icon, version,
                                and application ID match the official RETINA
                                release information displayed here.
                            </p>

                        </div>

                    </div>

                </div>

            </aside>

        </div>



        {{-- ========================================================= --}}
        {{-- AUTHORIZED DISTRIBUTION NOTICE                            --}}
        {{-- ========================================================= --}}

        <section
            class="mt-6
                   rounded-2xl
                   border border-[#334155]
                   bg-[#0f172a]
                   p-6"
        >

            <div class="flex items-start gap-4">

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
                        Authorized Distribution Only
                    </h3>


                    <p
                        class="mt-1
                               text-xs leading-5
                               text-[#64748b]"
                    >
                        Do not redistribute the RETINA installation package
                        or provide access to unauthorized users. RETINA is an
                        AI-assisted screening and clinical decision-support
                        system and is not a substitute for diagnosis by a
                        qualified eye-care professional.
                    </p>

                </div>

            </div>

        </section>



        {{-- ========================================================= --}}
        {{-- SYSTEM INFORMATION                                       --}}
        {{-- ========================================================= --}}

        <section
            class="mt-6
                   rounded-2xl
                   border border-[#334155]
                   bg-[#0f172a]
                   p-6"
        >

            <h2
                class="text-sm font-bold
                       text-[#cbd5e1]"
            >
                About RETINA Mobile
            </h2>


            <p
                class="mt-2
                       text-xs leading-6
                       text-[#64748b]"
            >
                RETINA Mobile extends the Diabetic Retinopathy Detection
                System to supported Android devices. The application is
                intended to assist authorized users in diabetic retinopathy
                screening workflows using the RETINA artificial intelligence
                model.
            </p>


            <p
                class="mt-3
                       text-xs leading-6
                       text-[#64748b]"
            >
                Model-generated results must always be interpreted alongside
                appropriate clinical assessment and professional judgment.
            </p>

        </section>



        {{-- ========================================================= --}}
        {{-- FOOTER                                                    --}}
        {{-- ========================================================= --}}

        <footer
            class="mt-8
                   border-t border-[#334155]
                   pt-5
                   text-center"
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