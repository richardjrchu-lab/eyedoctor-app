<x-access-request-layout
    title="Access Request Received"
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
                <path
                    d="M20 6 9 17l-5-5"
                />
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
            Submission processed
        </div>


        <h2
            class="mt-2
                   text-2xl
                   font-bold
                   tracking-tight
                   text-[#f1f5f9]
                   sm:text-3xl"
        >
            Thank you
        </h2>


        <p
            class="mx-auto
                   mt-3
                   max-w-xl
                   text-sm
                   leading-6
                   text-[#94a3b8]"
        >
            If a new professional-access request was accepted,
            it will continue through RETINA's verification process.
            Further instructions will be sent to the email address
            provided when action is required.
        </p>


        <div
            class="mt-6
                   rounded-xl
                   border
                   border-[#334155]
                   bg-[#0f172a]
                   p-4
                   text-left"
        >

            <p
                class="text-sm
                       font-semibold
                       text-[#e2e8f0]"
            >
                Why is this confirmation intentionally general?
            </p>


            <p
                class="mt-1
                       text-xs
                       leading-5
                       text-[#94a3b8]"
            >
                For account security, RETINA does not disclose on
                this public page whether an email address already
                belongs to an account or an earlier access request.
            </p>

        </div>


        <div
            class="mt-7
                   flex
                   flex-col
                   justify-center
                   gap-3
                   sm:flex-row"
        >

            <a
                href="{{ route(
                    'access-request.resend-verification.create'
                ) }}"
                class="inline-flex
                       items-center
                       justify-center
                       rounded-lg
                       border
                       border-[#334155]
                       bg-[#0f172a]
                       px-5 py-2.5
                       text-sm
                       font-semibold
                       text-[#94a3b8]
                       transition
                       hover:border-[#475569]
                       hover:text-[#f1f5f9]"
            >
                Resend verification email
            </a>


            <a
                href="{{ route('login') }}"
                class="inline-flex
                       items-center
                       justify-center
                       rounded-lg
                       bg-[#334155]
                       px-5 py-2.5
                       text-sm
                       font-semibold
                       text-[#f1f5f9]
                       transition
                       hover:bg-[#475569]
                       focus:outline-none
                       focus:ring-2
                       focus:ring-[#94a3b8]"
            >
                Return to sign in
            </a>

        </div>

    </div>

</x-access-request-layout>
