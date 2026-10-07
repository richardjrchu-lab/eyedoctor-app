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
            Authentication
        </div>

        <h2
            class="mt-2 text-xl font-bold
                   text-[#f1f5f9]"
        >
            Update Password
        </h2>

        <p
            class="mt-2 text-sm leading-6
                   text-[#94a3b8]"
        >
            Change your password while keeping your
            current authenticated session protected.
        </p>
    </header>


    <form
        method="POST"
        action="{{ route('password.update') }}"
        class="mt-7 space-y-5"
    >

        @csrf
        @method('PUT')


        <div>
            <label
                for="update_password_current_password"
                class="block text-sm font-semibold
                       text-[#cbd5e1]"
            >
                Current Password
            </label>

            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                class="mt-2 block w-full
                       rounded-lg
                       border border-[#475569]
                       bg-[#111c30]
                       px-4 py-3
                       text-sm text-[#f1f5f9]
                       outline-none
                       transition
                       focus:border-[#38bdf8]
                       focus:ring-1
                       focus:ring-[#38bdf8]"
            >

            @if ($errors->updatePassword->has('current_password'))
                <p class="mt-2 text-xs text-red-300">
                    {{ $errors->updatePassword->first('current_password') }}
                </p>
            @endif
        </div>


        <div>
            <label
                for="update_password_password"
                class="block text-sm font-semibold
                       text-[#cbd5e1]"
            >
                New Password
            </label>

            <input
                id="update_password_password"
                name="password"
                type="password"
                autocomplete="new-password"
                class="mt-2 block w-full
                       rounded-lg
                       border border-[#475569]
                       bg-[#111c30]
                       px-4 py-3
                       text-sm text-[#f1f5f9]
                       outline-none
                       transition
                       focus:border-[#38bdf8]
                       focus:ring-1
                       focus:ring-[#38bdf8]"
            >

            @if ($errors->updatePassword->has('password'))
                <p class="mt-2 text-xs text-red-300">
                    {{ $errors->updatePassword->first('password') }}
                </p>
            @endif
        </div>


        <div>
            <label
                for="update_password_password_confirmation"
                class="block text-sm font-semibold
                       text-[#cbd5e1]"
            >
                Confirm New Password
            </label>

            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                class="mt-2 block w-full
                       rounded-lg
                       border border-[#475569]
                       bg-[#111c30]
                       px-4 py-3
                       text-sm text-[#f1f5f9]
                       outline-none
                       transition
                       focus:border-[#38bdf8]
                       focus:ring-1
                       focus:ring-[#38bdf8]"
            >

            @if ($errors->updatePassword->has('password_confirmation'))
                <p class="mt-2 text-xs text-red-300">
                    {{ $errors->updatePassword->first('password_confirmation') }}
                </p>
            @endif
        </div>


        <div
            class="border-t border-[#334155]
                   pt-5"
        >

            <div
                class="flex flex-wrap
                       items-center gap-4"
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
                    Update Password
                </button>


                @if (session('status') === 'password-updated')

                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2500)"
                        class="text-sm font-semibold
                               text-emerald-300"
                    >
                        Password updated.
                    </p>

                @endif

            </div>


            <p
                class="mt-4 text-xs leading-5
                       text-[#64748b]"
            >
                Forgot your current password? Sign out and use
                “Forgot password?” on the RETINA login page to
                request a secure reset link.
            </p>

        </div>

    </form>

</section>