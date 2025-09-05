<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.connection_restored_heading', ['provider' => $providerName]) }}</title>
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
            background-color: #d4edda;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        .success {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #218838;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
        .status-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .next-steps {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('messages.connection_restored_heading', ['provider' => $providerName]) }}</h1>
        <p>{{ __('messages.connection_restored_subheading') }}</p>
    </div>

    <div class="success">
        <strong>🎉 {{ __('messages.connection_restored_alert', ['provider' => $providerName]) }}</strong>
    </div>

    <p>{{ __('messages.connection_restored_greeting', ['name' => $user->name]) }}</p>

    <p>{{ __('messages.connection_restored_intro', ['provider' => $providerName]) }}</p>

    <div class="status-summary">
        <h4>{{ __('messages.connection_restored_current_status') }}</h4>
        <ul>
            <li><strong>{{ __('messages.connection_restored_connection_status') }}</strong></li>
            <li><strong>{{ __('messages.connection_restored_uploads_status') }}</strong></li>
            <li><strong>{{ __('messages.connection_restored_pending_status') }}</strong></li>
            <li><strong>{{ __('messages.connection_restored_system_status') }}</strong></li>
        </ul>
    </div>

    <h3>{{ __('messages.connection_restored_what_happened') }}</h3>
    <p>{{ __('messages.connection_restored_explanation', ['provider' => $providerName]) }}</p>

    <div class="next-steps">
        <h4>{{ __('messages.connection_restored_whats_happening') }}</h4>
        <ul>
            <li>{{ __('messages.connection_restored_processing_queued') }}</li>
            <li>{{ __('messages.connection_restored_accepting_new') }}</li>
            <li>{{ __('messages.connection_restored_operations_resumed', ['provider' => $providerName]) }}</li>
            <li>{{ __('messages.connection_restored_monitoring_active') }}</li>
        </ul>
    </div>

    <h3>{{ __('messages.connection_restored_access_dashboard') }}</h3>
    <p>{{ __('messages.connection_restored_dashboard_intro') }}</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">{{ __('messages.connection_restored_view_dashboard') }}</a>
    </div>

    <h3>{{ __('messages.connection_restored_preventing_issues') }}</h3>
    <ul>
        <li>{{ __('messages.connection_restored_keep_active', ['provider' => $providerName]) }}</li>
        <li>{{ __('messages.connection_restored_avoid_password_change', ['provider' => $providerName]) }}</li>
        <li>{{ __('messages.connection_restored_monitor_email') }}</li>
        <li>{{ __('messages.connection_restored_contact_support') }}</li>
    </ul>

    <h3>{{ __('messages.connection_restored_need_assistance') }}</h3>
    <p>{{ __('messages.connection_restored_support', ['provider' => $providerName, 'email' => $supportEmail]) }}</p>

    <div class="footer">
        <p><strong>{{ __('messages.connection_restored_footer_timestamp', ['timestamp' => now()->format('Y-m-d H:i:s T')]) }}</strong></p>
        <p><strong>{{ __('messages.connection_restored_footer_service_status') }}</strong></p>
        
        <p>{{ __('messages.connection_restored_footer_thanks') }}</p>
    </div>
</body>
</html>