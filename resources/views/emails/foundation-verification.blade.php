<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Foundation Verification Request</title>
</head>

<body style="font-family: Arial, sans-serif;">

    <h2>New Foundation Verification Request</h2>

    <p>
        A foundation has submitted its verification documents and requires review.
    </p>

    <table cellpadding="8" cellspacing="0" border="1">
        <tr>
            <td><strong>Foundation Name</strong></td>
            <td>{{ $foundation->name }}</td>
        </tr>

        <tr>
            <td><strong>Status</strong></td>
            <td>{{ $foundation->status }}</td>
        </tr>
    </table>

    <br>

    <p>
        Please log in to the FoundationLink Super Admin panel to review and approve or reject this request.
    </p>

    <p>
        Thank you,<br>
        FoundationLink System
    </p>

</body>

</html>