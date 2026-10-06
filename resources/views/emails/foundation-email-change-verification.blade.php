<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
</head>

<body style="margin:0;padding:0;background:#f0f4f9;font-family:Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#0F2D52;padding:24px 32px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:800;">FoundationLink</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 12px;color:#0F2D52;font-size:18px;">Confirm your new email address
                            </h2>
                            <p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.6;">
                                Hi {{ $user->first_name }}, we received a request to change the email on your
                                FoundationLink account to <strong>{{ $newEmail }}</strong>.
                            </p>
                            <p style="margin:0 0 24px;color:#475569;font-size:14px;line-height:1.6;">
                                Is this you?
                            </p>
                            <table cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="padding-right:10px;">
                                        <a href="{{ $confirmUrl }}"
                                            style="display:inline-block;background:#1a56c4;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 24px;border-radius:10px;">
                                            Yes, confirm
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ $cancelUrl }}"
                                            style="display:inline-block;background:#ffffff;color:#64748b;text-decoration:none;font-size:14px;font-weight:700;padding:11px 24px;border-radius:10px;border:1px solid #e2e8f0;">
                                            No, cancel
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.6;">
                                This link expires in 24 hours. Your email will not change until you press
                                Yes, confirm.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>