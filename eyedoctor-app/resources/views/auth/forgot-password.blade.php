<x-guest-layout>

    {{-- ============================================================= --}}
    {{-- HEADER                                                        --}}
    {{-- ============================================================= --}}

    <div class="mb-5">

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
            Account Recovery
        </div>


        <h2
            class="text-xl font-bold tracking-tight
                   text-[#f1f5f9]"
        >
            Forgot Password
        </h2>


        <p
            class="mt-1 text-sm leading-5
                   text-[#94a3b8]"
        >
            Enter the email address for your RETINA account and we
            will send you a secure link to choose a new password.
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
            role="status"
        >
            {{ session('status') }}
        </div>

    @endif



    {{-- ============================================================= --}}
    {{-- RESET LINK REQUEST FORM                                       --}}
    {{-- ============================================================= --}}

    <form
        method="POST"
        action="{{ route('password.email') }}"
    >

        @csrf


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


                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="name@institution.org"
                    class="block w-full
                           rounded-lg
                           border border-[#475569]
                           bg-[#0f172a]
                           py-2.5
                           pl-11 pr-4
                           text-sm
                           text-[#f1f5f9]
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
        {{-- SUBMIT                                                    --}}
        {{-- ========================================================= --}}

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
            Send Password Reset Link
        </button>

    </form>



    {{-- ============================================================= --}}
    {{-- BACK TO LOGIN                                                 --}}
    {{-- ============================================================= --}}

    <div
        class="mt-6 border-t border-[#334155]
               pt-5 text-center"
    >

        <a
            href="{{ route('login') }}"
            class="inline-flex items-center gap-2
                   text-sm text-[#94a3b8]
                   underline-offset-4
                   transition
                   hover:text-[#f1f5f9]
                   hover:underline
                   focus:outline-none
                   focus-visible:text-[#f1f5f9]
                   focus-visible:underline"
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
                <path d="M15 18 9 12l6-6" />
            </svg>

            Back to Login

        </a>

    </div>

</x-guest-layout>
