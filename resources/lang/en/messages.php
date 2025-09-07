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
    'nav_dashboard' => 'Dashboard',
    'nav_client_users' => 'Client Management',
    'nav_cloud_storage' => 'Cloud Storage',
    'nav_employee_users' => 'Employee Users',
    'nav_security_settings' => 'Security Settings',
    'nav_access_control' => 'Access Control',
    'nav_security_policies' => 'Security Policies',
    'nav_your_files' => 'Your Files',
    'nav_upload_files' => 'Upload Files',

    // Admin Settings Page
    'app_settings' => 'Branding Settings',
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
    'google_drive_disconnected_success' => 'Google Drive disconnected successfully.',
    'uploaded_files_title' => 'Uploaded Files',
    'toggle_columns_title' => 'Show/Hide Columns:',
    'column_file_name' => 'File Name',
    'column_user' => 'User',
    'column_size' => 'Size',
    'column_status' => 'Status',
    'column_message' => 'Message',

    // Recovery Strategy Messages
    'recovery_strategy_token_refresh' => 'Attempting to refresh authentication tokens',
    'recovery_strategy_network_retry' => 'Retrying after network connectivity issues',
    'recovery_strategy_quota_wait' => 'Waiting for API quota to be restored',
    'recovery_strategy_service_retry' => 'Retrying after service becomes available',
    'recovery_strategy_health_check_retry' => 'Performing health check and retry',
    'recovery_strategy_user_intervention_required' => 'Manual user intervention required',
    'recovery_strategy_no_action_needed' => 'No action needed, connection is healthy',
    'recovery_strategy_unknown' => 'Unknown recovery strategy',

    // Token Status Service Messages
    'token_status_not_connected' => 'No token found - account not connected',
    'token_status_requires_intervention' => 'Token requires manual reconnection due to repeated failures',
    'token_status_expired_refreshable' => 'Token expired but can be automatically refreshed',
    'token_status_expired_manual' => 'Token expired and requires manual reconnection',
    'token_status_expiring_soon' => 'Token will be automatically renewed soon',
    'token_status_healthy_with_warnings' => 'Token healthy but has :count recent refresh failure(s)',
    'token_status_healthy' => 'Token is healthy and valid',
    'token_status_scheduled_now' => 'Scheduled now',
    'token_status_less_than_minute' => 'Less than 1 minute',
    'token_status_minute' => 'minute',
    'token_status_minutes' => 'minutes',
    'token_status_hour' => 'hour',
    'token_status_hours' => 'hours',
    'token_status_day' => 'day',
    'token_status_days' => 'days',
    'token_status_last_error_intervention' => 'Token requires manual reconnection due to repeated failures',
    'token_status_last_error_generic' => 'Token refresh failed - will retry automatically',
    'token_status_manual_refresh_success' => 'Token refreshed successfully',
    'token_status_manual_refresh_failed' => 'Manual token refresh failed',
    'column_uploaded_at' => 'Uploaded At',
    'column_actions' => 'Actions',
    'columns' => 'Columns',
    'show_columns' => 'Show Columns',
    'reset_columns' => 'Reset to Default',
    'filter_files_label' => 'Filter files', // sr-only
    'filter_files_placeholder' => 'Filter by filename, user, or message...',
    'view_button' => 'View',
    'delete_button' => 'Delete',
    'status_uploaded' => 'Uploaded', // Was "Uploaded to Drive"
    'status_pending' => 'Pending',
    'mobile_label_uploaded_at' => 'Uploaded at', // Was "Uploaded"
    'mobile_label_message' => 'Message',
    'no_message_provided' => 'No message provided',
    'message_label' => 'Message',
    'message_section_title' => 'Client Message',
    'message_section_empty' => 'No additional message was provided with this upload',
    'no_files_found' => 'No files match your filter criteria.',
    'delete_modal_title' => 'Delete File',
    'delete_modal_text' => 'Are you sure you want to delete this file? This action cannot be undone.',
    'delete_modal_confirm_button' => 'Confirm Delete',
    'delete_modal_cancel_button' => 'Cancel',

    // Admin Client Management Page
    'client_management_title' => 'Client User Management',
    'create_user_title' => 'Create New Client User',
    'create_user_error_intro' => 'Please correct the following errors:',
    'label_name' => 'Name',
    'label_email' => 'Email Address',
    'button_create_user' => 'Create User',
    'button_create_user_tooltip' => 'Create user account without sending invitation email',
    'button_create_and_invite' => 'Create & Send Invitation',
    'button_create_and_invite_tooltip' => 'Create user account and automatically send invitation email',
    'dual_action_help_text' => 'Choose "Create User" to create an account without sending an email, or "Create & Send Invitation" to create and automatically email the login link.',
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

    // ---- BATCH UPLOAD EMAILS ----

    // Client Batch Email (count-aware)
    'client_batch_upload_subject' => '{0} Upload confirmation — :app_name|{1} Upload confirmation — :app_name|[2,*] Upload confirmation (:count files) — :app_name',
    'client_batch_upload_heading' => '{0} No files uploaded|{1} File upload successful|[2,*] File uploads successful',
    'client_batch_upload_body' => '{0} No files were uploaded.|{1} Your file has been successfully uploaded.|[2,*] Your :count files have been successfully uploaded.',
    // Show “Recipient” vs “Recipients” based on how many names you pass
    'upload_recipient_line' => '{1} Recipient: :names|[2,*] Recipients: :names',
    'uploaded_files_list' => 'Uploaded Files',
    'upload_thank_you' => 'Thank you for using our service.',
    'unsubscribe_link_text' => 'If you’re having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser:',
    'unsubscribe_action_text' => 'Unsubscribe from notifications',
    'want_to_unsubscribe_from_notifications' => 'Want to unsubscribe from notifications?',

    // Admin Batch Email
    'admin_batch_upload_subject' => '{0} No new files.|{1} New file uploaded by :userName to :app_name|[2,*] :count new files uploaded by :userName to :app_name',
    'admin_batch_upload_heading' => 'File Upload Notification',
    'admin_batch_upload_body_intro' => '{0} No new files were uploaded.|{1} :userName (:userEmail) has uploaded 1 new file.|[2,*] :userName (:userEmail) has uploaded :count new files.',
    'uploaded_files_details' => 'Uploaded File Details',
    'file_label' => 'File',
    'file_name' => 'File name',
    'file_size' => 'Size',
    'file_message' => 'Message',

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

    // Security and Access Settings
    'security_and_access_settings' => 'Security and Access Settings',
    'security_settings' => 'Security Settings',
    'access_control_settings' => 'Access Control Settings',
    'public_registration_settings' => 'Public Registration Security',
    'public_registration_description' => 'Control whether new users can register accounts through the public registration process. This setting affects the overall security posture of your application.',
    'allow_public_registration' => 'Enable Public Registration',
    'domain_access_control' => 'Email Domain Access Control',
    'domain_access_description' => 'Manage which email domains are allowed or blocked from registering on the platform. These security rules only apply when public registration is enabled.',
    'access_control_mode' => 'Security Access Control Mode',
    'blacklist_mode' => 'Blacklist Mode (block specified domains for security)',
    'whitelist_mode' => 'Whitelist Mode (allow only approved domains)',
    'domain_rules' => 'Security Domain Rules',
    'domain_rules_hint' => 'Enter one rule per line. Use * as a wildcard. Examples: *.example.com, user@domain.com, *.co.uk',

    // Security Settings Page Specific
    'security_settings_title' => 'Security and Access Settings',
    'security_settings_description' => 'Manage security policies and access control settings for your application.',
    'registration_security_title' => 'Registration Security',
    'registration_security_description' => 'Control how new users can register and access your application.',
    'domain_security_title' => 'Domain Security',
    'domain_security_description' => 'Manage email domain restrictions for enhanced security.',
    'security_policy_updated' => 'Security policy updated successfully.',
    'access_control_updated' => 'Access control settings updated successfully.',
    'security_configuration' => 'Security Configuration',
    'access_management' => 'Access Management',
    'breadcrumb_security_settings' => 'Security Settings',
    'breadcrumb_access_control' => 'Access Control',
    'breadcrumb_admin' => 'Admin',

    // Security and Registration Validation Messages
    'public_registration_disabled' => 'New user registration is currently disabled. If you already have an account, please try again or contact support.',
    'email_domain_not_allowed' => 'This email domain is not allowed for new registrations. If you already have an account, please try again or contact support.',
    'security_settings_saved' => 'Security settings have been updated successfully.',
    
    // Enhanced verification messages for existing vs new users
    'existing_user_verification_sent' => 'Verification email sent to your existing account. Please check your inbox.',
    'new_user_verification_sent' => 'Verification email sent. Please check your inbox to complete registration.',
    'registration_temporarily_unavailable' => 'Unable to process registration at this time. Please try again later.',
    'access_control_rules_updated' => 'Access control rules have been updated successfully.',
    'registration_security_updated' => 'Registration security settings have been updated successfully.',
    'domain_rules_validation_error' => 'Please check your domain rules format. Each rule should be on a separate line.',
    'security_mode_required' => 'Please select a security access control mode.',
    'invalid_domain_rule' => 'Invalid domain rule format. Use patterns like *.example.com or user@domain.com',

    // User Management Messages
    'client_created' => 'Client user created successfully. You can provide them with their login link manually.',
    'client_created_and_invited' => 'Client user created and invitation sent successfully.',

    // Admin User Creation Messages
    'admin_user_created' => 'Client user created successfully. You can provide them with their login link manually.',
    'admin_user_created_and_invited' => 'Client user created and invitation sent successfully.',
    'admin_user_created_email_failed' => 'Client user created successfully, but invitation email failed to send. You can provide them with their login link manually.',
    'admin_user_creation_failed' => 'Failed to create client user. Please try again.',

    // Employee Client Creation Messages
    'employee_client_created' => 'Client user created successfully. You can provide them with their login link manually.',
    'employee_client_created_and_invited' => 'Client user created and invitation sent successfully.',
    'employee_client_created_email_failed' => 'Client user created successfully, but invitation email failed to send. You can provide them with their login link manually.',
    'employee_client_creation_failed' => 'Failed to create client user. Please try again.',

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

    // Role-Based Email Verification
    // Admin Verification
    'admin_verify_email_subject' => 'Verify Your Administrator Email Address',
    'admin_verify_email_title' => 'Verify Your Administrator Email Address',
    'admin_verify_email_intro' => 'Welcome to the :company_name file management system. As an administrator, you have full access to manage users, configure cloud storage, and oversee all file uploads. Please verify your email address to complete your admin account setup.',
    'admin_verify_email_button' => 'Verify Administrator Access',

    // Employee Verification  
    'employee_verify_email_subject' => 'Verify Your Employee Email Address',
    'employee_verify_email_title' => 'Verify Your Employee Email Address',
    'employee_verify_email_intro' => 'Welcome to :company_name! As an employee, you can receive client file uploads directly to your Google Drive and manage your own client relationships. Please verify your email address to start receiving client files.',
    'employee_verify_email_button' => 'Verify Employee Access',

    // Client Verification
    'client_verify_email_subject' => 'Verify Your Email Address',
    'client_verify_email_title' => 'Verify Your Email Address', 
    'client_verify_email_intro' => 'To upload files to :company_name, please verify your email address by clicking on the link below. Once verified, you\'ll be able to securely upload files that will be delivered directly to the appropriate team member.',
    'client_verify_email_button' => 'Verify Email Address',

    // Account Deletion Email
    'delete_account_email_title' => 'Confirm Account Deletion',
    'delete_account_email_request' => 'We received a request to delete your account at :app_name.',
    'delete_account_email_warning' => 'Warning: This action cannot be undone. All your data and files will be permanently deleted.',
    'delete_account_email_proceed' => 'If you wish to proceed with account deletion, please click the button below:',
    'delete_account_email_confirm_button' => 'Confirm Account Deletion',
    'delete_account_email_ignore' => 'If you did not request to delete your account, you can safely ignore this email. Your account will remain active.',

    // Navigation
    // (Keys above already defined earlier) Removed duplicates: nav_dashboard, nav_client_users, nav_upload_files, nav_your_files, nav_cloud_storage
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

    // Performance Optimized Health Validator Messages
    'health_validation_failed' => 'Validation failed: :message',
    'health_user_not_found' => 'User not found',
    'health_batch_processing_failed' => 'Batch processing failed: :message',
    'health_validation_rate_limited' => 'Health validation rate limited - please try again later',

    // Cloud Storage Configuration
    'cloud_storage_configuration' => 'Cloud Storage Configuration',
    'configure_microsoft_teams_storage' => 'Configure Microsoft Teams storage settings and connection details.',
    'configure_microsoft_teams_storage_link' => 'Microsoft Teams Storage Settings',
    'configure_microsoft_teams_storage_link_description' => 'Configure Microsoft Teams storage settings and connection details.',

    'configure_google_drive_storage_link' => 'Google Drive Storage Settings',
    'configure_google_drive_storage_link_description' => 'Configure Google Drive storage settings and connection details.',
    'connected' => 'Connected',
    'not_connected' => 'Not Connected',
    'connect' => 'Connect',
    'disconnect' => 'Disconnect',
    'client_id' => 'My Client ID',
    'client_secret' => 'My Client Secret',
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
    'select_folder_prompt' => 'No folder selected',
    'select_folder' => 'Select Folder',
    'change_folder' => 'Select Different Folder',
    'no_folders_found' => 'No folders found',
    'create_new_folder' => 'New folder name',
    'create_folder' => 'Create Folder',
    'up' => 'Up',
    'cancel' => 'Cancel',
    'confirm' => 'Confirm',

    'employee_upload_page_title' => 'Employee File Drop Page',
    'employee_select_folder_prompt' => 'Select your Google Drive folder',

    // Employee Management
    'nav_employee_management' => 'Employee Management',
    'employee_management_title' => 'Employee Management',
    'create_employee_title' => 'Create New Employee',
    'employees_list_title' => 'Employee Users',
    'button_create_employee' => 'Create Employee',
    'no_employees_match_filter' => 'No employees match your filter criteria',
    'no_employees_found' => 'No employees found',
    'column_reset_url' => 'Reset URL',
    'button_copy_reset_url' => 'Copy Reset URL',

    // Employee Creation Messages
    'employee_created_success' => 'Employee user created successfully.',
    'employee_created_and_invited_success' => 'Employee user created and verification email sent successfully.',
    'employee_created_email_failed' => 'Employee user created but verification email failed to send. Please check the logs.',
    'employee_creation_failed' => 'Failed to create employee user. Please check the logs.',

    // Public Employee Upload Page
    'drop_files_for' => 'Drop files for :name',
    'choose_files' => 'Choose files',
    'optional_message' => 'Message (Optional)',
    'optional_message_placeholder' => 'Enter an optional message to associate with your files...',
    'upload' => 'Upload',

    // Profile Status Messages
    'profile_update_success' => 'Profile updated successfully.',

    // Account Deletion Messages
    'account_deletion_request_failed' => 'Failed to process deletion request. Please try again.',
    'account_deletion_link_invalid' => 'The deletion confirmation link is invalid or has expired.',
    'account_deletion_verification_invalid' => 'Invalid or expired verification link.',
    'account_deletion_user_invalid' => 'Invalid user account.',
    'account_deletion_success' => 'Your account and all associated data have been permanently deleted.',
    'account_deletion_error' => 'An error occurred while deleting your account. Please try again or contact support.',
    'account_deletion_unexpected_error' => 'An unexpected error occurred. Please try again or contact support.',

    // Google Drive OAuth Error Messages
    'oauth_authorization_code_missing' => 'Authorization code not provided.',
    'oauth_state_parameter_missing' => 'State parameter missing.',
    'oauth_state_parameter_invalid' => 'Invalid state parameter.',
    'oauth_user_not_found' => 'User not found.',
    'oauth_connection_validation_failed' => 'Connection established but validation failed. Please try reconnecting again.',

    // Client Dashboard
    'client_dashboard_title' => 'My Dashboard',

    // Employee Dashboard
    'employee_dashboard_title' => 'Employee Dashboard',
    'employee_management' => 'Employee Management',

    // Client Upload Page
    'select_recipient' => 'Select Upload Recipient',
    'select_recipient_help' => 'Choose which company representative should receive this upload. Files will be stored in their Google Drive.',

    // Client Relationships
    'client_relationships_title' => 'Client Relationships',
    'client_relationships_description' => 'View and manage your client relationships.',
    'primary_client' => 'Primary',
    'primary_contact_badge' => 'Primary Contact',
    'no_client_relationships' => 'You have no client relationships yet.',
    'filter_by_primary_contact' => 'Filter',
    'filter_all_clients' => 'All Clients',
    'filter_primary_contact_only' => 'Only Primary Clients',

    // File Upload Messages
    'no_valid_upload_destination' => 'No valid upload destination found. Please contact support.',
    'no_file_uploaded' => 'No file was uploaded or the upload initialization failed.',
    'failed_to_process_upload' => 'Failed to process the upload request. Please try again.',
    'chunk_received_successfully' => 'Chunk received successfully.',
    'failed_to_save_uploaded_file' => 'Failed to save the uploaded file. Please try again.',
    'failed_to_record_file_upload' => 'Failed to record the file upload. Please try again.',

    // Employee Client Management
    'nav_client_management' => 'Client Management',
    'client_management_title' => 'Client Management',
    'my_clients_title' => 'My Clients',
    // Removed duplicates: create_client_user, create_client_description
    'create_client_error_intro' => 'Please correct the following errors:',
    'client_created_success' => 'Client user created and invitation sent successfully.',
    // Removed duplicate: create_and_invite_button
    'toggle_client_columns_title' => 'Show/Hide Columns:',
    'filter_clients_label' => 'Filter clients',
    'filter_clients_placeholder' => 'Filter by name or email...',
    'no_clients_match_filter' => 'No clients match your filter criteria.',
    'no_clients_found' => 'No clients found.',
    'manage_clients' => 'Manage Clients',
    'actions' => 'Actions',

    // Google Drive Connection for Employees
    'google_drive_connection' => 'Google Drive Connection',
    // Using earlier definition: google_drive_connected
    // Removed duplicate: google_drive_not_connected
    'google_drive_app_not_configured' => 'Google Drive App Not Configured',
    'configure_google_drive_app_first' => 'Configure your Google Drive app credentials in Cloud Storage settings first.',
    // Keeping single definition above; removed duplicate: google_drive_not_configured
    'contact_admin_to_configure_google_drive' => 'Contact your administrator to configure Google Drive integration.',
    'configure_cloud_storage' => 'Configure Cloud Storage',
    'client_uploads_will_go_to_your_drive' => 'Client uploads will be saved to your Google Drive.',
    'connect_drive_to_receive_uploads' => 'Connect your Google Drive to receive client uploads directly.',
    'connect_google_drive' => 'Connect Google Drive',
    // Using earlier definition: disconnect
    'google_drive_disconnected' => 'Google Drive disconnected successfully.',
    'google_drive_disconnect_failed' => 'Failed to disconnect Google Drive. Please try again.',
    'google_drive_connection_failed' => 'Failed to connect Google Drive. Please try again.',
    'your_upload_page' => 'Your Upload Page',
    'copy_url' => 'Copy URL',
    'copied' => 'Copied!',
    'share_this_url_with_clients' => 'Share this URL with your clients to receive file uploads.',

    // Public Employee Upload Page
    'upload_files_for_employee' => 'Upload Files for :name',
    'upload_files_description' => 'Use this page to upload files that will be delivered directly to the employee.',
    'employee_drive_not_connected' => 'Employee Google Drive Not Connected',
    'files_will_go_to_admin_drive' => 'Files will be saved to the admin\'s Google Drive as a fallback.',
    'ready_to_receive_files' => 'Ready to Receive Files',
    'files_will_go_to_employee_drive' => 'Files will be saved directly to the employee\'s Google Drive.',
    'your_email' => 'Your Email Address',
    'email_for_organization' => 'This helps us organize your files and send you confirmations.',
    'max_file_size_10mb' => 'Maximum file size: 10MB per file.',
    'files_uploaded_successfully' => 'Files uploaded successfully! They will be processed and saved to Google Drive shortly.',

    // Authentication for Employee Upload Pages
    'email_validation_required_for_upload' => 'Please verify your email address to upload files.',
    'authentication_required' => 'Authentication Required',
    'validate_email_to_upload_files' => 'Verify your email address to access the upload form.',
    'validation_email_sent' => 'Verification email sent!',
    'check_email_and_click_link' => 'Please check your email and click the verification link to continue.',

    // File Manager Interface
    'file_management_title' => 'File Management',
    'file_management_description' => 'Manage uploaded files with bulk operations, preview, and download capabilities.',
    'total_files' => 'Total Files',
    'pending_uploads' => 'Pending',
    'total_size' => 'Total Size',
    'select_all' => 'Select All',
    'delete_selected' => 'Delete Selected',
    'download_selected' => 'Download Selected',
    'search_files_placeholder' => 'Search files, users, or messages...',
    'all_statuses' => 'All Statuses',
    'select_file' => 'Select',
    'filename' => 'Filename',
    'uploaded_by' => 'Uploaded By',
    'size' => 'Size',
    'date' => 'Date',
    'preview' => 'Preview',
    'download' => 'Download',
    'delete' => 'Delete',
    // Using earlier definition: no_files_found
    'no_files_description' => 'No files match your current search or filter criteria.',
    'file_preview' => 'File Preview',
    'close' => 'Close',
    'loading_preview' => 'Loading preview...',
    'preview_not_available' => 'Preview not available',
    'preview_not_available_description' => 'This file type cannot be previewed in the browser.',
    'delete_file' => 'Delete File',
    'delete_file_confirmation' => 'Are you sure you want to delete this file?',
    'delete_file_warning' => 'This action cannot be undone. The file will be removed from both local storage and Google Drive.',
    // Using earlier definition: cancel
    'deleting' => 'Deleting...',
    'delete_error' => 'An error occurred while deleting the file.',
    'delete_selected_files' => 'Delete Selected Files',
    'bulk_delete_confirmation' => 'Are you sure you want to delete the selected files?',
    'files_selected' => 'files selected',
    'bulk_delete_warning' => 'This action cannot be undone. All selected files will be removed from both local storage and Google Drive.',

    // Token Monitoring Dashboard
    'token_monitoring' => [
        'dashboard_title' => 'Token Monitoring Dashboard',
        'dashboard_description' => 'Monitor Google Drive token health, refresh operations, and system performance metrics.',
        'metrics_reset_success' => 'Metrics reset for provider: :provider',
        'overview_title' => 'System Overview',
        'performance_metrics_title' => 'Performance Metrics',
        'token_status_title' => 'Token Status Summary',
        'recent_operations_title' => 'Recent Operations',
        'health_trends_title' => 'Health Trends',
        'user_statistics_title' => 'User Statistics',
        'system_status_title' => 'System Status',
        'recommendations_title' => 'Recommendations',
        'export_data' => 'Export Data',
        'reset_metrics' => 'Reset Metrics',
        'refresh_dashboard' => 'Refresh Dashboard',
        'last_updated' => 'Last Updated',
        'total_users' => 'Total Users',
        'connected_users' => 'Connected Users',
        'success_rate' => 'Success Rate',
        'average_refresh_time' => 'Average Refresh Time',
        'active_alerts' => 'Active Alerts',
        'overall_health' => 'Overall Health',
        'tokens_expiring_soon' => 'Expiring Soon',
        'tokens_requiring_attention' => 'Requiring Attention',
        'healthy' => 'Healthy',
        'warning' => 'Warning',
        'critical' => 'Critical',
        'unknown' => 'Unknown',
        'degraded' => 'Degraded',
        'unhealthy' => 'Unhealthy',
        'queue_health' => 'Queue Health',
        'cache_health' => 'Cache Health',
        'database_health' => 'Database Health',
        'api_health' => 'API Health',
        'overall_system_health' => 'Overall System Health',
        'last_maintenance' => 'Last Maintenance',
        'next_maintenance' => 'Next Maintenance',
        'no_alerts' => 'No active alerts',
        'view_details' => 'View Details',
        'time_period' => 'Time Period',
        'last_hour' => 'Last Hour',
        'last_6_hours' => 'Last 6 Hours',
        'last_24_hours' => 'Last 24 Hours',
        'last_week' => 'Last Week',
        'provider' => 'Provider',
        'google_drive' => 'Google Drive',
        'microsoft_teams' => 'Microsoft Teams',
        'dropbox' => 'Dropbox',
        'loading' => 'Loading...',
        'loading_dashboard_data' => 'Loading dashboard data...',
        'total_users_label' => 'total users',
        'token_refresh_operations' => 'Token refresh operations',
        'milliseconds' => 'Milliseconds',
        'overall_system_health' => 'Overall System Health',
        'token_refresh' => 'Token Refresh',
        'api_connectivity' => 'API Connectivity',
        'cache_performance' => 'Cache Performance',
        'valid' => 'Valid',
        'expiring_soon' => 'Expiring Soon',
        'need_attention' => 'Need Attention',
        'error_breakdown' => 'Error Breakdown',
        'no_errors_in_period' => 'No errors in selected time period',
        'time' => 'Time',
        'user' => 'User',
        'operation' => 'Operation',
        'status' => 'Status',
        'duration' => 'Duration',
        'details' => 'Details',
        'success' => 'Success',
        'error_loading_dashboard' => 'Error Loading Dashboard',
        'try_again' => 'Try Again',
        'recommended_actions' => 'Recommended Actions',
    ],
    'bulk_delete_confirmation' => 'Selected files will be removed from both local storage and Google Drive.',
    'delete_all' => 'Delete All',
    'bulk_delete_error' => 'An error occurred during bulk deletion.',

    // Enhanced Validation Messages
    'validation_name_required' => 'The name field is required.',
    'validation_name_string' => 'The name must be a valid text string.',

    // Health Status Messages
    'health_status_healthy' => 'Healthy',
    'health_status_authentication_required' => 'Authentication Required',
    'health_status_connection_issues' => 'Connection Issues',
    'health_status_not_connected' => 'Not Connected',
    'health_status_authentication_error' => 'Authentication Error',
    'health_status_connection_error' => 'Connection Error',
    'health_status_token_error' => 'Token Error',
    'health_status_api_error' => 'API Error',
    'health_status_token_validation_failed' => 'Token validation failed',
    'health_status_api_connectivity_test_failed' => 'API connectivity test failed',
    'validation_name_max' => 'The name may not be greater than 255 characters.',
    'validation_email_required' => 'The email field is required.',
    'validation_email_format' => 'The email must be a valid email address.',
    'validation_action_required' => 'Please select an action (Create User or Create & Send Invitation).',
    'validation_action_invalid' => 'The selected action is invalid.',

    // Team Assignment Validation Messages
    'validation_team_members_required' => 'At least one team member must be assigned to this client.',
    'validation_team_members_min' => 'At least one team member must be assigned to this client.',
    'validation_team_member_invalid' => 'One or more selected team members are invalid.',

    // Primary Contact Validation Messages
    'validation_primary_contact_required' => 'A primary contact must be selected.',
    'validation_primary_contact_invalid' => 'The selected primary contact is invalid.',
    'validation_primary_contact_not_in_team' => 'The primary contact must be one of the selected team members.',

    // Primary Contact UI Text
    'primary_contact' => 'Primary Contact',
    'primary_contact_explanation_title' => 'About Primary Contact',
    'primary_contact_explanation_text' => 'The primary contact receives file uploads and notifications when clients don\'t select a specific recipient. Only one team member can be the primary contact.',
    'current_primary_contact' => 'Current Primary Contact',
    'make_primary' => 'Make Primary',
    'assign_team_members' => 'Assign Team Members',
    'select_team_members_help' => 'Select which team members should have access to this client\'s files and information.',
    'update_team_access' => 'Update Team Access',
    'no_team_members_available' => 'No team members available for assignment.',

    // Primary Contact Modal Text
    'change_primary_contact_modal_title' => 'Change Primary Contact',
    'change_primary_contact_modal_text' => 'Are you sure you want to make :name the primary contact for this client? This person will receive all file uploads and notifications when no specific recipient is selected.',
    'yes_change_primary_contact' => 'Yes, Change Primary Contact',
    'primary_contact_responsibility_uploads' => 'They will receive all file uploads when no specific recipient is selected',
    'primary_contact_responsibility_notifications' => 'They will receive all upload notifications for this client',
    'primary_contact_responsibility_unique' => 'Only one team member can be the primary contact at a time',

    // Dashboard Primary Contact Statistics
    'primary_contact_for' => 'Primary Contact For',
    'primary_contact_clients_singular' => 'Client',
    'primary_contact_clients_plural' => 'Clients',
    'primary_contact_responsibility_description' => 'You receive files and notifications when clients don\'t select a specific recipient.',
    'view_primary_contact_clients' => 'View clients',
    'no_primary_contact_assignments' => 'No primary contact assignments',

    // Primary Contact Success Messages
    'primary_contact_updated_success' => 'Primary contact updated successfully.',
    'team_assignments_updated_success' => 'Team assignments updated successfully.',

    // Additional UI Text
    'what_this_means' => 'What this means:',

    // Primary Contact Validation Messages (already defined above, keeping for reference)
    'validation_primary_contact_required' => 'A primary contact must be selected.',
    'validation_primary_contact_invalid' => 'The selected primary contact is invalid.',
    'validation_primary_contact_not_in_team' => 'The primary contact must be one of the selected team members.',

    // Cloud Storage Error Messages
    'status_error' => 'Error',
    'retry' => 'Retry',
    'retrying' => 'Retrying',
    'retry_selected' => 'Retry Selected',
    'cloud_storage_error' => 'Cloud Storage Error',
    'error_recoverable' => 'Recoverable Error',
    'error_requires_intervention' => 'Requires Intervention',
    'error_severity_low' => 'Low Severity',
    'error_severity_medium' => 'Medium Severity',
    'error_severity_high' => 'High Severity',
    'error_severity_critical' => 'Critical Severity',

    // Token Refresh Error Types - Descriptions
    'token_refresh_error_network_timeout' => 'Network timeout during token refresh',
    'token_refresh_error_invalid_refresh_token' => 'Invalid refresh token provided',
    'token_refresh_error_expired_refresh_token' => 'Refresh token has expired',
    'token_refresh_error_api_quota_exceeded' => 'API quota exceeded during token refresh',
    'token_refresh_error_service_unavailable' => 'OAuth service temporarily unavailable',
    'token_refresh_error_unknown_error' => 'Unknown token refresh error',

    // Token Refresh Error Types - User Notifications
    'token_refresh_notification_network_timeout' => 'Network issues prevented token refresh. Retrying automatically.',
    'token_refresh_notification_invalid_refresh_token' => 'Your Google Drive connection is invalid. Please reconnect your account.',
    'token_refresh_notification_expired_refresh_token' => 'Your Google Drive connection has expired. Please reconnect your account.',
    'token_refresh_notification_api_quota_exceeded' => 'Google Drive API limit reached. Token refresh will retry automatically.',
    'token_refresh_notification_service_unavailable' => 'Google Drive service is temporarily unavailable. Retrying automatically.',
    'token_refresh_notification_unknown_error' => 'An unexpected error occurred during token refresh. Please contact support if this persists.',

    // Google Drive Provider-Specific Error Messages
    'google_drive_error_token_expired' => 'Your Google Drive connection has expired. Please reconnect your Google Drive account to continue uploading files.',
    'google_drive_error_insufficient_permissions' => 'Insufficient Google Drive permissions. Please reconnect your account and ensure you grant full access to Google Drive.',
    'google_drive_error_api_quota_exceeded' => 'Google Drive API limit reached. Your uploads will resume automatically in :time. No action is required.',
    'google_drive_error_storage_quota_exceeded' => 'Your Google Drive storage is full. Please free up space in your Google Drive account or upgrade your storage plan.',
    'google_drive_error_file_not_found' => 'The file \':filename\' could not be found in Google Drive. It may have been deleted or moved.',
    'google_drive_error_folder_access_denied' => 'Access denied to the Google Drive folder. Please check your folder permissions or reconnect your account.',
    'google_drive_error_invalid_file_type' => 'The file type of \':filename\' is not supported by Google Drive. Please try a different file format.',

    // Cloud Storage Status Messages (from CloudStorageStatusMessages class)
    'cloud_storage_rate_limited' => 'Too many token refresh attempts. Please try again later.',
    'cloud_storage_auth_required' => 'Authentication required. Please reconnect your account.',
    'cloud_storage_connection_healthy' => 'Connected and working properly',
    'cloud_storage_not_connected' => 'Account not connected. Please set up your cloud storage connection.',
    'cloud_storage_connection_issues' => 'Connection issue detected. Please test your connection.',
    'cloud_storage_multiple_failures' => 'Multiple connection failures detected. Please check your account settings.',
    'cloud_storage_status_unknown' => 'Status unknown. Please refresh or contact support.',
    'cloud_storage_retry_time_message' => '{1} Too many attempts. Please try again in :minutes minute.|[2,*] Too many attempts. Please try again in :minutes minutes.',
    'cloud_storage_retry_seconds_message' => '{1} Too many attempts. Please try again in :seconds second.|[2,*] Too many attempts. Please try again in :seconds seconds.',
    'cloud_storage_retry_persistent_message' => '{1} Persistent connection issues with :provider. Please try again in :minutes minute.|[2,*] Persistent connection issues with :provider. Please try again in :minutes minutes.',
    'cloud_storage_retry_multiple_message' => '{1} Multiple connection attempts detected. Please try again in :minutes minute.|[2,*] Multiple connection attempts detected. Please try again in :minutes minutes.',

    // Cloud Storage Error Messages (from CloudStorageErrorMessageService class)
    'cloud_storage_token_expired' => 'Your :provider connection has expired. Please reconnect your account to continue.',
    'cloud_storage_token_refresh_rate_limited' => 'Too many :provider connection attempts. Please wait before trying again to avoid extended delays.',
    'cloud_storage_invalid_credentials' => 'Invalid :provider credentials. Please check your configuration and reconnect your account.',
    'cloud_storage_insufficient_permissions' => 'Insufficient :provider permissions. Please reconnect your account and ensure you grant full access.',
    'cloud_storage_api_quota_exceeded' => ':provider API limit reached. Your operations will resume automatically when the limit resets.',
    'cloud_storage_storage_quota_exceeded' => 'Your :provider storage is full. Please free up space or upgrade your storage plan.',
    'cloud_storage_network_error' => 'Network connection issue prevented the :provider operation. Please check your internet connection and try again.',
    'cloud_storage_service_unavailable' => ':provider is temporarily unavailable. Please try again in a few minutes.',
    'cloud_storage_timeout' => 'The :provider :operation timed out. This is usually temporary - please try again.',
    'cloud_storage_file_not_found' => 'The file \':filename\' could not be found in :provider. It may have been deleted or moved.',
    'cloud_storage_folder_access_denied' => 'Access denied to the :provider folder. Please check your folder permissions.',
    'cloud_storage_invalid_file_type' => 'The file type of \':filename\' is not supported by :provider. Please try a different file format.',
    'cloud_storage_file_too_large' => 'The file \':filename\' is too large for :provider. Please reduce the file size and try again.',
    'cloud_storage_invalid_file_content' => 'The file \':filename\' appears to be corrupted. Please try uploading the file again.',
    'cloud_storage_provider_not_configured' => ':provider is not properly configured. Please check your settings and try again.',
    'cloud_storage_unknown_error' => 'An unexpected error occurred with :provider. Please try again or contact support if the problem persists.',
    'cloud_storage_default_error' => 'An error occurred during the :provider :operation. Please try again.',

    // Connection Issue Context Messages
    'cloud_storage_persistent_failures' => 'Persistent connection failures detected. Please check your :provider account settings and network connection.',
    'cloud_storage_multiple_token_refresh_attempts' => 'Multiple token refresh attempts detected. Please wait a few minutes before trying again.',
    'cloud_storage_retry_with_time' => 'Too many token refresh attempts. Please wait :minutes more minute before trying again.|Too many token refresh attempts. Please wait :minutes more minutes before trying again.',

    // Recovery Instructions
    'recovery_instructions_token_expired' => [
        'Go to Settings → Cloud Storage',
        'Click "Reconnect :provider"',
        'Complete the authorization process',
        'Retry your operation'
    ],
    'recovery_instructions_rate_limited' => [
        'Wait for the rate limit to reset',
        'Avoid repeatedly clicking connection test buttons',
        'Operations will resume automatically when the limit resets',
        'Contact support if the issue persists beyond the expected time'
    ],
    'recovery_instructions_insufficient_permissions' => [
        'Go to Settings → Cloud Storage',
        'Click "Reconnect :provider"',
        'Ensure you grant full access when prompted',
        'Check that you have the necessary permissions'
    ],
    'recovery_instructions_storage_quota_exceeded' => [
        'Free up space in your :provider account',
        'Empty your :provider trash',
        'Consider upgrading your :provider storage plan',
        'Contact your administrator if using a business account'
    ],
    'recovery_instructions_api_quota_exceeded' => [
        'Wait for the quota to reset (usually within an hour)',
        'Operations will resume automatically',
        'Consider spreading large operations across multiple days'
    ],
    'recovery_instructions_network_error' => [
        'Check your internet connection',
        'Try again in a few minutes',
        'Contact your network administrator if the problem persists'
    ],
    'recovery_instructions_service_unavailable' => [
        'Wait a few minutes and try again',
        'Check :provider status page for service updates',
        'Operations will be retried automatically'
    ],
    'recovery_instructions_timeout' => [
        'Try again - timeouts are usually temporary',
        'Check your internet connection speed',
        'For large files, try uploading during off-peak hours'
    ],
    'recovery_instructions_folder_access_denied' => [
        'Check that the target folder exists in your :provider',
        'Verify you have write permissions to the folder',
        'Try reconnecting your :provider account'
    ],
    'recovery_instructions_invalid_file_type' => [
        'Convert the file to a supported format',
        'Check :provider\'s supported file types',
        'Try uploading a different file to test'
    ],
    'recovery_instructions_file_too_large' => [
        'Compress the file to reduce its size',
        'Split large files into smaller parts',
        'Use :provider\'s web interface for very large files'
    ],
    'recovery_instructions_invalid_file_content' => [
        'Check that the file is not corrupted',
        'Try re-creating or re-downloading the file',
        'Scan the file for viruses or malware'
    ],
    'recovery_instructions_provider_not_configured' => [
        'Go to Settings → Cloud Storage',
        'Check your configuration settings',
        'Ensure all required fields are filled correctly',
        'Contact support if you need assistance'
    ],
    'recovery_instructions_unknown_error' => [
        'Try the operation again',
        'Check your internet connection',
        'Contact support if the problem persists',
        'Include any error details when contacting support'
    ],
    'recovery_instructions_default' => [
        'Try the operation again',
        'Check your connection and settings',
        'Contact support if the problem persists'
    ],
    'google_drive_error_file_too_large' => 'The file \':filename\' is too large for Google Drive. Maximum file size is 5TB for most file types.',
    'google_drive_error_network_error' => 'Network connection issue prevented the Google Drive upload. The upload will be retried automatically.',
    'google_drive_error_service_unavailable' => 'Google Drive is temporarily unavailable. Your uploads will be retried automatically when the service is restored.',
    'google_drive_error_invalid_credentials' => 'Invalid Google Drive credentials. Please reconnect your Google Drive account in the settings.',
    'google_drive_error_timeout' => 'The Google Drive :operation timed out. This is usually temporary and will be retried automatically.',
    'google_drive_error_invalid_file_content' => 'The file \':filename\' appears to be corrupted or has invalid content. Please try uploading the file again.',
    'google_drive_error_unknown_error' => 'An unexpected error occurred with Google Drive. :message',

    // Google Drive Error Recovery Actions - Token Expired
    'google_drive_action_token_expired_1' => 'Go to Settings → Cloud Storage',
    'google_drive_action_token_expired_2' => 'Click "Reconnect Google Drive"',
    'google_drive_action_token_expired_3' => 'Complete the authorization process',
    'google_drive_action_token_expired_4' => 'Retry your upload',

    // Google Drive Error Recovery Actions - Insufficient Permissions
    'google_drive_action_insufficient_permissions_1' => 'Go to Settings → Cloud Storage',
    'google_drive_action_insufficient_permissions_2' => 'Click "Reconnect Google Drive"',
    'google_drive_action_insufficient_permissions_3' => 'Ensure you grant full access when prompted',
    'google_drive_action_insufficient_permissions_4' => 'Check that you have edit permissions for the target folder',

    // Google Drive Error Recovery Actions - Storage Quota Exceeded
    'google_drive_action_storage_quota_exceeded_1' => 'Free up space in your Google Drive account',
    'google_drive_action_storage_quota_exceeded_2' => 'Empty your Google Drive trash',

    // Cloud Storage Error Messages - Generic
    'cloud_storage_error_token_expired' => 'Your :provider connection has expired. Please reconnect your account to continue.',
    'cloud_storage_error_token_refresh_rate_limited' => 'Too many token refresh attempts. Please try again later.',
    'cloud_storage_error_invalid_credentials' => 'Invalid :provider credentials. Please check your configuration and reconnect your account.',
    'cloud_storage_error_insufficient_permissions' => 'Insufficient :provider permissions. Please reconnect your account and ensure you grant full access.',
    'cloud_storage_error_api_quota_exceeded' => ':provider API limit reached. Your operations will resume automatically when the limit resets.',
    'cloud_storage_error_storage_quota_exceeded' => 'Your :provider storage is full. Please free up space or upgrade your storage plan.',
    'cloud_storage_error_network_error' => 'Network connection issue prevented the :provider operation. Please check your internet connection and try again.',
    'cloud_storage_error_service_unavailable' => ':provider is temporarily unavailable. Please try again in a few minutes.',
    'cloud_storage_error_timeout' => 'The :provider :operation timed out. This is usually temporary - please try again.',
    'cloud_storage_error_file_not_found' => 'The file \':filename\' could not be found in :provider. It may have been deleted or moved.',
    'cloud_storage_error_folder_access_denied' => 'Access denied to the :provider folder. Please check your folder permissions.',
    'cloud_storage_error_invalid_file_type' => 'The file type of \':filename\' is not supported by :provider. Please try a different file format.',
    'cloud_storage_error_file_too_large' => 'The file \':filename\' is too large for :provider. Please reduce the file size and try again.',
    'cloud_storage_error_invalid_file_content' => 'The file \':filename\' appears to be corrupted. Please try uploading the file again.',
    'cloud_storage_error_provider_not_configured' => ':provider is not properly configured. Please check your settings and try again.',
    'cloud_storage_error_unknown_error' => 'An unexpected error occurred with :provider. :message',
    'cloud_storage_error_default' => 'An error occurred during the :provider :operation. Please try again.',

    // Cloud Storage Recovery Instructions - Token Expired
    'cloud_storage_recovery_token_expired_1' => 'Go to Settings → Cloud Storage',
    'cloud_storage_recovery_token_expired_2' => 'Click "Reconnect :provider"',
    'cloud_storage_recovery_token_expired_3' => 'Complete the authorization process',
    'cloud_storage_recovery_token_expired_4' => 'Retry your operation',

    // Cloud Storage Recovery Instructions - Rate Limited
    'cloud_storage_recovery_rate_limited_1' => 'Wait for the rate limit to reset',
    'cloud_storage_recovery_rate_limited_2' => 'Avoid repeatedly clicking connection test buttons',
    'cloud_storage_recovery_rate_limited_3' => 'Operations will resume automatically when the limit resets',
    'cloud_storage_recovery_rate_limited_4' => 'Contact support if the issue persists beyond the expected time',

    // Cloud Storage Recovery Instructions - Insufficient Permissions
    'cloud_storage_recovery_insufficient_permissions_1' => 'Go to Settings → Cloud Storage',
    'cloud_storage_recovery_insufficient_permissions_2' => 'Click "Reconnect :provider"',
    'cloud_storage_recovery_insufficient_permissions_3' => 'Ensure you grant full access when prompted',
    'cloud_storage_recovery_insufficient_permissions_4' => 'Check that you have the necessary permissions',

    // Cloud Storage Recovery Instructions - Storage Quota Exceeded
    'cloud_storage_recovery_storage_quota_exceeded_1' => 'Free up space in your :provider account',
    'cloud_storage_recovery_storage_quota_exceeded_2' => 'Empty your :provider trash',
    'cloud_storage_recovery_storage_quota_exceeded_3' => 'Consider upgrading your :provider storage plan',
    'cloud_storage_recovery_storage_quota_exceeded_4' => 'Contact your administrator if using a business account',

    // Cloud Storage Recovery Instructions - API Quota Exceeded
    'cloud_storage_recovery_api_quota_exceeded_1' => 'Wait for the quota to reset (usually within an hour)',
    'cloud_storage_recovery_api_quota_exceeded_2' => 'Operations will resume automatically',
    'cloud_storage_recovery_api_quota_exceeded_3' => 'Consider spreading large operations across multiple days',

    // Cloud Storage Recovery Instructions - Network Error
    'cloud_storage_recovery_network_error_1' => 'Check your internet connection',
    'cloud_storage_recovery_network_error_2' => 'Try again in a few minutes',
    'cloud_storage_recovery_network_error_3' => 'Contact your network administrator if the problem persists',

    // Cloud Storage Recovery Instructions - Service Unavailable
    'cloud_storage_recovery_service_unavailable_1' => 'Wait a few minutes and try again',
    'cloud_storage_recovery_service_unavailable_2' => 'Check :provider status page for service updates',
    'cloud_storage_recovery_service_unavailable_3' => 'Operations will be retried automatically',

    // Cloud Storage Recovery Instructions - Timeout
    'cloud_storage_recovery_timeout_1' => 'Try again - timeouts are usually temporary',
    'cloud_storage_recovery_timeout_2' => 'Check your internet connection speed',
    'cloud_storage_recovery_timeout_3' => 'For large files, try uploading during off-peak hours',

    // Cloud Storage Recovery Instructions - Folder Access Denied
    'cloud_storage_recovery_folder_access_denied_1' => 'Check that the target folder exists in your :provider',
    'cloud_storage_recovery_folder_access_denied_2' => 'Verify you have write permissions to the folder',
    'cloud_storage_recovery_folder_access_denied_3' => 'Try reconnecting your :provider account',

    // Cloud Storage Recovery Instructions - Invalid File Type
    'cloud_storage_recovery_invalid_file_type_1' => 'Convert the file to a supported format',
    'cloud_storage_recovery_invalid_file_type_2' => 'Check :provider\'s supported file types',
    'cloud_storage_recovery_invalid_file_type_3' => 'Try uploading a different file to test',

    // Cloud Storage Recovery Instructions - File Too Large
    'cloud_storage_recovery_file_too_large_1' => 'Compress the file to reduce its size',
    'cloud_storage_recovery_file_too_large_2' => 'Split large files into smaller parts',
    'cloud_storage_recovery_file_too_large_3' => 'Use :provider\'s web interface for very large files',

    // Cloud Storage Recovery Instructions - Invalid File Content
    'cloud_storage_recovery_invalid_file_content_1' => 'Check that the file is not corrupted',
    'cloud_storage_recovery_invalid_file_content_2' => 'Try re-creating or re-downloading the file',
    'cloud_storage_recovery_invalid_file_content_3' => 'Scan the file for viruses or malware',

    // Cloud Storage Recovery Instructions - Provider Not Configured
    'cloud_storage_recovery_provider_not_configured_1' => 'Go to Settings → Cloud Storage',
    'cloud_storage_recovery_provider_not_configured_2' => 'Check your configuration settings',
    'cloud_storage_recovery_provider_not_configured_3' => 'Ensure all required fields are filled correctly',
    'cloud_storage_recovery_provider_not_configured_4' => 'Contact support if you need assistance',

    // Cloud Storage Recovery Instructions - Unknown Error
    'cloud_storage_recovery_unknown_error_1' => 'Try the operation again',
    'cloud_storage_recovery_unknown_error_2' => 'Check your internet connection',
    'cloud_storage_recovery_unknown_error_3' => 'Contact support if the problem persists',
    'cloud_storage_recovery_unknown_error_4' => 'Include any error details when contacting support',

    // Cloud Storage Recovery Instructions - Default
    'cloud_storage_recovery_default_1' => 'Try the operation again',
    'cloud_storage_recovery_default_2' => 'Check your connection and settings',
    'cloud_storage_recovery_default_3' => 'Contact support if the problem persists',

    // Cloud Storage Provider Display Names
    'cloud_storage_provider_google_drive' => 'Google Drive',
    'cloud_storage_provider_amazon_s3' => 'Amazon S3',
    'cloud_storage_provider_azure_blob' => 'Azure Blob Storage',
    'cloud_storage_provider_microsoft_teams' => 'Microsoft Teams',
    'cloud_storage_provider_dropbox' => 'Dropbox',
    'cloud_storage_provider_onedrive' => 'OneDrive',

    'google_drive_action_storage_quota_exceeded_3' => 'Consider upgrading your Google Drive storage plan',
    'google_drive_action_storage_quota_exceeded_4' => 'Contact your administrator if using a business account',

    // Google Drive Error Recovery Actions - API Quota Exceeded
    'google_drive_action_api_quota_exceeded_1' => 'Wait for the quota to reset (usually within an hour)',
    'google_drive_action_api_quota_exceeded_2' => 'Uploads will resume automatically',
    'google_drive_action_api_quota_exceeded_3' => 'Consider spreading uploads across multiple days for large batches',

    // Google Drive Error Recovery Actions - Invalid Credentials
    'google_drive_action_invalid_credentials_1' => 'Go to Settings → Cloud Storage',
    'google_drive_action_invalid_credentials_2' => 'Disconnect and reconnect your Google Drive account',
    'google_drive_action_invalid_credentials_3' => 'Ensure your Google account is active and accessible',

    // Google Drive Error Recovery Actions - Folder Access Denied
    'google_drive_action_folder_access_denied_1' => 'Check that the target folder exists in your Google Drive',
    'google_drive_action_folder_access_denied_2' => 'Verify you have write permissions to the folder',
    'google_drive_action_folder_access_denied_3' => 'Try reconnecting your Google Drive account',

    // Google Drive Error Recovery Actions - Invalid File Type
    'google_drive_action_invalid_file_type_1' => 'Convert the file to a supported format',
    'google_drive_action_invalid_file_type_2' => 'Check Google Drive\'s supported file types',
    'google_drive_action_invalid_file_type_3' => 'Try uploading a different file to test',

    // Google Drive Error Recovery Actions - File Too Large
    'google_drive_action_file_too_large_1' => 'Compress the file to reduce its size',
    'google_drive_action_file_too_large_2' => 'Split large files into smaller parts',
    'google_drive_action_file_too_large_3' => 'Use Google Drive\'s web interface for very large files',

    // Time-related messages for quota reset
    'quota_reset_time_1_hour' => '1 hour',
    'quota_reset_time_hours' => ':hours hours',
    'quota_reset_time_minutes' => ':minutes minutes',
    'quota_reset_time_unknown' => 'a short time',

    // Token Refresh Result Messages
    'token_refresh_success' => 'Token refreshed successfully',
    'token_already_valid' => 'Token is already valid',
    'token_refreshed_by_another_process' => 'Token was refreshed by another process',
    'token_already_valid_description' => 'Token was already valid and did not need refreshing',
    'token_refreshed_by_another_process_description' => 'Token was refreshed by another concurrent process',
    'token_refresh_success_description' => 'Token was successfully refreshed',
    'token_refresh_failed_description' => 'Token refresh failed: :message',

    // Proactive Token Renewal Messages
    'proactive_refresh_provider_not_supported' => 'Provider not supported for proactive refresh',
    'proactive_refresh_no_token_found' => 'No authentication token found',
    'proactive_refresh_token_not_expiring' => 'Token is not expiring soon and does not need refreshing',
    'proactive_refresh_requires_reauth' => 'Token requires user re-authentication',

    // Health Status Messages
    'health_status_healthy' => 'Healthy',
    'health_status_authentication_required' => 'Authentication Required',
    'health_status_connection_issues' => 'Connection Issues',
    'health_status_not_connected' => 'Not Connected',
    'health_status_token_validation_failed' => 'Token validation failed',
    'health_status_api_connectivity_test_failed' => 'API connectivity test failed',
    'health_status_authentication_error' => 'Authentication error',
    'health_status_connection_error' => 'Connection error',
    'health_status_token_error' => 'Token error',
    'health_status_api_error' => 'API error',

    // Token Renewal Notification Service
    'notification_failure_alert_subject' => 'Notification Failure Alert - User :email',
    'notification_failure_alert_body' => 'Failed to send :type notification to user :email for :provider provider after :attempts attempts.\n\nLast error: :error\n\nPlease check the user\'s email address and system configuration.',

    // Token Expired Email
    'token_expired_subject' => ':provider Connection Expired - Action Required',
    'token_expired_heading' => ':provider Connection Expired',
    'token_expired_subheading' => 'Action Required to Resume File Uploads',
    'token_expired_alert' => 'Attention Required: Your :provider connection has expired and needs to be renewed.',
    'token_expired_greeting' => 'Hello :name,',
    'token_expired_intro' => 'We\'re writing to let you know that your :provider connection has expired. This means that new file uploads cannot be processed until you reconnect your account.',
    'token_expired_what_this_means' => 'What This Means:',
    'token_expired_impact_uploads' => 'New file uploads will fail until you reconnect',
    'token_expired_impact_existing' => 'Existing files in your :provider are not affected',
    'token_expired_impact_resume' => 'Your upload system will resume normal operation once reconnected',
    'token_expired_how_to_reconnect' => 'How to Reconnect:',
    'token_expired_step_1' => 'Click the "Reconnect :provider" button below',
    'token_expired_step_2' => 'Sign in to your :provider account when prompted',
    'token_expired_step_3' => 'Grant permission for the upload system to access your account',
    'token_expired_step_4' => 'Verify the connection is working on your dashboard',
    'token_expired_reconnect_button' => 'Reconnect :provider',
    'token_expired_why_happened' => 'Why Did This Happen?',
    'token_expired_explanation' => ':provider connections expire periodically for security reasons. This is normal and helps protect your account. The system attempted to automatically renew the connection, but manual intervention is now required.',
    'token_expired_need_help' => 'Need Help?',
    'token_expired_support' => 'If you\'re having trouble reconnecting or have questions about this process, please don\'t hesitate to contact our support team at :email.',
    'token_expired_footer_important' => 'This email was sent because your :provider connection expired. If you did not expect this email, please contact support immediately.',
    'token_expired_footer_automated' => 'This is an automated message from your file upload system. Please do not reply directly to this email.',

    // Token Refresh Failed Email
    'token_refresh_failed_subject' => ':provider Connection Issue - :urgency',
    'token_refresh_failed_heading' => ':provider Connection Issue',
    'token_refresh_failed_action_required' => 'Action Required',
    'token_refresh_failed_auto_recovery' => 'Automatic Recovery in Progress',
    'token_refresh_failed_alert_action' => 'Action Required: Your :provider connection needs manual attention.',
    'token_refresh_failed_alert_auto' => 'Connection Issue: We\'re working to restore your :provider connection automatically.',
    'token_refresh_failed_greeting' => 'Hello :name,',
    'token_refresh_failed_intro' => 'We encountered an issue while trying to refresh your :provider connection. Here\'s what happened and what we\'re doing about it:',
    'token_refresh_failed_issue_details' => 'Issue Details:',
    'token_refresh_failed_error_type' => 'Error Type: :type',
    'token_refresh_failed_attempt' => 'Attempt: :current of :max',
    'token_refresh_failed_description' => 'Description: :description',
    'token_refresh_failed_technical_details' => 'Technical Details: :details',
    'token_refresh_failed_what_to_do' => 'What You Need to Do:',
    'token_refresh_failed_manual_required' => 'This type of error requires manual intervention. Please reconnect your :provider account to restore file upload functionality.',
    'token_refresh_failed_reconnect_now' => 'Reconnect :provider Now',
    'token_refresh_failed_why_manual' => 'Why Manual Action is Needed:',
    'token_refresh_failed_credentials_invalid' => 'Your authentication credentials are no longer valid. This typically happens when you change your password, revoke access, or the token has been inactive for an extended period.',
    'token_refresh_failed_cannot_resolve' => 'This error type cannot be resolved automatically and requires you to re-establish the connection.',
    'token_refresh_failed_auto_recovery_status' => 'Automatic Recovery Status:',
    'token_refresh_failed_no_action_needed' => 'The system will continue attempting to restore the connection automatically. You don\'t need to take any action at this time.',
    'token_refresh_failed_max_attempts' => 'Maximum Attempts Reached:',
    'token_refresh_failed_exhausted' => 'The system has exhausted all automatic retry attempts. Please reconnect manually to restore functionality.',
    'token_refresh_failed_what_happens_next' => 'What Happens Next:',
    'token_refresh_failed_auto_retry' => 'The system will automatically retry the connection',
    'token_refresh_failed_success_email' => 'If successful, you\'ll receive a confirmation email',
    'token_refresh_failed_manual_notify' => 'If all attempts fail, you\'ll be notified to reconnect manually',
    'token_refresh_failed_uploads_paused' => 'File uploads are temporarily paused until the connection is restored',
    'token_refresh_failed_impact' => 'Impact on Your Service:',
    'token_refresh_failed_uploads_impact' => 'File Uploads: New uploads are temporarily paused',
    'token_refresh_failed_existing_impact' => 'Existing Files: All previously uploaded files remain safe and accessible',
    'token_refresh_failed_system_impact' => 'System Status: All other features continue to work normally',
    'token_refresh_failed_no_action_required' => 'No action is required from you at this time. We\'ll keep you updated on the recovery progress.',
    'token_refresh_failed_need_help' => 'Need Help?',
    'token_refresh_failed_support' => 'If you\'re experiencing repeated connection issues or need assistance with reconnecting, please contact our support team at :email. Include this error reference: :reference',
    'token_refresh_failed_error_reference' => 'Error Reference: :type (Attempt :attempt)',
    'token_refresh_failed_timestamp' => 'Timestamp: :timestamp',
    'token_refresh_failed_footer_automated' => 'This is an automated message from your file upload system. Please do not reply directly to this email.',

    // Connection Restored Email
    'connection_restored_subject' => ':provider Connection Restored',
    'connection_restored_heading' => '✅ :provider Connection Restored',
    'connection_restored_subheading' => 'Your file upload system is back online!',
    'connection_restored_alert' => 'Great News: Your :provider connection has been successfully restored and is working normally.',
    'connection_restored_greeting' => 'Hello :name,',
    'connection_restored_intro' => 'We\'re pleased to inform you that the connection issue with your :provider account has been resolved. Your file upload system is now fully operational again.',
    'connection_restored_current_status' => 'Current Status:',
    'connection_restored_connection_status' => 'Connection: ✅ Active and healthy',
    'connection_restored_uploads_status' => 'File Uploads: ✅ Accepting new uploads',
    'connection_restored_pending_status' => 'Pending Files: ✅ Processing any queued uploads',
    'connection_restored_system_status' => 'System Status: ✅ All features operational',
    'connection_restored_what_happened' => 'What Happened:',
    'connection_restored_explanation' => 'The system successfully renewed your :provider authentication and restored full connectivity. Any file uploads that were temporarily paused during the connection issue are now being processed automatically.',
    'connection_restored_whats_happening' => 'What\'s Happening Now:',
    'connection_restored_processing_queued' => 'The system is processing any uploads that were queued during the outage',
    'connection_restored_accepting_new' => 'New file uploads will be accepted and processed normally',
    'connection_restored_operations_resumed' => 'All :provider operations have resumed',
    'connection_restored_monitoring_active' => 'Connection monitoring is active to prevent future issues',
    'connection_restored_access_dashboard' => 'Access Your Dashboard:',
    'connection_restored_dashboard_intro' => 'You can view your upload status and manage your files through your dashboard:',
    'connection_restored_view_dashboard' => 'View Dashboard',
    'connection_restored_preventing_issues' => 'Preventing Future Issues:',
    'connection_restored_keep_active' => 'Keep your :provider account active and in good standing',
    'connection_restored_avoid_password_change' => 'Avoid changing your :provider password without updating the connection',
    'connection_restored_monitor_email' => 'Monitor your email for any connection alerts',
    'connection_restored_contact_support' => 'Contact support if you notice any unusual behavior',
    'connection_restored_need_assistance' => 'Need Assistance?',
    'connection_restored_support' => 'If you experience any issues with file uploads or have questions about your :provider connection, please don\'t hesitate to contact our support team at :email.',
    'connection_restored_footer_timestamp' => 'Connection Restored: :timestamp',
    'connection_restored_footer_service_status' => 'Service Status: All systems operational',
    'connection_restored_footer_thanks' => 'Thank you for your patience during the connection issue. This is an automated message from your file upload system.',

    // Error Type Display Names
    'error_type_network_timeout' => 'Network Timeout',
    'error_type_invalid_refresh_token' => 'Invalid Refresh Token',
    'error_type_expired_refresh_token' => 'Expired Refresh Token',
    'error_type_api_quota_exceeded' => 'API Quota Exceeded',
    'error_type_service_unavailable' => 'Service Unavailable',
    'error_type_unknown_error' => 'Unknown Error',

    // Error Descriptions
    'error_desc_network_timeout' => 'We encountered a network timeout while trying to refresh your connection. This is usually temporary and the system will retry automatically.',
    'error_desc_invalid_refresh_token' => 'Your stored authentication token is no longer valid. This typically happens when you revoke access or change your password on the cloud service.',
    'error_desc_expired_refresh_token' => 'Your authentication token has expired and cannot be renewed automatically. You will need to reconnect your account.',
    'error_desc_api_quota_exceeded' => 'The cloud service has temporarily limited our access due to high usage. The system will retry automatically once the limit resets.',
    'error_desc_service_unavailable' => 'The cloud service is temporarily unavailable. This is usually a temporary issue on their end, and the system will retry automatically.',
    'error_desc_unknown_error' => 'An unexpected error occurred while refreshing your connection. Our technical team has been notified and will investigate.',

    // Retry Information
    'retry_no_automatic' => 'No automatic retry will be attempted. Please reconnect manually.',
    'retry_max_attempts_reached' => 'Maximum retry attempts reached. Please reconnect manually.',
    'retry_in_seconds' => 'The system will retry in :seconds seconds. :remaining attempts remaining.',
    'retry_in_minutes' => 'The system will retry in :minutes minutes. :remaining attempts remaining.',
    'retry_in_hours' => 'The system will retry in :hours hours. :remaining attempts remaining.',

    // Provider Display Names
    'provider_google_drive' => 'Google Drive',
    'provider_microsoft_teams' => 'Microsoft Teams',
    'provider_dropbox' => 'Dropbox',

    // Connection Recovery Messages
    'recovery_connection_healthy' => 'Connection is healthy',
    'recovery_connection_health_restored' => 'Connection health restored',
    'recovery_token_refreshed_successfully' => 'Token refreshed successfully',
    'recovery_network_connectivity_restored' => 'Network connectivity restored',
    'recovery_api_quota_restored' => 'API quota restored',
    'recovery_service_availability_restored' => 'Service availability restored',
    'recovery_no_action_needed' => 'No action needed',
    'recovery_user_intervention_required' => 'User intervention required',
    'recovery_manual_action_needed' => 'Manual action needed',
    'recovery_failed_due_to_exception' => 'Recovery failed due to exception',
    'recovery_strategy_failed' => 'Recovery strategy failed',
    'recovery_unknown_strategy' => 'Unknown recovery strategy',

    // Recovery Failure Messages
    'recovery_token_refresh_failed' => 'Token refresh failed',
    'recovery_network_connectivity_still_failing' => 'Network connectivity still failing',
    'recovery_api_quota_still_exceeded' => 'API quota still exceeded',
    'recovery_service_still_unavailable' => 'Service still unavailable',
    'recovery_connection_still_unhealthy' => 'Connection still unhealthy',

    // Recovery Exception Messages
    'recovery_token_refresh_exception' => 'Token refresh exception',
    'recovery_network_test_exception' => 'Network test exception',
    'recovery_quota_check_exception' => 'Quota check exception',
    'recovery_service_check_exception' => 'Service check exception',
    'recovery_health_check_exception' => 'Health check exception',

    // Upload Recovery Messages
    'recovery_local_file_no_longer_exists' => 'Local file no longer exists',
    'recovery_no_target_user_found' => 'No target user found',
    'recovery_retry_job_permanently_failed' => 'Retry job permanently failed',
    'recovery_upload_retry_failed_for_file' => 'Upload retry failed for file',

    // Token Refresh Configuration Validation Messages
    'token_config_proactive_refresh_minutes_min' => 'Proactive refresh minutes must be at least 1',
    'token_config_max_retry_attempts_range' => 'Max retry attempts must be between 1 and 10',
    'token_config_retry_base_delay_min' => 'Retry base delay must be at least 1 second',
    'token_config_notification_throttle_hours_min' => 'Notification throttle hours must be at least 1',
    'token_config_max_attempts_per_hour_min' => 'Max attempts per hour must be at least 1',
    'token_config_max_health_checks_per_minute_min' => 'Max health checks per minute must be at least 1',
    
    // Token Refresh Admin Controller Validation Messages
    'token_config_proactive_refresh_range' => 'Proactive refresh minutes must be between 1 and 60',
    'token_config_background_refresh_range' => 'Background refresh minutes must be between 5 and 120',
    'token_config_notification_throttle_range' => 'Notification throttle hours must be between 1 and 168 (1 week)',
    'token_config_max_attempts_per_hour_range' => 'Max attempts per hour must be between 1 and 100',
    
    // Token Refresh Admin Interface Messages
    'token_config_admin_interface_disabled' => 'Admin interface is disabled',
    'token_config_runtime_changes_disabled' => 'Runtime changes are disabled',
    'token_config_update_failed' => 'Failed to update configuration',
    'token_config_toggle_failed' => 'Failed to toggle feature',
    'token_config_cache_clear_failed' => 'Failed to clear cache',
    'token_config_setting_updated' => 'Configuration \':key\' updated successfully.',
    'token_config_feature_enabled' => 'Feature \':feature\' enabled successfully.',
    'token_config_feature_disabled' => 'Feature \':feature\' disabled successfully.',
    'token_config_cache_cleared' => 'Configuration cache cleared successfully.',
    'token_config_change_requires_confirmation' => 'Changing \':key\' requires confirmation as it may affect system behavior.',
    'token_config_toggle_requires_confirmation' => 'Toggling \':feature\' requires confirmation as it may significantly affect system behavior.',
    
    // Token Refresh Configuration Dashboard
    'token_config_dashboard_title' => 'Token Refresh Configuration',
    'token_config_dashboard_description' => 'Manage token refresh settings and feature flags for gradual rollout.',
    'token_config_status_title' => 'Configuration Status',
    'token_config_refresh_button' => 'Refresh',
    'token_config_clear_cache_button' => 'Clear Cache',
    'token_config_environment' => 'Environment',
    'token_config_runtime_changes' => 'Runtime Changes',
    'token_config_validation_status' => 'Validation Status',
    'token_config_enabled' => 'Enabled',
    'token_config_disabled' => 'Disabled',
    'token_config_valid' => 'Valid',
    'token_config_issues_found' => 'Issues Found',
    'token_config_issues_title' => 'Configuration Issues',
    'token_config_feature_flags_title' => 'Feature Flags',
    'token_config_timing_title' => 'Timing Configuration',
    'token_config_notifications_title' => 'Notification Configuration',
    'token_config_rate_limiting_title' => 'Rate Limiting Configuration',
    'token_config_security_title' => 'Security Configuration',
    'token_config_confirm_change_title' => 'Confirm Configuration Change',
    'token_config_confirm_button' => 'Confirm',
    'token_config_cancel_button' => 'Cancel',
    
    // Token Refresh Console Command Messages
    'token_config_cmd_unknown_action' => 'Unknown action: :action',
    'token_config_cmd_key_value_required' => 'Both --key and --value options are required for set action',
    'token_config_cmd_feature_enabled_required' => 'Both --feature and --enabled options are required for toggle action',
    'token_config_cmd_validation_failed' => 'Validation failed: :errors',
    'token_config_cmd_change_confirmation' => 'Changing \':key\' may affect system behavior. Continue?',
    'token_config_cmd_toggle_confirmation' => 'Toggling \':feature\' may significantly affect system behavior. Continue?',
    'token_config_cmd_operation_cancelled' => 'Operation cancelled',
    'token_config_cmd_setting_updated' => 'Configuration \':key\' updated successfully to: :value',
    'token_config_cmd_setting_update_failed' => 'Failed to update configuration \':key\'',
    'token_config_cmd_feature_enabled' => 'Feature \':feature\' enabled successfully',
    'token_config_cmd_feature_disabled' => 'Feature \':feature\' disabled successfully',
    'token_config_cmd_feature_toggle_failed' => 'Failed to toggle feature \':feature\'',
    'token_config_cmd_validation_success' => '✓ Configuration is valid',
    'token_config_cmd_validation_failed_title' => 'Configuration validation failed:',
    'token_config_cmd_cache_cleared' => 'Configuration cache cleared successfully',
    'token_config_cmd_cache_clear_failed' => 'Failed to clear configuration cache: :error',
    
    // Additional Cloud Storage Status Messages
    'cloud_storage_status_retrieval_failed' => 'Unable to retrieve cloud storage status. Please try again.',
    'cloud_storage_health_check_failed' => 'Health check failed due to an unexpected error. Please try again.',

    // Email Verification Metrics Service
    'email_verification_bypass_spike_alert' => 'Unusual spike in existing user bypasses in the last hour',
    'email_verification_repeated_bypass_alert' => 'User :user_id has bypassed restrictions :count times',
    'email_verification_unusual_domain_alert' => 'Multiple bypasses from domain: :domain',
    'email_verification_high_bypass_volume_alert' => 'High volume of existing user bypasses: :count in the last hour (threshold: :threshold)',
    'email_verification_high_restriction_volume_alert' => 'High volume of restriction enforcements: :count in the last hour (threshold: :threshold)',
    'email_verification_no_activity_alert' => 'No email verification activity detected during business hours - possible system issue',
    'email_verification_no_alerts_detected' => 'No alerts detected',
    'email_verification_no_unusual_activity' => 'No unusual activity detected',
    'email_verification_no_unusual_activity_24h' => 'No unusual activity detected in the last 24 hours',
    'email_verification_alert_cooldown_active' => 'Alert cooldown active, skipping notifications',
    'email_verification_alert_email_sent' => 'Alert email sent to :email',
    'email_verification_alert_email_failed' => 'Failed to send alert email: :error',
    'email_verification_dashboard_all_bypasses' => 'All bypasses',
    'email_verification_dashboard_no_bypasses' => 'No bypasses',
    'email_verification_dashboard_system_normal' => 'System operating normally',
    'email_verification_dashboard_unusual_activity' => 'Unusual activity detected',
    'email_verification_dashboard_no_recent_activity' => 'No recent activity',
    'email_verification_dashboard_high_bypass_volume' => 'High bypass volume',
    'email_verification_dashboard_title' => 'Email Verification Metrics',
    'email_verification_dashboard_last_hours' => 'Last :hours hours',
    'email_verification_dashboard_existing_user_bypasses' => 'Existing User Bypasses',
    'email_verification_dashboard_restrictions_enforced' => 'Restrictions Enforced',
    'email_verification_dashboard_bypass_ratio' => 'Bypass Ratio',
    'email_verification_dashboard_unusual_activity_alerts' => 'Unusual Activity Alerts',
    'email_verification_dashboard_bypass_patterns' => 'Bypass Patterns',
    'email_verification_dashboard_by_user_role' => 'By User Role',
    'email_verification_dashboard_by_restriction_type' => 'By Restriction Type',
    'email_verification_dashboard_top_bypass_domains' => 'Top Bypass Domains',
    'email_verification_dashboard_restriction_enforcement' => 'Restriction Enforcement',
    'email_verification_dashboard_top_blocked_domains' => 'Top Blocked Domains',
    'email_verification_dashboard_activity_timeline' => 'Activity Timeline (Last :hours hours)',
    'email_verification_dashboard_bypasses' => 'Bypasses',
    'email_verification_dashboard_restrictions' => 'Restrictions',
    'email_verification_dashboard_last_updated' => 'Last updated',
    'email_verification_dashboard_refresh' => 'Refresh',
    'email_verification_dashboard_count' => 'Count',

    // Domain Rules Cache Service Messages
    'domain_rules_cache_failed' => 'Failed to retrieve domain access rules from cache',
    'domain_rules_cache_cleared' => 'Domain access rules cache has been cleared',
    'domain_rules_cache_warmed' => 'Domain access rules cache has been warmed up',
    'domain_rules_not_configured' => 'No domain access rules configured - using default settings',
    'domain_rules_email_check_completed' => 'Email domain validation completed',
    'domain_rules_cache_statistics' => 'Domain Rules Cache Statistics',
    'domain_rules_cache_performance' => 'Cache Performance',
    'domain_rules_query_performance' => 'Database Query Performance',

    // Cache Statistics Labels
    'cache_hit' => 'Cache Hit',
    'cache_miss' => 'Cache Miss',
    'cache_key' => 'Cache Key',
    'cache_ttl' => 'Cache TTL (seconds)',
    'rules_loaded' => 'Rules Loaded',
    'rules_mode' => 'Rules Mode',
    'rules_count' => 'Number of Rules',
    'query_time' => 'Query Time (ms)',
    'total_time' => 'Total Time (ms)',
    'warm_up_time' => 'Warm-up Time (ms)',

    // Domain Rules Cache Command Messages
    'domain_rules_cache_command_invalid_action' => 'Invalid action. Use: stats, clear, or warm',
    'domain_rules_cache_command_stats_title' => 'Domain Rules Cache Statistics',
    'domain_rules_cache_command_property' => 'Property',
    'domain_rules_cache_command_value' => 'Value',
    'domain_rules_cache_command_yes' => 'Yes',
    'domain_rules_cache_command_no' => 'No',
    'domain_rules_cache_command_seconds' => 'seconds',
];
