<?php return [
    'welcome' => 'Welcome to our application!',
    'login-message' => 'Admin users at <b>' . config('app.company_name') . '</b> can log in with their email and password. Client users must use email verification on  at <a href="' . config('app.url') . '" class="text-blue-500 underline">the home page</a>.',
    'email-validation-message' => 'You will receive an email with a link to validate your email address. Clicking the link we send you will allow you to upload files to John Smith at ' . config('app.company_name') . '.',
    'validate-email-button' => 'Validate Email Address',
];
