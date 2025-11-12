<?php

return [
    'welcome' => 'Willkommen in unserer Anwendung!',
    'login-message' => 'Benutzer bei <b>' . config('app.company_name') . '</b> können sich mit ihrer E-Mail-Adresse und ihrem Passwort anmelden. Wenn Sie Ihr Passwort nicht kennen, <a href="/">melden Sie sich an</a> mit Ihrer E-Mail-Adresse und wir senden Ihnen einen Link.',
    'email-validation-message' => 'Sie erhalten eine E-Mail mit einem Link zur Bestätigung Ihrer E-Mail-Adresse. Durch Klicken auf den Link, den wir Ihnen senden, können Sie Dateien an ' . config('app.company_name') . ' hochladen.',

    // Token Refresh Error Types - Descriptions
    'token_refresh_error_network_timeout' => 'Netzwerk-Timeout während der Token-Erneuerung',
    'token_refresh_error_invalid_refresh_token' => 'Ungültiger Erneuerungs-Token bereitgestellt',
    'token_refresh_error_expired_refresh_token' => 'Der Erneuerungs-Token ist abgelaufen',
    'token_refresh_error_api_quota_exceeded' => 'API-Kontingent während der Token-Erneuerung überschritten',
    'token_refresh_error_service_unavailable' => 'OAuth-Dienst vorübergehend nicht verfügbar',
    'token_refresh_error_unknown_error' => 'Unbekannter Token-Erneuerungsfehler',

    // Token Refresh Error Types - User Notifications
    'token_refresh_notification_network_timeout' => 'Netzwerkprobleme verhinderten die Token-Erneuerung. Automatischer Wiederholungsversuch.',
    'token_refresh_notification_invalid_refresh_token' => 'Ihre Google Drive-Verbindung ist ungültig. Bitte verbinden Sie Ihr Konto erneut.',
    'token_refresh_notification_expired_refresh_token' => 'Ihre Google Drive-Verbindung ist abgelaufen. Bitte verbinden Sie Ihr Konto erneut.',
    'token_refresh_notification_api_quota_exceeded' => 'Google Drive API-Limit erreicht. Die Token-Erneuerung wird automatisch wiederholt.',
    'token_refresh_notification_service_unavailable' => 'Google Drive-Dienst ist vorübergehend nicht verfügbar. Automatischer Wiederholungsversuch.',
    'token_refresh_notification_unknown_error' => 'Ein unerwarteter Fehler ist bei der Token-Erneuerung aufgetreten. Bitte kontaktieren Sie den Support, falls dies anhält.',

    // Google Drive Provider-Specific Error Messages
    'google_drive_error_token_expired' => 'Ihre Google Drive-Verbindung ist abgelaufen. Bitte verbinden Sie Ihr Google Drive-Konto erneut, um weiterhin Dateien hochzuladen.',
    'google_drive_error_insufficient_permissions' => 'Unzureichende Google Drive-Berechtigungen. Bitte verbinden Sie Ihr Konto erneut und stellen Sie sicher, dass Sie vollständigen Zugriff auf Google Drive gewähren.',
    'google_drive_error_api_quota_exceeded' => 'Google Drive API-Limit erreicht. Ihre Uploads werden automatisch in :time fortgesetzt. Keine Aktion erforderlich.',
    'google_drive_error_storage_quota_exceeded' => 'Ihr Google Drive-Speicher ist voll. Bitte schaffen Sie Platz in Ihrem Google Drive-Konto oder upgraden Sie Ihren Speicherplan.',
    'google_drive_error_file_not_found' => 'Die Datei \':filename\' konnte in Google Drive nicht gefunden werden. Sie wurde möglicherweise gelöscht oder verschoben.',
    'google_drive_error_folder_access_denied' => 'Zugriff auf den Google Drive-Ordner verweigert. Bitte überprüfen Sie Ihre Ordnerberechtigungen oder verbinden Sie Ihr Konto erneut.',
    'google_drive_error_invalid_file_type' => 'Der Dateityp von \':filename\' wird von Google Drive nicht unterstützt. Bitte versuchen Sie ein anderes Dateiformat.',
    'google_drive_error_file_too_large' => 'Die Datei \':filename\' ist zu groß für Google Drive. Die maximale Dateigröße beträgt 5TB für die meisten Dateitypen.',
    'google_drive_error_network_error' => 'Ein Netzwerkverbindungsproblem verhinderte den Google Drive-Upload. Der Upload wird automatisch wiederholt.',
    'google_drive_error_service_unavailable' => 'Google Drive ist vorübergehend nicht verfügbar. Ihre Uploads werden automatisch wiederholt, wenn der Dienst wiederhergestellt ist.',
    'google_drive_error_invalid_credentials' => 'Ungültige Google Drive-Anmeldedaten. Bitte verbinden Sie Ihr Google Drive-Konto in den Einstellungen erneut.',
    'google_drive_error_timeout' => 'Die Google Drive :operation ist abgelaufen. Dies ist normalerweise vorübergehend und wird automatisch wiederholt.',
    'google_drive_error_invalid_file_content' => 'Die Datei \':filename\' scheint beschädigt zu sein oder ungültigen Inhalt zu haben. Bitte versuchen Sie, die Datei erneut hochzuladen.',
    'google_drive_error_unknown_error' => 'Ein unerwarteter Fehler ist mit Google Drive aufgetreten. :message',

    // Google Drive Error Recovery Actions - Token Expired
    'google_drive_action_token_expired_1' => 'Gehen Sie zu Einstellungen → Cloud-Speicher',
    'google_drive_action_token_expired_2' => 'Klicken Sie auf "Google Drive erneut verbinden"',
    'google_drive_action_token_expired_3' => 'Schließen Sie den Autorisierungsprozess ab',
    'google_drive_action_token_expired_4' => 'Wiederholen Sie Ihren Upload',

    // Google Drive Error Recovery Actions - Insufficient Permissions
    'google_drive_action_insufficient_permissions_1' => 'Gehen Sie zu Einstellungen → Cloud-Speicher',
    'google_drive_action_insufficient_permissions_2' => 'Klicken Sie auf "Google Drive erneut verbinden"',
    'google_drive_action_insufficient_permissions_3' => 'Stellen Sie sicher, dass Sie vollständigen Zugriff gewähren, wenn Sie dazu aufgefordert werden',
    'google_drive_action_insufficient_permissions_4' => 'Überprüfen Sie, dass Sie Bearbeitungsberechtigungen für den Zielordner haben',

    // Google Drive Error Recovery Actions - Storage Quota Exceeded
    'google_drive_action_storage_quota_exceeded_1' => 'Schaffen Sie Platz in Ihrem Google Drive-Konto',
    'google_drive_action_storage_quota_exceeded_2' => 'Leeren Sie Ihren Google Drive-Papierkorb',

    // Employee Management
    'nav_employee_management' => 'Mitarbeiterverwaltung',
    'employee_management_title' => 'Mitarbeiterverwaltung',
    'create_employee_title' => 'Neuen Mitarbeiter erstellen',
    'employees_list_title' => 'Mitarbeiterbenutzer',
    'button_create_employee' => 'Mitarbeiter erstellen',
    'no_employees_match_filter' => 'Keine Mitarbeiter entsprechen Ihren Filterkriterien',
    'no_employees_found' => 'Keine Mitarbeiter gefunden',
    'column_reset_url' => 'Reset-URL',
    'button_copy_reset_url' => 'Reset-URL kopieren',

    // Employee Creation Messages
    'employee_created_success' => 'Mitarbeiterbenutzer erfolgreich erstellt.',
    'employee_created_and_invited_success' => 'Mitarbeiterbenutzer erstellt und Bestätigungs-E-Mail erfolgreich gesendet.',
    'employee_created_email_failed' => 'Mitarbeiterbenutzer erstellt, aber Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte überprüfen Sie die Logs.',
    'employee_creation_failed' => 'Fehler beim Erstellen des Mitarbeiterbenutzers. Bitte überprüfen Sie die Logs.',

    // Role-Based Email Verification
    // Admin Verification
    'admin_verify_email_subject' => 'Bestätigen Sie Ihre Administrator-E-Mail-Adresse',
    'admin_verify_email_title' => 'Bestätigen Sie Ihre Administrator-E-Mail-Adresse',
    'admin_verify_email_intro' => 'Willkommen im :company_name Dateiverwaltungssystem. Als Administrator haben Sie vollständigen Zugriff auf die Benutzerverwaltung, Cloud-Speicher-Konfiguration und die Überwachung aller Datei-Uploads. Bitte bestätigen Sie Ihre E-Mail-Adresse, um die Einrichtung Ihres Admin-Kontos abzuschließen.',
    'admin_verify_email_button' => 'Administrator-Zugang bestätigen',

    // Employee Verification  
    'employee_verify_email_subject' => 'Bestätigen Sie Ihre Mitarbeiter-E-Mail-Adresse',
    'employee_verify_email_title' => 'Bestätigen Sie Ihre Mitarbeiter-E-Mail-Adresse',
    'employee_verify_email_intro' => 'Willkommen bei :company_name! Als Mitarbeiter können Sie Kunden-Datei-Uploads direkt in Ihr Google Drive empfangen und Ihre eigenen Kundenbeziehungen verwalten. Bitte bestätigen Sie Ihre E-Mail-Adresse, um mit dem Empfang von Kundendateien zu beginnen.',
    'employee_verify_email_button' => 'Mitarbeiter-Zugang bestätigen',

    // Client Verification
    'client_verify_email_subject' => 'Bestätigen Sie Ihre E-Mail-Adresse',
    'client_verify_email_title' => 'Bestätigen Sie Ihre E-Mail-Adresse', 
    'client_verify_email_intro' => 'Um Dateien an :company_name zu senden, bestätigen Sie bitte Ihre E-Mail-Adresse, indem Sie auf den untenstehenden Link klicken. Nach der Bestätigung können Sie sicher Dateien hochladen, die direkt an das entsprechende Teammitglied geliefert werden.',
    'client_verify_email_button' => 'E-Mail-Adresse bestätigen',

    // Shared elements
    'verify_email_ignore' => 'Falls Sie diese Bestätigung nicht angefordert haben, können Sie diese E-Mail sicher ignorieren.',
    'thanks_signature' => 'Vielen Dank',

    // Button Loading States
    'button_create_user_loading' => 'Benutzer wird erstellt...',
    'button_create_and_invite_loading' => 'Erstellen und Senden...',

    // Admin User Creation Messages
    'admin_user_created' => 'Kundenbenutzer erfolgreich erstellt. Sie können ihnen den Login-Link manuell bereitstellen.',
    'admin_user_created_and_invited' => 'Kundenbenutzer erstellt und Einladung erfolgreich gesendet.',
    'admin_user_created_email_failed' => 'Kundenbenutzer erfolgreich erstellt, aber Einladungs-E-Mail konnte nicht gesendet werden. Sie können ihnen den Login-Link manuell bereitstellen.',
    'admin_user_creation_failed' => 'Fehler beim Erstellen des Kundenbenutzers. Bitte versuchen Sie es erneut.',

    // Employee Client Creation Messages
    'employee_client_created' => 'Kundenbenutzer erfolgreich erstellt. Sie können ihnen den Login-Link manuell bereitstellen.',
    'employee_client_created_and_invited' => 'Kundenbenutzer erstellt und Einladung erfolgreich gesendet.',
    'employee_client_created_email_failed' => 'Kundenbenutzer erfolgreich erstellt, aber Einladungs-E-Mail konnte nicht gesendet werden. Sie können ihnen den Login-Link manuell bereitstellen.',
    'employee_client_creation_failed' => 'Fehler beim Erstellen des Kundenbenutzers. Bitte versuchen Sie es erneut.',

    // Account Deletion Messages
    'account_deletion_request_failed' => 'Fehler beim Verarbeiten der Löschungsanfrage. Bitte versuchen Sie es erneut.',
    'account_deletion_link_invalid' => 'Der Bestätigungslink für die Löschung ist ungültig oder abgelaufen.',
    'account_deletion_verification_invalid' => 'Ungültiger oder abgelaufener Bestätigungslink.',
    'account_deletion_user_invalid' => 'Ungültiges Benutzerkonto.',
    'account_deletion_success' => 'Ihr Konto und alle zugehörigen Daten wurden dauerhaft gelöscht.',
    'account_deletion_error' => 'Ein Fehler ist beim Löschen Ihres Kontos aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie den Support.',
    'account_deletion_unexpected_error' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie den Support.',

    // Google Drive OAuth Error Messages
    'oauth_authorization_code_missing' => 'Autorisierungscode nicht bereitgestellt.',
    'oauth_state_parameter_missing' => 'State-Parameter fehlt.',
    'oauth_state_parameter_invalid' => 'Ungültiger State-Parameter.',
    'oauth_user_not_found' => 'Benutzer nicht gefunden.',
    'oauth_connection_validation_failed' => 'Verbindung hergestellt, aber Validierung fehlgeschlagen. Bitte versuchen Sie, die Verbindung erneut herzustellen.',

    // Enhanced Validation Messages
    'validation_name_required' => 'Das Namensfeld ist erforderlich.',
    'validation_name_string' => 'Der Name muss eine gültige Textzeichenfolge sein.',
    'validation_name_max' => 'Der Name darf nicht länger als 255 Zeichen sein.',
    'validation_email_required' => 'Das E-Mail-Feld ist erforderlich.',
    'validation_email_format' => 'Die E-Mail-Adresse muss ein gültiges Format haben.',
    'validation_action_required' => 'Das Aktionsfeld ist erforderlich.',
    'validation_action_invalid' => 'Die ausgewählte Aktion ist ungültig.',
    'validation_team_members_required' => 'Mindestens ein Teammitglied muss ausgewählt werden.',
    'validation_team_members_min' => 'Mindestens ein Teammitglied muss ausgewählt werden.',
    'validation_team_member_invalid' => 'Ein oder mehrere ausgewählte Teammitglieder sind ungültig.',
    'validation_primary_contact_required' => 'Ein Hauptansprechpartner muss ausgewählt werden.',
    'validation_primary_contact_invalid' => 'Der ausgewählte Hauptansprechpartner ist ungültig.',
    'validation_primary_contact_not_in_team' => 'Der Hauptansprechpartner muss ein Mitglied des ausgewählten Teams sein.',
    'validation_team_members_unauthorized' => 'Sie sind nicht berechtigt, einen oder mehrere der ausgewählten Teammitglieder zuzuweisen.',
    'validation_primary_contact_unauthorized' => 'Sie sind nicht berechtigt, diesen Hauptansprechpartner zuzuweisen.',

    // Team Assignment Messages
    'team_assignments_updated_success' => 'Teamzuweisungen erfolgreich aktualisiert.',
    'team_assignments_update_failed' => 'Fehler beim Aktualisieren der Teamzuweisungen. Bitte versuchen Sie es erneut.',

    // Cloud Storage Status Messages (from CloudStorageStatusMessages class)
    'cloud_storage_rate_limited' => 'Zu viele Token-Erneuerungsversuche. Bitte versuchen Sie es später erneut.',
    'cloud_storage_auth_required' => 'Authentifizierung erforderlich. Bitte verbinden Sie Ihr Konto erneut.',
    'cloud_storage_connection_healthy' => 'Verbunden und funktioniert ordnungsgemäß',
    'cloud_storage_not_connected' => 'Konto nicht verbunden. Bitte richten Sie Ihre Cloud-Speicher-Verbindung ein.',
    'cloud_storage_connection_issues' => 'Verbindungsproblem erkannt. Bitte testen Sie Ihre Verbindung.',
    'cloud_storage_multiple_failures' => 'Mehrere Verbindungsfehler erkannt. Bitte überprüfen Sie Ihre Kontoeinstellungen.',
    'cloud_storage_status_unknown' => 'Status unbekannt. Bitte aktualisieren Sie oder kontaktieren Sie den Support.',
    'cloud_storage_retry_time_message' => '{1} Zu viele Versuche. Bitte versuchen Sie es in :minutes Minute erneut.|[2,*] Zu viele Versuche. Bitte versuchen Sie es in :minutes Minuten erneut.',
    'cloud_storage_retry_seconds_message' => '{1} Zu viele Versuche. Bitte versuchen Sie es in :seconds Sekunde erneut.|[2,*] Zu viele Versuche. Bitte versuchen Sie es in :seconds Sekunden erneut.',
    'cloud_storage_retry_persistent_message' => '{1} Anhaltende Verbindungsprobleme mit :provider. Bitte versuchen Sie es in :minutes Minute erneut.|[2,*] Anhaltende Verbindungsprobleme mit :provider. Bitte versuchen Sie es in :minutes Minuten erneut.',
    'cloud_storage_retry_multiple_message' => '{1} Mehrere Verbindungsversuche erkannt. Bitte versuchen Sie es in :minutes Minute erneut.|[2,*] Mehrere Verbindungsversuche erkannt. Bitte versuchen Sie es in :minutes Minuten erneut.',

    // Cloud Storage Error Messages (from CloudStorageErrorMessageService class)
    'cloud_storage_token_expired' => 'Ihre :provider-Verbindung ist abgelaufen. Bitte verbinden Sie Ihr Konto erneut, um fortzufahren.',
    'cloud_storage_token_refresh_rate_limited' => 'Zu viele :provider-Verbindungsversuche. Bitte warten Sie, bevor Sie es erneut versuchen, um längere Verzögerungen zu vermeiden.',
    'cloud_storage_invalid_credentials' => 'Ungültige :provider-Anmeldedaten. Bitte überprüfen Sie Ihre Konfiguration und verbinden Sie Ihr Konto erneut.',
    'cloud_storage_insufficient_permissions' => 'Unzureichende :provider-Berechtigungen. Bitte verbinden Sie Ihr Konto erneut und stellen Sie sicher, dass Sie vollständigen Zugriff gewähren.',
    'cloud_storage_api_quota_exceeded' => ':provider API-Limit erreicht. Ihre Operationen werden automatisch fortgesetzt, wenn das Limit zurückgesetzt wird.',
    'cloud_storage_storage_quota_exceeded' => 'Ihr :provider-Speicher ist voll. Bitte schaffen Sie Platz oder upgraden Sie Ihren Speicherplan.',
    'cloud_storage_network_error' => 'Ein Netzwerkverbindungsproblem verhinderte die :provider-Operation. Bitte überprüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.',
    'cloud_storage_service_unavailable' => ':provider ist vorübergehend nicht verfügbar. Bitte versuchen Sie es in ein paar Minuten erneut.',
    'cloud_storage_timeout' => 'Die :provider :operation ist abgelaufen. Dies ist normalerweise vorübergehend - bitte versuchen Sie es erneut.',
    'cloud_storage_file_not_found' => 'Die Datei \':filename\' konnte in :provider nicht gefunden werden. Sie wurde möglicherweise gelöscht oder verschoben.',
    'cloud_storage_folder_access_denied' => 'Zugriff auf den :provider-Ordner verweigert. Bitte überprüfen Sie Ihre Ordnerberechtigungen.',
    'cloud_storage_invalid_file_type' => 'Der Dateityp von \':filename\' wird von :provider nicht unterstützt. Bitte versuchen Sie ein anderes Dateiformat.',
    'cloud_storage_file_too_large' => 'Die Datei \':filename\' ist zu groß für :provider. Bitte reduzieren Sie die Dateigröße und versuchen Sie es erneut.',
    'cloud_storage_invalid_file_content' => 'Die Datei \':filename\' scheint beschädigt zu sein. Bitte versuchen Sie, die Datei erneut hochzuladen.',
    'cloud_storage_provider_not_configured' => ':provider ist nicht ordnungsgemäß konfiguriert. Bitte überprüfen Sie Ihre Einstellungen und versuchen Sie es erneut.',
    'cloud_storage_unknown_error' => 'Ein unerwarteter Fehler ist mit :provider aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie den Support, falls das Problem weiterhin besteht.',
    'cloud_storage_default_error' => 'Ein Fehler ist während der :provider :operation aufgetreten. Bitte versuchen Sie es erneut.',

    // S3-spezifische Fehlermeldungen
    'cloud_storage_bucket_not_found' => 'Der :provider-Bucket existiert nicht. Bitte überprüfen Sie den Bucket-Namen in Ihrer Konfiguration.',
    'cloud_storage_bucket_access_denied' => 'Der Zugriff auf den :provider-Bucket wurde verweigert. Bitte überprüfen Sie, ob Ihre AWS-Anmeldedaten die erforderlichen Berechtigungen haben.',
    'cloud_storage_invalid_bucket_name' => 'Der :provider-Bucket-Name ist ungültig. Bucket-Namen müssen 3-63 Zeichen lang sein und den S3-Namensregeln folgen.',
    'cloud_storage_invalid_region' => 'Die :provider-Region ist ungültig. Bitte überprüfen Sie, ob die Region mit dem Standort Ihres Buckets übereinstimmt.',

    // Connection Issue Context Messages
    'cloud_storage_persistent_failures' => 'Anhaltende Verbindungsfehler erkannt. Bitte überprüfen Sie Ihre :provider-Kontoeinstellungen und Netzwerkverbindung.',
    'cloud_storage_multiple_token_refresh_attempts' => 'Mehrere Token-Erneuerungsversuche erkannt. Bitte warten Sie ein paar Minuten, bevor Sie es erneut versuchen.',
    'cloud_storage_retry_with_time' => 'Zu viele Token-Erneuerungsversuche. Bitte warten Sie noch :minutes Minute, bevor Sie es erneut versuchen.|Zu viele Token-Erneuerungsversuche. Bitte warten Sie noch :minutes Minuten, bevor Sie es erneut versuchen.',

    // Recovery Instructions
    'recovery_instructions_token_expired' => [
        'Gehen Sie zu Einstellungen → Cloud-Speicher',
        'Klicken Sie auf ":provider erneut verbinden"',
        'Schließen Sie den Autorisierungsprozess ab',
        'Wiederholen Sie Ihre Operation'
    ],
    'recovery_instructions_rate_limited' => [
        'Warten Sie, bis das Ratenlimit zurückgesetzt wird',
        'Vermeiden Sie wiederholtes Klicken auf Verbindungstest-Buttons',
        'Operationen werden automatisch fortgesetzt, wenn das Limit zurückgesetzt wird',
        'Kontaktieren Sie den Support, falls das Problem über die erwartete Zeit hinaus anhält'
    ],
    'recovery_instructions_insufficient_permissions' => [
        'Gehen Sie zu Einstellungen → Cloud-Speicher',
        'Klicken Sie auf ":provider erneut verbinden"',
        'Stellen Sie sicher, dass Sie vollständigen Zugriff gewähren, wenn Sie dazu aufgefordert werden',
        'Überprüfen Sie, dass Sie die notwendigen Berechtigungen haben'
    ],
    'recovery_instructions_storage_quota_exceeded' => [
        'Schaffen Sie Platz in Ihrem :provider-Konto',
        'Leeren Sie Ihren :provider-Papierkorb',
        'Erwägen Sie ein Upgrade Ihres :provider-Speicherplans',
        'Kontaktieren Sie Ihren Administrator, falls Sie ein Geschäftskonto verwenden'
    ],
    'recovery_instructions_api_quota_exceeded' => [
        'Warten Sie, bis das Kontingent zurückgesetzt wird (normalerweise innerhalb einer Stunde)',
        'Operationen werden automatisch fortgesetzt',
        'Erwägen Sie, große Operationen über mehrere Tage zu verteilen'
    ],
    'recovery_instructions_network_error' => [
        'Überprüfen Sie Ihre Internetverbindung',
        'Versuchen Sie es in ein paar Minuten erneut',
        'Kontaktieren Sie Ihren Netzwerkadministrator, falls das Problem anhält'
    ],
    'recovery_instructions_service_unavailable' => [
        'Warten Sie ein paar Minuten und versuchen Sie es erneut',
        'Überprüfen Sie die :provider-Statusseite für Service-Updates',
        'Operationen werden automatisch wiederholt'
    ],
    'recovery_instructions_timeout' => [
        'Versuchen Sie es erneut - Timeouts sind normalerweise vorübergehend',
        'Überprüfen Sie Ihre Internetverbindungsgeschwindigkeit',
        'Für große Dateien versuchen Sie das Hochladen während verkehrsarmer Zeiten'
    ],
    'recovery_instructions_folder_access_denied' => [
        'Überprüfen Sie, dass der Zielordner in Ihrem :provider existiert',
        'Überprüfen Sie, dass Sie Schreibberechtigungen für den Ordner haben',
        'Versuchen Sie, Ihr :provider-Konto erneut zu verbinden'
    ],
    'recovery_instructions_invalid_file_type' => [
        'Konvertieren Sie die Datei in ein unterstütztes Format',
        'Überprüfen Sie die von :provider unterstützten Dateitypen',
        'Versuchen Sie, eine andere Datei zum Testen hochzuladen'
    ],
    'recovery_instructions_file_too_large' => [
        'Komprimieren Sie die Datei, um ihre Größe zu reduzieren',
        'Teilen Sie große Dateien in kleinere Teile auf',
        'Verwenden Sie die Web-Oberfläche von :provider für sehr große Dateien'
    ],
    'recovery_instructions_invalid_file_content' => [
        'Überprüfen Sie, dass die Datei nicht beschädigt ist',
        'Versuchen Sie, die Datei neu zu erstellen oder erneut herunterzuladen',
        'Scannen Sie die Datei auf Viren oder Malware'
    ],
    'recovery_instructions_provider_not_configured' => [
        'Gehen Sie zu Einstellungen → Cloud-Speicher',
        'Überprüfen Sie Ihre Konfigurationseinstellungen',
        'Stellen Sie sicher, dass alle erforderlichen Felder korrekt ausgefüllt sind',
        'Kontaktieren Sie den Support, falls Sie Hilfe benötigen'
    ],
    'recovery_instructions_unknown_error' => [
        'Versuchen Sie die Operation erneut',
        'Überprüfen Sie Ihre Internetverbindung',
        'Kontaktieren Sie den Support, falls das Problem anhält',
        'Fügen Sie alle Fehlerdetails hinzu, wenn Sie den Support kontaktieren'
    ],
    'recovery_instructions_default' => [
        'Versuchen Sie die Operation erneut',
        'Überprüfen Sie Ihre Verbindung und Einstellungen',
        'Kontaktieren Sie den Support, falls das Problem anhält'
    ],

    // Cloud Storage Status Messages
    'cloud_storage_status_rate_limited' => 'Zu viele Token-Erneuerungsversuche. Bitte versuchen Sie es später erneut.',
    'cloud_storage_status_auth_required' => 'Authentifizierung erforderlich. Bitte verbinden Sie Ihr Konto erneut.',
    'cloud_storage_status_connection_healthy' => 'Verbunden und funktioniert ordnungsgemäß',
    'cloud_storage_status_not_connected' => 'Konto nicht verbunden. Bitte richten Sie Ihre Cloud-Speicher-Verbindung ein.',
    'cloud_storage_status_connection_issues' => 'Verbindungsproblem erkannt. Bitte testen Sie Ihre Verbindung.',
    'cloud_storage_status_multiple_failures' => 'Mehrere Verbindungsfehler erkannt. Bitte überprüfen Sie Ihre Kontoeinstellungen.',
    'cloud_storage_status_unknown' => 'Status unbekannt. Bitte aktualisieren Sie oder kontaktieren Sie den Support.',
    'cloud_storage_retry_time_message' => 'Zu viele Versuche. Bitte versuchen Sie es in :minutes Minute erneut.|Zu viele Versuche. Bitte versuchen Sie es in :minutes Minuten erneut.',

    // Cloud Storage Error Messages - Generic
    'cloud_storage_error_token_expired' => 'Ihre :provider-Verbindung ist abgelaufen. Bitte verbinden Sie Ihr Konto erneut, um fortzufahren.',
    'cloud_storage_error_token_refresh_rate_limited' => 'Zu viele Token-Erneuerungsversuche. Bitte versuchen Sie es später erneut.',
    'cloud_storage_error_invalid_credentials' => 'Ungültige :provider-Anmeldedaten. Bitte überprüfen Sie Ihre Konfiguration und verbinden Sie Ihr Konto erneut.',
    'cloud_storage_error_insufficient_permissions' => 'Unzureichende :provider-Berechtigungen. Bitte verbinden Sie Ihr Konto erneut und stellen Sie sicher, dass Sie vollständigen Zugriff gewähren.',
    'cloud_storage_error_api_quota_exceeded' => ':provider API-Limit erreicht. Ihre Vorgänge werden automatisch fortgesetzt, wenn das Limit zurückgesetzt wird.',
    'cloud_storage_error_storage_quota_exceeded' => 'Ihr :provider-Speicher ist voll. Bitte schaffen Sie Platz oder upgraden Sie Ihren Speicherplan.',
    'cloud_storage_error_network_error' => 'Ein Netzwerkverbindungsproblem verhinderte den :provider-Vorgang. Bitte überprüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.',
    'cloud_storage_error_service_unavailable' => ':provider ist vorübergehend nicht verfügbar. Bitte versuchen Sie es in ein paar Minuten erneut.',
    'cloud_storage_error_timeout' => 'Der :provider :operation ist abgelaufen. Dies ist normalerweise vorübergehend - bitte versuchen Sie es erneut.',
    'cloud_storage_error_file_not_found' => 'Die Datei \':filename\' konnte in :provider nicht gefunden werden. Sie wurde möglicherweise gelöscht oder verschoben.',
    'cloud_storage_error_folder_access_denied' => 'Zugriff auf den :provider-Ordner verweigert. Bitte überprüfen Sie Ihre Ordnerberechtigungen.',
    'cloud_storage_error_invalid_file_type' => 'Der Dateityp von \':filename\' wird von :provider nicht unterstützt. Bitte versuchen Sie ein anderes Dateiformat.',
    'cloud_storage_error_file_too_large' => 'Die Datei \':filename\' ist zu groß für :provider. Bitte reduzieren Sie die Dateigröße und versuchen Sie es erneut.',
    'cloud_storage_error_invalid_file_content' => 'Die Datei \':filename\' scheint beschädigt zu sein. Bitte versuchen Sie, die Datei erneut hochzuladen.',
    'cloud_storage_error_provider_not_configured' => ':provider ist nicht ordnungsgemäß konfiguriert. Bitte überprüfen Sie Ihre Einstellungen und versuchen Sie es erneut.',
    'cloud_storage_error_unknown_error' => 'Ein unerwarteter Fehler ist mit :provider aufgetreten. :message',
    'cloud_storage_error_default' => 'Ein Fehler ist während des :provider :operation aufgetreten. Bitte versuchen Sie es erneut.',

    // Cloud Storage Recovery Instructions - Token Expired
    'cloud_storage_recovery_token_expired_1' => 'Gehen Sie zu Einstellungen → Cloud-Speicher',
    'cloud_storage_recovery_token_expired_2' => 'Klicken Sie auf ":provider erneut verbinden"',
    'cloud_storage_recovery_token_expired_3' => 'Schließen Sie den Autorisierungsprozess ab',
    'cloud_storage_recovery_token_expired_4' => 'Wiederholen Sie Ihren Vorgang',

    // Cloud Storage Recovery Instructions - Rate Limited
    'cloud_storage_recovery_rate_limited_1' => 'Warten Sie, bis das Rate-Limit zurückgesetzt wird',
    'cloud_storage_recovery_rate_limited_2' => 'Vermeiden Sie wiederholtes Klicken auf Verbindungstest-Schaltflächen',
    'cloud_storage_recovery_rate_limited_3' => 'Vorgänge werden automatisch fortgesetzt, wenn das Limit zurückgesetzt wird',
    'cloud_storage_recovery_rate_limited_4' => 'Kontaktieren Sie den Support, wenn das Problem über die erwartete Zeit hinaus anhält',

    // Cloud Storage Recovery Instructions - Insufficient Permissions
    'cloud_storage_recovery_insufficient_permissions_1' => 'Gehen Sie zu Einstellungen → Cloud-Speicher',
    'cloud_storage_recovery_insufficient_permissions_2' => 'Klicken Sie auf ":provider erneut verbinden"',
    'cloud_storage_recovery_insufficient_permissions_3' => 'Stellen Sie sicher, dass Sie vollständigen Zugriff gewähren, wenn Sie dazu aufgefordert werden',
    'cloud_storage_recovery_insufficient_permissions_4' => 'Überprüfen Sie, dass Sie die erforderlichen Berechtigungen haben',

    // Cloud Storage Recovery Instructions - Storage Quota Exceeded
    'cloud_storage_recovery_storage_quota_exceeded_1' => 'Schaffen Sie Platz in Ihrem :provider-Konto',
    'cloud_storage_recovery_storage_quota_exceeded_2' => 'Leeren Sie Ihren :provider-Papierkorb',
    'cloud_storage_recovery_storage_quota_exceeded_3' => 'Erwägen Sie ein Upgrade Ihres :provider-Speicherplans',
    'cloud_storage_recovery_storage_quota_exceeded_4' => 'Kontaktieren Sie Ihren Administrator, wenn Sie ein Geschäftskonto verwenden',

    // Cloud Storage Recovery Instructions - API Quota Exceeded
    'cloud_storage_recovery_api_quota_exceeded_1' => 'Warten Sie, bis das Kontingent zurückgesetzt wird (normalerweise innerhalb einer Stunde)',
    'cloud_storage_recovery_api_quota_exceeded_2' => 'Vorgänge werden automatisch fortgesetzt',
    'cloud_storage_recovery_api_quota_exceeded_3' => 'Erwägen Sie, große Vorgänge auf mehrere Tage zu verteilen',

    // Cloud Storage Recovery Instructions - Network Error
    'cloud_storage_recovery_network_error_1' => 'Überprüfen Sie Ihre Internetverbindung',
    'cloud_storage_recovery_network_error_2' => 'Versuchen Sie es in ein paar Minuten erneut',
    'cloud_storage_recovery_network_error_3' => 'Kontaktieren Sie Ihren Netzwerkadministrator, wenn das Problem anhält',

    // Cloud Storage Recovery Instructions - Service Unavailable
    'cloud_storage_recovery_service_unavailable_1' => 'Warten Sie ein paar Minuten und versuchen Sie es erneut',
    'cloud_storage_recovery_service_unavailable_2' => 'Überprüfen Sie die :provider-Statusseite für Service-Updates',
    'cloud_storage_recovery_service_unavailable_3' => 'Vorgänge werden automatisch wiederholt',

    // Cloud Storage Recovery Instructions - Timeout
    'cloud_storage_recovery_timeout_1' => 'Versuchen Sie es erneut - Timeouts sind normalerweise vorübergehend',
    'cloud_storage_recovery_timeout_2' => 'Überprüfen Sie Ihre Internetverbindungsgeschwindigkeit',
    'cloud_storage_recovery_timeout_3' => 'Für große Dateien versuchen Sie das Hochladen außerhalb der Stoßzeiten',

    // Cloud Storage Recovery Instructions - Folder Access Denied
    'cloud_storage_recovery_folder_access_denied_1' => 'Überprüfen Sie, dass der Zielordner in Ihrem :provider existiert',
    'cloud_storage_recovery_folder_access_denied_2' => 'Überprüfen Sie, dass Sie Schreibberechtigungen für den Ordner haben',
    'cloud_storage_recovery_folder_access_denied_3' => 'Versuchen Sie, Ihr :provider-Konto erneut zu verbinden',

    // Cloud Storage Recovery Instructions - Invalid File Type
    'cloud_storage_recovery_invalid_file_type_1' => 'Konvertieren Sie die Datei in ein unterstütztes Format',
    'cloud_storage_recovery_invalid_file_type_2' => 'Überprüfen Sie die unterstützten Dateitypen von :provider',
    'cloud_storage_recovery_invalid_file_type_3' => 'Versuchen Sie, eine andere Datei zum Testen hochzuladen',

    // Cloud Storage Recovery Instructions - File Too Large
    'cloud_storage_recovery_file_too_large_1' => 'Komprimieren Sie die Datei, um ihre Größe zu reduzieren',
    'cloud_storage_recovery_file_too_large_2' => 'Teilen Sie große Dateien in kleinere Teile auf',
    'cloud_storage_recovery_file_too_large_3' => 'Verwenden Sie die Web-Oberfläche von :provider für sehr große Dateien',

    // Cloud Storage Recovery Instructions - Invalid File Content
    'cloud_storage_recovery_invalid_file_content_1' => 'Überprüfen Sie, dass die Datei nicht beschädigt ist',
    'cloud_storage_recovery_invalid_file_content_2' => 'Versuchen Sie, die Datei neu zu erstellen oder erneut herunterzuladen',
    'cloud_storage_recovery_invalid_file_content_3' => 'Scannen Sie die Datei auf Viren oder Malware',

    // Cloud Storage Recovery Instructions - Provider Not Configured
    'cloud_storage_recovery_provider_not_configured_1' => 'Gehen Sie zu Einstellungen → Cloud-Speicher',
    'cloud_storage_recovery_provider_not_configured_2' => 'Überprüfen Sie Ihre Konfigurationseinstellungen',
    'cloud_storage_recovery_provider_not_configured_3' => 'Stellen Sie sicher, dass alle erforderlichen Felder korrekt ausgefüllt sind',
    'cloud_storage_recovery_provider_not_configured_4' => 'Kontaktieren Sie den Support, wenn Sie Hilfe benötigen',

    // Cloud Storage Recovery Instructions - Unknown Error
    'cloud_storage_recovery_unknown_error_1' => 'Versuchen Sie den Vorgang erneut',
    'cloud_storage_recovery_unknown_error_2' => 'Überprüfen Sie Ihre Internetverbindung',
    'cloud_storage_recovery_unknown_error_3' => 'Kontaktieren Sie den Support, wenn das Problem anhält',
    'cloud_storage_recovery_unknown_error_4' => 'Fügen Sie alle Fehlerdetails hinzu, wenn Sie den Support kontaktieren',

    // Cloud Storage Recovery Instructions - Default
    'cloud_storage_recovery_default_1' => 'Versuchen Sie den Vorgang erneut',
    'cloud_storage_recovery_default_2' => 'Überprüfen Sie Ihre Verbindung und Einstellungen',
    'cloud_storage_recovery_default_3' => 'Kontaktieren Sie den Support, wenn das Problem anhält',

    // Cloud Storage Provider Display Names
    'cloud_storage_provider_google_drive' => 'Google Drive',
    'cloud_storage_provider_amazon_s3' => 'Amazon S3',
    'cloud_storage_provider_azure_blob' => 'Azure Blob Storage',
    'cloud_storage_provider_microsoft_teams' => 'Microsoft Teams',
    'cloud_storage_provider_dropbox' => 'Dropbox',
    'cloud_storage_provider_onedrive' => 'OneDrive',

    // Recovery Strategy Messages
    'recovery_strategy_token_refresh' => 'Versuche Authentifizierungs-Token zu erneuern',
    'recovery_strategy_network_retry' => 'Wiederholung nach Netzwerkverbindungsproblemen',
    'recovery_strategy_quota_wait' => 'Warten auf Wiederherstellung des API-Kontingents',
    'recovery_strategy_service_retry' => 'Wiederholung nachdem Service verfügbar wird',
    'recovery_strategy_health_check_retry' => 'Durchführung von Gesundheitsprüfung und Wiederholung',
    'recovery_strategy_user_intervention_required' => 'Manuelle Benutzereingriff erforderlich',
    'recovery_strategy_no_action_needed' => 'Keine Aktion erforderlich, Verbindung ist gesund',
    'recovery_strategy_unknown' => 'Unbekannte Wiederherstellungsstrategie',
    'google_drive_action_storage_quota_exceeded_3' => 'Erwägen Sie ein Upgrade Ihres Google Drive-Speicherplans',
    'google_drive_action_storage_quota_exceeded_4' => 'Kontaktieren Sie Ihren Administrator, wenn Sie ein Geschäftskonto verwenden',

    // Google Drive Error Recovery Actions - API Quota Exceeded
    'google_drive_action_api_quota_exceeded_1' => 'Warten Sie, bis sich das Kontingent zurücksetzt (normalerweise innerhalb einer Stunde)',
    'google_drive_action_api_quota_exceeded_2' => 'Uploads werden automatisch fortgesetzt',
    'google_drive_action_api_quota_exceeded_3' => 'Erwägen Sie, Uploads über mehrere Tage zu verteilen bei großen Mengen',

    // Google Drive Error Recovery Actions - Invalid Credentials
    'google_drive_action_invalid_credentials_1' => 'Gehen Sie zu Einstellungen → Cloud-Speicher',
    'google_drive_action_invalid_credentials_2' => 'Trennen und verbinden Sie Ihr Google Drive-Konto erneut',
    'google_drive_action_invalid_credentials_3' => 'Stellen Sie sicher, dass Ihr Google-Konto aktiv und zugänglich ist',

    // Google Drive Error Recovery Actions - Folder Access Denied
    'google_drive_action_folder_access_denied_1' => 'Überprüfen Sie, dass der Zielordner in Ihrem Google Drive existiert',
    'google_drive_action_folder_access_denied_2' => 'Überprüfen Sie, dass Sie Schreibberechtigungen für den Ordner haben',
    'google_drive_action_folder_access_denied_3' => 'Versuchen Sie, Ihr Google Drive-Konto erneut zu verbinden',

    // Google Drive Error Recovery Actions - Invalid File Type
    'google_drive_action_invalid_file_type_1' => 'Konvertieren Sie die Datei in ein unterstütztes Format',
    'google_drive_action_invalid_file_type_2' => 'Überprüfen Sie die von Google Drive unterstützten Dateitypen',
    'google_drive_action_invalid_file_type_3' => 'Versuchen Sie, eine andere Datei zum Testen hochzuladen',

    // Google Drive Error Recovery Actions - File Too Large
    'google_drive_action_file_too_large_1' => 'Komprimieren Sie die Datei, um ihre Größe zu reduzieren',
    'google_drive_action_file_too_large_2' => 'Teilen Sie große Dateien in kleinere Teile auf',
    'google_drive_action_file_too_large_3' => 'Verwenden Sie die Google Drive-Weboberfläche für sehr große Dateien',

    // Time-related messages for quota reset
    'quota_reset_time_1_hour' => '1 Stunde',
    'quota_reset_time_hours' => ':hours Stunden',
    'quota_reset_time_minutes' => ':minutes Minuten',
    'quota_reset_time_unknown' => 'kurze Zeit',

    // Common error messages
    'error_generic' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',

    // Token Refresh Result Messages
    'token_refresh_success' => 'Token erfolgreich erneuert',
    'token_already_valid' => 'Token ist bereits gültig',
    'token_refreshed_by_another_process' => 'Token wurde von einem anderen Prozess erneuert',
    'token_already_valid_description' => 'Token war bereits gültig und musste nicht erneuert werden',
    'token_refreshed_by_another_process_description' => 'Token wurde von einem anderen gleichzeitigen Prozess erneuert',
    'token_refresh_success_description' => 'Token wurde erfolgreich erneuert',
    'token_refresh_failed_description' => 'Token-Erneuerung fehlgeschlagen: :message',

    // Proactive Token Renewal Messages
    'proactive_refresh_provider_not_supported' => 'Anbieter wird für proaktive Erneuerung nicht unterstützt',
    'proactive_refresh_no_token_found' => 'Kein Authentifizierungs-Token gefunden',
    'proactive_refresh_token_not_expiring' => 'Token läuft nicht bald ab und muss nicht erneuert werden',
    'proactive_refresh_requires_reauth' => 'Token erfordert eine erneute Benutzerauthentifizierung',

    // Health Status Messages
    'health_status_healthy' => 'Gesund',
    'health_status_authentication_required' => 'Authentifizierung erforderlich',
    'health_status_connection_issues' => 'Verbindungsprobleme',
    'health_status_not_connected' => 'Nicht verbunden',
    'health_status_token_validation_failed' => 'Token-Validierung fehlgeschlagen',
    'health_status_api_connectivity_test_failed' => 'API-Konnektivitätstest fehlgeschlagen',
    'health_status_authentication_error' => 'Authentifizierungsfehler',
    'health_status_connection_error' => 'Verbindungsfehler',
    'health_status_token_error' => 'Token-Fehler',
    'health_status_api_error' => 'API-Fehler',

    // Token Renewal Notification Service
    'notification_failure_alert_subject' => 'Benachrichtigungsfehler-Warnung - Benutzer :email',
    'notification_failure_alert_body' => 'Fehler beim Senden der :type Benachrichtigung an Benutzer :email für Anbieter :provider nach :attempts Versuchen.\n\nLetzter Fehler: :error\n\nBitte überprüfen Sie die E-Mail-Adresse des Benutzers und die Systemkonfiguration.',

    // Token Expired Email
    'token_expired_subject' => ':provider Verbindung Abgelaufen - Aktion Erforderlich',
    'token_expired_heading' => ':provider Verbindung Abgelaufen',
    'token_expired_subheading' => 'Aktion Erforderlich um Datei-Uploads Fortzusetzen',
    'token_expired_alert' => 'Aufmerksamkeit Erforderlich: Ihre :provider Verbindung ist abgelaufen und muss erneuert werden.',
    'token_expired_greeting' => 'Hallo :name,',
    'token_expired_intro' => 'Wir schreiben Ihnen, um Sie darüber zu informieren, dass Ihre :provider Verbindung abgelaufen ist. Das bedeutet, dass neue Datei-Uploads nicht verarbeitet werden können, bis Sie Ihr Konto erneut verbinden.',
    'token_expired_what_this_means' => 'Was Das Bedeutet:',
    'token_expired_impact_uploads' => 'Neue Datei-Uploads werden fehlschlagen, bis Sie erneut verbinden',
    'token_expired_impact_existing' => 'Bestehende Dateien in Ihrem :provider sind nicht betroffen',
    'token_expired_impact_resume' => 'Ihr Upload-System wird den normalen Betrieb wieder aufnehmen, sobald es erneut verbunden ist',
    'token_expired_how_to_reconnect' => 'Wie Sie Erneut Verbinden:',
    'token_expired_step_1' => 'Klicken Sie auf die Schaltfläche ":provider Erneut Verbinden" unten',
    'token_expired_step_2' => 'Melden Sie sich bei Ihrem :provider Konto an, wenn Sie dazu aufgefordert werden',
    'token_expired_step_3' => 'Gewähren Sie dem Upload-System die Berechtigung, auf Ihr Konto zuzugreifen',
    'token_expired_step_4' => 'Überprüfen Sie, dass die Verbindung in Ihrem Dashboard funktioniert',
    'token_expired_reconnect_button' => ':provider Erneut Verbinden',
    'token_expired_why_happened' => 'Warum Ist Das Passiert?',
    'token_expired_explanation' => ':provider Verbindungen laufen regelmäßig aus Sicherheitsgründen ab. Das ist normal und hilft, Ihr Konto zu schützen. Das System hat versucht, die Verbindung automatisch zu erneuern, aber jetzt ist ein manueller Eingriff erforderlich.',
    'token_expired_need_help' => 'Brauchen Sie Hilfe?',
    'token_expired_support' => 'Wenn Sie Probleme beim erneuten Verbinden haben oder Fragen zu diesem Prozess haben, zögern Sie nicht, unser Support-Team unter :email zu kontaktieren.',
    'token_expired_footer_important' => 'Diese E-Mail wurde gesendet, weil Ihre :provider Verbindung abgelaufen ist. Wenn Sie diese E-Mail nicht erwartet haben, kontaktieren Sie bitte sofort den Support.',
    'token_expired_footer_automated' => 'Dies ist eine automatisierte Nachricht von Ihrem Datei-Upload-System. Bitte antworten Sie nicht direkt auf diese E-Mail.',

    // Token Refresh Failed Email
    'token_refresh_failed_subject' => ':provider Verbindungsproblem - :urgency',
    'token_refresh_failed_heading' => ':provider Verbindungsproblem',
    'token_refresh_failed_action_required' => 'Aktion Erforderlich',
    'token_refresh_failed_auto_recovery' => 'Automatische Wiederherstellung Im Gange',
    'token_refresh_failed_alert_action' => 'Aktion Erforderlich: Ihre :provider Verbindung benötigt manuelle Aufmerksamkeit.',
    'token_refresh_failed_alert_auto' => 'Verbindungsproblem: Wir arbeiten daran, Ihre :provider Verbindung automatisch wiederherzustellen.',
    'token_refresh_failed_greeting' => 'Hallo :name,',
    'token_refresh_failed_intro' => 'Wir sind auf ein Problem gestoßen, als wir versucht haben, Ihre :provider Verbindung zu erneuern. Hier ist, was passiert ist und was wir dagegen tun:',
    'token_refresh_failed_issue_details' => 'Problem-Details:',
    'token_refresh_failed_error_type' => 'Fehlertyp: :type',
    'token_refresh_failed_attempt' => 'Versuch: :current von :max',
    'token_refresh_failed_description' => 'Beschreibung: :description',
    'token_refresh_failed_technical_details' => 'Technische Details: :details',
    'token_refresh_failed_what_to_do' => 'Was Sie Tun Müssen:',
    'token_refresh_failed_manual_required' => 'Diese Art von Fehler erfordert einen manuellen Eingriff. Bitte verbinden Sie Ihr :provider Konto erneut, um die Datei-Upload-Funktionalität wiederherzustellen.',
    'token_refresh_failed_reconnect_now' => ':provider Jetzt Erneut Verbinden',
    'token_refresh_failed_why_manual' => 'Warum Manuelle Aktion Erforderlich Ist:',
    'token_refresh_failed_credentials_invalid' => 'Ihre Authentifizierungsdaten sind nicht mehr gültig. Das passiert normalerweise, wenn Sie Ihr Passwort ändern, den Zugriff widerrufen oder der Token für längere Zeit inaktiv war.',
    'token_refresh_failed_cannot_resolve' => 'Diese Art von Fehler kann nicht automatisch behoben werden und erfordert, dass Sie die Verbindung neu herstellen.',
    'token_refresh_failed_auto_recovery_status' => 'Status der Automatischen Wiederherstellung:',
    'token_refresh_failed_no_action_needed' => 'Das System wird weiterhin versuchen, die Verbindung automatisch wiederherzustellen. Sie müssen im Moment keine Aktion unternehmen.',
    'token_refresh_failed_max_attempts' => 'Maximale Versuche Erreicht:',
    'token_refresh_failed_exhausted' => 'Das System hat alle automatischen Wiederholungsversuche ausgeschöpft. Bitte verbinden Sie manuell erneut, um die Funktionalität wiederherzustellen.',
    'token_refresh_failed_what_happens_next' => 'Was Als Nächstes Passiert:',
    'token_refresh_failed_auto_retry' => 'Das System wird automatisch die Verbindung wiederholen',
    'token_refresh_failed_success_email' => 'Bei Erfolg erhalten Sie eine Bestätigungs-E-Mail',
    'token_refresh_failed_manual_notify' => 'Wenn alle Versuche fehlschlagen, werden Sie benachrichtigt, manuell erneut zu verbinden',
    'token_refresh_failed_uploads_paused' => 'Datei-Uploads sind vorübergehend pausiert, bis die Verbindung wiederhergestellt ist',
    'token_refresh_failed_impact' => 'Auswirkungen auf Ihren Service:',
    'token_refresh_failed_uploads_impact' => 'Datei-Uploads: Neue Uploads sind vorübergehend pausiert',
    'token_refresh_failed_existing_impact' => 'Bestehende Dateien: Alle zuvor hochgeladenen Dateien bleiben sicher und zugänglich',
    'token_refresh_failed_system_impact' => 'Systemstatus: Alle anderen Funktionen funktionieren weiterhin normal',
    'token_refresh_failed_no_action_required' => 'Im Moment ist keine Aktion von Ihrer Seite erforderlich. Wir werden Sie über den Wiederherstellungsfortschritt auf dem Laufenden halten.',
    'token_refresh_failed_need_help' => 'Brauchen Sie Hilfe?',
    'token_refresh_failed_support' => 'Wenn Sie wiederholt Verbindungsprobleme haben oder Hilfe beim erneuten Verbinden benötigen, kontaktieren Sie bitte unser Support-Team unter :email. Fügen Sie diese Fehlerreferenz hinzu: :reference',
    'token_refresh_failed_error_reference' => 'Fehlerreferenz: :type (Versuch :attempt)',
    'token_refresh_failed_timestamp' => 'Zeitstempel: :timestamp',
    'token_refresh_failed_footer_automated' => 'Dies ist eine automatisierte Nachricht von Ihrem Datei-Upload-System. Bitte antworten Sie nicht direkt auf diese E-Mail.',

    // Connection Restored Email
    'connection_restored_subject' => ':provider Verbindung Wiederhergestellt',
    'connection_restored_heading' => '✅ :provider Verbindung Wiederhergestellt',
    'connection_restored_subheading' => 'Ihr Datei-Upload-System ist wieder online!',
    'connection_restored_alert' => 'Großartige Neuigkeiten: Ihre :provider Verbindung wurde erfolgreich wiederhergestellt und funktioniert normal.',
    'connection_restored_greeting' => 'Hallo :name,',
    'connection_restored_intro' => 'Wir freuen uns, Ihnen mitteilen zu können, dass das Verbindungsproblem mit Ihrem :provider Konto behoben wurde. Ihr Datei-Upload-System ist jetzt wieder vollständig betriebsbereit.',
    'connection_restored_current_status' => 'Aktueller Status:',
    'connection_restored_connection_status' => 'Verbindung: ✅ Aktiv und gesund',
    'connection_restored_uploads_status' => 'Datei-Uploads: ✅ Akzeptiert neue Uploads',
    'connection_restored_pending_status' => 'Ausstehende Dateien: ✅ Verarbeitung aller wartenden Uploads',
    'connection_restored_system_status' => 'Systemstatus: ✅ Alle Funktionen betriebsbereit',
    'connection_restored_what_happened' => 'Was Passiert Ist:',
    'connection_restored_explanation' => 'Das System hat Ihre :provider Authentifizierung erfolgreich erneuert und die vollständige Konnektivität wiederhergestellt. Alle Datei-Uploads, die während des Verbindungsproblems vorübergehend pausiert wurden, werden jetzt automatisch verarbeitet.',
    'connection_restored_whats_happening' => 'Was Jetzt Passiert:',
    'connection_restored_processing_queued' => 'Das System verarbeitet alle Uploads, die während des Ausfalls in die Warteschlange eingereiht wurden',
    'connection_restored_accepting_new' => 'Neue Datei-Uploads werden akzeptiert und normal verarbeitet',
    'connection_restored_operations_resumed' => 'Alle :provider Operationen wurden wieder aufgenommen',
    'connection_restored_monitoring_active' => 'Verbindungsüberwachung ist aktiv, um zukünftige Probleme zu verhindern',
    'connection_restored_access_dashboard' => 'Zugriff auf Ihr Dashboard:',
    'connection_restored_dashboard_intro' => 'Sie können Ihren Upload-Status einsehen und Ihre Dateien über Ihr Dashboard verwalten:',
    'connection_restored_view_dashboard' => 'Dashboard Anzeigen',
    'connection_restored_preventing_issues' => 'Zukünftige Probleme Verhindern:',
    'connection_restored_keep_active' => 'Halten Sie Ihr :provider Konto aktiv und in gutem Zustand',
    'connection_restored_avoid_password_change' => 'Vermeiden Sie es, Ihr :provider Passwort zu ändern, ohne die Verbindung zu aktualisieren',
    'connection_restored_monitor_email' => 'Überwachen Sie Ihre E-Mails auf Verbindungswarnungen',
    'connection_restored_contact_support' => 'Kontaktieren Sie den Support, wenn Sie ungewöhnliches Verhalten bemerken',
    'connection_restored_need_assistance' => 'Brauchen Sie Hilfe?',
    'connection_restored_support' => 'Wenn Sie Probleme mit Datei-Uploads haben oder Fragen zu Ihrer :provider Verbindung haben, zögern Sie nicht, unser Support-Team unter :email zu kontaktieren.',
    'connection_restored_footer_timestamp' => 'Verbindung Wiederhergestellt: :timestamp',
    'connection_restored_footer_service_status' => 'Service-Status: Alle Systeme betriebsbereit',
    'connection_restored_footer_thanks' => 'Vielen Dank für Ihre Geduld während des Verbindungsproblems. Dies ist eine automatisierte Nachricht von Ihrem Datei-Upload-System.',

    // Error Type Display Names
    'error_type_network_timeout' => 'Netzwerk-Timeout',
    'error_type_invalid_refresh_token' => 'Ungültiger Erneuerungs-Token',
    'error_type_expired_refresh_token' => 'Abgelaufener Erneuerungs-Token',
    'error_type_api_quota_exceeded' => 'API-Kontingent Überschritten',
    'error_type_service_unavailable' => 'Service Nicht Verfügbar',
    'error_type_unknown_error' => 'Unbekannter Fehler',

    // Error Descriptions
    'error_desc_network_timeout' => 'Wir sind auf ein Netzwerk-Timeout gestoßen, als wir versucht haben, Ihre Verbindung zu erneuern. Das ist normalerweise vorübergehend und das System wird automatisch wiederholen.',
    'error_desc_invalid_refresh_token' => 'Ihr gespeicherter Authentifizierungs-Token ist nicht mehr gültig. Das passiert normalerweise, wenn Sie den Zugriff widerrufen oder Ihr Passwort beim Cloud-Service ändern.',
    'error_desc_expired_refresh_token' => 'Ihr Authentifizierungs-Token ist abgelaufen und kann nicht automatisch erneuert werden. Sie müssen Ihr Konto erneut verbinden.',
    'error_desc_api_quota_exceeded' => 'Der Cloud-Service hat unseren Zugriff aufgrund hoher Nutzung vorübergehend begrenzt. Das System wird automatisch wiederholen, sobald das Limit zurückgesetzt wird.',
    'error_desc_service_unavailable' => 'Der Cloud-Service ist vorübergehend nicht verfügbar. Das ist normalerweise ein vorübergehendes Problem auf ihrer Seite, und das System wird automatisch wiederholen.',
    'error_desc_unknown_error' => 'Ein unerwarteter Fehler ist beim Erneuern Ihrer Verbindung aufgetreten. Unser technisches Team wurde benachrichtigt und wird untersuchen.',

    // Retry Information
    'retry_no_automatic' => 'Es wird kein automatischer Wiederholungsversuch unternommen. Bitte verbinden Sie manuell erneut.',
    'retry_max_attempts_reached' => 'Maximale Wiederholungsversuche erreicht. Bitte verbinden Sie manuell erneut.',
    'retry_in_seconds' => 'Das System wird in :seconds Sekunden wiederholen. :remaining Versuche verbleibend.',
    'retry_in_minutes' => 'Das System wird in :minutes Minuten wiederholen. :remaining Versuche verbleibend.',
    'retry_in_hours' => 'Das System wird in :hours Stunden wiederholen. :remaining Versuche verbleibend.',

    // Provider Display Names
    'provider_google_drive' => 'Google Drive',
    'provider_microsoft_teams' => 'Microsoft Teams',
    'provider_dropbox' => 'Dropbox',

    // Connection Recovery Messages
    'recovery_connection_healthy' => 'Verbindung ist gesund',
    'recovery_connection_health_restored' => 'Verbindungsgesundheit wiederhergestellt',
    'recovery_token_refreshed_successfully' => 'Token erfolgreich erneuert',
    'recovery_network_connectivity_restored' => 'Netzwerkverbindung wiederhergestellt',
    'recovery_api_quota_restored' => 'API-Kontingent wiederhergestellt',
    'recovery_service_availability_restored' => 'Dienstverfügbarkeit wiederhergestellt',
    'recovery_no_action_needed' => 'Keine Aktion erforderlich',
    'recovery_user_intervention_required' => 'Benutzereingriff erforderlich',
    'recovery_manual_action_needed' => 'Manuelle Aktion erforderlich',
    'recovery_failed_due_to_exception' => 'Wiederherstellung aufgrund einer Ausnahme fehlgeschlagen',
    'recovery_strategy_failed' => 'Wiederherstellungsstrategie fehlgeschlagen',
    'recovery_unknown_strategy' => 'Unbekannte Wiederherstellungsstrategie',

    // Recovery Failure Messages
    'recovery_token_refresh_failed' => 'Token-Erneuerung fehlgeschlagen',
    'recovery_network_connectivity_still_failing' => 'Netzwerkverbindung funktioniert immer noch nicht',
    'recovery_api_quota_still_exceeded' => 'API-Kontingent immer noch überschritten',
    'recovery_service_still_unavailable' => 'Dienst immer noch nicht verfügbar',
    'recovery_connection_still_unhealthy' => 'Verbindung immer noch ungesund',

    // Recovery Exception Messages
    'recovery_token_refresh_exception' => 'Token-Erneuerungs-Ausnahme',
    'recovery_network_test_exception' => 'Netzwerktest-Ausnahme',
    'recovery_quota_check_exception' => 'Kontingentprüfungs-Ausnahme',
    'recovery_service_check_exception' => 'Dienstprüfungs-Ausnahme',
    'recovery_health_check_exception' => 'Gesundheitsprüfungs-Ausnahme',

    // Upload Recovery Messages
    'recovery_local_file_no_longer_exists' => 'Lokale Datei existiert nicht mehr',
    'recovery_no_target_user_found' => 'Kein Zielbenutzer gefunden',
    'recovery_retry_job_permanently_failed' => 'Wiederholungsauftrag dauerhaft fehlgeschlagen',
    'recovery_upload_retry_failed_for_file' => 'Upload-Wiederholung fehlgeschlagen für Datei',

    // Token Monitoring Dashboard
    'token_monitoring' => [
        'dashboard_title' => 'Token-Überwachungs-Dashboard',
        'dashboard_description' => 'Überwachen Sie die Google Drive Token-Gesundheit, Erneuerungsoperationen und System-Performance-Metriken.',
        'metrics_reset_success' => 'Metriken für Anbieter zurückgesetzt: :provider',
        'overview_title' => 'Systemübersicht',
        'performance_metrics_title' => 'Performance-Metriken',
        'token_status_title' => 'Token-Status-Zusammenfassung',
        'recent_operations_title' => 'Aktuelle Operationen',
        'health_trends_title' => 'Gesundheitstrends',
        'user_statistics_title' => 'Benutzerstatistiken',
        'system_status_title' => 'Systemstatus',
        'recommendations_title' => 'Empfehlungen',
        'export_data' => 'Daten Exportieren',
        'reset_metrics' => 'Metriken Zurücksetzen',
        'refresh_dashboard' => 'Dashboard Aktualisieren',
        'last_updated' => 'Zuletzt Aktualisiert',
        'total_users' => 'Benutzer Gesamt',
        'connected_users' => 'Verbundene Benutzer',
        'success_rate' => 'Erfolgsrate',
        'average_refresh_time' => 'Durchschnittliche Erneuerungszeit',
        'active_alerts' => 'Aktive Warnungen',
        'overall_health' => 'Gesamtgesundheit',
        'tokens_expiring_soon' => 'Laufen Bald Ab',
        'tokens_requiring_attention' => 'Benötigen Aufmerksamkeit',
        'healthy' => 'Gesund',
        'warning' => 'Warnung',
        'critical' => 'Kritisch',
        'unknown' => 'Unbekannt',
        'degraded' => 'Beeinträchtigt',
        'unhealthy' => 'Ungesund',
        'queue_health' => 'Warteschlangen-Gesundheit',
        'cache_health' => 'Cache-Gesundheit',
        'database_health' => 'Datenbank-Gesundheit',
        'api_health' => 'API-Gesundheit',
        'overall_system_health' => 'Gesamtsystem-Gesundheit',
        'last_maintenance' => 'Letzte Wartung',
        'next_maintenance' => 'Nächste Wartung',
        'no_alerts' => 'Keine aktiven Warnungen',
        'view_details' => 'Details Anzeigen',
        'time_period' => 'Zeitraum',
        'last_hour' => 'Letzte Stunde',
        'last_6_hours' => 'Letzte 6 Stunden',
        'last_24_hours' => 'Letzte 24 Stunden',
        'last_week' => 'Letzte Woche',
        'provider' => 'Anbieter',
        'google_drive' => 'Google Drive',
        'microsoft_teams' => 'Microsoft Teams',
        'dropbox' => 'Dropbox',
        'loading' => 'Laden...',
        'loading_dashboard_data' => 'Dashboard-Daten werden geladen...',
        'total_users_label' => 'Benutzer insgesamt',
        'token_refresh_operations' => 'Token-Erneuerungsoperationen',
        'milliseconds' => 'Millisekunden',
        'overall_system_health' => 'Gesamtsystem-Gesundheit',
        'token_refresh' => 'Token-Erneuerung',
        'api_connectivity' => 'API-Konnektivität',
        'cache_performance' => 'Cache-Performance',
        'valid' => 'Gültig',
        'expiring_soon' => 'Laufen Bald Ab',
        'need_attention' => 'Benötigen Aufmerksamkeit',
        'error_breakdown' => 'Fehleraufschlüsselung',
        'no_errors_in_period' => 'Keine Fehler im ausgewählten Zeitraum',
        'time' => 'Zeit',
        'user' => 'Benutzer',
        'operation' => 'Operation',
        'status' => 'Status',
        'duration' => 'Dauer',
        'details' => 'Details',
        'success' => 'Erfolg',
        'error_loading_dashboard' => 'Fehler beim Laden des Dashboards',
        'try_again' => 'Erneut Versuchen',
        'recommended_actions' => 'Empfohlene Aktionen',
    ],

    // Token Status Service Messages
    'token_status_not_connected' => 'Kein Token gefunden - Konto nicht verbunden',
    'token_status_requires_intervention' => 'Token erfordert manuelle Wiederverbindung aufgrund wiederholter Fehler',
    'token_status_expired_refreshable' => 'Token abgelaufen, kann aber automatisch erneuert werden',
    'token_status_expired_manual' => 'Token abgelaufen und erfordert manuelle Wiederverbindung',
    'token_status_expiring_soon' => 'Token wird bald automatisch erneuert',
    'token_status_healthy_with_warnings' => 'Token gesund, aber hat :count kürzliche Erneuerungsfehler',
    'token_status_healthy' => 'Token ist gesund und gültig',
    'token_status_scheduled_now' => 'Jetzt geplant',
    'token_status_less_than_minute' => 'Weniger als 1 Minute',
    'token_status_minute' => 'Minute',
    'token_status_minutes' => 'Minuten',
    'token_status_hour' => 'Stunde',
    'token_status_hours' => 'Stunden',
    'token_status_day' => 'Tag',
    'token_status_days' => 'Tage',

    // E-Mail-Verifizierung
    'verify_email_title' => 'Verifizieren Sie Ihre E-Mail-Adresse',
    'verify_email_intro' => 'Um Dateien an :company_name hochzuladen, verifizieren Sie bitte Ihre E-Mail-Adresse, indem Sie auf den untenstehenden Link klicken.',
    'verify_email_sent' => 'Ein neuer Verifizierungslink wurde an die E-Mail-Adresse gesendet, die Sie bei der Registrierung angegeben haben.',
    'verify_email_resend_button' => 'Verifizierungs-E-Mail erneut senden',
    'verify_email_button' => 'E-Mail-Adresse verifizieren',
    'verify_email_ignore' => 'Wenn Sie diese Verifizierung nicht angefordert haben, können Sie diese E-Mail sicher ignorieren.',

    // Rollenbasierte E-Mail-Verifizierung
    // Administrator-Verifizierung
    'admin_verify_email_subject' => 'Verifizieren Sie Ihre Administrator-E-Mail-Adresse',
    'admin_verify_email_title' => 'Verifizieren Sie Ihre Administrator-E-Mail-Adresse',
    'admin_verify_email_intro' => 'Willkommen im :company_name Dateiverwaltungssystem. Als Administrator haben Sie vollständigen Zugriff auf die Benutzerverwaltung, Cloud-Speicher-Konfiguration und die Überwachung aller Datei-Uploads. Bitte verifizieren Sie Ihre E-Mail-Adresse, um die Einrichtung Ihres Admin-Kontos abzuschließen.',
    'admin_verify_email_button' => 'Administrator-Zugang verifizieren',

    // Mitarbeiter-Verifizierung  
    'employee_verify_email_subject' => 'Verifizieren Sie Ihre Mitarbeiter-E-Mail-Adresse',
    'employee_verify_email_title' => 'Verifizieren Sie Ihre Mitarbeiter-E-Mail-Adresse',
    'employee_verify_email_intro' => 'Willkommen bei :company_name! Als Mitarbeiter können Sie Kunden-Datei-Uploads direkt in Ihr Google Drive empfangen und Ihre eigenen Kundenbeziehungen verwalten. Bitte verifizieren Sie Ihre E-Mail-Adresse, um mit dem Empfang von Kundendateien zu beginnen.',
    'employee_verify_email_button' => 'Mitarbeiter-Zugang verifizieren',

    // Kunden-Verifizierung
    'client_verify_email_subject' => 'Verifizieren Sie Ihre E-Mail-Adresse',
    'client_verify_email_title' => 'Verifizieren Sie Ihre E-Mail-Adresse', 
    'client_verify_email_intro' => 'Um Dateien an :company_name hochzuladen, verifizieren Sie bitte Ihre E-Mail-Adresse, indem Sie auf den untenstehenden Link klicken. Nach der Verifizierung können Sie sicher Dateien hochladen, die direkt an das entsprechende Teammitglied geliefert werden.',
    'client_verify_email_button' => 'E-Mail-Adresse verifizieren',

    // Gemeinsame Elemente
    'thanks_signature' => 'Vielen Dank',

    // Profil
    'profile_information' => 'Profilinformationen',
    'profile_update' => 'Profil aktualisieren',
    'profile_saved' => 'Profil erfolgreich aktualisiert.',
    'profile_update_info' => 'Aktualisieren Sie die Profilinformationen und E-Mail-Adresse Ihres Kontos.',
    'profile_name' => 'Name',
    'profile_email' => 'E-Mail',
    'profile_save' => 'Speichern',
    'profile_email_unverified' => 'Ihre E-Mail-Adresse ist nicht verifiziert.',
    'profile_email_verify_resend' => 'Klicken Sie hier, um die Verifizierungs-E-Mail erneut zu senden.',
    'profile_email_verify_sent' => 'Ein neuer Verifizierungslink wurde an Ihre E-Mail-Adresse gesendet.',

    // Sicherheits- und Registrierungsvalidierungsnachrichten
    'public_registration_disabled' => 'Nur eingeladene Benutzer können sich bei diesem System anmelden. Wenn Sie glauben, dass Sie Zugriff haben sollten, wenden Sie sich bitte an den Administrator.',
    'email_domain_not_allowed' => 'Diese E-Mail-Domain ist für neue Registrierungen nicht zugelassen. Wenn Sie bereits ein Konto haben, versuchen Sie es erneut oder kontaktieren Sie den Support.',
    'security_settings_saved' => 'Die Sicherheitseinstellungen wurden erfolgreich aktualisiert.',
    
    // Erweiterte Verifizierungsnachrichten für bestehende vs. neue Benutzer
    'existing_user_verification_sent' => 'Verifizierungs-E-Mail an Ihr bestehendes Konto gesendet. Bitte überprüfen Sie Ihren Posteingang.',
    'new_user_verification_sent' => 'Verifizierungs-E-Mail gesendet. Bitte überprüfen Sie Ihren Posteingang, um die Registrierung abzuschließen.',
    'registration_temporarily_unavailable' => 'Die Registrierung kann derzeit nicht verarbeitet werden. Bitte versuchen Sie es später erneut.',

    // Upload Progress Overlay
    'upload_progress_title' => 'Dateien werden hochgeladen',
    'upload_progress_preparing' => 'Upload wird vorbereitet...',
    'upload_progress_overall' => 'Gesamtfortschritt',
    'upload_progress_cancel_button' => 'Upload abbrechen',
    'upload_progress_cancel_confirm' => 'Sind Sie sicher, dass Sie den Upload abbrechen möchten?',
    
    // Upload Progress Status Messages (for JavaScript)
    'upload_status_processing' => 'Uploads werden verarbeitet...',
    'upload_status_uploading_files' => 'Lade :remaining von :total Dateien hoch...',
    'upload_status_upload_completed_with_errors' => 'Upload abgeschlossen mit :count Fehler|Upload abgeschlossen mit :count Fehlern',
    'upload_button_uploading' => 'Dateien werden hochgeladen...',

    // Google Drive OAuth Callback Messages
    'google_drive_connected_success' => 'Erfolgreich mit Google Drive verbunden!',
    'google_drive_pending_uploads_queued' => ':count ausstehende Uploads wurden zur Wiederholung eingereiht.',
    'google_drive_connection_failed' => 'Verbindung zu Google Drive fehlgeschlagen',
    'google_drive_auth_code_expired' => 'Der Autorisierungscode ist abgelaufen. Bitte versuchen Sie erneut, eine Verbindung herzustellen.',
    'google_drive_access_denied' => 'Zugriff wurde verweigert. Bitte gewähren Sie die erforderlichen Berechtigungen, um Google Drive zu verbinden.',
    'google_drive_invalid_configuration' => 'Ungültige Google Drive-Konfiguration. Bitte wenden Sie sich an Ihren Administrator.',
    'google_drive_authorization_failed' => 'Autorisierung fehlgeschlagen: :error',

    // Authentication Messages
    'auth_2fa_verification_required' => 'Bitte verifizieren Sie Ihren Zwei-Faktor-Authentifizierungscode.',
    'auth_invalid_login_link' => 'Ungültiger Anmelde-Link.',
    'auth_login_successful' => 'Erfolgreich angemeldet.',

    // Navigation & Email Validation
    'nav_email_label' => 'E-Mail-Adresse',
    'nav_email_placeholder' => 'Geben Sie Ihre E-Mail-Adresse ein',
    'nav_validate_email_button' => 'E-Mail Validieren',
    'nav_validate_email_sending' => 'Wird gesendet...',
    'nav_validation_success' => 'Sie erhalten eine E-Mail mit einem Link zur Validierung Ihrer E-Mail-Adresse. Durch Klicken auf den Link, den wir Ihnen senden, können Sie Dateien zu :company_name hochladen.',
    'nav_validation_error' => 'Bei der Bearbeitung Ihrer Anfrage ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.',
    'nav_logo_alt' => ':company_name Logo',
    'email_validation_title' => 'Dateien zu :company_name hochladen',
    'email_validation_subtitle' => 'Beginnen Sie mit der Validierung Ihrer E-Mail-Adresse.',
    'already_have_account' => 'Haben Sie bereits ein Konto?',
    'sign_in' => 'Anmelden',

    // Google Drive Chunked Upload Service Messages
    'chunked_upload_local_file_not_found' => 'Lokale Datei nicht gefunden: :path',
    'chunked_upload_could_not_open_file' => 'Datei konnte nicht zum Lesen geöffnet werden: :path',
    'chunked_upload_failed_to_read_chunk' => 'Fehler beim Lesen des Datei-Chunks',
    'chunked_upload_no_file_object_returned' => 'Upload abgeschlossen, aber kein Dateiobjekt zurückgegeben',
    'chunked_upload_starting' => 'Starte chunked Upload zu Google Drive',
    'chunked_upload_chunk_uploaded' => 'Chunk zu Google Drive hochgeladen',
    'chunked_upload_completed_successfully' => 'Chunked Upload zu Google Drive erfolgreich abgeschlossen',
    'chunked_upload_failed' => 'Chunked Upload zu Google Drive fehlgeschlagen',
    'chunked_upload_optimal_chunk_size_determined' => 'Optimale Chunk-Größe bestimmt',
    'chunked_upload_decision_made' => 'Chunked Upload-Entscheidung getroffen',

    // S3 Multipart Upload Messages
    's3_multipart_upload_configured' => 'S3 Multipart-Upload konfiguriert',
    's3_multipart_upload_starting' => 'Starte S3 Multipart-Upload',
    's3_multipart_upload_part_uploaded' => 'Teil erfolgreich hochgeladen',
    's3_multipart_upload_completed' => 'S3 Multipart-Upload erfolgreich abgeschlossen',
    's3_multipart_upload_failed' => 'S3 Multipart-Upload fehlgeschlagen',
    's3_multipart_upload_aborted' => 'Multipart-Upload aufgrund eines Fehlers abgebrochen',
    's3_multipart_abort_failed' => 'Fehler beim Abbrechen des Multipart-Uploads',
    's3_upload_optimization_applied' => 'S3 Upload-Optimierungen angewendet',
    's3_failed_to_open_file' => 'Fehler beim Öffnen der Datei: :path',
];