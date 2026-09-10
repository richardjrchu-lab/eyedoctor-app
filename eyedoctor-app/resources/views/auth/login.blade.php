<x-guest-layout>

    {{-- ============================================================= --}}
    {{-- LOGIN HEADER                                                  --}}
    {{-- ============================================================= --}}

    <div class="mb-5">

        <h2
            class="text-xl font-bold tracking-tight
                   text-[#f1f5f9]"
        >
            Welcome back
        </h2>

        <p
            class="mt-1 text-sm leading-5
                   text-[#94a3b8]"
        >
            Sign in with your authorized account to continue.
        </p>

    </div>



    {{-- ============================================================= --}}
    {{-- SESSION STATUS                                                --}}
    {{-- ============================================================= --}}

    @if (session('status'))

        <div
            class="mb-4 rounded-lg
                   border border-[#475569]
                   bg-[#0f172a]
                   px-4 py-3
                   text-sm text-[#cbd5e1]"
        >
            {{ session('status') }}
        </div>

    @endif



    {{-- ============================================================= --}}
    {{-- LOGIN FORM                                                    --}}
    {{-- ============================================================= --}}

    <form
        method="POST"
        action="{{ route('login') }}"
        x-data="{ showPassword: false }"
    >

        @csrf



        {{-- ========================================================= --}}
        {{-- EMAIL                                                     --}}
        {{-- ========================================================= --}}

        <div>

            <label
                for="email"
                class="mb-1.5 block
                       text-xs font-semibold uppercase
                       tracking-[0.14em]
                       text-[#cbd5e1]"
            >
                Email
            </label>


            <div class="relative">

                <div
                    class="pointer-events-none absolute
                           inset-y-0 left-0
                           flex items-center pl-3.5
                           text-[#64748b]"
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
                            x="3"
                            y="5"
                            width="18"
                            height="14"
                            rx="2"
                        />

                        <path d="m4 7 8 6 8-6" />
                    </svg>

                </div>


                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="name@example.com"
                    class="block w-full rounded-lg
                           border border-[#475569]
                           bg-[#0f172a]
                           py-2.5 pl-11 pr-4
                           text-sm text-[#f1f5f9]
                           placeholder:text-[#64748b]
                           focus:border-[#94a3b8]
                           focus:outline-none
                           focus:ring-1
                           focus:ring-[#94a3b8]"
                >

            </div>


            @error('email')

                <p class="mt-1.5 text-xs text-red-300">
                    {{ $message }}
                </p>

            @enderror

        </div>



        {{-- ========================================================= --}}
        {{-- PASSWORD                                                  --}}
        {{-- ========================================================= --}}

        <div class="mt-4">

            <label
                for="password"
                class="mb-1.5 block
                       text-xs font-semibold uppercase
                       tracking-[0.14em]
                       text-[#cbd5e1]"
            >
                Password
            </label>


            <div class="relative">

                {{-- Lock Icon --}}
                <div
                    class="pointer-events-none absolute
                           inset-y-0 left-0
                           flex items-center pl-3.5
                           text-[#64748b]"
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
                            x="5"
                            y="10"
                            width="14"
                            height="10"
                            rx="2"
                        />

                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                    </svg>

                </div>


                <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="block w-full rounded-lg
                           border border-[#475569]
                           bg-[#0f172a]
                           py-2.5 pl-11 pr-12
                           text-sm text-[#f1f5f9]
                           placeholder:text-[#64748b]
                           focus:border-[#94a3b8]
                           focus:outline-none
                           focus:ring-1
                           focus:ring-[#94a3b8]"
                >


                {{-- Show / Hide Password --}}
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0
                           flex items-center px-3.5
                           text-[#64748b]
                           transition
                           hover:text-[#cbd5e1]
                           focus:outline-none"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                >

                    <svg
                        x-show="!showPassword"
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
                            d="M2.5 12s3.5-6 9.5-6
                               9.5 6 9.5 6
                               -3.5 6-9.5 6
                               -9.5-6-9.5-6Z"
                        />

                        <circle
                            cx="12"
                            cy="12"
                            r="2.5"
                        />
                    </svg>


                    <svg
                        x-show="showPassword"
                        x-cloak
                        viewBox="0 0 24 24"
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m3 3 18 18" />

                        <path
                            d="M10.7 6.1
                               A9.9 9.9 0 0 1 12 6
                               c6 0 9.5 6 9.5 6
                               a17 17 0 0 1-2.1 2.8"
                        />

                        <path
                            d="M6.6 6.6
                               C4 8.3 2.5 12 2.5 12
                               s3.5 6 9.5 6
                               a9.6 9.6 0 0 0 3.3-.6"
                        />
                    </svg>

                </button>

            </div>


            @error('password')

                <p class="mt-1.5 text-xs text-red-300">
                    {{ $message }}
                </p>

            @enderror

        </div>



        {{-- ========================================================= --}}
        {{-- REMEMBER / FORGOT PASSWORD                                --}}
        {{-- ========================================================= --}}

        <div
            class="mt-4 flex items-center
                   justify-between gap-4"
        >

            <label
                for="remember_me"
                class="flex cursor-pointer
                       items-center gap-2
                       text-sm text-[#cbd5e1]"
            >

                <input
                    id="remember_me"
                    name="remember"
                    type="checkbox"
                    class="h-4 w-4 rounded
                           border-[#475569]
                           bg-[#0f172a]
                           text-[#64748b]
                           focus:ring-[#94a3b8]
                           focus:ring-offset-[#1e293b]"
                >

                <span>
                    Remember me
                </span>

            </label>


            @if (Route::has('password.request'))

                <a
                    href="{{ route('password.request') }}"
                    class="text-sm text-[#94a3b8]
                           underline-offset-4
                           transition
                           hover:text-[#f1f5f9]
                           hover:underline
                           focus:outline-none"
                >
                    Forgot password?
                </a>

            @endif

        </div>



        {{-- ========================================================= --}}
        {{-- LOGIN BUTTON                                              --}}
        {{-- ========================================================= --}}

        <button
            type="submit"
            class="mt-5 inline-flex w-full
                   items-center justify-center
                   rounded-lg
                   border border-[#475569]
                   bg-[#334155]
                   px-4 py-2.5
                   text-sm font-semibold
                   tracking-wide
                   text-[#f1f5f9]
                   shadow-sm
                   transition duration-150
                   hover:bg-[#475569]
                   focus:outline-none
                   focus:ring-2
                   focus:ring-[#94a3b8]
                   focus:ring-offset-2
                   focus:ring-offset-[#1e293b]"
        >
            LOG IN
        </button>



        {{-- ========================================================= --}}
        {{-- AUTHORIZED ACCESS NOTICE                                  --}}
        {{-- ========================================================= --}}

        <div
            class="mt-5 border-t
                   border-[#334155]
                   pt-4 text-center"
        >

            <p
                class="text-xs leading-5
                       text-[#64748b]"
            >
                Access to this system is limited to authorized users.
            </p>

        </div>

    </form>

</x-guest-layout>