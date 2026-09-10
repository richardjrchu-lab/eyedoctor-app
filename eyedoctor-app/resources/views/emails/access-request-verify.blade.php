<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Verify your RETINA professional access request
    </title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background: #f8fafc;
        font-family: Arial, Helvetica, sans-serif;
        color: #0f172a;
    "
>

    <div
        style="
            max-width: 620px;
            margin: 0 auto;
            padding: 32px 20px;
        "
    >

        <div
            style="
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 32px;
            "
        >

            <div
                style="
                    margin-bottom: 24px;
                    font-size: 24px;
                    font-weight: 800;
                    letter-spacing: 0.16em;
                    color: #0f172a;
                "
            >
                RETINA
            </div>


            <h1
                style="
                    margin: 0;
                    font-size: 22px;
                    line-height: 1.3;
                    color: #0f172a;
                "
            >
                Verify your email address
            </h1>


            <p
                style="
                    margin: 18px 0 0;
                    font-size: 15px;
                    line-height: 1.7;
                    color: #475569;
                "
            >
                Hello {{ $applicantName }},
            </p>


            <p
                style="
                    margin: 12px 0 0;
                    font-size: 15px;
                    line-height: 1.7;
                    color: #475569;
                "
            >
                RETINA received a professional-access request using
                this email address. Verify the address before the
                request can move to administrative review.
            </p>


            <div
                style="
                    margin: 28px 0;
                "
            >

                <a
                    href="{{ $verificationUrl }}"
                    style="
                        display: inline-block;
                        background: #2dd4bf;
                        color: #0f172a;
                        text-decoration: none;
                        font-size: 14px;
                        font-weight: 700;
                        padding: 12px 20px;
                        border-radius: 8px;
                    "
                >
                    Verify email address
                </a>

            </div>


            <p
                style="
                    margin: 0;
                    font-size: 13px;
                    line-height: 1.7;
                    color: #64748b;
                "
            >
                This signed verification link expires in
                {{ $expiresMinutes }} minutes.
            </p>


            <p
                style="
                    margin: 14px 0 0;
                    font-size: 13px;
                    line-height: 1.7;
                    color: #64748b;
                "
            >
                If you did not submit a RETINA professional-access
                request, you can ignore this email.
            </p>


            <div
                style="
                    margin-top: 26px;
                    padding-top: 18px;
                    border-top: 1px solid #e2e8f0;
                    font-size: 12px;
                    line-height: 1.7;
                    color: #94a3b8;
                "
            >
                RETINA ? AI-Assisted Diabetic Retinopathy Screening Platform
            </div>

        </div>

    </div>

</body>

</html>
