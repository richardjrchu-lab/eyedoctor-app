<header class="border-b border-[#334155] bg-[#0f172a]">

    {{-- ============================================================= --}}
    {{-- TOP HEADER                                                    --}}
    {{-- ============================================================= --}}

    <div
        class="mx-auto flex w-full max-w-7xl
               items-center justify-between
               gap-5 px-5 py-4
               sm:px-6"
    >

        {{-- ========================================================= --}}
        {{-- RETINA BRAND                                              --}}
        {{-- ========================================================= --}}

        <a
            href="{{ auth()->user()->hasRole('doctor') ? route('welcome') : route('history') }}"
            class="flex min-w-0 items-center gap-3"
        >

            <img
                src="{{ asset('images/retina-logo.png') }}"
                alt="RETINA Logo"
                class="w-16 shrink-0 object-contain
                       sm:w-20"
            >


            <div class="min-w-0">

                <div
                    class="text-lg font-extrabold
                           tracking-[0.22em]
                           text-[#f1f5f9]"
                >
                    RETINA
                </div>


                <div
                    class="hidden text-xs
                           text-[#94a3b8]
                           sm:block"
                >
                    Diabetic Retinopathy Detection System
                </div>

            </div>

        </a>



        {{-- ========================================================= --}}
        {{-- USER / LOGOUT                                             --}}
        {{-- ========================================================= --}}

        <div
            class="flex shrink-0
                   items-center gap-4"
        >

            <div class="hidden text-right sm:block">

                <div
                    class="text-sm font-semibold
                           text-[#f1f5f9]"
                >
                    {{ auth()->user()->name }}
                </div>


                <div
                    class="text-[10px] uppercase
                           tracking-[0.14em]
                           text-[#64748b]"
                >
                    {{ auth()->user()->hasRole('admin')
                        ? 'Administrator'
                        : 'Doctor'
                    }}
                </div>

            </div>


            <form
                method="POST"
                action="{{ route('logout') }}"
            >

                @csrf


                <button
                    type="submit"
                    class="rounded-lg
                           border border-[#475569]
                           bg-[#1e293b]
                           px-4 py-2
                           text-sm font-semibold
                           text-[#cbd5e1]
                           transition
                           hover:border-[#64748b]
                           hover:bg-[#334155]
                           hover:text-[#f1f5f9]"
                >
                    Log out
                </button>

            </form>

        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- MAIN NAVIGATION                                               --}}
    {{-- ============================================================= --}}

    <div class="bg-[#1e293b]">

        <nav
            class="mx-auto flex w-full max-w-7xl
                   flex-wrap items-center
                   gap-2 px-5 py-3
                   sm:px-6"
        >

            {{-- ===================================================== --}}
            {{-- DOCTOR NAVIGATION                                     --}}
            {{-- ===================================================== --}}

            @role('doctor')

                {{-- OVERVIEW --}}
                <a
                    href="{{ route('welcome') }}"
                    class="
                        rounded-lg border
                        px-4 py-2
                        text-sm font-semibold
                        transition

                        {{ request()->routeIs('welcome')
                            ? 'border-[#64748b] bg-[#334155] text-[#f1f5f9]'
                            : 'border-[#334155] bg-[#0f172a] text-[#94a3b8] hover:border-[#64748b] hover:bg-[#172033] hover:text-[#f1f5f9]'
                        }}
                    "
                >
                    Overview
                </a>


                {{-- SCREENING --}}
                <a
                    href="{{ route('screening') }}"
                    class="
                        rounded-lg border
                        px-4 py-2
                        text-sm font-semibold
                        transition

                        {{ request()->routeIs('screening')
                            ? 'border-[#64748b] bg-[#334155] text-[#f1f5f9]'
                            : 'border-[#334155] bg-[#0f172a] text-[#94a3b8] hover:border-[#64748b] hover:bg-[#172033] hover:text-[#f1f5f9]'
                        }}
                    "
                >
                    Screening
                </a>

            @endrole



            {{-- ===================================================== --}}
            {{-- ADMIN ACCESS REQUESTS                                  --}}
            {{-- ===================================================== --}}

            @role('admin')

                <a
                    href="{{ route('admin.access-requests.index') }}"
                    class="
                        rounded-lg border
                        px-4 py-2
                        text-sm font-semibold
                        transition

                        {{ request()->routeIs('admin.access-requests.*')
                            ? 'border-[#2dd4bf] bg-[#134e4a]/40 text-[#5eead4]'
                            : 'border-[#334155] bg-[#0f172a] text-[#94a3b8] hover:border-[#64748b] hover:bg-[#172033] hover:text-[#f1f5f9]'
                        }}
                    "
                >
                    Access Requests
                </a>

            @endrole



            {{-- ===================================================== --}}
            {{-- PREDICTION HISTORY                                    --}}
            {{-- ===================================================== --}}

            <a
                href="{{ route('history') }}"
                class="
                    rounded-lg border
                    px-4 py-2
                    text-sm font-semibold
                    transition

                    {{ request()->routeIs('history')
                        || request()->routeIs('predictions.*')
                            ? 'border-[#64748b] bg-[#334155] text-[#f1f5f9]'
                            : 'border-[#334155] bg-[#0f172a] text-[#94a3b8] hover:border-[#64748b] hover:bg-[#172033] hover:text-[#f1f5f9]'
                    }}
                "
            >
                Prediction History
            </a>



            {{-- ===================================================== --}}
            {{-- MOBILE APP                                            --}}
            {{-- ===================================================== --}}

            <a
                href="{{ route('mobile-app') }}"
                class="
                    rounded-lg border
                    px-4 py-2
                    text-sm font-semibold
                    transition

                    {{ request()->routeIs('mobile-app*')
                        ? 'border-[#64748b] bg-[#334155] text-[#f1f5f9]'
                        : 'border-[#334155] bg-[#0f172a] text-[#94a3b8] hover:border-[#64748b] hover:bg-[#172033] hover:text-[#f1f5f9]'
                    }}
                "
            >
                Mobile App
            </a>

        </nav>

    </div>

</header>