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
                            <h2 style="margin:0 0 12px;color:#0F2D52;font-size:18px;">Your email address was changed
                            </h2>
                            <p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.6;">
                                Hi {{ $user->first_name }}, the email address on your FoundationLink account
                                was just changed to <strong>{{ $newEmail }}</strong>.
                            </p>
                            <p
                                style="margin:0;color:#92400e;background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;font-size:13px;line-height:1.6;">
                                If you made this change, no action is needed. If you didn't, please contact support
                                immediately — this inbox is no longer linked to your account.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>