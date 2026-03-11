<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.token_refresh_failed_heading', ['provider' => $providerName]) }}</title>
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
        h1, h3, h4 {
            font-family: 'Outfit', 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        h1 {
            color: #2D2A26;
        }
        h3, h4 {
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
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background-color: #FFF8F0;
            border: 1px solid #E8772E;
            color: #625646;
        }
        .alert-danger {
            background-color: #FDF2F0;
            border: 1px solid #D06420;
            color: #625646;
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
        .button-danger {
            background-color: #D06420;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #EDE6DD;
            font-size: 14px;
            color: #B0A08A;
        }
        .error-details {
            background-color: #F5F0EB;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #E8772E;
        }
        .status-info {
            background-color: #F5F0EB;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #E8772E;
        }
        a {
            color: #E8772E;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('messages.token_refresh_failed_heading', ['provider' => $providerName]) }}</h1>
        <p>{{ $requiresUserAction ? __('messages.token_refresh_failed_action_required') : __('messages.token_refresh_failed_auto_recovery') }}</p>
    </div>

    @if($requiresUserAction)
        <div class="alert alert-danger">
            <strong>🚨 {{ __('messages.token_refresh_failed_alert_action', ['provider' => $providerName]) }}</strong>
        </div>
    @else
        <div class="alert alert-warning">
            <strong>⚠️ {{ __('messages.token_refresh_failed_alert_auto', ['provider' => $providerName]) }}</strong>
        </div>
    @endif

    <p>{{ __('messages.token_refresh_failed_greeting', ['name' => $user->name]) }}</p>

    <p>{{ __('messages.token_refresh_failed_intro', ['provider' => $providerName]) }}</p>

    <div class="error-details">
        <h4>{{ __('messages.token_refresh_failed_issue_details') }}</h4>
        <p><strong>{{ __('messages.token_refresh_failed_error_type', ['type' => $errorTypeName]) }}</strong></p>
        <p><strong>{{ __('messages.token_refresh_failed_attempt', ['current' => $attemptCount, 'max' => $errorType->getMaxRetryAttempts()]) }}</strong></p>
        <p><strong>{{ __('messages.token_refresh_failed_description_label', ['description' => $errorDescription]) }}</strong></p>
        @if($errorMessage)
            <p><strong>{{ __('messages.token_refresh_failed_technical_details', ['details' => $errorMessage]) }}</strong></p>
        @endif
    </div>

    @if($requiresUserAction)
        <h3>{{ __('messages.token_refresh_failed_what_to_do') }}</h3>
        <p>{{ __('messages.token_refresh_failed_manual_required', ['provider' => $providerName]) }}</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $reconnectUrl }}" class="button button-danger">{{ __('messages.token_refresh_failed_reconnect_now', ['provider' => $providerName]) }}</a>
        </div>

        <h3>{{ __('messages.token_refresh_failed_why_manual') }}</h3>
        <p>{{ $errorType === App\Enums\TokenRefreshErrorType::INVALID_REFRESH_TOKEN || $errorType === App\Enums\TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN ?
            __('messages.token_refresh_failed_credentials_invalid') :
            __('messages.token_refresh_failed_cannot_resolve') }}</p>
    @else
        <div class="status-info">
            <h4>{{ __('messages.token_refresh_failed_auto_recovery_status') }}</h4>
            <p>{{ $nextRetryInfo }}</p>
            @if($isRecoverable)
                <p>{{ __('messages.token_refresh_failed_no_action_needed') }}</p>
            @endif
        </div>

        @if($attemptCount >= $errorType->getMaxRetryAttempts())
            <h3>{{ __('messages.token_refresh_failed_max_attempts') }}</h3>
            <p>{{ __('messages.token_refresh_failed_exhausted') }}</p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $reconnectUrl }}" class="button">{{ __('messages.token_expired_reconnect_button', ['provider' => $providerName]) }}</a>
            </div>
        @else
            <h3>{{ __('messages.token_refresh_failed_what_happens_next') }}</h3>
            <ul>
                <li>{{ __('messages.token_refresh_failed_auto_retry') }}</li>
                <li>{{ __('messages.token_refresh_failed_success_email') }}</li>
                <li>{{ __('messages.token_refresh_failed_manual_notify') }}</li>
                <li>{{ __('messages.token_refresh_failed_uploads_paused') }}</li>
            </ul>
        @endif
    @endif

    <h3>{{ __('messages.token_refresh_failed_impact') }}</h3>
    <ul>
        <li><strong>{{ __('messages.token_refresh_failed_uploads_impact') }}</strong></li>
        <li><strong>{{ __('messages.token_refresh_failed_existing_impact') }}</strong></li>
        <li><strong>{{ __('messages.token_refresh_failed_system_impact') }}</strong></li>
    </ul>

    @if(!$requiresUserAction && $isRecoverable)
        <p><strong>{{ __('messages.token_refresh_failed_no_action_required') }}</strong></p>
    @endif

    <h3>{{ __('messages.token_refresh_failed_need_help') }}</h3>
    <p>{{ __('messages.token_refresh_failed_support', ['email' => $supportEmail, 'reference' => $errorType->value . '-' . $attemptCount]) }}</p>

    <div class="footer">
        <p><strong>{{ __('messages.token_refresh_failed_error_reference', ['type' => $errorType->value, 'attempt' => $attemptCount]) }}</strong></p>
        <p><strong>{{ __('messages.token_refresh_failed_timestamp', ['timestamp' => now()->format('Y-m-d H:i:s T')]) }}</strong></p>

        <p>{{ __('messages.token_refresh_failed_footer_automated') }}</p>
    </div>
</body>
</html>
