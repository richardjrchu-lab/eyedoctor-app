<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RETINA — Diabetic Retinopathy Detection System</title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
        rel="stylesheet"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>


<body class="bg-[#0f172a] font-sans text-[#f1f5f9] antialiased">

    <main
        class="flex min-h-screen items-start justify-center
               px-4 py-4
               sm:px-6 sm:pt-4 sm:pb-5"
    >

        <div class="w-full max-w-md">


            {{-- ========================================================= --}}
            {{-- RETINA BRANDING                                           --}}
            {{-- ========================================================= --}}

            <div class="mb-4 text-center">

                <img
                    src="{{ asset('images/retina-logo.png') }}"
                    alt="RETINA Logo"
                    class="mx-auto h-auto w-[220px]
                           object-contain
                           sm:w-[240px]"
                >


                <div class="mt-2">

                    <div
                        class="text-3xl font-extrabold
                               tracking-[0.30em]
                               text-[#f1f5f9]
                               sm:text-4xl"
                    >
                        RETINA
                    </div>


                    <h1
                        class="mt-2 text-xl font-bold
                               tracking-tight
                               text-[#f1f5f9]
                               sm:text-2xl"
                    >
                        Diabetic Retinopathy Detection System
                    </h1>


                    <p
                        class="mt-1.5 text-sm font-medium
                               leading-5
                               text-[#94a3b8]
                               sm:text-[15px]"
                    >
                        AI-Assisted Diabetic Retinopathy Screening Platform
                    </p>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- AUTHENTICATION CARD                                       --}}
            {{-- ========================================================= --}}

            <section
                class="overflow-hidden rounded-2xl
                       border border-[#334155]
                       bg-[#1e293b]
                       shadow-2xl shadow-black/25"
            >

                <div
                    class="px-6 py-6
                           sm:px-9 sm:py-7"
                >

                    {{ $slot }}

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- RESTRICTED ACCESS NOTICE                                  --}}
            {{-- ========================================================= --}}

            <div class="mt-3 text-center">

                <div
                    class="flex items-center justify-center
                           gap-2
                           text-xs font-medium
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
                            x="5"
                            y="10"
                            width="14"
                            height="10"
                            rx="2"
                        />

                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                    </svg>


                    <span>
                        Restricted professional access
                    </span>

                </div>


                <p
                    class="mt-1 text-[11px]
                           leading-5
                           text-[#64748b]"
                >
                    Authorized users and approved clinical personnel only.
                </p>

            </div>

        </div>

    </main>

</body>

</html>