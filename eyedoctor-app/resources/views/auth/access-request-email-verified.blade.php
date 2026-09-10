<x-access-request-layout
    title="Email Verified"
>

    <div
        class="mx-auto
               max-w-2xl
               py-4
               text-center"
    >

        <div
            class="mx-auto
                   flex
                   h-14 w-14
                   items-center
                   justify-center
                   rounded-full
                   border
                   border-[#2dd4bf]/30
                   bg-[#0f172a]
                   text-[#2dd4bf]"
        >

            <svg
                viewBox="0 0 24 24"
                class="h-7 w-7"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M20 6 9 17l-5-5" />
            </svg>

        </div>


        <div
            class="mt-5
                   text-xs
                   font-semibold
                   uppercase
                   tracking-[0.16em]
                   text-[#2dd4bf]"
        >
            EMAIL VERIFIED
        </div>


        <h2
            class="mt-2
                   text-2xl
                   font-bold
                   tracking-tight
                   text-[#f1f5f9]
                   sm:text-3xl"
        >
            Your request can now be reviewed
        </h2>


        <p
            class="mx-auto
                   mt-3
                   max-w-xl
                   text-sm
                   leading-6
                   text-[#94a3b8]"
        >
            The email address associated with this professional-access
            request has been verified. RETINA will not create an account
            unless an authorized administrator subsequently approves the
            request.
        </p>


        <a
            href="{{ route('login') }}"
            class="mt-7
                   inline-flex
                   items-center
                   justify-center
                   rounded-lg
                   bg-[#334155]
                   px-5 py-2.5
                   text-sm
                   font-semibold
                   text-[#f1f5f9]
                   transition
                   hover:bg-[#475569]"
        >
            Return to sign in
        </a>

    </div>

</x-access-request-layout>
