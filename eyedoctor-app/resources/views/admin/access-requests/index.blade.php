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

    <title>Access Requests | RETINA</title>

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


    <main
        class="mx-auto
               w-full
               max-w-6xl
               px-5 py-8
               sm:px-6"
    >

        <div
            class="flex flex-col
                   gap-3
                   sm:flex-row
                   sm:items-end
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
                    Administrator
                </div>

                <h1
                    class="mt-1
                           text-2xl
                           font-bold
                           tracking-tight"
                >
                    Professional Access Requests
                </h1>

                <p
                    class="mt-1
                           text-sm
                           text-[#94a3b8]"
                >
                    Review verified professional and institutional
                    access applications.
                </p>

            </div>


            <div
                class="rounded-lg
                       border
                       border-[#334155]
                       bg-[#0f172a]
                       px-4 py-2
                       text-sm
                       text-[#94a3b8]"
            >
                Total requests:
                <span
                    class="font-bold
                           text-[#f1f5f9]"
                >
                    {{ $counts['all'] }}
                </span>
            </div>

        </div>


        {{-- FILTERS --}}
        <div
            class="mt-6
                   flex
                   flex-wrap
                   gap-2"
        >

            <a
                href="{{ route('admin.access-requests.index') }}"
                class="rounded-lg
                       border
                       px-3 py-2
                       text-xs
                       font-semibold
                       transition
                       {{ $selectedStatus === ''
                           ? 'border-[#2dd4bf] bg-[#134e4a]/40 text-[#5eead4]'
                           : 'border-[#334155] bg-[#0f172a] text-[#94a3b8] hover:border-[#64748b]'
                       }}"
            >
                All ({{ $counts['all'] }})
            </a>


            @foreach ($statusLabels as $status => $label)

                <a
                    href="{{ route(
                        'admin.access-requests.index',
                        ['status' => $status]
                    ) }}"
                    class="rounded-lg
                           border
                           px-3 py-2
                           text-xs
                           font-semibold
                           transition
                           {{ $selectedStatus === $status
                               ? 'border-[#2dd4bf] bg-[#134e4a]/40 text-[#5eead4]'
                               : 'border-[#334155] bg-[#0f172a] text-[#94a3b8] hover:border-[#64748b]'
                           }}"
                >
                    {{ $label }}
                    ({{ $counts[$status] ?? 0 }})
                </a>

            @endforeach

        </div>


        {{-- REQUEST LIST --}}
        <div
            class="mt-6
                   overflow-hidden
                   rounded-xl
                   border
                   border-[#334155]
                   bg-[#0f172a]"
        >

            @forelse ($accessRequests as $accessRequest)

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
                @endphp


                <a
                    href="{{ route(
                        'admin.access-requests.show',
                        $accessRequest
                    ) }}"
                    class="block
                           border-b
                           border-[#334155]
                           p-5
                           transition
                           last:border-b-0
                           hover:bg-[#172033]"
                >

                    <div
                        class="flex
                               flex-col
                               gap-4
                               md:flex-row
                               md:items-center
                               md:justify-between"
                    >

                        <div class="min-w-0">

                            <div
                                class="flex
                                       flex-wrap
                                       items-center
                                       gap-2"
                            >

                                <span
                                    class="font-semibold
                                           text-[#f1f5f9]"
                                >
                                    {{ $accessRequest->full_name }}
                                </span>


                                <span
                                    class="rounded-full
                                           border
                                           px-2.5 py-1
                                           text-[10px]
                                           font-bold
                                           uppercase
                                           tracking-wider
                                           {{ $statusClass }}"
                                >
                                    {{ $statusLabels[$accessRequest->status] ?? $accessRequest->status }}
                                </span>

                            </div>


                            <div
                                class="mt-1
                                       text-sm
                                       text-[#94a3b8]"
                            >
                                {{ $accessRequest->email }}
                            </div>


                            <div
                                class="mt-2
                                       flex
                                       flex-wrap
                                       gap-x-5
                                       gap-y-1
                                       text-xs
                                       text-[#64748b]"
                            >

                                <span>
                                    {{ $professionLabel }}
                                </span>

                                <span>
                                    {{ $accessRequest->institution }}
                                </span>

                                @if ($accessRequest->license_registration_last4)

                                    <span>
                                        Registration:
                                        &bull;&bull;&bull;&bull;{{ $accessRequest->license_registration_last4 }}
                                    </span>

                                @endif

                            </div>

                        </div>


                        <div
                            class="shrink-0
                                   md:text-right"
                        >

                            <div
                                class="text-xs
                                       text-[#64748b]"
                            >
                                Submitted
                            </div>

                            <div
                                class="mt-0.5
                                       text-sm
                                       font-medium
                                       text-[#cbd5e1]"
                            >
                                {{ $accessRequest->created_at->format('M d, Y g:i A') }}
                            </div>


                            @if ($accessRequest->email_verified_at)

                                <div
                                    class="mt-2
                                           text-xs
                                           text-[#2dd4bf]"
                                >
                                    Email verified
                                </div>

                            @endif


                            <div
                                class="mt-2
                                       text-xs
                                       font-semibold
                                       text-[#94a3b8]"
                            >
                                Review details &rarr;
                            </div>

                        </div>

                    </div>

                </a>

            @empty

                <div
                    class="px-6 py-16
                           text-center"
                >

                    <div
                        class="text-base
                               font-semibold
                               text-[#cbd5e1]"
                    >
                        No access requests found
                    </div>

                    <p
                        class="mt-1
                               text-sm
                               text-[#64748b]"
                    >
                        Applications matching this filter will appear here.
                    </p>

                </div>

            @endforelse

        </div>


        @if ($accessRequests->hasPages())

            <div class="mt-6">
                {{ $accessRequests->links() }}
            </div>

        @endif

    </main>

</body>

</html>
