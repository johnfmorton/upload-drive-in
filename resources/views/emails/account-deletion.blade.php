<!DOCTYPE html>
<html>
<head>
    <title>{{ __('messages.delete_account_email_title') }}</title>
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
    <h1>{{ __('messages.delete_account_email_title') }}</h1>

    <p>{{ __('messages.delete_account_email_request', ['app_name' => config('app.name')]) }}</p>

    <p class="warning">{{ __('messages.delete_account_email_warning') }}</p>

    <p>{{ __('messages.delete_account_email_proceed') }}</p>

    <a href="{{ $verificationUrl }}" class="button">{{ __('messages.delete_account_email_confirm_button') }}</a>

    <p>{{ __('messages.delete_account_email_ignore') }}</p>

    <div class="footer">
        <p>{{ __('messages.thanks_signature') }},<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
