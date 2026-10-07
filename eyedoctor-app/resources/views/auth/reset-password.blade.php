<x-guest-layout>

    <div
        x-data="{
            showPassword: false,
            showConfirmation: false
        }"
    >

        {{-- ========================================================= --}}
        {{-- HEADER                                                    --}}
        {{-- ========================================================= --}}

        <div class="mb-6">

            <div
                class="mb-3 inline-flex items-center
                       rounded-full
                       border border-[#334155]
                       bg-[#0f172a]
                       px-3 py-1
                       text-[11px]
                       font-semibold
                       uppercase
                       tracking-[0.14em]
                       text-[#2dd4bf]"
            >
                Secure Password Reset
            </div>


            <h2
                class="text-2xl font-bold
                       tracking-tight
                       text-[#f1f5f9]"
            >
                Reset Password
            </h2>


            {{-- This page also serves the first-time setup link sent
                 when an administrator approves an access request. --}}
            <p
                class="mt-2
                       text-sm
                       leading-6
                       text-[#94a3b8]"
            >
                Choose a new password for your RETINA account.
                Newly approved users also use this page to set
                their first password.
            </p>

        </div>



        {{-- ========================================================= --}}
        {{-- PASSWORD RESET FORM                                       --}}
        {{-- ========================================================= --}}

        <form
            method="POST"
            action="{{ route('password.store') }}"
        >

            @csrf


            {{-- Reset token --}}
            <input
                type="hidden"
                name="token"
                value="{{ $request->route('token') }}"
            >



            {{-- ===================================================== --}}
            {{-- EMAIL                                                 --}}
            {{-- ===================================================== --}}

            <div>

                <label
                    for="email"
                    class="mb-1.5 block
                           text-xs
                           font-semibold
                           uppercase
                           tracking-[0.14em]
                           text-[#cbd5e1]"
                >
                    Email
                </label>


                <div class="relative">

                    <div
                        class="pointer-events-none
                               absolute inset-y-0 left-0
                               flex items-center
                               pl-3.5
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


                    {{-- Read-only only when the reset link supplied the
                         address; otherwise the user must be able to type it. --}}
                    @php($emailFromLink = filled($request->email))

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        @if ($emailFromLink)
                            readonly
                        @else
                            autofocus
                        @endif
                        autocomplete="username"
                        class="block w-full
                               rounded-lg
                               border border-[#475569]
                               bg-[#0f172a]
                               py-2.5
                               pl-11 pr-4
                               text-sm
                               focus:outline-none
                               focus:ring-1

                               {{ $emailFromLink
                                   ? 'cursor-not-allowed text-[#cbd5e1] opacity-90 focus:border-[#64748b] focus:ring-[#64748b]'
                                   : 'text-[#f1f5f9] placeholder:text-[#64748b] focus:border-[#94a3b8] focus:ring-[#94a3b8]'
                               }}"
                    >

                </div>


                @error('email')

                    <p class="mt-1.5 text-xs text-red-300">
                        {{ $message }}
                    </p>

                @enderror

            </div>



            {{-- ===================================================== --}}
            {{-- PASSWORD                                              --}}
            {{-- ===================================================== --}}

            <div class="mt-5">

                <label
                    for="password"
                    class="mb-1.5 block
                           text-xs
                           font-semibold
                           uppercase
                           tracking-[0.14em]
                           text-[#cbd5e1]"
                >
                    New Password
                </label>


                <div class="relative">

                    <div
                        class="pointer-events-none
                               absolute inset-y-0 left-0
                               flex items-center
                               pl-3.5
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
                        type="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="Enter a new password"
                        class="block w-full
                               rounded-lg
                               border border-[#475569]
                               bg-[#0f172a]
                               py-2.5
                               pl-11 pr-12
                               text-sm
                               text-[#f1f5f9]
                               placeholder:text-[#64748b]
                               focus:border-[#94a3b8]
                               focus:outline-none
                               focus:ring-1
                               focus:ring-[#94a3b8]"
                    >


                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0
                               flex items-center
                               px-3.5
                               text-[#64748b]
                               transition
                               hover:text-[#cbd5e1]
                               focus:outline-none"
                        :aria-label="
                            showPassword
                                ? 'Hide password'
                                : 'Show password'
                        "
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



            {{-- ===================================================== --}}
            {{-- CONFIRM PASSWORD                                      --}}
            {{-- ===================================================== --}}

            <div class="mt-5">

                <label
                    for="password_confirmation"
                    class="mb-1.5 block
                           text-xs
                           font-semibold
                           uppercase
                           tracking-[0.14em]
                           text-[#cbd5e1]"
                >
                    Confirm Password
                </label>


                <div class="relative">

                    <div
                        class="pointer-events-none
                               absolute inset-y-0 left-0
                               flex items-center
                               pl-3.5
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
                            <path d="m5 12 4 4L19 6" />
                        </svg>

                    </div>


                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        :type="
                            showConfirmation
                                ? 'text'
                                : 'password'
                        "
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter your password"
                        class="block w-full
                               rounded-lg
                               border border-[#475569]
                               bg-[#0f172a]
                               py-2.5
                               pl-11 pr-12
                               text-sm
                               text-[#f1f5f9]
                               placeholder:text-[#64748b]
                               focus:border-[#94a3b8]
                               focus:outline-none
                               focus:ring-1
                               focus:ring-[#94a3b8]"
                    >


                    <button
                        type="button"
                        @click="
                            showConfirmation =
                                !showConfirmation
                        "
                        class="absolute inset-y-0 right-0
                               flex items-center
                               px-3.5
                               text-[#64748b]
                               transition
                               hover:text-[#cbd5e1]
                               focus:outline-none"
                        :aria-label="
                            showConfirmation
                                ? 'Hide password'
                                : 'Show password'
                        "
                    >

                        <svg
                            x-show="!showConfirmation"
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
                            x-show="showConfirmation"
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


                @error('password_confirmation')

                    <p class="mt-1.5 text-xs text-red-300">
                        {{ $message }}
                    </p>

                @enderror

            </div>



            {{-- ===================================================== --}}
            {{-- SECURITY NOTE                                         --}}
            {{-- ===================================================== --}}

            <div
                class="mt-5 rounded-lg
                       border border-[#334155]
                       bg-[#0f172a]
                       px-4 py-3"
            >

                <p
                    class="text-xs
                           leading-5
                           text-[#94a3b8]"
                >
                    Use a strong password that you do not reuse
                    for other services. This link can only be used
                    once, while its secure token remains valid.
                </p>

            </div>



            {{-- ===================================================== --}}
            {{-- SUBMIT                                                --}}
            {{-- ===================================================== --}}

            <button
                type="submit"
                class="mt-6
                       inline-flex w-full
                       items-center
                       justify-center
                       rounded-lg
                       bg-[#2dd4bf]
                       px-4 py-3
                       text-sm
                       font-bold
                       tracking-wide
                       text-[#0f172a]
                       shadow-sm
                       transition
                       hover:bg-[#5eead4]
                       focus:outline-none
                       focus:ring-2
                       focus:ring-[#2dd4bf]
                       focus:ring-offset-2
                       focus:ring-offset-[#1e293b]"
            >
                Reset Password
            </button>

        </form>



        {{-- ========================================================= --}}
        {{-- BACK TO LOGIN                                             --}}
        {{-- ========================================================= --}}

        <div
            class="mt-6 border-t border-[#334155]
                   pt-5 text-center"
        >

            <a
                href="{{ route('login') }}"
                class="text-sm text-[#94a3b8]
                       underline-offset-4
                       transition
                       hover:text-[#f1f5f9]
                       hover:underline
                       focus:outline-none
                       focus-visible:text-[#f1f5f9]
                       focus-visible:underline"
            >
                Back to Login
            </a>

        </div>

    </div>

</x-guest-layout>