<!DOCTYPE html>
<html>
<head>
    <title>Account Deletion Verification</title>
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
        .footer {
            margin-top: 30px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Account Deletion Request</h1>

    <p>We received a request to delete your account. To confirm this action, please click the button below. This link will expire in {{ $expiresIn }}.</p>

    <a href="{{ $verificationUrl }}" class="button">Confirm Account Deletion</a>

    <p>If you did not request to delete your account, please ignore this email or contact support if you have concerns.</p>

    <div class="footer">
        <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
