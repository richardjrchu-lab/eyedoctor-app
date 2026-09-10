<x-access-request-layout
    title="Request Professional Access"
>

    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
    ></script>

    <div
        x-data="{
            proofType: @js(old('proof_type', ''))
        }"
    >

        {{-- ========================================================= --}}
        {{-- HEADER                                                    --}}
        {{-- ========================================================= --}}

        <div
            class="border-b
                   border-[#334155]
                   pb-6"
        >

            <div
                class="inline-flex
                       items-center
                       rounded-full
                       border
                       border-[#334155]
                       bg-[#0f172a]
                       px-3 py-1
                       text-xs
                       font-semibold
                       tracking-wide
                       text-[#94a3b8]"
            >
                PROFESSIONAL VERIFICATION
            </div>


            <h2
                class="mt-4
                       text-2xl
                       font-bold
                       tracking-tight
                       text-[#f1f5f9]
                       sm:text-3xl"
            >
                Request professional access
            </h2>


            <p
                class="mt-2
                       max-w-3xl
                       text-sm
                       leading-6
                       text-[#94a3b8]"
            >
                RETINA uses reviewed access rather than open registration.
                Submit the minimum information needed to verify your
                professional, research, or institutional affiliation.
            </p>

        </div>


        {{-- ========================================================= --}}
        {{-- PRIVACY / SAFETY NOTICE                                   --}}
        {{-- ========================================================= --}}

        <div
            class="mt-6
                   rounded-xl
                   border
                   border-[#334155]
                   bg-[#0f172a]
                   p-4"
        >

            <div class="flex gap-3">

                <svg
                    viewBox="0 0 24 24"
                    class="mt-0.5
                           h-5 w-5
                           shrink-0
                           text-[#2dd4bf]"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path
                        d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"
                    />

                    <path
                        d="m9 12 2 2 4-4"
                    />
                </svg>


                <div>

                    <p
                        class="text-sm
                               font-semibold
                               text-[#e2e8f0]"
                    >
                        Submit only what is necessary for verification
                    </p>


                    <p
                        class="mt-1
                               text-xs
                               leading-5
                               text-[#94a3b8]"
                    >
                        Do not upload patient records, retinal images,
                        medical histories, unrelated government IDs,
                        home addresses, or other unnecessary personal
                        information.
                    </p>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- VALIDATION SUMMARY                                        --}}
        {{-- ========================================================= --}}

        @if ($errors->any())

            <div
                class="mt-6
                       rounded-xl
                       border
                       border-red-400/30
                       bg-red-950/20
                       px-4 py-3"
                role="alert"
            >

                <p
                    class="text-sm
                           font-semibold
                           text-red-200"
                >
                    Please review the highlighted fields.
                </p>


                <p
                    class="mt-1
                           text-xs
                           leading-5
                           text-red-300/80"
                >
                    Your verification document and professional
                    registration number may need to be selected or
                    entered again.
                </p>

            </div>

        @endif


        {{-- ========================================================= --}}
        {{-- APPLICATION FORM                                         --}}
        {{-- ========================================================= --}}

        <form
            method="POST"
            action="{{ route('access-request.store') }}"
            enctype="multipart/form-data"
            class="mt-7"
        >

            @csrf


            {{-- ===================================================== --}}
            {{-- SECTION 1 ? IDENTITY                                  --}}
            {{-- ===================================================== --}}

            <section>

                <div
                    class="flex
                           items-start
                           gap-3"
                >

                    <div
                        class="flex
                               h-8 w-8
                               shrink-0
                               items-center
                               justify-center
                               rounded-full
                               bg-[#334155]
                               text-sm
                               font-bold
                               text-[#f1f5f9]"
                    >
                        1
                    </div>


                    <div>

                        <h3
                            class="text-lg
                                   font-bold
                                   text-[#f1f5f9]"
                        >
                            Identity and affiliation
                        </h3>

                        <p
                            class="mt-0.5
                                   text-xs
                                   leading-5
                                   text-[#94a3b8]"
                        >
                            Tell the review team who you are and
                            where you are professionally affiliated.
                        </p>

                    </div>

                </div>


                <div
                    class="mt-5
                           grid
                           gap-5
                           md:grid-cols-2"
                >

                    {{-- Full name --}}
                    <div>

                        <label
                            for="full_name"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Full name
                            <span class="text-[#2dd4bf]">*</span>
                        </label>


                        <input
                            id="full_name"
                            name="full_name"
                            type="text"
                            value="{{ old('full_name') }}"
                            required
                            maxlength="150"
                            autocomplete="name"
                            class="block
                                   w-full
                                   rounded-lg
                                   border
                                   border-[#475569]
                                   bg-[#0f172a]
                                   px-3.5 py-2.5
                                   text-sm
                                   text-[#f1f5f9]
                                   placeholder:text-[#64748b]
                                   focus:border-[#2dd4bf]
                                   focus:outline-none
                                   focus:ring-1
                                   focus:ring-[#2dd4bf]"
                            placeholder="Your full professional name"
                        >

                        @error('full_name')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Email --}}
                    <div>

                        <label
                            for="email"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Email address
                            <span class="text-[#2dd4bf]">*</span>
                        </label>


                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            maxlength="254"
                            autocomplete="email"
                            class="block
                                   w-full
                                   rounded-lg
                                   border
                                   border-[#475569]
                                   bg-[#0f172a]
                                   px-3.5 py-2.5
                                   text-sm
                                   text-[#f1f5f9]
                                   placeholder:text-[#64748b]
                                   focus:border-[#2dd4bf]
                                   focus:outline-none
                                   focus:ring-1
                                   focus:ring-[#2dd4bf]"
                            placeholder="name@institution.org"
                        >

                        @error('email')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Profession --}}
                    <div>

                        <label
                            for="profession"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Profession / training category
                            <span class="text-[#2dd4bf]">*</span>
                        </label>


                        <select
                            id="profession"
                            name="profession"
                            required
                            class="block
                                   w-full
                                   rounded-lg
                                   border
                                   border-[#475569]
                                   bg-[#0f172a]
                                   px-3.5 py-2.5
                                   text-sm
                                   text-[#f1f5f9]
                                   focus:border-[#2dd4bf]
                                   focus:outline-none
                                   focus:ring-1
                                   focus:ring-[#2dd4bf]"
                        >

                            <option
                                value=""
                                @selected(old('profession') === null)
                            >
                                Select your category
                            </option>

                            @foreach ($professionOptions as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(old('profession') === $value)
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                        @error('profession')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Institution --}}
                    <div>

                        <label
                            for="institution"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Institution / organization
                            <span class="text-[#2dd4bf]">*</span>
                        </label>


                        <input
                            id="institution"
                            name="institution"
                            type="text"
                            value="{{ old('institution') }}"
                            required
                            maxlength="200"
                            autocomplete="organization"
                            class="block
                                   w-full
                                   rounded-lg
                                   border
                                   border-[#475569]
                                   bg-[#0f172a]
                                   px-3.5 py-2.5
                                   text-sm
                                   text-[#f1f5f9]
                                   placeholder:text-[#64748b]
                                   focus:border-[#2dd4bf]
                                   focus:outline-none
                                   focus:ring-1
                                   focus:ring-[#2dd4bf]"
                            placeholder="Hospital, clinic, school, or organization"
                        >

                        @error('institution')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Position --}}
                    <div class="md:col-span-2">

                        <label
                            for="department_position"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Department / position
                            <span
                                class="font-normal
                                       normal-case
                                       tracking-normal
                                       text-[#64748b]"
                            >
                                optional
                            </span>
                        </label>


                        <input
                            id="department_position"
                            name="department_position"
                            type="text"
                            value="{{ old('department_position') }}"
                            maxlength="150"
                            autocomplete="organization-title"
                            class="block
                                   w-full
                                   rounded-lg
                                   border
                                   border-[#475569]
                                   bg-[#0f172a]
                                   px-3.5 py-2.5
                                   text-sm
                                   text-[#f1f5f9]
                                   placeholder:text-[#64748b]
                                   focus:border-[#2dd4bf]
                                   focus:outline-none
                                   focus:ring-1
                                   focus:ring-[#2dd4bf]"
                            placeholder="e.g. Ophthalmology Department, Research Assistant"
                        >

                        @error('department_position')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>

            </section>


            {{-- ===================================================== --}}
            {{-- SECTION DIVIDER                                       --}}
            {{-- ===================================================== --}}

            <div
                class="my-8
                       border-t
                       border-[#334155]"
            ></div>


            {{-- ===================================================== --}}
            {{-- SECTION 2 ? VERIFICATION                              --}}
            {{-- ===================================================== --}}

            <section>

                <div
                    class="flex
                           items-start
                           gap-3"
                >

                    <div
                        class="flex
                               h-8 w-8
                               shrink-0
                               items-center
                               justify-center
                               rounded-full
                               bg-[#334155]
                               text-sm
                               font-bold
                               text-[#f1f5f9]"
                    >
                        2
                    </div>


                    <div>

                        <h3
                            class="text-lg
                                   font-bold
                                   text-[#f1f5f9]"
                        >
                            Professional verification
                        </h3>

                        <p
                            class="mt-0.5
                                   text-xs
                                   leading-5
                                   text-[#94a3b8]"
                        >
                            Provide one document that can reasonably
                            verify your stated affiliation.
                        </p>

                    </div>

                </div>


                <div
                    class="mt-5
                           grid
                           gap-5
                           md:grid-cols-2"
                >

                    {{-- Proof type --}}
                    <div>

                        <label
                            for="proof_type"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Verification document type
                            <span class="text-[#2dd4bf]">*</span>
                        </label>


                        <select
                            id="proof_type"
                            name="proof_type"
                            x-model="proofType"
                            required
                            class="block
                                   w-full
                                   rounded-lg
                                   border
                                   border-[#475569]
                                   bg-[#0f172a]
                                   px-3.5 py-2.5
                                   text-sm
                                   text-[#f1f5f9]
                                   focus:border-[#2dd4bf]
                                   focus:outline-none
                                   focus:ring-1
                                   focus:ring-[#2dd4bf]"
                        >

                            <option value="">
                                Select document type
                            </option>

                            @foreach ($proofTypeOptions as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(old('proof_type') === $value)
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                        @error('proof_type')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Registration number --}}
                    <div
                        x-show="proofType === 'professional_license'"
                        x-cloak
                    >

                        <label
                            for="license_registration_number"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Professional registration number
                            <span class="text-[#2dd4bf]">*</span>
                        </label>


                        <input
                            id="license_registration_number"
                            name="license_registration_number"
                            type="text"
                            value=""
                            maxlength="120"
                            autocomplete="off"
                            :required="proofType === 'professional_license'"
                            class="block
                                   w-full
                                   rounded-lg
                                   border
                                   border-[#475569]
                                   bg-[#0f172a]
                                   px-3.5 py-2.5
                                   text-sm
                                   text-[#f1f5f9]
                                   placeholder:text-[#64748b]
                                   focus:border-[#2dd4bf]
                                   focus:outline-none
                                   focus:ring-1
                                   focus:ring-[#2dd4bf]"
                            placeholder="Enter the number shown on the submitted license"
                        >


                        <p
                            class="mt-1.5
                                   text-[11px]
                                   leading-5
                                   text-[#64748b]"
                        >
                            This value is encrypted by RETINA before
                            database storage and is not repopulated
                            after validation errors.
                        </p>

                        @error('license_registration_number')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Proof upload --}}
                    <div class="md:col-span-2">

                        <label
                            for="proof_document"
                            class="mb-1.5
                                   block
                                   text-xs
                                   font-semibold
                                   uppercase
                                   tracking-[0.12em]
                                   text-[#cbd5e1]"
                        >
                            Verification document
                            <span class="text-[#2dd4bf]">*</span>
                        </label>


                        <div
                            class="rounded-xl
                                   border
                                   border-dashed
                                   border-[#475569]
                                   bg-[#0f172a]
                                   p-4"
                        >

                            <input
                                id="proof_document"
                                name="proof_document"
                                type="file"
                                required
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="block
                                       w-full
                                       text-sm
                                       text-[#cbd5e1]
                                       file:mr-4
                                       file:rounded-lg
                                       file:border-0
                                       file:bg-[#334155]
                                       file:px-4
                                       file:py-2
                                       file:text-sm
                                       file:font-semibold
                                       file:text-[#f1f5f9]
                                       hover:file:bg-[#475569]"
                            >


                            <p
                                class="mt-3
                                       text-xs
                                       leading-5
                                       text-[#64748b]"
                            >
                                Accepted formats: PDF, JPG, JPEG, or PNG.
                                Maximum size: {{ $maximumProofSizeMb }} MB.
                                The original filename is not stored.
                            </p>

                        </div>

                        @error('proof_document')
                            <p
                                class="mt-1.5
                                       text-xs
                                       text-red-300"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>

            </section>


            <div
                class="my-8
                       border-t
                       border-[#334155]"
            ></div>


            {{-- ===================================================== --}}
            {{-- SECTION 3 ? CONSENT                                   --}}
            {{-- ===================================================== --}}

            <section>

                <div
                    class="flex
                           items-start
                           gap-3"
                >

                    <div
                        class="flex
                               h-8 w-8
                               shrink-0
                               items-center
                               justify-center
                               rounded-full
                               bg-[#334155]
                               text-sm
                               font-bold
                               text-[#f1f5f9]"
                    >
                        3
                    </div>


                    <div>

                        <h3
                            class="text-lg
                                   font-bold
                                   text-[#f1f5f9]"
                        >
                            Privacy and appropriate use
                        </h3>

                        <p
                            class="mt-0.5
                                   text-xs
                                   leading-5
                                   text-[#94a3b8]"
                        >
                            Both acknowledgements are required before
                            the request can be submitted.
                        </p>

                    </div>

                </div>


                <div class="mt-5 space-y-4">

                    {{-- Privacy --}}
                    <label
                        class="flex
                               cursor-pointer
                               items-start
                               gap-3
                               rounded-xl
                               border
                               border-[#334155]
                               bg-[#0f172a]
                               p-4"
                    >

                        <input
                            name="privacy_consent"
                            type="checkbox"
                            value="1"
                            required
                            @checked(old('privacy_consent'))
                            class="mt-1
                                   h-4 w-4
                                   rounded
                                   border-[#475569]
                                   bg-[#0f172a]
                                   text-[#2dd4bf]
                                   focus:ring-[#2dd4bf]
                                   focus:ring-offset-[#0f172a]"
                        >


                        <span>

                            <span
                                class="block
                                       text-sm
                                       font-semibold
                                       text-[#e2e8f0]"
                            >
                                Privacy acknowledgement
                            </span>

                            <span
                                class="mt-1
                                       block
                                       text-xs
                                       leading-5
                                       text-[#94a3b8]"
                            >
                                I understand that the information and
                                verification document I submit will be
                                used to evaluate my request for access
                                to RETINA and should contain only the
                                information necessary for that purpose.
                            </span>

                        </span>

                    </label>

                    @error('privacy_consent')
                        <p class="text-xs text-red-300">
                            {{ $message }}
                        </p>
                    @enderror


                    {{-- Appropriate use --}}
                    <label
                        class="flex
                               cursor-pointer
                               items-start
                               gap-3
                               rounded-xl
                               border
                               border-[#334155]
                               bg-[#0f172a]
                               p-4"
                    >

                        <input
                            name="appropriate_use_consent"
                            type="checkbox"
                            value="1"
                            required
                            @checked(old('appropriate_use_consent'))
                            class="mt-1
                                   h-4 w-4
                                   rounded
                                   border-[#475569]
                                   bg-[#0f172a]
                                   text-[#2dd4bf]
                                   focus:ring-[#2dd4bf]
                                   focus:ring-offset-[#0f172a]"
                        >


                        <span>

                            <span
                                class="block
                                       text-sm
                                       font-semibold
                                       text-[#e2e8f0]"
                            >
                                RETINA appropriate-use acknowledgement
                            </span>

                            <span
                                class="mt-1
                                       block
                                       text-xs
                                       leading-5
                                       text-[#94a3b8]"
                            >
                                I understand that RETINA is an
                                AI-assisted research prototype and is
                                not a substitute for professional
                                clinical judgment, diagnosis, or
                                emergency medical evaluation.
                            </span>

                        </span>

                    </label>

                    @error('appropriate_use_consent')
                        <p class="text-xs text-red-300">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </section>


            {{-- ===================================================== --}}
            {{-- SECURITY VERIFICATION                                 --}}
            {{-- ===================================================== --}}

            <div
                class="mt-8
                       rounded-xl
                       border
                       border-[#334155]
                       bg-[#0f172a]
                       p-4"
            >

                <p
                    class="mb-3
                           text-xs
                           font-semibold
                           uppercase
                           tracking-[0.12em]
                           text-[#cbd5e1]"
                >
                    Security verification
                    <span class="text-[#2dd4bf]">*</span>
                </p>

                <div
                    class="cf-turnstile"
                    data-sitekey="{{ config('turnstile.site_key') }}"
                    data-action="{{ config('turnstile.expected_action') }}"
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


            {{-- ===================================================== --}}
            {{-- SUBMIT                                                --}}
            {{-- ===================================================== --}}

            <div
                class="mt-8
                       flex
                       flex-col-reverse
                       gap-3
                       border-t
                       border-[#334155]
                       pt-6
                       sm:flex-row
                       sm:items-center
                       sm:justify-between"
            >

                <a
                    href="{{ route('login') }}"
                    class="inline-flex
                           items-center
                           justify-center
                           rounded-lg
                           px-4 py-2.5
                           text-sm
                           font-semibold
                           text-[#94a3b8]
                           transition
                           hover:text-[#f1f5f9]
                           focus:outline-none"
                >
                    Back to sign in
                </a>


                <button
                    type="submit"
                    class="inline-flex
                           items-center
                           justify-center
                           rounded-lg
                           bg-[#2dd4bf]
                           px-6 py-2.5
                           text-sm
                           font-bold
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
                    Submit access request
                </button>

            </div>

        </form>

    </div>

</x-access-request-layout>
