<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
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
            background-color: #4F46E5;
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
    <h1>Verify Your Email Address</h1>

    <p>Please click the button below to verify your email address and access {{ config('app.name') }}.</p>

    <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>

    <p>If you did not request this verification, you can safely ignore this email.</p>

    <div class="footer">
        <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
