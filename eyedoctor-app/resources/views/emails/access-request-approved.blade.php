<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>RETINA Access Approved</title>
</head>


<body
    style="
        margin: 0;
        padding: 0;
        background: #0f172a;
        color: #f1f5f9;
        font-family: Arial, Helvetica, sans-serif;
    "
>

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="
            background: #0f172a;
            padding: 32px 16px;
        "
    >

        <tr>

            <td align="center">

                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    style="
                        max-width: 620px;
                        background: #1e293b;
                        border: 1px solid #334155;
                        border-radius: 16px;
                        overflow: hidden;
                    "
                >

                    <tr>

                        <td
                            style="
                                padding: 32px;
                            "
                        >

                            <div
                                style="
                                    color: #2dd4bf;
                                    font-size: 12px;
                                    font-weight: 700;
                                    letter-spacing: 1.5px;
                                    text-transform: uppercase;
                                "
                            >
                                Professional Access Approved
                            </div>


                            <h1
                                style="
                                    margin: 12px 0 0;
                                    color: #f1f5f9;
                                    font-size: 25px;
                                    line-height: 1.3;
                                "
                            >
                                Welcome to RETINA
                            </h1>


                            <p
                                style="
                                    margin: 22px 0 0;
                                    color: #cbd5e1;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                Hello {{ $recipientName }},
                            </p>


                            <p
                                style="
                                    margin: 14px 0 0;
                                    color: #cbd5e1;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                Your request for professional access to
                                the RETINA Diabetic Retinopathy Detection
                                System has been approved.
                            </p>


                            <p
                                style="
                                    margin: 14px 0 0;
                                    color: #cbd5e1;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                Use the secure button below to create
                                your account password.
                            </p>


                            <table
                                role="presentation"
                                cellspacing="0"
                                cellpadding="0"
                                style="
                                    margin: 26px 0;
                                "
                            >

                                <tr>

                                    <td
                                        style="
                                            border-radius: 8px;
                                            background: #2dd4bf;
                                        "
                                    >

                                        <a
                                            href="{{ $setupUrl }}"
                                            style="
                                                display: inline-block;
                                                padding: 13px 22px;
                                                color: #0f172a;
                                                font-size: 14px;
                                                font-weight: 700;
                                                text-decoration: none;
                                            "
                                        >
                                            Set up RETINA password
                                        </a>

                                    </td>

                                </tr>

                            </table>


                            <p
                                style="
                                    margin: 0;
                                    color: #94a3b8;
                                    font-size: 13px;
                                    line-height: 1.6;
                                "
                            >
                                This password-setup link expires in
                                {{ $expiresMinutes }}
                                {{ $expiresMinutes === 1 ? 'minute' : 'minutes' }}.
                            </p>


                            <p
                                style="
                                    margin: 14px 0 0;
                                    color: #94a3b8;
                                    font-size: 13px;
                                    line-height: 1.6;
                                "
                            >
                                If the button does not work, copy and
                                paste this address into your browser:
                            </p>


                            <p
                                style="
                                    margin: 8px 0 0;
                                    word-break: break-all;
                                    color: #5eead4;
                                    font-size: 12px;
                                    line-height: 1.6;
                                "
                            >
                                {{ $setupUrl }}
                            </p>


                            <div
                                style="
                                    margin-top: 28px;
                                    padding-top: 20px;
                                    border-top: 1px solid #334155;
                                    color: #64748b;
                                    font-size: 11px;
                                    line-height: 1.6;
                                "
                            >
                                RETINA | AI-Assisted Diabetic Retinopathy
                                Screening Platform
                            </div>

                        </td>

                    </tr>

                </table>

            </td>

        </tr>

    </table>

</body>

</html>
