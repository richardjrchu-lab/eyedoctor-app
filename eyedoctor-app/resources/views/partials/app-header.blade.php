@php
    $user = auth()->user();

    /*
    |--------------------------------------------------------------------------
    | Brand destination
    |--------------------------------------------------------------------------
    | Guest  -> homepage
    | Doctor -> homepage
    | Others -> history if available
    */
    if (!$user) {
        $brandUrl = url('/');
    } elseif (
        method_exists($user, 'hasRole') &&
        $user->hasRole('doctor')
    ) {
        $brandUrl = Route::has('welcome')
            ? route('welcome')
            : url('/');
    } else {
        $brandUrl = Route::has('history')
            ? route('history')
            : (Route::has('dashboard')
                ? route('dashboard')
                : url('/'));
    }

    /*
    |--------------------------------------------------------------------------
    | Role label
    |--------------------------------------------------------------------------
    */
    $roleLabel = '';

    if ($user && method_exists($user, 'hasRole')) {
        if ($user->hasRole('admin')) {
            $roleLabel = 'ADMINISTRATOR';
        } elseif ($user->hasRole('doctor')) {
            $roleLabel = 'DOCTOR';
        } else {
            $roleLabel = 'USER';
        }
    }
@endphp


<header
    class="w-full border-b"
    style="
        background-color: #0f172a;
        border-color: #1e293b;
    "
>
    <div
        class="mx-auto flex min-h-[80px] items-center justify-between px-6"
        style="max-width: 1400px;"
    >

        {{-- ============================================================
            RETINA BRAND
        ============================================================ --}}
        <a
            href="{{ $brandUrl }}"
            class="flex min-w-0 items-center gap-3"
        >

            {{-- RETINA LOGO --}}
            <img
                src="{{ asset('images/retina-logo.png') }}"
                alt="RETINA Logo"
                class="h-14 w-auto shrink-0 object-contain"
            >

            {{-- RETINA NAME --}}
            <div class="min-w-0">

                <div
                    class="text-xl font-bold tracking-[0.18em]"
                    style="color: #f8fafc;"
                >
                    RETINA
                </div>

                <div
                    class="mt-1 text-xs sm:text-sm"
                    style="color: #60a5fa;"
                >
                    Diabetic Retinopathy Detection System
                </div>

            </div>
        </a>


        {{-- ============================================================
            RIGHT SIDE
        ============================================================ --}}
        <div class="flex items-center gap-4">

            {{-- ========================================================
                LOGGED-IN USER
            ======================================================== --}}
            @auth

                <div class="hidden text-right sm:block">

                    {{-- USER NAME --}}
                    <div
                        class="text-sm font-semibold"
                        style="color: #f1f5f9;"
                    >
                        {{ auth()->user()->name }}
                    </div>

                    {{-- ROLE --}}
                    <div
                        class="mt-1 text-[10px] uppercase tracking-[0.14em]"
                        style="color: #64748b;"
                    >
                        {{ $roleLabel }}
                    </div>

                </div>


                {{-- LOGOUT BUTTON --}}
                @if(Route::has('logout'))

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="
                                rounded-lg
                                border
                                px-4
                                py-2
                                text-sm
                                font-semibold
                                transition
                                duration-150
                            "
                            style="
                                color: #e2e8f0;
                                border-color: #475569;
                                background-color: #172033;
                            "
                        >
                            Log out
                        </button>

                    </form>

                @endif


            {{-- ========================================================
                GUEST
            ======================================================== --}}
            @else

                @if(Route::has('login'))

                    <a
                        href="{{ route('login') }}"
                        class="
                            rounded-lg
                            border
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            transition
                            duration-150
                        "
                        style="
                            color: #e2e8f0;
                            border-color: #475569;
                            background-color: #172033;
                        "
                    >
                        Log in
                    </a>

                @endif

            @endauth

        </div>

    </div>
</header>