<x-access-request-layout
    title="Resend Verification Email"
>

    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
    ></script>


    <div
        class="mx-auto
               max-w-xl
               py-4"
    >

        <div
            class="text-xs
                   font-semibold
                   uppercase
                   tracking-[0.16em]
                   text-[#2dd4bf]"
        >
            Professional Access
        </div>


        <h2
            class="mt-2
                   text-2xl
                   font-bold
                   tracking-tight
                   text-[#f1f5f9]"
        >
            Resend verification email
        </h2>


        <p
            class="mt-3
                   text-sm
                   leading-6
                   text-[#94a3b8]"
        >
            Enter the email address used for the professional-access
            request. For security, RETINA always returns a general
            response and does not reveal whether an application exists.
        </p>


        @if (session('status'))

            <div
                class="mt-5
                       rounded-xl
                       border
                       border-[#2dd4bf]/30
                       bg-[#134e4a]/20
                       px-4 py-3"
            >
                <p
                    class="text-sm
                           leading-6
                           text-[#99f6e4]"
                >
                    {{ session('status') }}
                </p>
            </div>

        @endif


        <form
            method="POST"
            action="{{ route(
                'access-request.resend-verification.store'
            ) }}"
            class="mt-6"
        >

            @csrf


            <label
                for="email"
                class="block
                       text-sm
                       font-semibold
                       text-[#e2e8f0]"
            >
                Email address
            </label>


            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                class="mt-2
                       block
                       w-full
                       rounded-lg
                       border
                       border-[#475569]
                       bg-[#0f172a]
                       px-3 py-2.5
                       text-sm
                       text-[#f1f5f9]
                       placeholder-[#64748b]
                       focus:border-[#2dd4bf]
                       focus:outline-none
                       focus:ring-1
                       focus:ring-[#2dd4bf]"
            >


            @error('email')

                <p
                    class="mt-2
                           text-xs
                           text-red-300"
                >
                    {{ $message }}
                </p>

            @enderror


            <div class="mt-5">

                <div
                    class="cf-turnstile"
                    data-sitekey="{{ config('turnstile.site_key') }}"
                    data-action="{{ config(
                        'turnstile.expected_action',
                        'professional_access_request'
                    ) }}"
                    data-theme="dark"
                ></div>

                @error('cf-turnstile-response')

                    <p
                        class="mt-2
                               text-xs
                               text-red-300"
                    >
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <button
                type="submit"
                class="mt-6
                       inline-flex
                       w-full
                       items-center
                       justify-center
                       rounded-lg
                       bg-[#2dd4bf]
                       px-5 py-2.5
                       text-sm
                       font-bold
                       text-[#0f172a]
                       transition
                       hover:bg-[#5eead4]"
            >
                Process resend request
            </button>

        </form>


        <div
            class="mt-6
                   flex
                   flex-col
                   gap-2
                   text-center
                   text-sm"
        >

            <a
                href="{{ route('access-request.create') }}"
                class="font-semibold
                       text-[#94a3b8]
                       hover:text-[#f1f5f9]"
            >
                Back to professional access
            </a>


            <a
                href="{{ route('login') }}"
                class="font-semibold
                       text-[#94a3b8]
                       hover:text-[#f1f5f9]"
            >
                Return to sign in
            </a>

        </div>

    </div>

</x-access-request-layout>
