<section
    class="rounded-2xl
           border border-[#334155]
           bg-[#0f172a]
           p-6
           shadow-xl shadow-black/10
           sm:p-8"
>

    <header>
        <div
            class="text-xs font-semibold
                   uppercase tracking-widest
                   text-[#64748b]"
        >
            Personal Information
        </div>

        <h2
            class="mt-2 text-xl font-bold
                   text-[#f1f5f9]"
        >
            Profile Information
        </h2>

        <p
            class="mt-2 text-sm leading-6
                   text-[#94a3b8]"
        >
            Update the name and email address associated
            with your RETINA account.
        </p>
    </header>


    <form
        id="send-verification"
        method="POST"
        action="{{ route('verification.send') }}"
    >
        @csrf
    </form>


    <form
        method="POST"
        action="{{ route('profile.update') }}"
        class="mt-7 space-y-5"
    >

        @csrf
        @method('PATCH')


        <div>
            <label
                for="name"
                class="block text-sm font-semibold
                       text-[#cbd5e1]"
            >
                Name
            </label>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
                class="mt-2 block w-full
                       rounded-lg
                       border border-[#475569]
                       bg-[#111c30]
                       px-4 py-3
                       text-sm text-[#f1f5f9]
                       outline-none
                       transition
                       placeholder:text-[#64748b]
                       focus:border-[#38bdf8]
                       focus:ring-1
                       focus:ring-[#38bdf8]"
            >

            @error('name')
                <p class="mt-2 text-xs text-red-300">
                    {{ $message }}
                </p>
            @enderror
        </div>


        <div>
            <label
                for="email"
                class="block text-sm font-semibold
                       text-[#cbd5e1]"
            >
                Email Address
            </label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
                class="mt-2 block w-full
                       rounded-lg
                       border border-[#475569]
                       bg-[#111c30]
                       px-4 py-3
                       text-sm text-[#f1f5f9]
                       outline-none
                       transition
                       placeholder:text-[#64748b]
                       focus:border-[#38bdf8]
                       focus:ring-1
                       focus:ring-[#38bdf8]"
            >

            @error('email')
                <p class="mt-2 text-xs text-red-300">
                    {{ $message }}
                </p>
            @enderror


            @if (
                $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail
                && ! $user->hasVerifiedEmail()
            )

                <div
                    class="mt-4 rounded-lg
                           border border-amber-800
                           bg-amber-950/20
                           p-4"
                >

                    <p class="text-xs leading-5 text-amber-200">
                        Your email address has not yet been verified.
                    </p>

                    <button
                        form="send-verification"
                        class="mt-2 text-xs font-semibold
                               text-amber-300
                               underline
                               hover:text-amber-200"
                    >
                        Re-send verification email
                    </button>

                </div>


                @if (session('status') === 'verification-link-sent')

                    <p
                        class="mt-3 text-xs font-semibold
                               text-emerald-300"
                    >
                        A new verification link has been sent.
                    </p>

                @endif

            @endif
        </div>


        <div
            class="flex flex-wrap
                   items-center gap-4
                   border-t border-[#334155]
                   pt-5"
        >

            <button
                type="submit"
                class="inline-flex
                       items-center justify-center
                       rounded-lg
                       border border-[#64748b]
                       bg-[#334155]
                       px-5 py-2.5
                       text-sm font-bold
                       text-[#f1f5f9]
                       transition
                       hover:bg-[#475569]"
            >
                Save Changes
            </button>


            @if (session('status') === 'profile-updated')

                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm font-semibold
                           text-emerald-300"
                >
                    Profile updated.
                </p>

            @endif

        </div>

    </form>

</section>