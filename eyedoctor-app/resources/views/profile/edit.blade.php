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

    <title>RETINA Profile</title>

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

    @include('partials.app-header')

    <main
        class="mx-auto w-full max-w-7xl
               px-5 py-8
               sm:px-6 sm:py-10"
    >

        {{-- PAGE HEADER --}}
        <section
            class="rounded-3xl
                   border border-[#334155]
                   bg-[#0f172a]
                   px-6 py-8
                   sm:px-8"
        >

            <div
                class="inline-flex items-center gap-2
                       rounded-full
                       border border-[#334155]
                       bg-[#1e293b]
                       px-3 py-1.5
                       text-xs font-semibold
                       text-[#94a3b8]"
            >
                <span
                    class="h-2 w-2 rounded-full
                           bg-[#38bdf8]"
                ></span>

                Account Settings
            </div>

            <h1
                class="mt-5 text-3xl
                       font-extrabold tracking-tight
                       text-[#f1f5f9]
                       sm:text-4xl"
            >
                Account Profile
            </h1>

            <p
                class="mt-3 max-w-2xl
                       text-sm leading-6
                       text-[#94a3b8]"
            >
                Manage your RETINA account information and
                authentication credentials.
            </p>

        </section>


        {{-- PROFILE SECTIONS --}}
        <div
            class="mt-6 grid grid-cols-1
                   gap-6
                   lg:grid-cols-2"
        >

            @include('profile.partials.update-profile-information-form')

            @include('profile.partials.update-password-form')

        </div>


        {{-- ACCOUNT NOTICE --}}
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
                        <path d="M12 3 4.5 6v5.5c0 4.6 3.1 7.8 7.5 9.5 4.4-1.7 7.5-4.9 7.5-9.5V6L12 3Z" />
                        <path d="M9.5 12 11 13.5 14.5 10" />
                    </svg>

                </div>

                <div>
                    <h2
                        class="text-sm font-bold
                               text-[#cbd5e1]"
                    >
                        Account Protection
                    </h2>

                    <p
                        class="mt-1 text-xs
                               leading-5
                               text-[#64748b]"
                    >
                        Self-service account deletion is unavailable because
                        RETINA accounts may be referenced by authorized
                        clinical, audit, and legal records.
                    </p>
                </div>

            </div>

        </section>


        <footer
            class="mt-8
                   border-t border-[#334155]
                   pt-5 text-center"
        >
            <p class="text-xs text-[#64748b]">
                RETINA &mdash; Diabetic Retinopathy Screening Support System
            </p>
        </footer>

    </main>

</body>

</html>