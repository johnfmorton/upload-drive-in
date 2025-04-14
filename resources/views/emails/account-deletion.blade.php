<!DOCTYPE html>
<html>
<head>
    <title>Confirm Account Deletion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            color: #dc2626;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Account Deletion Confirmation</h1>

    <p>We received a request to delete your account at {{ config('app.name') }}.</p>

    <p class="warning">Warning: This action cannot be undone. All your data and files will be permanently deleted.</p>

    <p>If you wish to proceed with account deletion, please click the button below:</p>

    <a href="{{ $verificationUrl }}" class="button">Confirm Account Deletion</a>

    <p>If you did not request to delete your account, you can safely ignore this email. Your account will remain active.</p>

    <div class="footer">
        <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
