<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>RETINA Web — Privacy Notice</title>

    <style>
        :root {
            --page-bg: #1e293b;
            --header-bg: #0f172a;
            --card-bg: #111c30;
            --footer-bg: #0d1728;

            --border: #334155;
            --border-soft: #2a394f;

            --text-main: #f8fafc;
            --text-body: #c2cedd;
            --text-muted: #8191a7;

            --blue: #60a5fa;
            --teal: #2dd4bf;
            --teal-dark: #0f766e;
            --teal-hover: #0d9488;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;

            background: var(--page-bg);
            color: var(--text-main);

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;
        }


        /* =========================================================
           RETINA HEADER
        ========================================================= */

        .retina-header {
            width: 100%;

            background: var(--header-bg);

            border-bottom: 1px solid #26364d;
        }

        .retina-header-inner {
            width: min(1180px, calc(100% - 40px));
            min-height: 82px;

            margin: 0 auto;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 30px;
        }

        .brand {
            display: flex;
            align-items: center;

            gap: 14px;

            color: inherit;
            text-decoration: none;
        }

        .brand-logo {
            width: 68px;
            height: auto;

            display: block;

            object-fit: contain;
        }

        .brand-name {
            color: var(--text-main);

            font-size: 20px;
            font-weight: 800;

            letter-spacing: 0.18em;

            line-height: 1;
        }

        .brand-subtitle {
            margin-top: 6px;

            color: var(--blue);

            font-size: 12px;
        }


        /* =========================================================
           USER
        ========================================================= */

        .user-area {
            display: flex;
            align-items: center;

            gap: 14px;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            color: #f1f5f9;

            font-size: 13px;
            font-weight: 700;
        }

        .user-role {
            margin-top: 4px;

            color: #64748b;

            font-size: 9px;
            font-weight: 700;

            letter-spacing: 0.16em;

            text-transform: uppercase;
        }

        .logout-button {
            padding: 9px 17px;

            border: 1px solid #475569;
            border-radius: 8px;

            background: #172033;
            color: #e2e8f0;

            font-family: inherit;
            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.15s ease,
                border-color 0.15s ease;
        }

        .logout-button:hover {
            background: #223047;
            border-color: #64748b;
        }


        /* =========================================================
           PAGE
        ========================================================= */

        .page {
            width: 100%;

            padding:
                48px
                20px
                64px;
        }

        .legal-card {
            width: min(900px, 100%);

            margin: 0 auto;

            overflow: hidden;

            background: var(--card-bg);

            border: 1px solid var(--border);
            border-radius: 14px;

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.20);
        }


        /* =========================================================
           DOCUMENT TITLE
        ========================================================= */

        .document-header {
            padding:
                34px
                38px
                28px;

            border-bottom: 1px solid var(--border-soft);
        }

        .document-type {
            margin-bottom: 10px;

            color: var(--teal);

            font-size: 10px;
            font-weight: 800;

            letter-spacing: 0.18em;

            text-transform: uppercase;
        }

        .document-title {
            margin: 0;

            color: var(--text-main);

            font-size: 28px;
            font-weight: 750;

            line-height: 1.25;
        }

        .document-meta {
            margin-top: 10px;

            color: var(--text-muted);

            font-size: 12px;
        }


        /* =========================================================
           DOCUMENT CONTENT
        ========================================================= */

        .document-content {
            padding:
                32px
                38px
                38px;

            color: var(--text-body);

            font-size: 14px;
            line-height: 1.75;
        }

        .document-content p {
            margin:
                0
                0
                14px;
        }

        .section {
            margin-top: 29px;
        }

        .section h2 {
            margin:
                0
                0
                9px;

            color: #eef4fb;

            font-size: 16px;
            font-weight: 750;

            line-height: 1.45;
        }

        .notice-box {
            margin-top: 30px;

            padding:
                15px
                17px;

            background: #17243a;

            border-left: 3px solid var(--teal);
            border-radius: 6px;

            color: #d8e2ed;

            font-size: 13px;
            line-height: 1.65;
        }


        /* =========================================================
           ACCEPTANCE FOOTER
        ========================================================= */

        .acceptance-footer {
            padding:
                24px
                38px
                27px;

            background: var(--footer-bg);

            border-top: 1px solid var(--border);
        }

        .checkbox-row {
            display: flex;
            align-items: flex-start;

            gap: 12px;
        }

        .checkbox {
            width: 18px;
            height: 18px;

            margin-top: 2px;

            flex-shrink: 0;

            accent-color: #14b8a6;

            cursor: pointer;
        }

        .checkbox-label {
            color: #c5d0dd;

            font-size: 12px;
            line-height: 1.6;
        }

        .error {
            margin:
                10px
                0
                0
                30px;

            color: #fca5a5;

            font-size: 12px;
        }

        .button-row {
            margin-top: 23px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 14px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 100px;

            padding:
                11px
                18px;

            border: 1px solid #475569;
            border-radius: 8px;

            background: transparent;
            color: #b8c5d5;

            text-decoration: none;

            font-size: 13px;
            font-weight: 700;

            transition:
                background 0.15s ease,
                color 0.15s ease;
        }

        .back-button:hover {
            background: #17243a;
            color: #ffffff;
        }

        .continue-button {
            min-width: 215px;

            padding:
                12px
                22px;

            border: 1px solid #14b8a6;
            border-radius: 8px;

            background: var(--teal-dark);
            color: #ffffff;

            font-family: inherit;
            font-size: 13px;
            font-weight: 800;

            cursor: pointer;

            transition:
                background 0.15s ease,
                border-color 0.15s ease,
                transform 0.1s ease;
        }

        .continue-button:hover {
            background: var(--teal-hover);
            border-color: var(--teal);
        }

        .continue-button:active {
            transform: translateY(1px);
        }


        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 700px) {

            .retina-header-inner {
                width: calc(100% - 28px);
                min-height: 72px;
            }

            .brand-logo {
                width: 54px;
            }

            .brand-name {
                font-size: 17px;
            }

            .brand-subtitle {
                display: none;
            }

            .user-details {
                display: none;
            }

            .page {
                padding:
                    28px
                    14px
                    45px;
            }

            .document-header,
            .document-content,
            .acceptance-footer {
                padding-left: 22px;
                padding-right: 22px;
            }

            .document-title {
                font-size: 22px;
            }

            .button-row {
                flex-direction: column-reverse;
            }

            .back-button,
            .continue-button {
                width: 100%;
            }
        }
    </style>
</head>


<body>

    {{-- =========================================================
         HEADER
    ========================================================= --}}

    <header class="retina-header">

        <div class="retina-header-inner">

            <a
                href="{{ route('welcome') }}"
                class="brand"
            >

                <img
                    src="{{ asset('images/retina-logo.png') }}"
                    alt="RETINA Logo"
                    class="brand-logo"
                >

                <div>

                    <div class="brand-name">
                        RETINA
                    </div>

                    <div class="brand-subtitle">
                        Diabetic Retinopathy Detection System
                    </div>

                </div>

            </a>


            @auth

                <div class="user-area">

                    <div class="user-details">

                        <div class="user-name">
                            {{ auth()->user()->name }}
                        </div>

                        <div class="user-role">
                            @if(
                                method_exists(auth()->user(), 'hasRole') &&
                                auth()->user()->hasRole('admin')
                            )
                                Administrator
                            @else
                                Doctor
                            @endif
                        </div>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="logout-button"
                        >
                            Log out
                        </button>

                    </form>

                </div>

            @endauth

        </div>

    </header>


    {{-- =========================================================
         PRIVACY NOTICE
    ========================================================= --}}

    <main class="page">

        <section class="legal-card">

            <div class="document-header">

                <div class="document-type">
                    Privacy &amp; Data Handling
                </div>

                <h1 class="document-title">
                    RETINA Web Privacy Notice
                </h1>

                <div class="document-meta">
                    Version 1.0
                    &nbsp;•&nbsp;
                    Last Updated: September 3, 2026
                </div>

            </div>


            <div class="document-content">

                <p>
                    This Privacy Notice explains how information is processed,
                    stored, protected, retained, and deleted when RETINA Web
                    is used.
                </p>


                <section class="section">

                    <h2>
                        1. Information RETINA Web Processes
                    </h2>

                    <p>
                        RETINA Web may process account information, retinal
                        fundus photographs, coded case or study identifiers,
                        AI-generated screening results, referral-related
                        information, and technical information required for
                        system operation.
                    </p>

                    <p>
                        Users should avoid including unnecessary personally
                        identifiable information in image filenames, case
                        identifiers, or other information submitted to the
                        system.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        2. Purpose of Data Processing
                    </h2>

                    <p>
                        Information is processed to operate RETINA Web,
                        analyze submitted retinal fundus photographs, generate
                        diabetic retinopathy severity and referral-related
                        outputs, maintain authorized user access, support
                        system security, evaluate technical performance, and
                        conduct authorized research or system evaluation.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        3. Retinal Image Processing
                    </h2>

                    <p>
                        When an authorized user submits a retinal image,
                        RETINA Web processes the image through its
                        image-handling, safety, and artificial intelligence
                        pipeline.
                    </p>

                    <p>
                        Images may undergo sanitization before storage or
                        processing. Screening outputs are intended to provide
                        clinical decision-support information and are not
                        standalone medical diagnoses.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        4. Storage
                    </h2>

                    <p>
                        Sanitized retinal images are stored using private
                        storage rather than being made publicly accessible.
                        Associated coded information and system records are
                        maintained within RETINA Web's database
                        infrastructure.
                    </p>

                    <p>
                        Access to stored information is restricted to
                        authorized system functions and appropriately
                        authorized users or research personnel.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        5. Data Retention
                    </h2>

                    <p>
                        Sanitized retinal images become eligible for deletion
                        one calendar year after their original creation date.
                    </p>

                    <p>
                        Coded non-image research records may be retained
                        according to the approved research retention
                        procedure.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        6. Image Deletion
                    </h2>

                    <p>
                        Once the applicable retinal-image retention period
                        expires, the image becomes eligible for deletion
                        according to RETINA Web's retention and deletion
                        procedures.
                    </p>

                    <p>
                        Deletion of an image does not necessarily require
                        deletion of coded non-image research records when
                        those records are permitted to be retained under the
                        approved research protocol.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        7. Research and Evaluation Data
                    </h2>

                    <p>
                        During authorized research or system evaluation,
                        coded information may be analyzed to assess RETINA
                        Web's performance, usability, reliability, safety,
                        and agreement with clinician assessments.
                    </p>

                    <p>
                        Coded identifiers should be used whenever appropriate
                        instead of directly identifying patient information.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        8. Access to Information
                    </h2>

                    <p>
                        Access to RETINA Web information is limited according
                        to user authorization, role, and the purpose for
                        which access has been granted.
                    </p>

                    <p>
                        Authorized researchers or system administrators may
                        access information when necessary for approved
                        research procedures, technical maintenance,
                        troubleshooting, security, or data-management
                        responsibilities.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        9. Security
                    </h2>

                    <p>
                        RETINA Web uses technical and organizational
                        safeguards intended to reduce unauthorized access,
                        disclosure, alteration, or loss of information.
                    </p>

                    <p>
                        These safeguards include authenticated access,
                        private image storage, controlled server-side
                        processing, image sanitization procedures, and
                        restricted access to stored information.
                    </p>

                    <p>
                        No internet-connected information system can
                        guarantee absolute security.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        10. External Infrastructure
                    </h2>

                    <p>
                        RETINA Web relies on external hosting, database,
                        storage, and computing infrastructure to provide its
                        online functionality.
                    </p>

                    <p>
                        Users should therefore submit only information that
                        is necessary and authorized for use within RETINA
                        Web.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        11. User Responsibilities
                    </h2>

                    <p>
                        Users are responsible for ensuring that they are
                        authorized to submit the retinal images they upload
                        to RETINA Web.
                    </p>

                    <p>
                        Users should not upload unrelated personal documents,
                        unnecessary identifying information, or retinal
                        images that they are not permitted to process.
                    </p>

                    <p>
                        Account credentials should not be shared with
                        unauthorized individuals.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        12. Automated Processing
                    </h2>

                    <p>
                        RETINA Web uses artificial intelligence models to
                        automatically analyze retinal fundus photographs and
                        generate diabetic retinopathy severity and
                        referral-related outputs.
                    </p>

                    <p>
                        Because artificial intelligence predictions may be
                        incorrect, results must be interpreted together with
                        appropriate professional clinical judgment.
                    </p>

                </section>


                <section class="section">

                    <h2>
                        13. Changes to this Privacy Notice
                    </h2>

                    <p>
                        This Privacy Notice may be updated when RETINA Web's
                        data-handling procedures, research requirements,
                        security procedures, or system infrastructure change.
                    </p>

                    <p>
                        Significant changes may require users to review and
                        acknowledge an updated version of this notice.
                    </p>

                </section>


                <div class="notice-box">
                    RETINA Web is designed to limit unnecessary identifying
                    information and to use coded information where
                    appropriate for authorized research and system
                    evaluation.
                </div>

            </div>


            {{-- =====================================================
                 PRIVACY ACKNOWLEDGMENT
            ===================================================== --}}

            <form
                method="POST"
                action="{{ route('legal.privacy.accept') }}"
                class="acceptance-footer"
            >

                @csrf


                <label class="checkbox-row">

                    <input
                        type="checkbox"
                        name="acknowledge"
                        value="1"
                        required
                        class="checkbox"
                    >

                    <span class="checkbox-label">
                        I acknowledge that I have read and understood the
                        RETINA Web Privacy Notice and understand how
                        information submitted through the system may be
                        processed, stored, retained, and deleted.
                    </span>

                </label>


                @error('acknowledge')

                    <div class="error">
                        You must acknowledge the Privacy Notice before
                        continuing.
                    </div>

                @enderror


                <div class="button-row">

                    <a
                        href="{{ route('legal.eula') }}"
                        class="back-button"
                    >
                        Back
                    </a>


                    <button
                        type="submit"
                        class="continue-button"
                    >
                        Acknowledge &amp; Continue
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>

</html>