<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.token_expired_heading', ['provider' => $providerName]) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
        .steps {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('messages.token_expired_heading', ['provider' => $providerName]) }}</h1>
        <p>{{ __('messages.token_expired_subheading') }}</p>
    </div>

    <div class="alert">
        <strong>⚠️ {{ __('messages.token_expired_alert', ['provider' => $providerName]) }}</strong>
    </div>

    <p>{{ __('messages.token_expired_greeting', ['name' => $user->name]) }}</p>

    <p>{{ __('messages.token_expired_intro', ['provider' => $providerName]) }}</p>

    <h3>{{ __('messages.token_expired_what_this_means') }}</h3>
    <ul>
        <li>{{ __('messages.token_expired_impact_uploads') }}</li>
        <li>{{ __('messages.token_expired_impact_existing', ['provider' => $providerName]) }}</li>
        <li>{{ __('messages.token_expired_impact_resume') }}</li>
    </ul>

    <h3>{{ __('messages.token_expired_how_to_reconnect') }}</h3>
    <div class="steps">
        <ol>
            <li>{{ __('messages.token_expired_step_1', ['provider' => $providerName]) }}</li>
            <li>{{ __('messages.token_expired_step_2', ['provider' => $providerName]) }}</li>
            <li>{{ __('messages.token_expired_step_3') }}</li>
            <li>{{ __('messages.token_expired_step_4') }}</li>
        </ol>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $reconnectUrl }}" class="button">{{ __('messages.token_expired_reconnect_button', ['provider' => $providerName]) }}</a>
    </div>

    <h3>{{ __('messages.token_expired_why_happened') }}</h3>
    <p>{{ __('messages.token_expired_explanation', ['provider' => $providerName]) }}</p>

    <h3>{{ __('messages.token_expired_need_help') }}</h3>
    <p>{{ __('messages.token_expired_support', ['email' => $supportEmail]) }}</p>

    <div class="footer">
        <p><strong>{{ __('messages.token_expired_footer_important', ['provider' => $providerName]) }}</strong></p>
        
        <p>{{ __('messages.token_expired_footer_automated') }}</p>
    </div>
</body>
</html>