@props([
    'title' => 'Professional Access',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <meta
        name="robots"
        content="noindex, nofollow"
    >

    <meta
        name="referrer"
        content="no-referrer"
    >

    <title>
        {{ $title }} ? RETINA
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
    class="bg-[#0f172a]
           font-sans
           text-[#f1f5f9]
           antialiased"
>

    <main
        class="min-h-screen
               px-4 py-8
               sm:px-6
               lg:px-8"
    >

        <div
            class="mx-auto
                   w-full
                   max-w-4xl"
        >

            {{-- ===================================================== --}}
            {{-- RETINA BRANDING                                       --}}
            {{-- ===================================================== --}}

            <header
                class="mb-7
                       text-center"
            >

                <a
                    href="{{ route('login') }}"
                    class="inline-block"
                    aria-label="Return to RETINA sign in"
                >

                    <img
                        src="{{ asset('images/retina-logo.png') }}"
                        alt="RETINA Logo"
                        class="mx-auto
                               h-auto
                               w-[190px]
                               object-contain
                               sm:w-[220px]"
                    >

                </a>


                <div
                    class="mt-2
                           text-3xl
                           font-extrabold
                           tracking-[0.28em]
                           text-[#f1f5f9]
                           sm:text-4xl"
                >
                    RETINA
                </div>


                <h1
                    class="mt-2
                           text-xl
                           font-bold
                           tracking-tight
                           text-[#f1f5f9]
                           sm:text-2xl"
                >
                    Diabetic Retinopathy Detection System
                </h1>


                <p
                    class="mt-1.5
                           text-sm
                           font-medium
                           text-[#94a3b8]"
                >
                    AI-Assisted Diabetic Retinopathy Screening Platform
                </p>

            </header>


            {{-- ===================================================== --}}
            {{-- PAGE CARD                                             --}}
            {{-- ===================================================== --}}

            <section
                class="overflow-hidden
                       rounded-2xl
                       border
                       border-[#334155]
                       bg-[#1e293b]
                       shadow-2xl
                       shadow-black/25"
            >

                <div
                    class="px-5 py-6
                           sm:px-8 sm:py-8
                           lg:px-10"
                >

                    {{ $slot }}

                </div>

            </section>


            {{-- ===================================================== --}}
            {{-- FOOTER                                                --}}
            {{-- ===================================================== --}}

            <footer
                class="mt-5
                       text-center"
            >

                <div
                    class="flex
                           items-center
                           justify-center
                           gap-2
                           text-xs
                           font-medium
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

                        <path
                            d="M8 10V7a4 4 0 0 1 8 0v3"
                        />
                    </svg>

                    <span>
                        Restricted professional access
                    </span>

                </div>


                <p
                    class="mt-1
                           text-[11px]
                           leading-5
                           text-[#64748b]"
                >
                    Access requests are reviewed before an account
                    can be activated.
                </p>

            </footer>

        </div>

    </main>

</body>

</html>
