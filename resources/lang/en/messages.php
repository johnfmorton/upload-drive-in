<?php return [
    'welcome' => 'Welcome to our application!',
    'login-message' => 'Admin users at <b>' . config('app.company_name') . '</b> can log in with their email and password. Client users must use email verification on  at <a href="' . config('app.url') . '" class="text-blue-500 underline">the home page</a>.',
    'email-validation-message' => 'You will receive an email with a link to validate your email address. Clicking the link we send you will allow you to upload files to John Smith at ' . config('app.company_name') . '.',
    'validate-email-button' => 'Validate Email Address',

    // Navigation & General UI
    'profile' => 'Profile*',
    'log_out' => 'Log Out*',
    'dashboard' => 'Dashboard*',
    'client_users' => 'Client Users*',
    'your_files' => 'Your Files*',
    'upload_files' => 'Upload Files*',

    // Admin Settings Page
    'app_settings' => 'Application Settings*',
    'branding_settings_title' => 'Branding Settings*',
    'branding_settings_description' => 'Update your application\'s branding details like name, color, and icon.*',
    'business_name_label' => 'Business Name*',
    'branding_color_label' => 'Branding Color*',
    'app_icon_label' => 'Application Icon (Logo)*',
    'app_icon_hint' => 'Upload a PNG, JPG, or SVG. Recommended size: 128x128px.*',
    'save_button' => 'Save*',
    'saved_confirmation' => 'Saved.*',
];
