<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="robots"
        content="noindex, nofollow"
    >

    <title>Access Request Review | RETINA</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>


<body
    class="min-h-screen
           bg-[#1e293b]
           font-sans
           text-[#f1f5f9]
           antialiased"
>

    @include('partials.app-header')


    @php
        $statusClass = match ($accessRequest->status) {
            'pending_review' =>
                'border-amber-700/60 bg-amber-950/30 text-amber-300',

            'approved' =>
                'border-emerald-700/60 bg-emerald-950/30 text-emerald-300',

            'rejected' =>
                'border-rose-700/60 bg-rose-950/30 text-rose-300',

            default =>
                'border-slate-600 bg-slate-800 text-slate-300',
        };

        $professionLabel =
            $professionOptions[$accessRequest->profession]
            ?? $accessRequest->profession;

        $proofLabel =
            $proofTypeOptions[$accessRequest->proof_type]
            ?? $accessRequest->proof_type;
    @endphp


    <main
        class="mx-auto
               w-full
               max-w-5xl
               px-5 py-8
               sm:px-6"
    >

        <a
            href="{{ route('admin.access-requests.index') }}"
            class="text-sm
                   font-semibold
                   text-[#94a3b8]
                   hover:text-[#f1f5f9]"
        >
            &larr; Back to access requests
        </a>


        <div
            class="mt-5
                   flex
                   flex-col
                   gap-3
                   sm:flex-row
                   sm:items-start
                   sm:justify-between"
        >

            <div>

                <div
                    class="text-xs
                           font-semibold
                           uppercase
                           tracking-[0.15em]
                           text-[#2dd4bf]"
                >
                    Administrator Review
                </div>

                <h1
                    class="mt-1
                           text-2xl
                           font-bold"
                >
                    {{ $accessRequest->full_name }}
                </h1>

                <p
                    class="mt-1
                           text-sm
                           text-[#94a3b8]"
                >
                    {{ $accessRequest->email }}
                </p>

            </div>


            <span
                class="inline-flex
                       self-start
                       rounded-full
                       border
                       px-3 py-1.5
                       text-xs
                       font-bold
                       uppercase
                       tracking-wider
                       {{ $statusClass }}"
            >
                {{ $statusLabels[$accessRequest->status] ?? $accessRequest->status }}
            </span>

        </div>


        @if (session('status'))

            <div
                class="mt-6
                       rounded-xl
                       border
                       border-emerald-800/60
                       bg-emerald-950/25
                       px-4 py-3"
            >
                <p
                    class="text-sm
                           text-emerald-200"
                >
                    {{ session('status') }}
                </p>
            </div>

        @endif


        @if (session('warning'))

            <div
                class="mt-6
                       rounded-xl
                       border
                       border-amber-800/60
                       bg-amber-950/25
                       px-4 py-3"
            >
                <p
                    class="text-sm
                           text-amber-200"
                >
                    {{ session('warning') }}
                </p>
            </div>

        @endif


        @error('decision')

            <div
                class="mt-6
                       rounded-xl
                       border
                       border-red-800/60
                       bg-red-950/25
                       px-4 py-3"
            >
                <p
                    class="text-sm
                           text-red-200"
                >
                    {{ $message }}
                </p>
            </div>

        @enderror


        @if (
            $accessRequest->isPendingReview()
            && $accessRequest->email_verified_at
        )

            <section
                class="mt-6
                       rounded-xl
                       border
                       border-[#334155]
                       bg-[#0f172a]
                       p-5"
            >

                <h2
                    class="text-lg
                           font-bold"
                >
                    Administrative decision
                </h2>


                <p
                    class="mt-1
                           text-sm
                           leading-6
                           text-[#94a3b8]"
                >
                    Approval creates a doctor account and sends a secure
                    first-time password setup link. Rejection creates no
                    user account.
                </p>


                <div
                    class="mt-5
                           grid
                           gap-5
                           lg:grid-cols-2"
                >

                    <div
                        class="rounded-xl
                               border
                               border-emerald-800/50
                               bg-emerald-950/15
                               p-4"
                    >

                        <h3
                            class="font-semibold
                                   text-emerald-300"
                        >
                            Approve request
                        </h3>


                        <p
                            class="mt-1
                                   text-xs
                                   leading-5
                                   text-[#94a3b8]"
                        >
                            A doctor account will be created using the
                            applicant's verified email address.
                        </p>


                        <form
                            method="POST"
                            action="{{ route(
                                'admin.access-requests.approve',
                                $accessRequest
                            ) }}"
                            class="mt-4"
                            onsubmit="return confirm('Approve this professional access request and create a doctor account?');"
                        >

                            @csrf


                            <button
                                type="submit"
                                class="rounded-lg
                                       bg-emerald-400
                                       px-4 py-2.5
                                       text-sm
                                       font-bold
                                       text-emerald-950
                                       transition
                                       hover:bg-emerald-300"
                            >
                                Approve and create doctor account
                            </button>

                        </form>

                    </div>


                    <div
                        class="rounded-xl
                               border
                               border-rose-800/50
                               bg-rose-950/15
                               p-4"
                    >

                        <h3
                            class="font-semibold
                                   text-rose-300"
                        >
                            Reject request
                        </h3>


                        <p
                            class="mt-1
                                   text-xs
                                   leading-5
                                   text-[#94a3b8]"
                        >
                            Record an internal reason for the administrative
                            decision. No user account will be created.
                        </p>


                        <form
                            method="POST"
                            action="{{ route(
                                'admin.access-requests.reject',
                                $accessRequest
                            ) }}"
                            class="mt-4"
                            onsubmit="return confirm('Reject this professional access request?');"
                        >

                            @csrf


                            <label
                                for="rejection_reason"
                                class="block
                                       text-xs
                                       font-semibold
                                       uppercase
                                       tracking-wider
                                       text-[#cbd5e1]"
                            >
                                Internal rejection reason
                            </label>


                            <textarea
                                id="rejection_reason"
                                name="rejection_reason"
                                rows="4"
                                maxlength="2000"
                                required
                                class="mt-2
                                       block
                                       w-full
                                       rounded-lg
                                       border
                                       border-[#475569]
                                       bg-[#111827]
                                       px-3 py-2.5
                                       text-sm
                                       text-[#f1f5f9]
                                       focus:border-rose-400
                                       focus:outline-none
                                       focus:ring-1
                                       focus:ring-rose-400"
                            >{{ old('rejection_reason') }}</textarea>


                            @error('rejection_reason')
                                <p
                                    class="mt-2
                                           text-xs
                                           text-red-300"
                                >
                                    {{ $message }}
                                </p>
                            @enderror


                            <button
                                type="submit"
                                class="mt-3
                                       rounded-lg
                                       border
                                       border-rose-700
                                       bg-rose-950/40
                                       px-4 py-2.5
                                       text-sm
                                       font-bold
                                       text-rose-300
                                       transition
                                       hover:bg-rose-900/40"
                            >
                                Reject request
                            </button>

                        </form>

                    </div>

                </div>

            </section>

        @elseif ($accessRequest->isApproved())

            <div
                class="mt-6
                       rounded-xl
                       border
                       border-emerald-800/50
                       bg-emerald-950/20
                       px-4 py-3
                       text-sm
                       text-emerald-200"
            >
                This request has been approved.

                @if ($accessRequest->approvedUser)
                    Doctor account:
                    {{ $accessRequest->approvedUser->email }}.
                @endif

                @if ($accessRequest->account_setup_sent_at)
                    Password setup invitation sent
                    {{ $accessRequest->account_setup_sent_at->format('M d, Y g:i A') }}.
                @else
                    Password setup invitation has not been recorded as sent.
                @endif
            </div>

        @elseif ($accessRequest->isRejected())

            <div
                class="mt-6
                       rounded-xl
                       border
                       border-rose-800/50
                       bg-rose-950/20
                       px-4 py-3"
            >

                <p
                    class="text-sm
                           font-semibold
                           text-rose-200"
                >
                    This request was rejected.
                </p>


                @if ($accessRequest->rejection_reason)

                    <p
                        class="mt-2
                               whitespace-pre-wrap
                               text-sm
                               leading-6
                               text-[#cbd5e1]"
                    >{{ $accessRequest->rejection_reason }}</p>

                @endif

            </div>

        @endif


        {{-- APPLICANT --}}
        <section
            class="mt-6
                   rounded-xl
                   border
                   border-[#334155]
                   bg-[#0f172a]
                   p-5"
        >

            <h2
                class="text-lg
                       font-bold"
            >
                Applicant and affiliation
            </h2>


            <dl
                class="mt-5
                       grid
                       gap-5
                       sm:grid-cols-2"
            >

                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Full name
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->full_name }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Email
                    </dt>

                    <dd class="mt-1 break-all text-sm text-[#e2e8f0]">
                        {{ $accessRequest->email }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Profession / category
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $professionLabel }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Institution
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->institution }}
                    </dd>
                </div>


                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Department / position
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->department_position ?: 'Not provided' }}
                    </dd>
                </div>

            </dl>

        </section>


        {{-- PROFESSIONAL VERIFICATION --}}
        <section
            class="mt-5
                   rounded-xl
                   border
                   border-[#334155]
                   bg-[#0f172a]
                   p-5"
        >

            <h2
                class="text-lg
                       font-bold"
            >
                Professional verification
            </h2>


            <dl
                class="mt-5
                       grid
                       gap-5
                       sm:grid-cols-2"
            >

                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Verification document
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $proofLabel }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        File type
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->proof_mime_type }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        File size
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ number_format(
                            $accessRequest->proof_size_bytes / 1048576,
                            2
                        ) }} MB
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Uploaded
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->proof_uploaded_at?->format('M d, Y g:i A') }}
                    </dd>
                </div>


                @if ($accessRequest->license_registration_last4)

                    <div class="sm:col-span-2">

                        <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                            Professional registration number
                        </dt>


                        @if ($licenseUnavailable)

                            <dd class="mt-1 text-sm text-red-300">
                                Registration number is temporarily unavailable.
                            </dd>

                        @elseif ($licenseNumber)

                            <dd
                                class="mt-1
                                       break-all
                                       font-mono
                                       text-sm
                                       text-[#e2e8f0]"
                            >
                                {{ $licenseNumber }}
                            </dd>

                        @else

                            <dd class="mt-1 text-sm text-[#94a3b8]">
                                &bull;&bull;&bull;&bull;{{ $accessRequest->license_registration_last4 }}
                            </dd>

                        @endif

                    </div>

                @endif

            </dl>


            @if ($accessRequest->proof_deleted_at)

                <div
                    class="mt-5
                           rounded-lg
                           border
                           border-rose-800/50
                           bg-rose-950/20
                           px-4 py-3
                           text-sm
                           text-rose-300"
                >
                    This verification document has been deleted under
                    the retention policy.
                </div>

            @else

                <a
                    href="{{ route(
                        'admin.access-requests.proof',
                        $accessRequest
                    ) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-5
                           inline-flex
                           items-center
                           rounded-lg
                           bg-[#2dd4bf]
                           px-4 py-2.5
                           text-sm
                           font-bold
                           text-[#0f172a]
                           transition
                           hover:bg-[#5eead4]"
                >
                    View verification document
                </a>

                <p
                    class="mt-2
                           text-xs
                           text-[#64748b]"
                >
                    The private document link expires after 5 minutes.
                </p>

            @endif

        </section>


        {{-- EMAIL / REVIEW STATUS --}}
        <section
            class="mt-5
                   rounded-xl
                   border
                   border-[#334155]
                   bg-[#0f172a]
                   p-5"
        >

            <h2 class="text-lg font-bold">
                Verification status
            </h2>


            <dl
                class="mt-5
                       grid
                       gap-5
                       sm:grid-cols-2"
            >

                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Email verification sent
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->email_verification_sent_at
                            ? $accessRequest->email_verification_sent_at->format('M d, Y g:i A')
                            : 'Not recorded'
                        }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Email verified
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->email_verified_at
                            ? $accessRequest->email_verified_at->format('M d, Y g:i A')
                            : 'Not yet verified'
                        }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Reviewed by
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->reviewer?->name ?? 'Not reviewed' }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs uppercase tracking-wider text-[#64748b]">
                        Reviewed at
                    </dt>

                    <dd class="mt-1 text-sm text-[#e2e8f0]">
                        {{ $accessRequest->reviewed_at
                            ? $accessRequest->reviewed_at->format('M d, Y g:i A')
                            : 'Not reviewed'
                        }}
                    </dd>
                </div>

            </dl>

        </section>


        {{-- AUDIT TRAIL --}}
        <section
            class="mt-5
                   rounded-xl
                   border
                   border-[#334155]
                   bg-[#0f172a]
                   p-5"
        >

            <h2 class="text-lg font-bold">
                Application history
            </h2>


            <div
                class="mt-5
                       space-y-3"
            >

                @forelse ($accessRequest->events as $event)

                    <div
                        class="rounded-lg
                               border
                               border-[#334155]
                               bg-[#111827]
                               px-4 py-3"
                    >

                        <div
                            class="flex
                                   flex-col
                                   gap-1
                                   sm:flex-row
                                   sm:items-center
                                   sm:justify-between"
                        >

                            <div
                                class="text-sm
                                       font-semibold
                                       text-[#e2e8f0]"
                            >
                                {{ ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $event->event_type
                                    )
                                ) }}
                            </div>


                            <div
                                class="text-xs
                                       text-[#64748b]"
                            >
                                {{ $event->created_at->format('M d, Y g:i A') }}
                            </div>

                        </div>


                        <div
                            class="mt-1
                                   text-xs
                                   text-[#94a3b8]"
                        >
                            Actor:
                            {{ ucfirst($event->actor_type) }}

                            @if ($event->actorUser)
                                - {{ $event->actorUser->name }}
                            @endif
                        </div>


                        @if ($event->from_status || $event->to_status)

                            <div
                                class="mt-1
                                       text-xs
                                       text-[#64748b]"
                            >
                                {{ $event->from_status ?: 'New request' }}
                                &rarr;
                                {{ $event->to_status ?: 'No state' }}
                            </div>

                        @endif

                    </div>

                @empty

                    <p
                        class="text-sm
                               text-[#64748b]"
                    >
                        No lifecycle events recorded.
                    </p>

                @endforelse

            </div>

        </section>

    </main>

</body>

</html>
