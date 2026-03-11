<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.token_expired_heading', ['provider' => $providerName]) }}</title>
    <style>
        @import url('https://fonts.bunny.net/css?family=dm-sans:400,500,700|outfit:500,700');

        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #625646;
            background-color: #FAF8F5;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h3 {
            font-family: 'Outfit', 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        h1 {
            color: #2D2A26;
        }
        h3 {
            color: #3D3530;
        }
        .header {
            background-color: #F5F0EB;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #E0D6C9;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert {
            background-color: #FFF8F0;
            border: 1px solid #E8772E;
            color: #625646;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #2D2A26;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 9999px;
            margin: 10px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #EDE6DD;
            font-size: 14px;
            color: #B0A08A;
        }
        .steps {
            background-color: #F5F0EB;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 8px;
        }
        a {
            color: #E8772E;
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
