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

    // Admin Email Content
    'admin_upload_heading' => 'New File Upload Notification',
    'admin_upload_body_intro' => ':userName (:userEmail) has uploaded a new file.',
    'file_details' => 'File Details',
    'file_name' => 'Name',
    'file_size' => 'Size',
    'file_message' => 'Message',

    // Client Email Content
    'client_upload_heading' => 'File Upload Successful',
    'client_upload_body_intro' => 'Your file, :fileName, has been successfully uploaded.',
    'client_upload_body_thanks' => 'Thank you for using our service.',
    'unsubscribe_button' => 'Stop Receiving These Emails',
    'unsubscribe_text_prefix' => "If you no longer wish to receive these confirmations, you can click the button above or visit:",

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
    'unsubscribe_action_text' => 'Unsubscribe', // Note: Removed the trailing * as it's part of the sentence structure in the view

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

];
