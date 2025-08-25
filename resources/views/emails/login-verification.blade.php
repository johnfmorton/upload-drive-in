<!DOCTYPE html>
<html>
<head>
    <title>{{ __('messages.verify_email_title') }}</title>
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
            background-color: #4F46E5 !important;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        
        /* Gmail-specific fixes */
        .button:visited {
            color: #ffffff !important;
        }
        
        .button:hover {
            color: #ffffff !important;
            background-color: #3730A3 !important;
        }
        
        .button:active {
            color: #ffffff !important;
        }
        .footer {
            margin-top: 30px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>{{ __('messages.verify_email_title') }}</h1>

    <p>{{ __('messages.verify_email_intro', ['company_name' => config('app.company_name')]) }}</p>

    <a href="{{ $verificationUrl }}" class="button" style="display: inline-block; padding: 10px 20px; background-color: #4F46E5 !important; color: #ffffff !important; text-decoration: none !important; border-radius: 5px; margin: 20px 0; font-weight: 600; text-align: center;">{{ __('messages.verify_email_button') }}</a>

    <p>{{ __('messages.verify_email_ignore') }}</p>

    <div class="footer">
        <p>{{ __('messages.thanks_signature') }},<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
