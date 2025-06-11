<?php return [
    'welcome' => 'Welcome to our application!',
    'login-message' => 'Admin users at <b>' . config('app.company_name') . '</b> can log in with their email and password. Client users must use email verification on  at <a href="' . config('app.url') . '" class="text-blue-500 underline">the home page</a>.',
    'email-validation-message' => 'You will receive an email with a link to validate your email address. Clicking the link we send you will allow you to upload files to ' . config('app.company_name') . '.',

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
    'google_drive_disconnected_success' => 'Google Drive disconnected successfully.*',
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
    'filter_files_placeholder' => 'Filter by filename, user, or message...',
    'view_button' => 'View',
    'delete_button' => 'Delete',
    'status_uploaded' => 'Uploaded', // Was "Uploaded to Drive"
    'status_pending' => 'Pending',
    'mobile_label_uploaded_at' => 'Uploaded at', // Was "Uploaded"
    'mobile_label_message' => 'Message',
    'no_message_provided' => 'No message provided',
    'message_section_title' => 'Client Message',
    'message_section_empty' => 'No additional message was provided with this upload',
    'no_files_found' => 'No files match your filter criteria.',
    'delete_modal_title' => 'Delete File',
    'delete_modal_text' => 'Are you sure you want to delete this file? This action cannot be undone.',
    'delete_modal_confirm_button' => 'Confirm Delete',
    'delete_modal_cancel_button' => 'Cancel',

    // Admin User Management Page
    'user_management_title' => 'Client User Management',
    'create_user_title' => 'Create New Client User',
    'create_user_error_intro' => 'Please correct the following errors:',
    'label_name' => 'Name',
    'label_email' => 'Email Address',
    'button_create_user' => 'Create User',
    'users_list_title' => 'Client Users',
    'user_created_success' => 'Client user created successfully.',
    'user_deleted_success' => 'Client user deleted successfully.',
    'user_delete_error' => 'Error deleting user.',
    'delete_user_modal_title' => 'Confirm Deletion',
    'delete_user_modal_text' => 'Are you sure you want to delete this user? This action cannot be undone.',
    'delete_user_modal_checkbox' => 'Also delete all uploaded files associated with this user?',
    'delete_user_modal_confirm_button' => 'Delete User',
    'button_copy_login_url' => 'Copy Login URL',
    'copied_confirmation' => 'Copied!',
    'toggle_user_columns_title' => 'Show/Hide Columns:',
    'column_created_at' => 'Created At',
    'column_login_url' => 'Login URL',
    'filter_users_label' => 'Filter users',
    'filter_users_placeholder' => 'Filter by name or email...',
    'no_users_match_filter' => 'No client users match your filter criteria.',
    'no_users_found' => 'No client users found.',
    // Mobile Card View Specific
    'mobile_label_created_at' => 'Created:',

    // Email Subjects
    'admin_upload_subject' => 'New File Uploaded to :app_name',
    'client_upload_subject' => 'Your file upload confirmation - :app_name',

    // Unsubscribe Page
    'unsubscribe_success_heading' => 'Notifications Disabled',
    'unsubscribe_success_message' => 'You will no longer receive email confirmations for file uploads.',
    'unsubscribe_invalid_link' => 'Invalid or Expired Link',
    'unsubscribe_invalid_message' => 'This unsubscribe link is invalid or has expired.',

    // Profile Settings
    'profile_receive_notifications_label' => 'Receive email confirmation upon file upload?',
    'profile_settings_updated' => 'Profile settings updated successfully.', // General update message
    'password_incorrect' => 'The password you entered was incorrect.',
    'upload_notification_subject' => 'New File Uploaded to Your Drive Folder', // Assuming this might exist or be needed
    'unsubscribe_success_message' => 'You have been successfully unsubscribed from upload notifications.',

    // ---- BATCH UPLOAD EMAILS ----

    // Client Batch Email
    'client_batch_upload_subject' => 'File Upload Batch Confirmation - :app_name',
    'client_batch_upload_heading' => 'File Upload Batch Successful',
    'client_batch_upload_body' => '{1} Your file has been successfully uploaded.|[2,*] Your :count files have been successfully uploaded.',
    'uploaded_files_list' => 'Uploaded Files',
    'upload_thank_you' => 'Thank you for using our service.',
    'unsubscribe_link_text' => "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:*",
    'unsubscribe_action_text' => 'Unsubscribe from notifications',
    'want_to_unsubscribe_from_notifications' => 'Want to unsubscribe from notifications?',

    // Admin Batch Email
    'admin_batch_upload_subject' => '{1} New File Uploaded by :userName to :app_name*|[2,*] :count New Files Uploaded by :userName to :app_name',
    'admin_batch_upload_heading' => 'Batch File Upload Notification',
    'admin_batch_upload_body_intro' => '{1} :userName (:userEmail) has uploaded 1 new file.*|[2,*] :userName (:userEmail) has uploaded :count new files.',
    'uploaded_files_details' => 'Uploaded File Details',
    'file_label' => 'File',
    // Re-using existing keys: 'file_name', 'file_size', 'file_message'

    // Two-Factor Authentication
    'two_factor_authentication' => 'Two-Factor Authentication',
    'two_factor_description' => 'Add additional security to your account using two-factor authentication.',
    'setup_2fa_button' => 'Set Up 2FA',
    'disable_2fa_button' => 'Disable 2FA',
    '2fa_enabled' => 'Enabled',
    '2fa_disabled' => 'Disabled',
    '2fa_enabled_message' => 'Two-factor authentication is enabled.',
    'column_2fa_status' => '2FA Status',
    '2fa_verify_title' => 'Two-Factor Authentication',
    '2fa_verify_instruction' => 'Please enter the code from your authenticator app to continue.',
    '2fa_verify_recovery_info' => 'You can also use one of your recovery codes if you cannot access your authenticator app.',
    '2fa_verify_code_label' => 'Authentication Code',
    '2fa_verify_button' => 'Verify',

    // User Management Settings
    'user_management_settings' => 'User Management Settings',
    'public_registration_settings' => 'Public Registration',
    'public_registration_description' => 'Control whether new users can register accounts through the public registration process.',
    'allow_public_registration' => 'Allow Public Registration',
    'domain_access_control' => 'Domain Access Control',
    'domain_access_description' => 'Manage which email domains are allowed or blocked from registering on the platform. These rules only apply when public registration is enabled.',
    'access_control_mode' => 'Access Control Mode',
    'blacklist_mode' => 'Blacklist Mode (block specified domains)',
    'whitelist_mode' => 'Whitelist Mode (allow only specified domains)',
    'domain_rules' => 'Domain Rules',
    'domain_rules_hint' => 'Enter one rule per line. Use * as a wildcard. Examples: *.example.com, user@domain.com, *.co.uk',
    'create_client_user' => 'Create Client User',
    'create_client_description' => 'Manually create a new client user and send them an invitation link.',
    'create_and_invite_button' => 'Create & Send Invitation',
    'email' => 'Email Address',
    'name' => 'Name',

    // Registration Validation Messages
    'public_registration_disabled' => 'Public registration is currently disabled. Please contact the administrator for access.',
    'email_domain_not_allowed' => 'This email domain is not allowed to register. Please use an approved email domain.',

    // User Management Messages
    'client_created' => 'Client user created successfully. An invitation email has been sent.',

    // Email Common Elements
    'email_signature' => 'Thanks',

    // Authentication
    'auth_email' => 'Email',
    'auth_password' => 'Password',
    'auth_remember_me' => 'Remember me',
    'auth_forgot_password' => 'Forgot your password?',
    'auth_log_in' => 'Log in',
    'auth_session_error' => 'Session expired. Please log in again.',

    // Profile
    'profile_title' => 'Profile',
    'profile_information' => 'Profile Information',
    'profile_update' => 'Update Profile',
    'profile_saved' => 'Profile updated successfully.',
    'profile_update_info' => "Update your account's profile information and email address.",
    'profile_name' => 'Name',
    'profile_email' => 'Email',
    'profile_save' => 'Save',
    'profile_email_unverified' => 'Your email address is unverified.',
    'profile_email_verify_resend' => 'Click here to re-send the verification email.',
    'profile_email_verify_sent' => 'A new verification link has been sent to your email address.',

    // Password Update
    'password_update_title' => 'Update Password',
    'password_update_info' => 'Ensure your account is using a long, random password to stay secure.',
    'password_current' => 'Current Password',
    'password_new' => 'New Password',
    'password_confirm' => 'Confirm Password',
    'password_updated' => 'Password updated successfully.',

    // Account Deletion
    'delete_account_title' => 'Delete Account',
    'delete_account_info' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
    'delete_account_confirm_title' => 'Are you sure you want to delete your account?',
    'delete_account_confirm_info' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
    'delete_account_client_confirm_info' => 'Once your account is deleted, all of its resources and data will be permanently deleted. A confirmation email will be sent to verify this action.',
    'delete_account_button' => 'Delete Account',
    'delete_account_cancel' => 'Cancel',
    'delete_account_email_sent_title' => 'Check Your Email',
    'delete_account_email_sent_info' => 'A confirmation email has been sent to your email address. Please check your inbox and follow the link to complete the account deletion process.',
    'delete_account_email_sent_understood' => 'Understood',

    // Email Verification
    'verify_email_title' => 'Verify Your Email Address',
    'verify_email_intro' => 'To upload files to :company_name, please verify your email address by clicking on the link below.',
    'verify_email_sent' => 'A new verification link has been sent to the email address you provided during registration.',
    'verify_email_resend_button' => 'Resend Verification Email',
    'verify_email_button' => 'Verify Email Address',
    'verify_email_ignore' => 'If you did not request this verification, you can safely ignore this email.',

    // Account Deletion Email
    'delete_account_email_title' => 'Confirm Account Deletion',
    'delete_account_email_request' => 'We received a request to delete your account at :app_name.',
    'delete_account_email_warning' => 'Warning: This action cannot be undone. All your data and files will be permanently deleted.',
    'delete_account_email_proceed' => 'If you wish to proceed with account deletion, please click the button below:',
    'delete_account_email_confirm_button' => 'Confirm Account Deletion',
    'delete_account_email_ignore' => 'If you did not request to delete your account, you can safely ignore this email. Your account will remain active.',

    // Navigation
    'nav_dashboard' => 'Dashboard',
    'nav_client_users' => 'Client Users',
    'nav_upload_files' => 'Upload Files',
    'nav_your_files' => 'Your Files',
    'nav_cloud_storage' => 'Cloud Storage',
    'nav_email_label' => 'Email Address',
    'nav_email_placeholder' => 'Enter your email address',
    'nav_validate_email_button' => 'Validate Email',
    'nav_validate_email_sending' => 'Sending...',
    'nav_validation_success' => 'You will receive an email with a link to validate your email address. Clicking the link we send you will allow you to upload files to :company_name.',
    'nav_validation_error' => 'There was an error processing your request. Please try again.',
    'nav_logo_alt' => ':company_name Logo',

    // Email Validation Form
    'email_validation_title' => 'Upload files to :company_name',
    'email_validation_subtitle' => 'Begin by validating your email address.',

    // Common Elements
    'app_name_default' => 'Upload Drive-In',
    'app_name_laravel' => 'Laravel',
    'thanks_signature' => 'Thanks',

    // File Upload
    'file_upload_message_required_title' => 'Message Required',
    'file_upload_message_required_text' => 'Please enter a message before submitting.',
    'file_upload_close_button' => 'Close',

    // Notifications
    'notification_go_home' => 'Go Home',

    // Error Messages
    'error_session_expired' => 'Session expired. Please log in again.',
    'error_generic' => 'An error occurred. Please try again.',
    'error_validation' => 'The given data was invalid.',

    // Success Messages
    'success_generic' => 'Operation completed successfully.',
    'success_saved' => 'Changes saved successfully.',

    // Common Actions
    'action_close' => 'Close',
    'action_cancel' => 'Cancel',
    'action_confirm' => 'Confirm',
    'action_save' => 'Save',
    'action_delete' => 'Delete',
    'action_edit' => 'Edit',
    'action_view' => 'View',

    // Cloud Storage Configuration
    'cloud_storage_configuration' => 'Cloud Storage Configuration',
    'configure_microsoft_teams_storage' => 'Configure Microsoft Teams storage settings and connection details.',
    'configure_microsoft_teams_storage_link' => 'Microsoft Teams Storage Settings',
    'configure_microsoft_teams_storage_link_description' => 'Configure Microsoft Teams storage settings and connection details.',
    'configure_dropbox_storage_link' => 'Dropbox Storage Settings',
    'configure_dropbox_storage_link_description' => 'Configure Dropbox storage settings and connection details.',
    'configure_google_drive_storage_link' => 'Google Drive Storage Settings',
    'configure_google_drive_storage_link_description' => 'Configure Google Drive storage settings and connection details.',
    'connected' => 'Connected',
    'not_connected' => 'Not Connected',
    'connect' => 'Connect',
    'disconnect' => 'Disconnect',
    'client_id' => 'Client ID',
    'client_secret' => 'Client Secret',
    'root_folder_id' => 'Root Folder ID',
    'root_folder' => 'Root Folder',
    'root_folder_description' => 'Choose the folder in your Google Drive where uploaded files will be stored.',
    'save_changes' => 'Save Changes',
    'save_google_app_credentials' => 'Save Google App Credentials',
    'save_root_folder' => 'Save Root Folder',
    'default_storage_provider' => 'Default Storage Provider',
    'select_default_provider_description' => 'Select which storage provider should be used as the default for new uploads.',

    // Cloud Storage Settings
    'settings_updated_successfully' => 'Settings updated successfully.',
    'settings_update_failed' => 'Failed to update settings. Please try again.',
    'cloud_storage_settings' => 'Cloud Storage Settings',
    'provider_configuration' => 'Provider Configuration',
    'provider_connection_status' => 'Connection Status',
    'provider_settings' => 'Provider Settings',
    'provider_credentials' => 'Provider Credentials',
    'provider_root_folder' => 'Root Folder Settings',

    // Folder Selection UI
    'select_folder_prompt' => 'No folder selected*',
    'select_folder' => 'Select Folder',
    'change_folder' => 'Select Different Folder',
    'no_folders_found' => 'No folders found',
    'create_new_folder' => 'New folder name',
    'create_folder' => 'Create Folder',
    'up' => 'Up',
    'cancel' => 'Cancel',
    'confirm' => 'Confirm',

    'employee_upload_page_title' => 'Employee File Drop Page*',
    'employee_select_folder_prompt' => 'Select your Google Drive folder*',

    // Employee Management
    'nav_employee_management' => 'Employee Management*',
    'employee_management_title' => 'Employee Management*',
    'create_employee_title' => 'Create New Employee*',
    'employees_list_title' => 'Employee Users*',
    'button_create_employee' => 'Create Employee*',
    'no_employees_match_filter' => 'No employees match your filter criteria*',
    'no_employees_found' => 'No employees found*',
    'column_reset_url' => 'Reset URL*',
    'button_copy_reset_url' => 'Copy Reset URL*',

    // Public Employee Upload Page
    'drop_files_for' => 'Drop files for :name*',
    'choose_files' => 'Choose files*',
    'optional_message' => 'Message (Optional)*',
    'optional_message_placeholder' => 'Enter an optional message to associate with your files...*',
    'upload' => 'Upload*',

];
