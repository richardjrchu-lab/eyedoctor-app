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

    <title>RETINA Web — End User License Agreement</title>

    <style>
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
            background: #1e293b;
            color: #e5edf7;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
        }

        /* =========================================================
           RETINA HEADER
        ========================================================= */

        .retina-header {
            width: 100%;
            background: #0f172a;
            border-bottom: 1px solid #26364d;
        }

        .retina-header-inner {
            width: min(1180px, calc(100% - 40px));
            min-height: 80px;

            margin: 0 auto;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 24px;
        }

        .retina-brand {
            display: flex;
            align-items: center;

            gap: 14px;

            text-decoration: none;
        }

        .retina-logo {
            width: 66px;
            height: auto;

            object-fit: contain;
        }

        .retina-name {
            margin: 0;

            color: #f8fafc;

            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.18em;
            line-height: 1;
        }

        .retina-subtitle {
            margin-top: 6px;

            color: #60a5fa;

            font-size: 12px;
            line-height: 1.2;
        }

        .user-section {
            display: flex;
            align-items: center;

            gap: 14px;
        }

        .user-information {
            text-align: right;
        }

        .user-name {
            color: #f8fafc;

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
            appearance: none;

            padding: 9px 17px;

            border: 1px solid #475569;
            border-radius: 8px;

            background: #172033;
            color: #e2e8f0;

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

        .legal-page {
            width: 100%;

            padding:
                54px
                20px
                70px;
        }

        .legal-card {
            width: min(900px, 100%);

            margin: 0 auto;

            overflow: hidden;

            background: #101a2e;

            border: 1px solid #304057;
            border-radius: 14px;

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.18);
        }

        /* =========================================================
           DOCUMENT HEADER
        ========================================================= */

        .document-header {
            padding:
                34px
                38px
                28px;

            border-bottom: 1px solid #2b3950;
        }

        .document-label {
            margin-bottom: 10px;

            color: #2dd4bf;

            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.16em;

            text-transform: uppercase;
        }

        .document-title {
            margin: 0;

            color: #f8fafc;

            font-size: 27px;
            font-weight: 750;
            line-height: 1.25;
        }

        .document-meta {
            margin-top: 10px;

            color: #8292a9;

            font-size: 12px;
        }

        /* =========================================================
           DOCUMENT BODY
        ========================================================= */

        .document-body {
            padding:
                32px
                38px
                36px;

            color: #bac7d8;

            font-size: 14px;
            line-height: 1.75;
        }

        .document-body > p:first-child {
            margin-top: 0;
        }

        .document-body p {
            margin:
                0
                0
                14px;
        }

        .document-section {
            margin-top: 30px;
        }

        .document-section:first-of-type {
            margin-top: 26px;
        }

        .document-section h2 {
            margin:
                0
                0
                10px;

            color: #eef4fb;

            font-size: 16px;
            font-weight: 750;
            line-height: 1.4;
        }

        .important-note {
            margin-top: 15px;

            padding:
                14px
                16px;

            background: #17243a;

            border-left: 3px solid #2dd4bf;
            border-radius: 6px;

            color: #e2e8f0;

            font-weight: 650;
        }

        /* =========================================================
           ACCEPTANCE AREA
        ========================================================= */

        .acceptance-area {
            position: sticky;
            bottom: 0;

            padding:
                23px
                38px
                26px;

            background: #0d1728;

            border-top: 1px solid #304057;
        }

        .agreement-row {
            display: flex;
            align-items: flex-start;

            gap: 12px;
        }

        .agreement-checkbox {
            width: 18px;
            height: 18px;

            margin-top: 2px;

            accent-color: #14b8a6;

            flex-shrink: 0;

            cursor: pointer;
        }

        .agreement-text {
            color: #c4cfdd;

            font-size: 12px;
            line-height: 1.55;
        }

        .error-message {
            margin:
                10px
                0
                0
                30px;

            color: #fca5a5;

            font-size: 12px;
        }

        .action-row {
            margin-top: 22px;

            display: flex;
            align-items: center;
            justify-content: flex-end;

            gap: 12px;
        }

        .accept-button {
            appearance: none;

            min-width: 185px;

            padding:
                12px
                22px;

            border: 1px solid #14b8a6;
            border-radius: 8px;

            background: #0f766e;
            color: #ffffff;

            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.015em;

            cursor: pointer;

            transition:
                background 0.15s ease,
                border-color 0.15s ease,
                transform 0.1s ease;
        }

        .accept-button:hover {
            background: #0d9488;
            border-color: #2dd4bf;
        }

        .accept-button:active {
            transform: translateY(1px);
        }

        .clinical-disclaimer {
            margin-top: 22px;

            color: #f59e0b;

            font-family:
                "Courier New",
                monospace;

            font-size: 10px;
            line-height: 1.55;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 700px) {
            .retina-header-inner {
                width: calc(100% - 28px);
                min-height: 72px;
            }

            .retina-logo {
                width: 53px;
            }

            .retina-name {
                font-size: 17px;
            }

            .retina-subtitle {
                display: none;
            }

            .user-information {
                display: none;
            }

            .legal-page {
                padding:
                    28px
                    14px
                    45px;
            }

            .document-header,
            .document-body,
            .acceptance-area {
                padding-left: 22px;
                padding-right: 22px;
            }

            .document-title {
                font-size: 22px;
            }

            .action-row {
                justify-content: stretch;
            }

            .accept-button {
                width: 100%;
            }
        }
    </style>
</head>


<body>

    {{-- =========================================================
         RETINA HEADER
    ========================================================= --}}

    <header class="retina-header">

        <div class="retina-header-inner">

            <a
                href="{{ route('welcome') }}"
                class="retina-brand"
            >

                <img
                    src="{{ asset('images/retina-logo.png') }}"
                    alt="RETINA Logo"
                    class="retina-logo"
                >

                <div>

                    <div class="retina-name">
                        RETINA
                    </div>

                    <div class="retina-subtitle">
                        Diabetic Retinopathy Detection System
                    </div>

                </div>

            </a>


            @auth

                <div class="user-section">

                    <div class="user-information">

                        <div class="user-name">
                            {{ auth()->user()->name }}
                        </div>

                        <div class="user-role">
                            Doctor
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
         EULA
    ========================================================= --}}

    <main class="legal-page">

        <section class="legal-card">

            <header class="document-header">

                <div class="document-label">
                    Legal Agreement
                </div>

                <h1 class="document-title">
                    RETINA Web End User License Agreement
                </h1>

                <div class="document-meta">
                    Version 1.0 &nbsp;•&nbsp;
                    Last Updated: September 3, 2026
                </div>

            </header>


            <div class="document-body">

                <p>
                    By accessing or using RETINA Web, you acknowledge that
                    you have read, understood, and agreed to the terms below.
                </p>


                <section class="document-section">

                    <h2>
                        1. Purpose of RETINA Web
                    </h2>

                    <p>
                        RETINA Web is an artificial intelligence-assisted
                        system designed to support the screening and
                        classification of diabetic retinopathy severity from
                        retinal fundus photographs.
                    </p>

                    <p>
                        The system provides decision-support information and
                        is not intended to replace examination, diagnosis,
                        treatment, or professional judgment by a qualified
                        healthcare professional.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        2. Intended Use
                    </h2>

                    <p>
                        RETINA Web may be used for authorized research,
                        educational, screening, and approved clinical-support
                        purposes.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        3. Artificial Intelligence Limitations
                    </h2>

                    <p>
                        RETINA Web uses artificial intelligence models to
                        analyze retinal images. AI-generated results may
                        contain errors, including underclassification,
                        overclassification, incorrect referral indications,
                        or inappropriate acceptance or rejection of images.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        4. Not a Standalone Medical Diagnosis
                    </h2>

                    <p>
                        RETINA Web does not provide a definitive medical
                        diagnosis. Results must not be used as the sole basis
                        for diagnosis, treatment, medication decisions,
                        emergency decisions, or replacement of professional
                        clinical examination.
                    </p>

                    <div class="important-note">
                        When RETINA Web and professional clinical judgment
                        disagree, professional clinical judgment takes
                        priority.
                    </div>

                </section>


                <section class="document-section">

                    <h2>
                        5. Image Quality and Suitability
                    </h2>

                    <p>
                        System performance may be affected by image quality,
                        imaging equipment, image acquisition conditions,
                        retinal abnormalities, unsupported image types, and
                        other technical or clinical factors.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        6. User Responsibilities
                    </h2>

                    <p>
                        Users must use RETINA Web only for its intended
                        purpose, upload only appropriately authorized retinal
                        images, protect account credentials, verify important
                        results using appropriate clinical procedures, and
                        avoid unauthorized modification, disruption, or
                        misuse of the system.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        7. Data and Privacy
                    </h2>

                    <p>
                        RETINA Web processes retinal images and associated
                        coded information required for system operation and
                        authorized research activities. Additional details
                        are provided in the RETINA Web Privacy Notice.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        8. Research and Evaluation Use
                    </h2>

                    <p>
                        During authorized research or evaluation, coded or
                        de-identified outputs may be analyzed to assess
                        system performance, safety, reliability, and
                        usability.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        9. System Availability
                    </h2>

                    <p>
                        RETINA Web depends on internet connectivity, hosting
                        infrastructure, databases, and external computing
                        services. Continuous availability cannot be
                        guaranteed.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        10. No Guarantee of Perfect Performance
                    </h2>

                    <p>
                        RETINA Web does not guarantee that every prediction,
                        referral indication, safety decision, or system
                        output will be correct.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        11. Unauthorized Use
                    </h2>

                    <p>
                        Users must not use RETINA Web for unlawful, malicious,
                        misleading, or unauthorized purposes.
                    </p>

                </section>


                <section class="document-section">

                    <h2>
                        12. Changes to the System
                    </h2>

                    <p>
                        RETINA Web may be updated for research, technical
                        maintenance, safety, security, or system improvement.
                        Significant changes may require acceptance of an
                        updated agreement.
                    </p>

                </section>


                <div class="clinical-disclaimer">
                    FOR CLINICAL DECISION SUPPORT ONLY. RETINA Web predictions
                    must be reviewed by a qualified eye-care professional and
                    are not a substitute for clinical diagnosis.
                </div>

            </div>


            {{-- =====================================================
                 ACCEPTANCE
            ===================================================== --}}

            <form
                method="POST"
                action="{{ route('legal.eula.accept') }}"
                class="acceptance-area"
            >

                @csrf


                <label class="agreement-row">

                    <input
                        type="checkbox"
                        name="agree"
                        value="1"
                        required
                        class="agreement-checkbox"
                    >

                    <span class="agreement-text">
                        I have read and agree to the RETINA Web End User
                        License Agreement and acknowledge that RETINA Web is
                        an AI-assisted screening tool and not a substitute
                        for professional medical diagnosis or clinical
                        judgment.
                    </span>

                </label>


                @error('agree')

                    <div class="error-message">
                        You must accept the agreement before continuing.
                    </div>

                @enderror


                <div class="action-row">

                    <button
                        type="submit"
                        class="accept-button"
                    >
                        Accept &amp; Continue
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>