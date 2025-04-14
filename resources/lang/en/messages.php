<?php return [
    'welcome' => 'Welcome to our application!',
    'login-message' => 'Admin users at <b>' . config('app.company_name') . '</b> can log in with their email and password. Client users must use email verification on  at <a href="' . config('app.url') . '" class="text-blue-500 underline">the home page</a>.',
    'email-validation-message' => 'You will receive an email with a link to validate your email address. Clicking the link we send you will allow you to upload files to John Smith at ' . config('app.company_name') . '.',
    'validate-email-button' => 'Validate Email Address',

    // Navigation & General UI
    'profile' => 'Profile',
    'log_out' => 'Log Out',
    'dashboard' => 'Dashboard',
    'client_users' => 'Users',
    'your_files' => 'Your Files',
    'upload_files' => 'Upload Files',

    // Admin Settings Page
    'app_settings' => 'Application Settings',
    'branding_settings_title' => 'Branding Settings',
    'branding_settings_description' => 'Update your application\'s branding details like name, color, and icon.',
    'company_name_label' => 'Company Name',
    'branding_color_label' => 'Branding Color',
    'app_icon_label' => 'Application Icon (Logo)',
    'app_icon_hint' => 'Upload a PNG, JPG, or SVG. Recommended size: 128x128px.',
    'save_button' => 'Save',
    'saved_confirmation' => 'Saved.',
    'remove_logo_button' => 'Remove Logo',

    // Admin Dashboard
    'admin_dashboard_title' => 'Admin Dashboard',
    'google_drive_connection_title' => 'Google Drive Connection',
    'google_drive_connection_description' => 'Connect to Google Drive to enable automatic file uploads.',
    'google_drive_connected' => 'Google Drive is connected',
    'google_drive_disconnect_button' => 'Disconnect Google Drive',
    'google_drive_not_connected' => 'Google Drive is not connected',
    'google_drive_connect_button' => 'Connect Google Drive',
    'uploaded_files_title' => 'Uploaded Files',
    'toggle_columns_title' => 'Show/Hide Columns:',
    'column_file_name' => 'File Name',
    'column_user' => 'User',
    'column_size' => 'Size',
    'column_status' => 'Status',
    'column_message' => 'Message',
    'column_uploaded_at' => 'Uploaded At',
    'column_actions' => 'Actions',
    'filter_files_label' => 'Filter files', // sr-only
    'filter_placeholder' => 'Filter by filename, user, or message...',
    'view_button' => 'View',
    'delete_button' => 'Delete',
    'status_uploaded' => 'Uploaded', // Was "Uploaded to Drive"
    'status_pending' => 'Pending',
    'mobile_label_uploaded_at' => 'Uploaded at', // Was "Uploaded"
    'mobile_label_message' => 'Message', // Added for consistency
    'no_files_found' => 'No files match your filter criteria.',
    'delete_modal_title' => 'Delete File',
    'delete_modal_text' => 'Are you sure you want to delete this file? This action cannot be undone.',
    'delete_modal_confirm_button' => 'Confirm Delete',
    'delete_modal_cancel_button' => 'Cancel',
];
