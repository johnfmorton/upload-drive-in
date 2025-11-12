<?php

return [
    'welcome' => 'Bienvenue dans notre application !',
    'login-message' => 'Les utilisateurs de <b>' . config('app.company_name') . '</b> peuvent se connecter avec leur adresse e-mail et leur mot de passe. Si vous ne connaissez pas votre mot de passe, <a href="/">connectez-vous</a> avec votre adresse e-mail et nous vous enverrons un lien.',
    'email-validation-message' => 'Vous recevrez un e-mail avec un lien pour valider votre adresse e-mail. En cliquant sur le lien que nous vous envoyons, vous pourrez télécharger des fichiers vers ' . config('app.company_name') . '.',

    // Token Refresh Error Types - Descriptions
    'token_refresh_error_network_timeout' => 'Délai d\'attente réseau dépassé lors du renouvellement du token',
    'token_refresh_error_invalid_refresh_token' => 'Token de renouvellement invalide fourni',
    'token_refresh_error_expired_refresh_token' => 'Le token de renouvellement a expiré',
    'token_refresh_error_api_quota_exceeded' => 'Quota API dépassé lors du renouvellement du token',
    'token_refresh_error_service_unavailable' => 'Service OAuth temporairement indisponible',
    'token_refresh_error_unknown_error' => 'Erreur inconnue lors du renouvellement du token',

    // Token Refresh Error Types - User Notifications
    'token_refresh_notification_network_timeout' => 'Des problèmes réseau ont empêché le renouvellement du token. Nouvelle tentative automatique.',
    'token_refresh_notification_invalid_refresh_token' => 'Votre connexion Google Drive est invalide. Veuillez reconnecter votre compte.',
    'token_refresh_notification_expired_refresh_token' => 'Votre connexion Google Drive a expiré. Veuillez reconnecter votre compte.',
    'token_refresh_notification_api_quota_exceeded' => 'Limite API Google Drive atteinte. Le renouvellement du token sera retenté automatiquement.',
    'token_refresh_notification_service_unavailable' => 'Le service Google Drive est temporairement indisponible. Nouvelle tentative automatique.',
    'token_refresh_notification_unknown_error' => 'Une erreur inattendue s\'est produite lors du renouvellement du token. Veuillez contacter le support si cela persiste.',

    // Google Drive Provider-Specific Error Messages
    'google_drive_error_token_expired' => 'Votre connexion Google Drive a expiré. Veuillez reconnecter votre compte Google Drive pour continuer à télécharger des fichiers.',
    'google_drive_error_insufficient_permissions' => 'Permissions Google Drive insuffisantes. Veuillez reconnecter votre compte et vous assurer d\'accorder un accès complet à Google Drive.',
    'google_drive_error_api_quota_exceeded' => 'Limite API Google Drive atteinte. Vos téléchargements reprendront automatiquement dans :time. Aucune action requise.',
    'google_drive_error_storage_quota_exceeded' => 'Votre stockage Google Drive est plein. Veuillez libérer de l\'espace dans votre compte Google Drive ou mettre à niveau votre plan de stockage.',
    'google_drive_error_file_not_found' => 'Le fichier \':filename\' n\'a pas pu être trouvé dans Google Drive. Il a peut-être été supprimé ou déplacé.',
    'google_drive_error_folder_access_denied' => 'Accès refusé au dossier Google Drive. Veuillez vérifier les permissions de votre dossier ou reconnecter votre compte.',
    'google_drive_error_invalid_file_type' => 'Le type de fichier de \':filename\' n\'est pas pris en charge par Google Drive. Veuillez essayer un format de fichier différent.',
    'google_drive_error_file_too_large' => 'Le fichier \':filename\' est trop volumineux pour Google Drive. La taille maximale de fichier est de 5 To pour la plupart des types de fichiers.',
    'google_drive_error_network_error' => 'Un problème de connexion réseau a empêché le téléchargement vers Google Drive. Le téléchargement sera retenté automatiquement.',
    'google_drive_error_service_unavailable' => 'Google Drive est temporairement indisponible. Vos téléchargements seront retentés automatiquement lorsque le service sera restauré.',
    'google_drive_error_invalid_credentials' => 'Identifiants Google Drive invalides. Veuillez reconnecter votre compte Google Drive dans les paramètres.',
    'google_drive_error_timeout' => 'L\'opération :operation de Google Drive a expiré. Ceci est généralement temporaire et sera retenté automatiquement.',
    'google_drive_error_invalid_file_content' => 'Le fichier \':filename\' semble être corrompu ou avoir un contenu invalide. Veuillez essayer de télécharger le fichier à nouveau.',
    'google_drive_error_unknown_error' => 'Une erreur inattendue s\'est produite avec Google Drive. :message',

    // Google Drive Error Recovery Actions - Token Expired
    'google_drive_action_token_expired_1' => 'Allez dans Paramètres → Stockage Cloud',
    'google_drive_action_token_expired_2' => 'Cliquez sur "Reconnecter Google Drive"',
    'google_drive_action_token_expired_3' => 'Complétez le processus d\'autorisation',
    'google_drive_action_token_expired_4' => 'Retentez votre téléchargement',

    // Google Drive Error Recovery Actions - Insufficient Permissions
    'google_drive_action_insufficient_permissions_1' => 'Allez dans Paramètres → Stockage Cloud',
    'google_drive_action_insufficient_permissions_2' => 'Cliquez sur "Reconnecter Google Drive"',
    'google_drive_action_insufficient_permissions_3' => 'Assurez-vous d\'accorder un accès complet lorsque demandé',
    'google_drive_action_insufficient_permissions_4' => 'Vérifiez que vous avez les permissions d\'édition pour le dossier cible',

    // Google Drive Error Recovery Actions - Storage Quota Exceeded
    'google_drive_action_storage_quota_exceeded_1' => 'Libérez de l\'espace dans votre compte Google Drive',
    'google_drive_action_storage_quota_exceeded_2' => 'Videz votre corbeille Google Drive',

    // Employee Management
    'nav_employee_management' => 'Gestion des Employés',
    'employee_management_title' => 'Gestion des Employés',
    'create_employee_title' => 'Créer un Nouvel Employé',
    'employees_list_title' => 'Utilisateurs Employés',
    'button_create_employee' => 'Créer un Employé',
    'no_employees_match_filter' => 'Aucun employé ne correspond à vos critères de filtre',
    'no_employees_found' => 'Aucun employé trouvé',
    'column_reset_url' => 'URL de Réinitialisation',
    'button_copy_reset_url' => 'Copier l\'URL de Réinitialisation',

    // Employee Creation Messages
    'employee_created_success' => 'Utilisateur employé créé avec succès.',
    'employee_created_and_invited_success' => 'Utilisateur employé créé et e-mail de vérification envoyé avec succès.',
    'employee_created_email_failed' => 'Utilisateur employé créé mais l\'e-mail de vérification n\'a pas pu être envoyé. Veuillez vérifier les journaux.',
    'employee_creation_failed' => 'Échec de la création de l\'utilisateur employé. Veuillez vérifier les journaux.',

    // Role-Based Email Verification
    // Admin Verification
    'admin_verify_email_subject' => 'Vérifiez votre Adresse E-mail d\'Administrateur',
    'admin_verify_email_title' => 'Vérifiez votre Adresse E-mail d\'Administrateur',
    'admin_verify_email_intro' => 'Bienvenue dans le système de gestion de fichiers de :company_name. En tant qu\'administrateur, vous avez un accès complet pour gérer les utilisateurs, configurer le stockage cloud et superviser tous les téléchargements de fichiers. Veuillez vérifier votre adresse e-mail pour terminer la configuration de votre compte administrateur.',
    'admin_verify_email_button' => 'Vérifier l\'Accès Administrateur',

    // Employee Verification  
    'employee_verify_email_subject' => 'Vérifiez votre Adresse E-mail d\'Employé',
    'employee_verify_email_title' => 'Vérifiez votre Adresse E-mail d\'Employé',
    'employee_verify_email_intro' => 'Bienvenue chez :company_name ! En tant qu\'employé, vous pouvez recevoir les téléchargements de fichiers clients directement dans votre Google Drive et gérer vos propres relations clients. Veuillez vérifier votre adresse e-mail pour commencer à recevoir les fichiers clients.',
    'employee_verify_email_button' => 'Vérifier l\'Accès Employé',

    // Client Verification
    'client_verify_email_subject' => 'Vérifiez votre Adresse E-mail',
    'client_verify_email_title' => 'Vérifiez votre Adresse E-mail', 
    'client_verify_email_intro' => 'Pour télécharger des fichiers vers :company_name, veuillez vérifier votre adresse e-mail en cliquant sur le lien ci-dessous. Une fois vérifié, vous pourrez télécharger des fichiers en toute sécurité qui seront livrés directement au membre de l\'équipe approprié.',
    'client_verify_email_button' => 'Vérifier l\'Adresse E-mail',

    // Shared elements
    'verify_email_ignore' => 'Si vous n\'avez pas demandé cette vérification, vous pouvez ignorer cet e-mail en toute sécurité.',
    'thanks_signature' => 'Merci',

    // Button Loading States
    'button_create_user_loading' => 'Création de l\'utilisateur...',
    'button_create_and_invite_loading' => 'Création et envoi...',

    // Admin User Creation Messages
    'admin_user_created' => 'Utilisateur client créé avec succès. Vous pouvez leur fournir le lien de connexion manuellement.',
    'admin_user_created_and_invited' => 'Utilisateur client créé et invitation envoyée avec succès.',
    'admin_user_created_email_failed' => 'Utilisateur client créé avec succès, mais l\'e-mail d\'invitation n\'a pas pu être envoyé. Vous pouvez leur fournir le lien de connexion manuellement.',
    'admin_user_creation_failed' => 'Échec de la création de l\'utilisateur client. Veuillez réessayer.',

    // Employee Client Creation Messages
    'employee_client_created' => 'Utilisateur client créé avec succès. Vous pouvez leur fournir le lien de connexion manuellement.',
    'employee_client_created_and_invited' => 'Utilisateur client créé et invitation envoyée avec succès.',
    'employee_client_created_email_failed' => 'Utilisateur client créé avec succès, mais l\'e-mail d\'invitation n\'a pas pu être envoyé. Vous pouvez leur fournir le lien de connexion manuellement.',
    'employee_client_creation_failed' => 'Échec de la création de l\'utilisateur client. Veuillez réessayer.',

    // Account Deletion Messages
    'account_deletion_request_failed' => 'Échec du traitement de la demande de suppression. Veuillez réessayer.',
    'account_deletion_link_invalid' => 'Le lien de confirmation de suppression est invalide ou a expiré.',
    'account_deletion_verification_invalid' => 'Lien de vérification invalide ou expiré.',
    'account_deletion_user_invalid' => 'Compte utilisateur invalide.',
    'account_deletion_success' => 'Votre compte et toutes les données associées ont été supprimés définitivement.',
    'account_deletion_error' => 'Une erreur s\'est produite lors de la suppression de votre compte. Veuillez réessayer ou contacter le support.',
    'account_deletion_unexpected_error' => 'Une erreur inattendue s\'est produite. Veuillez réessayer ou contacter le support.',

    // Google Drive OAuth Error Messages
    'oauth_authorization_code_missing' => 'Code d\'autorisation non fourni.',
    'oauth_state_parameter_missing' => 'Paramètre d\'état manquant.',
    'oauth_state_parameter_invalid' => 'Paramètre d\'état invalide.',
    'oauth_user_not_found' => 'Utilisateur non trouvé.',
    'oauth_connection_validation_failed' => 'Connexion établie mais la validation a échoué. Veuillez essayer de vous reconnecter.',

    // Enhanced Validation Messages
    'validation_name_required' => 'Le champ nom est obligatoire.',
    'validation_name_string' => 'Le nom doit être une chaîne de texte valide.',
    'validation_name_max' => 'Le nom ne peut pas dépasser 255 caractères.',
    'validation_email_required' => 'Le champ e-mail est obligatoire.',
    'validation_email_format' => 'L\'e-mail doit avoir un format valide.',
    'validation_action_required' => 'Le champ action est obligatoire.',
    'validation_action_invalid' => 'L\'action sélectionnée est invalide.',
    'validation_team_members_required' => 'Au moins un membre de l\'équipe doit être sélectionné.',
    'validation_team_members_min' => 'Au moins un membre de l\'équipe doit être sélectionné.',
    'validation_team_member_invalid' => 'Un ou plusieurs membres de l\'équipe sélectionnés sont invalides.',
    'validation_primary_contact_required' => 'Un contact principal doit être sélectionné.',
    'validation_primary_contact_invalid' => 'Le contact principal sélectionné est invalide.',
    'validation_primary_contact_not_in_team' => 'Le contact principal doit être membre de l\'équipe sélectionnée.',
    'validation_team_members_unauthorized' => 'Vous n\'êtes pas autorisé à assigner un ou plusieurs des membres de l\'équipe sélectionnés.',
    'validation_primary_contact_unauthorized' => 'Vous n\'êtes pas autorisé à assigner ce contact principal.',

    // Team Assignment Messages
    'team_assignments_updated_success' => 'Assignations d\'équipe mises à jour avec succès.',
    'team_assignments_update_failed' => 'Échec de la mise à jour des assignations d\'équipe. Veuillez réessayer.',

    // Cloud Storage Status Messages (from CloudStorageStatusMessages class)
    'cloud_storage_rate_limited' => 'Trop de tentatives de renouvellement de token. Veuillez réessayer plus tard.',
    'cloud_storage_auth_required' => 'Authentification requise. Veuillez reconnecter votre compte.',
    'cloud_storage_connection_healthy' => 'Connecté et fonctionne correctement',
    'cloud_storage_not_connected' => 'Compte non connecté. Veuillez configurer votre connexion de stockage cloud.',
    'cloud_storage_connection_issues' => 'Problème de connexion détecté. Veuillez tester votre connexion.',
    'cloud_storage_multiple_failures' => 'Plusieurs échecs de connexion détectés. Veuillez vérifier les paramètres de votre compte.',
    'cloud_storage_status_unknown' => 'Statut inconnu. Veuillez actualiser ou contacter le support.',
    'cloud_storage_retry_time_message' => '{1} Trop de tentatives. Veuillez réessayer dans :minutes minute.|[2,*] Trop de tentatives. Veuillez réessayer dans :minutes minutes.',
    'cloud_storage_retry_seconds_message' => '{1} Trop de tentatives. Veuillez réessayer dans :seconds seconde.|[2,*] Trop de tentatives. Veuillez réessayer dans :seconds secondes.',
    'cloud_storage_retry_persistent_message' => '{1} Problèmes de connexion persistants avec :provider. Veuillez réessayer dans :minutes minute.|[2,*] Problèmes de connexion persistants avec :provider. Veuillez réessayer dans :minutes minutes.',
    'cloud_storage_retry_multiple_message' => '{1} Plusieurs tentatives de connexion détectées. Veuillez réessayer dans :minutes minute.|[2,*] Plusieurs tentatives de connexion détectées. Veuillez réessayer dans :minutes minutes.',

    // Cloud Storage Error Messages (from CloudStorageErrorMessageService class)
    'cloud_storage_token_expired' => 'Votre connexion :provider a expiré. Veuillez reconnecter votre compte pour continuer.',
    'cloud_storage_token_refresh_rate_limited' => 'Trop de tentatives de connexion :provider. Veuillez attendre avant de réessayer pour éviter des délais prolongés.',
    'cloud_storage_invalid_credentials' => 'Identifiants :provider invalides. Veuillez vérifier votre configuration et reconnecter votre compte.',
    'cloud_storage_insufficient_permissions' => 'Permissions :provider insuffisantes. Veuillez reconnecter votre compte et vous assurer d\'accorder un accès complet.',
    'cloud_storage_api_quota_exceeded' => 'Limite API :provider atteinte. Vos opérations reprendront automatiquement lorsque la limite sera réinitialisée.',
    'cloud_storage_storage_quota_exceeded' => 'Votre stockage :provider est plein. Veuillez libérer de l\'espace ou mettre à niveau votre plan de stockage.',
    'cloud_storage_network_error' => 'Un problème de connexion réseau a empêché l\'opération :provider. Veuillez vérifier votre connexion internet et réessayer.',
    'cloud_storage_service_unavailable' => ':provider est temporairement indisponible. Veuillez réessayer dans quelques minutes.',
    'cloud_storage_timeout' => 'L\'opération :operation de :provider a expiré. Ceci est généralement temporaire - veuillez réessayer.',
    'cloud_storage_file_not_found' => 'Le fichier \':filename\' n\'a pas pu être trouvé dans :provider. Il a peut-être été supprimé ou déplacé.',
    'cloud_storage_folder_access_denied' => 'Accès refusé au dossier :provider. Veuillez vérifier les permissions de votre dossier.',
    'cloud_storage_invalid_file_type' => 'Le type de fichier de \':filename\' n\'est pas pris en charge par :provider. Veuillez essayer un format de fichier différent.',
    'cloud_storage_file_too_large' => 'Le fichier \':filename\' est trop volumineux pour :provider. Veuillez réduire la taille du fichier et réessayer.',
    'cloud_storage_invalid_file_content' => 'Le fichier \':filename\' semble être corrompu. Veuillez essayer de télécharger le fichier à nouveau.',
    'cloud_storage_provider_not_configured' => ':provider n\'est pas configuré correctement. Veuillez vérifier vos paramètres et réessayer.',
    'cloud_storage_unknown_error' => 'Une erreur inattendue s\'est produite avec :provider. Veuillez réessayer ou contacter le support si le problème persiste.',
    'cloud_storage_default_error' => 'Une erreur s\'est produite pendant l\'opération :operation de :provider. Veuillez réessayer.',

    // Messages d'erreur spécifiques à S3
    'cloud_storage_bucket_not_found' => 'Le bucket :provider n\'existe pas. Veuillez vérifier le nom du bucket dans votre configuration.',
    'cloud_storage_bucket_access_denied' => 'L\'accès au bucket :provider a été refusé. Veuillez vérifier que vos identifiants AWS disposent des autorisations nécessaires.',
    'cloud_storage_invalid_bucket_name' => 'Le nom du bucket :provider est invalide. Les noms de bucket doivent contenir 3 à 63 caractères et suivre les règles de nommage S3.',
    'cloud_storage_invalid_region' => 'La région :provider est invalide. Veuillez vérifier que la région correspond à l\'emplacement de votre bucket.',

    // Connection Issue Context Messages
    'cloud_storage_persistent_failures' => 'Échecs de connexion persistants détectés. Veuillez vérifier les paramètres de votre compte :provider et votre connexion réseau.',
    'cloud_storage_multiple_token_refresh_attempts' => 'Plusieurs tentatives de renouvellement de token détectées. Veuillez attendre quelques minutes avant de réessayer.',
    'cloud_storage_retry_with_time' => 'Trop de tentatives de renouvellement de token. Veuillez attendre :minutes minute de plus avant de réessayer.|Trop de tentatives de renouvellement de token. Veuillez attendre :minutes minutes de plus avant de réessayer.',

    // Recovery Instructions
    'recovery_instructions_token_expired' => [
        'Allez dans Paramètres → Stockage Cloud',
        'Cliquez sur "Reconnecter :provider"',
        'Complétez le processus d\'autorisation',
        'Retentez votre opération'
    ],
    'recovery_instructions_rate_limited' => [
        'Attendez que la limite de taux soit réinitialisée',
        'Évitez de cliquer répétitivement sur les boutons de test de connexion',
        'Les opérations reprendront automatiquement lorsque la limite sera réinitialisée',
        'Contactez le support si le problème persiste au-delà du temps prévu'
    ],
    'recovery_instructions_insufficient_permissions' => [
        'Allez dans Paramètres → Stockage Cloud',
        'Cliquez sur "Reconnecter :provider"',
        'Assurez-vous d\'accorder un accès complet lorsque demandé',
        'Vérifiez que vous avez les permissions nécessaires'
    ],
    'recovery_instructions_storage_quota_exceeded' => [
        'Libérez de l\'espace dans votre compte :provider',
        'Videz votre corbeille :provider',
        'Envisagez de mettre à niveau votre plan de stockage :provider',
        'Contactez votre administrateur si vous utilisez un compte professionnel'
    ],
    'recovery_instructions_api_quota_exceeded' => [
        'Attendez que le quota soit réinitialisé (généralement dans l\'heure)',
        'Les opérations reprendront automatiquement',
        'Envisagez d\'étaler les grandes opérations sur plusieurs jours'
    ],
    'recovery_instructions_network_error' => [
        'Vérifiez votre connexion internet',
        'Réessayez dans quelques minutes',
        'Contactez votre administrateur réseau si le problème persiste'
    ],
    'recovery_instructions_service_unavailable' => [
        'Attendez quelques minutes et réessayez',
        'Vérifiez la page de statut :provider pour les mises à jour du service',
        'Les opérations seront retentées automatiquement'
    ],
    'recovery_instructions_timeout' => [
        'Réessayez - les timeouts sont généralement temporaires',
        'Vérifiez la vitesse de votre connexion internet',
        'Pour les gros fichiers, essayez de télécharger pendant les heures creuses'
    ],
    'recovery_instructions_folder_access_denied' => [
        'Vérifiez que le dossier cible existe dans votre :provider',
        'Vérifiez que vous avez les permissions d\'écriture sur le dossier',
        'Essayez de reconnecter votre compte :provider'
    ],
    'recovery_instructions_invalid_file_type' => [
        'Convertissez le fichier vers un format pris en charge',
        'Vérifiez les types de fichiers pris en charge par :provider',
        'Essayez de télécharger un fichier différent pour tester'
    ],
    'recovery_instructions_file_too_large' => [
        'Compressez le fichier pour réduire sa taille',
        'Divisez les gros fichiers en parties plus petites',
        'Utilisez l\'interface web de :provider pour les très gros fichiers'
    ],
    'recovery_instructions_invalid_file_content' => [
        'Vérifiez que le fichier n\'est pas corrompu',
        'Essayez de recréer ou retélécharger le fichier',
        'Scannez le fichier pour les virus ou malwares'
    ],
    'recovery_instructions_provider_not_configured' => [
        'Allez dans Paramètres → Stockage Cloud',
        'Vérifiez vos paramètres de configuration',
        'Assurez-vous que tous les champs requis sont remplis correctement',
        'Contactez le support si vous avez besoin d\'assistance'
    ],
    'recovery_instructions_unknown_error' => [
        'Retentez l\'opération',
        'Vérifiez votre connexion internet',
        'Contactez le support si le problème persiste',
        'Incluez tous les détails d\'erreur lors du contact avec le support'
    ],
    'recovery_instructions_default' => [
        'Retentez l\'opération',
        'Vérifiez votre connexion et vos paramètres',
        'Contactez le support si le problème persiste'
    ],

    // Cloud Storage Status Messages
    'cloud_storage_status_rate_limited' => 'Trop de tentatives de renouvellement de token. Veuillez réessayer plus tard.',
    'cloud_storage_status_auth_required' => 'Authentification requise. Veuillez reconnecter votre compte.',
    'cloud_storage_status_connection_healthy' => 'Connecté et fonctionne correctement',
    'cloud_storage_status_not_connected' => 'Compte non connecté. Veuillez configurer votre connexion de stockage cloud.',
    'cloud_storage_status_connection_issues' => 'Problème de connexion détecté. Veuillez tester votre connexion.',
    'cloud_storage_status_multiple_failures' => 'Plusieurs échecs de connexion détectés. Veuillez vérifier les paramètres de votre compte.',
    'cloud_storage_status_unknown' => 'Statut inconnu. Veuillez actualiser ou contacter le support.',
    'cloud_storage_retry_time_message' => 'Trop de tentatives. Veuillez réessayer dans :minutes minute.|Trop de tentatives. Veuillez réessayer dans :minutes minutes.',

    // Cloud Storage Error Messages - Generic
    'cloud_storage_error_token_expired' => 'Votre connexion :provider a expiré. Veuillez reconnecter votre compte pour continuer.',
    'cloud_storage_error_token_refresh_rate_limited' => 'Trop de tentatives de renouvellement de token. Veuillez réessayer plus tard.',
    'cloud_storage_error_invalid_credentials' => 'Identifiants :provider invalides. Veuillez vérifier votre configuration et reconnecter votre compte.',
    'cloud_storage_error_insufficient_permissions' => 'Permissions :provider insuffisantes. Veuillez reconnecter votre compte et vous assurer d\'accorder un accès complet.',
    'cloud_storage_error_api_quota_exceeded' => 'Limite API :provider atteinte. Vos opérations reprendront automatiquement lorsque la limite sera réinitialisée.',
    'cloud_storage_error_storage_quota_exceeded' => 'Votre stockage :provider est plein. Veuillez libérer de l\'espace ou mettre à niveau votre plan de stockage.',
    'cloud_storage_error_network_error' => 'Un problème de connexion réseau a empêché l\'opération :provider. Veuillez vérifier votre connexion internet et réessayer.',
    'cloud_storage_error_service_unavailable' => ':provider est temporairement indisponible. Veuillez réessayer dans quelques minutes.',
    'cloud_storage_error_timeout' => 'L\'opération :operation de :provider a expiré. Ceci est généralement temporaire - veuillez réessayer.',
    'cloud_storage_error_file_not_found' => 'Le fichier \':filename\' n\'a pas pu être trouvé dans :provider. Il a peut-être été supprimé ou déplacé.',
    'cloud_storage_error_folder_access_denied' => 'Accès refusé au dossier :provider. Veuillez vérifier les permissions de votre dossier.',
    'cloud_storage_error_invalid_file_type' => 'Le type de fichier de \':filename\' n\'est pas pris en charge par :provider. Veuillez essayer un format de fichier différent.',
    'cloud_storage_error_file_too_large' => 'Le fichier \':filename\' est trop volumineux pour :provider. Veuillez réduire la taille du fichier et réessayer.',
    'cloud_storage_error_invalid_file_content' => 'Le fichier \':filename\' semble être corrompu. Veuillez essayer de télécharger le fichier à nouveau.',
    'cloud_storage_error_provider_not_configured' => ':provider n\'est pas configuré correctement. Veuillez vérifier vos paramètres et réessayer.',
    'cloud_storage_error_unknown_error' => 'Une erreur inattendue s\'est produite avec :provider. :message',
    'cloud_storage_error_default' => 'Une erreur s\'est produite pendant l\'opération :operation de :provider. Veuillez réessayer.',

    // Cloud Storage Recovery Instructions - Token Expired
    'cloud_storage_recovery_token_expired_1' => 'Allez dans Paramètres → Stockage Cloud',
    'cloud_storage_recovery_token_expired_2' => 'Cliquez sur "Reconnecter :provider"',
    'cloud_storage_recovery_token_expired_3' => 'Complétez le processus d\'autorisation',
    'cloud_storage_recovery_token_expired_4' => 'Retentez votre opération',

    // Cloud Storage Recovery Instructions - Rate Limited
    'cloud_storage_recovery_rate_limited_1' => 'Attendez que la limite de débit soit réinitialisée',
    'cloud_storage_recovery_rate_limited_2' => 'Évitez de cliquer de manière répétée sur les boutons de test de connexion',
    'cloud_storage_recovery_rate_limited_3' => 'Les opérations reprendront automatiquement lorsque la limite sera réinitialisée',
    'cloud_storage_recovery_rate_limited_4' => 'Contactez le support si le problème persiste au-delà du temps attendu',

    // Cloud Storage Recovery Instructions - Insufficient Permissions
    'cloud_storage_recovery_insufficient_permissions_1' => 'Allez dans Paramètres → Stockage Cloud',
    'cloud_storage_recovery_insufficient_permissions_2' => 'Cliquez sur "Reconnecter :provider"',
    'cloud_storage_recovery_insufficient_permissions_3' => 'Assurez-vous d\'accorder un accès complet lorsque demandé',
    'cloud_storage_recovery_insufficient_permissions_4' => 'Vérifiez que vous avez les permissions nécessaires',

    // Cloud Storage Recovery Instructions - Storage Quota Exceeded
    'cloud_storage_recovery_storage_quota_exceeded_1' => 'Libérez de l\'espace dans votre compte :provider',
    'cloud_storage_recovery_storage_quota_exceeded_2' => 'Videz votre corbeille :provider',
    'cloud_storage_recovery_storage_quota_exceeded_3' => 'Envisagez de mettre à niveau votre plan de stockage :provider',
    'cloud_storage_recovery_storage_quota_exceeded_4' => 'Contactez votre administrateur si vous utilisez un compte professionnel',

    // Cloud Storage Recovery Instructions - API Quota Exceeded
    'cloud_storage_recovery_api_quota_exceeded_1' => 'Attendez que le quota soit réinitialisé (généralement dans l\'heure)',
    'cloud_storage_recovery_api_quota_exceeded_2' => 'Les opérations reprendront automatiquement',
    'cloud_storage_recovery_api_quota_exceeded_3' => 'Envisagez de répartir les grandes opérations sur plusieurs jours',

    // Cloud Storage Recovery Instructions - Network Error
    'cloud_storage_recovery_network_error_1' => 'Vérifiez votre connexion internet',
    'cloud_storage_recovery_network_error_2' => 'Réessayez dans quelques minutes',
    'cloud_storage_recovery_network_error_3' => 'Contactez votre administrateur réseau si le problème persiste',

    // Cloud Storage Recovery Instructions - Service Unavailable
    'cloud_storage_recovery_service_unavailable_1' => 'Attendez quelques minutes et réessayez',
    'cloud_storage_recovery_service_unavailable_2' => 'Vérifiez la page de statut :provider pour les mises à jour du service',
    'cloud_storage_recovery_service_unavailable_3' => 'Les opérations seront retentées automatiquement',

    // Cloud Storage Recovery Instructions - Timeout
    'cloud_storage_recovery_timeout_1' => 'Réessayez - les timeouts sont généralement temporaires',
    'cloud_storage_recovery_timeout_2' => 'Vérifiez la vitesse de votre connexion internet',
    'cloud_storage_recovery_timeout_3' => 'Pour les gros fichiers, essayez de télécharger pendant les heures creuses',

    // Cloud Storage Recovery Instructions - Folder Access Denied
    'cloud_storage_recovery_folder_access_denied_1' => 'Vérifiez que le dossier cible existe dans votre :provider',
    'cloud_storage_recovery_folder_access_denied_2' => 'Vérifiez que vous avez les permissions d\'écriture sur le dossier',
    'cloud_storage_recovery_folder_access_denied_3' => 'Essayez de reconnecter votre compte :provider',

    // Cloud Storage Recovery Instructions - Invalid File Type
    'cloud_storage_recovery_invalid_file_type_1' => 'Convertissez le fichier dans un format pris en charge',
    'cloud_storage_recovery_invalid_file_type_2' => 'Vérifiez les types de fichiers pris en charge par :provider',
    'cloud_storage_recovery_invalid_file_type_3' => 'Essayez de télécharger un fichier différent pour tester',

    // Cloud Storage Recovery Instructions - File Too Large
    'cloud_storage_recovery_file_too_large_1' => 'Compressez le fichier pour réduire sa taille',
    'cloud_storage_recovery_file_too_large_2' => 'Divisez les gros fichiers en parties plus petites',
    'cloud_storage_recovery_file_too_large_3' => 'Utilisez l\'interface web de :provider pour les très gros fichiers',

    // Cloud Storage Recovery Instructions - Invalid File Content
    'cloud_storage_recovery_invalid_file_content_1' => 'Vérifiez que le fichier n\'est pas corrompu',
    'cloud_storage_recovery_invalid_file_content_2' => 'Essayez de recréer ou de retélécharger le fichier',
    'cloud_storage_recovery_invalid_file_content_3' => 'Scannez le fichier pour détecter les virus ou malwares',

    // Cloud Storage Recovery Instructions - Provider Not Configured
    'cloud_storage_recovery_provider_not_configured_1' => 'Allez dans Paramètres → Stockage Cloud',
    'cloud_storage_recovery_provider_not_configured_2' => 'Vérifiez vos paramètres de configuration',
    'cloud_storage_recovery_provider_not_configured_3' => 'Assurez-vous que tous les champs requis sont correctement remplis',
    'cloud_storage_recovery_provider_not_configured_4' => 'Contactez le support si vous avez besoin d\'aide',

    // Cloud Storage Recovery Instructions - Unknown Error
    'cloud_storage_recovery_unknown_error_1' => 'Retentez l\'opération',
    'cloud_storage_recovery_unknown_error_2' => 'Vérifiez votre connexion internet',
    'cloud_storage_recovery_unknown_error_3' => 'Contactez le support si le problème persiste',
    'cloud_storage_recovery_unknown_error_4' => 'Incluez tous les détails d\'erreur lors du contact avec le support',

    // Cloud Storage Recovery Instructions - Default
    'cloud_storage_recovery_default_1' => 'Retentez l\'opération',
    'cloud_storage_recovery_default_2' => 'Vérifiez votre connexion et paramètres',
    'cloud_storage_recovery_default_3' => 'Contactez le support si le problème persiste',

    // Cloud Storage Provider Display Names
    'cloud_storage_provider_google_drive' => 'Google Drive',
    'cloud_storage_provider_amazon_s3' => 'Amazon S3',
    'cloud_storage_provider_azure_blob' => 'Azure Blob Storage',
    'cloud_storage_provider_microsoft_teams' => 'Microsoft Teams',
    'cloud_storage_provider_dropbox' => 'Dropbox',
    'cloud_storage_provider_onedrive' => 'OneDrive',

    // Recovery Strategy Messages
    'recovery_strategy_token_refresh' => 'Tentative de renouvellement des tokens d\'authentification',
    'recovery_strategy_network_retry' => 'Nouvelle tentative après des problèmes de connectivité réseau',
    'recovery_strategy_quota_wait' => 'Attente de la restauration du quota API',
    'recovery_strategy_service_retry' => 'Nouvelle tentative après que le service devienne disponible',
    'recovery_strategy_health_check_retry' => 'Exécution d\'un contrôle de santé et nouvelle tentative',
    'recovery_strategy_user_intervention_required' => 'Intervention manuelle de l\'utilisateur requise',
    'recovery_strategy_no_action_needed' => 'Aucune action nécessaire, la connexion est saine',
    'recovery_strategy_unknown' => 'Stratégie de récupération inconnue',
    'google_drive_action_storage_quota_exceeded_3' => 'Envisagez de mettre à niveau votre plan de stockage Google Drive',
    'google_drive_action_storage_quota_exceeded_4' => 'Contactez votre administrateur si vous utilisez un compte professionnel',

    // Google Drive Error Recovery Actions - API Quota Exceeded
    'google_drive_action_api_quota_exceeded_1' => 'Attendez que le quota se réinitialise (généralement dans l\'heure)',
    'google_drive_action_api_quota_exceeded_2' => 'Les téléchargements reprendront automatiquement',
    'google_drive_action_api_quota_exceeded_3' => 'Envisagez d\'étaler les téléchargements sur plusieurs jours pour les gros lots',

    // Google Drive Error Recovery Actions - Invalid Credentials
    'google_drive_action_invalid_credentials_1' => 'Allez dans Paramètres → Stockage Cloud',
    'google_drive_action_invalid_credentials_2' => 'Déconnectez et reconnectez votre compte Google Drive',
    'google_drive_action_invalid_credentials_3' => 'Assurez-vous que votre compte Google est actif et accessible',

    // Google Drive Error Recovery Actions - Folder Access Denied
    'google_drive_action_folder_access_denied_1' => 'Vérifiez que le dossier cible existe dans votre Google Drive',
    'google_drive_action_folder_access_denied_2' => 'Vérifiez que vous avez les permissions d\'écriture sur le dossier',
    'google_drive_action_folder_access_denied_3' => 'Essayez de reconnecter votre compte Google Drive',

    // Google Drive Error Recovery Actions - Invalid File Type
    'google_drive_action_invalid_file_type_1' => 'Convertissez le fichier vers un format pris en charge',
    'google_drive_action_invalid_file_type_2' => 'Vérifiez les types de fichiers pris en charge par Google Drive',
    'google_drive_action_invalid_file_type_3' => 'Essayez de télécharger un fichier différent pour tester',

    // Google Drive Error Recovery Actions - File Too Large
    'google_drive_action_file_too_large_1' => 'Compressez le fichier pour réduire sa taille',
    'google_drive_action_file_too_large_2' => 'Divisez les gros fichiers en parties plus petites',
    'google_drive_action_file_too_large_3' => 'Utilisez l\'interface web de Google Drive pour les très gros fichiers',

    // Time-related messages for quota reset
    'quota_reset_time_1_hour' => '1 heure',
    'quota_reset_time_hours' => ':hours heures',
    'quota_reset_time_minutes' => ':minutes minutes',
    'quota_reset_time_unknown' => 'un court moment',

    // Common error messages
    'error_generic' => 'Une erreur s\'est produite. Veuillez réessayer.',
    'unknown_error' => 'Erreur inconnue',

    // Token Refresh Result Messages
    'token_refresh_success' => 'Token renouvelé avec succès',
    'token_already_valid' => 'Le token est déjà valide',
    'token_refreshed_by_another_process' => 'Le token a été renouvelé par un autre processus',
    'token_already_valid_description' => 'Le token était déjà valide et n\'avait pas besoin d\'être renouvelé',
    'token_refreshed_by_another_process_description' => 'Le token a été renouvelé par un autre processus concurrent',
    'token_refresh_success_description' => 'Le token a été renouvelé avec succès',
    'token_refresh_failed_description' => 'Échec du renouvellement du token : :message',

    // Proactive Token Renewal Messages
    'proactive_refresh_provider_not_supported' => 'Fournisseur non pris en charge pour le renouvellement proactif',
    'proactive_refresh_no_token_found' => 'Aucun token d\'authentification trouvé',
    'proactive_refresh_token_not_expiring' => 'Le token n\'expire pas bientôt et n\'a pas besoin d\'être renouvelé',
    'proactive_refresh_requires_reauth' => 'Le token nécessite une nouvelle authentification utilisateur',

    // Health Status Messages
    'health_status_healthy' => 'Sain',
    'health_status_authentication_required' => 'Authentification requise',
    'health_status_connection_issues' => 'Problèmes de connexion',
    'health_status_not_connected' => 'Non connecté',
    'health_status_token_validation_failed' => 'Échec de la validation du token',
    'health_status_api_connectivity_test_failed' => 'Échec du test de connectivité API',
    'health_status_authentication_error' => 'Erreur d\'authentification',
    'health_status_connection_error' => 'Erreur de connexion',
    'health_status_token_error' => 'Erreur de token',
    'health_status_api_error' => 'Erreur API',

    // Token Renewal Notification Service
    'notification_failure_alert_subject' => 'Alerte d\'Échec de Notification - Utilisateur :email',
    'notification_failure_alert_body' => 'Échec de l\'envoi de la notification :type à l\'utilisateur :email pour le fournisseur :provider après :attempts tentatives.\n\nDernière erreur : :error\n\nVeuillez vérifier l\'adresse email de l\'utilisateur et la configuration du système.',

    // Token Expired Email
    'token_expired_subject' => 'Connexion :provider Expirée - Action Requise',
    'token_expired_heading' => 'Connexion :provider Expirée',
    'token_expired_subheading' => 'Action Requise pour Reprendre les Téléchargements de Fichiers',
    'token_expired_alert' => 'Attention Requise : Votre connexion :provider a expiré et doit être renouvelée.',
    'token_expired_greeting' => 'Bonjour :name,',
    'token_expired_intro' => 'Nous vous écrivons pour vous informer que votre connexion :provider a expiré. Cela signifie que les nouveaux téléchargements de fichiers ne peuvent pas être traités jusqu\'à ce que vous reconnectiez votre compte.',
    'token_expired_what_this_means' => 'Ce Que Cela Signifie :',
    'token_expired_impact_uploads' => 'Les nouveaux téléchargements de fichiers échoueront jusqu\'à ce que vous reconnectiez',
    'token_expired_impact_existing' => 'Les fichiers existants dans votre :provider ne sont pas affectés',
    'token_expired_impact_resume' => 'Votre système de téléchargement reprendra un fonctionnement normal une fois reconnecté',
    'token_expired_how_to_reconnect' => 'Comment Reconnecter :',
    'token_expired_step_1' => 'Cliquez sur le bouton "Reconnecter :provider" ci-dessous',
    'token_expired_step_2' => 'Connectez-vous à votre compte :provider lorsque demandé',
    'token_expired_step_3' => 'Accordez la permission au système de téléchargement d\'accéder à votre compte',
    'token_expired_step_4' => 'Vérifiez que la connexion fonctionne sur votre tableau de bord',
    'token_expired_reconnect_button' => 'Reconnecter :provider',
    'token_expired_why_happened' => 'Pourquoi Cela S\'est-il Produit ?',
    'token_expired_explanation' => 'Les connexions :provider expirent périodiquement pour des raisons de sécurité. C\'est normal et aide à protéger votre compte. Le système a tenté de renouveler automatiquement la connexion, mais une intervention manuelle est maintenant requise.',
    'token_expired_need_help' => 'Besoin d\'Aide ?',
    'token_expired_support' => 'Si vous avez des difficultés à reconnecter ou des questions sur ce processus, n\'hésitez pas à contacter notre équipe de support à :email.',
    'token_expired_footer_important' => 'Cet email a été envoyé parce que votre connexion :provider a expiré. Si vous n\'attendiez pas cet email, veuillez contacter le support immédiatement.',
    'token_expired_footer_automated' => 'Ceci est un message automatisé de votre système de téléchargement de fichiers. Veuillez ne pas répondre directement à cet email.',

    // Token Refresh Failed Email
    'token_refresh_failed_subject' => 'Problème de Connexion :provider - :urgency',
    'token_refresh_failed_heading' => 'Problème de Connexion :provider',
    'token_refresh_failed_action_required' => 'Action Requise',
    'token_refresh_failed_auto_recovery' => 'Récupération Automatique en Cours',
    'token_refresh_failed_alert_action' => 'Action Requise : Votre connexion :provider nécessite une attention manuelle.',
    'token_refresh_failed_alert_auto' => 'Problème de Connexion : Nous travaillons à restaurer votre connexion :provider automatiquement.',
    'token_refresh_failed_greeting' => 'Bonjour :name,',
    'token_refresh_failed_intro' => 'Nous avons rencontré un problème en essayant de renouveler votre connexion :provider. Voici ce qui s\'est passé et ce que nous faisons à ce sujet :',
    'token_refresh_failed_issue_details' => 'Détails du Problème :',
    'token_refresh_failed_error_type' => 'Type d\'Erreur : :type',
    'token_refresh_failed_attempt' => 'Tentative : :current sur :max',
    'token_refresh_failed_description' => 'Description : :description',
    'token_refresh_failed_technical_details' => 'Détails Techniques : :details',
    'token_refresh_failed_what_to_do' => 'Ce Que Vous Devez Faire :',
    'token_refresh_failed_manual_required' => 'Ce type d\'erreur nécessite une intervention manuelle. Veuillez reconnecter votre compte :provider pour restaurer la fonctionnalité de téléchargement de fichiers.',
    'token_refresh_failed_reconnect_now' => 'Reconnecter :provider Maintenant',
    'token_refresh_failed_why_manual' => 'Pourquoi Une Action Manuelle Est Nécessaire :',
    'token_refresh_failed_credentials_invalid' => 'Vos identifiants d\'authentification ne sont plus valides. Cela se produit généralement lorsque vous changez votre mot de passe, révoquez l\'accès, ou que le token a été inactif pendant une période prolongée.',
    'token_refresh_failed_cannot_resolve' => 'Ce type d\'erreur ne peut pas être résolu automatiquement et nécessite que vous rétablissiez la connexion.',
    'token_refresh_failed_auto_recovery_status' => 'État de la Récupération Automatique :',
    'token_refresh_failed_no_action_needed' => 'Le système continuera à tenter de restaurer la connexion automatiquement. Vous n\'avez aucune action à entreprendre pour le moment.',
    'token_refresh_failed_max_attempts' => 'Tentatives Maximales Atteintes :',
    'token_refresh_failed_exhausted' => 'Le système a épuisé toutes les tentatives de retry automatique. Veuillez reconnecter manuellement pour restaurer la fonctionnalité.',
    'token_refresh_failed_what_happens_next' => 'Ce Qui Se Passe Ensuite :',
    'token_refresh_failed_auto_retry' => 'Le système retentera automatiquement la connexion',
    'token_refresh_failed_success_email' => 'En cas de succès, vous recevrez un email de confirmation',
    'token_refresh_failed_manual_notify' => 'Si toutes les tentatives échouent, vous serez notifié pour reconnecter manuellement',
    'token_refresh_failed_uploads_paused' => 'Les téléchargements de fichiers sont temporairement suspendus jusqu\'à ce que la connexion soit restaurée',
    'token_refresh_failed_impact' => 'Impact sur Votre Service :',
    'token_refresh_failed_uploads_impact' => 'Téléchargements de Fichiers : Les nouveaux téléchargements sont temporairement suspendus',
    'token_refresh_failed_existing_impact' => 'Fichiers Existants : Tous les fichiers précédemment téléchargés restent sûrs et accessibles',
    'token_refresh_failed_system_impact' => 'État du Système : Toutes les autres fonctionnalités continuent de fonctionner normalement',
    'token_refresh_failed_no_action_required' => 'Aucune action n\'est requise de votre part pour le moment. Nous vous tiendrons informé des progrès de la récupération.',
    'token_refresh_failed_need_help' => 'Besoin d\'Aide ?',
    'token_refresh_failed_support' => 'Si vous rencontrez des problèmes de connexion répétés ou avez besoin d\'aide pour reconnecter, veuillez contacter notre équipe de support à :email. Incluez cette référence d\'erreur : :reference',
    'token_refresh_failed_error_reference' => 'Référence d\'Erreur : :type (Tentative :attempt)',
    'token_refresh_failed_timestamp' => 'Horodatage : :timestamp',
    'token_refresh_failed_footer_automated' => 'Ceci est un message automatisé de votre système de téléchargement de fichiers. Veuillez ne pas répondre directement à cet email.',

    // Connection Restored Email
    'connection_restored_subject' => 'Connexion :provider Restaurée',
    'connection_restored_heading' => '✅ Connexion :provider Restaurée',
    'connection_restored_subheading' => 'Votre système de téléchargement de fichiers est de nouveau en ligne !',
    'connection_restored_alert' => 'Excellentes Nouvelles : Votre connexion :provider a été restaurée avec succès et fonctionne normalement.',
    'connection_restored_greeting' => 'Bonjour :name,',
    'connection_restored_intro' => 'Nous sommes heureux de vous informer que le problème de connexion avec votre compte :provider a été résolu. Votre système de téléchargement de fichiers est maintenant entièrement opérationnel à nouveau.',
    'connection_restored_current_status' => 'État Actuel :',
    'connection_restored_connection_status' => 'Connexion : ✅ Active et saine',
    'connection_restored_uploads_status' => 'Téléchargements de Fichiers : ✅ Accepte les nouveaux téléchargements',
    'connection_restored_pending_status' => 'Fichiers en Attente : ✅ Traitement des téléchargements en file d\'attente',
    'connection_restored_system_status' => 'État du Système : ✅ Toutes les fonctionnalités opérationnelles',
    'connection_restored_what_happened' => 'Ce Qui S\'est Passé :',
    'connection_restored_explanation' => 'Le système a renouvelé avec succès votre authentification :provider et restauré la connectivité complète. Tous les téléchargements de fichiers qui ont été temporairement suspendus pendant le problème de connexion sont maintenant traités automatiquement.',
    'connection_restored_whats_happening' => 'Ce Qui Se Passe Maintenant :',
    'connection_restored_processing_queued' => 'Le système traite tous les téléchargements qui ont été mis en file d\'attente pendant la panne',
    'connection_restored_accepting_new' => 'Les nouveaux téléchargements de fichiers seront acceptés et traités normalement',
    'connection_restored_operations_resumed' => 'Toutes les opérations :provider ont repris',
    'connection_restored_monitoring_active' => 'La surveillance de la connexion est active pour prévenir les problèmes futurs',
    'connection_restored_access_dashboard' => 'Accédez à Votre Tableau de Bord :',
    'connection_restored_dashboard_intro' => 'Vous pouvez voir l\'état de vos téléchargements et gérer vos fichiers via votre tableau de bord :',
    'connection_restored_view_dashboard' => 'Voir le Tableau de Bord',
    'connection_restored_preventing_issues' => 'Prévenir les Problèmes Futurs :',
    'connection_restored_keep_active' => 'Gardez votre compte :provider actif et en bon état',
    'connection_restored_avoid_password_change' => 'Évitez de changer votre mot de passe :provider sans mettre à jour la connexion',
    'connection_restored_monitor_email' => 'Surveillez vos emails pour toute alerte de connexion',
    'connection_restored_contact_support' => 'Contactez le support si vous remarquez un comportement inhabituel',
    'connection_restored_need_assistance' => 'Besoin d\'Assistance ?',
    'connection_restored_support' => 'Si vous rencontrez des problèmes avec les téléchargements de fichiers ou avez des questions sur votre connexion :provider, n\'hésitez pas à contacter notre équipe de support à :email.',
    'connection_restored_footer_timestamp' => 'Connexion Restaurée : :timestamp',
    'connection_restored_footer_service_status' => 'État du Service : Tous les systèmes opérationnels',
    'connection_restored_footer_thanks' => 'Merci pour votre patience pendant le problème de connexion. Ceci est un message automatisé de votre système de téléchargement de fichiers.',

    // Token Refresh Configuration Validation Messages
    'token_config_proactive_refresh_minutes_min' => 'Les minutes de rafraîchissement proactif doivent être d\'au moins 1',
    'token_config_max_retry_attempts_range' => 'Le nombre maximum de tentatives de retry doit être entre 1 et 10',
    'token_config_retry_base_delay_min' => 'Le délai de base de retry doit être d\'au moins 1 seconde',
    'token_config_notification_throttle_hours_min' => 'Les heures de limitation des notifications doivent être d\'au moins 1',
    'token_config_max_attempts_per_hour_min' => 'Le nombre maximum de tentatives par heure doit être d\'au moins 1',
    'token_config_max_health_checks_per_minute_min' => 'Le nombre maximum de vérifications de santé par minute doit être d\'au moins 1',
    
    // Token Refresh Admin Controller Validation Messages
    'token_config_proactive_refresh_range' => 'Les minutes de rafraîchissement proactif doivent être entre 1 et 60',
    'token_config_background_refresh_range' => 'Les minutes de rafraîchissement en arrière-plan doivent être entre 5 et 120',
    'token_config_notification_throttle_range' => 'Les heures de limitation des notifications doivent être entre 1 et 168 (1 semaine)',
    'token_config_max_attempts_per_hour_range' => 'Le nombre maximum de tentatives par heure doit être entre 1 et 100',
    
    // Token Refresh Admin Interface Messages
    'token_config_admin_interface_disabled' => 'L\'interface d\'administration est désactivée',
    'token_config_runtime_changes_disabled' => 'Les modifications à l\'exécution sont désactivées',
    'token_config_update_failed' => 'Échec de la mise à jour de la configuration',
    'token_config_toggle_failed' => 'Échec de l\'activation/désactivation de la fonctionnalité',
    'token_config_cache_clear_failed' => 'Échec de la suppression du cache',
    'token_config_setting_updated' => 'Configuration \':key\' mise à jour avec succès.',
    'token_config_feature_enabled' => 'Fonctionnalité \':feature\' activée avec succès.',
    'token_config_feature_disabled' => 'Fonctionnalité \':feature\' désactivée avec succès.',
    'token_config_cache_cleared' => 'Cache de configuration supprimé avec succès.',
    'token_config_change_requires_confirmation' => 'Modifier \':key\' nécessite une confirmation car cela peut affecter le comportement du système.',
    'token_config_toggle_requires_confirmation' => 'Activer/désactiver \':feature\' nécessite une confirmation car cela peut affecter significativement le comportement du système.',
    
    // Token Refresh Configuration Dashboard
    'token_config_dashboard_title' => 'Configuration de Rafraîchissement des Tokens',
    'token_config_dashboard_description' => 'Gérer les paramètres de rafraîchissement des tokens et les indicateurs de fonctionnalités pour un déploiement progressif.',
    'token_config_status_title' => 'État de la Configuration',
    'token_config_refresh_button' => 'Actualiser',
    'token_config_clear_cache_button' => 'Vider le Cache',
    'token_config_environment' => 'Environnement',
    'token_config_runtime_changes' => 'Modifications à l\'Exécution',
    'token_config_validation_status' => 'État de Validation',
    'token_config_enabled' => 'Activé',
    'token_config_disabled' => 'Désactivé',
    'token_config_valid' => 'Valide',
    'token_config_issues_found' => 'Problèmes Trouvés',
    'token_config_issues_title' => 'Problèmes de Configuration',
    'token_config_feature_flags_title' => 'Indicateurs de Fonctionnalités',
    'token_config_timing_title' => 'Configuration des Délais',
    'token_config_notifications_title' => 'Configuration des Notifications',
    'token_config_rate_limiting_title' => 'Configuration de Limitation de Débit',
    'token_config_security_title' => 'Configuration de Sécurité',
    'token_config_confirm_change_title' => 'Confirmer le Changement de Configuration',
    'token_config_confirm_button' => 'Confirmer',
    'token_config_cancel_button' => 'Annuler',
    
    // Token Refresh Console Command Messages
    'token_config_cmd_unknown_action' => 'Action inconnue : :action',
    'token_config_cmd_key_value_required' => 'Les options --key et --value sont requises pour l\'action set',
    'token_config_cmd_feature_enabled_required' => 'Les options --feature et --enabled sont requises pour l\'action toggle',
    'token_config_cmd_validation_failed' => 'Validation échouée : :errors',
    'token_config_cmd_change_confirmation' => 'Modifier \':key\' peut affecter le comportement du système. Continuer ?',
    'token_config_cmd_toggle_confirmation' => 'Activer/désactiver \':feature\' peut affecter significativement le comportement du système. Continuer ?',
    'token_config_cmd_operation_cancelled' => 'Opération annulée',
    'token_config_cmd_setting_updated' => 'Configuration \':key\' mise à jour avec succès à : :value',
    'token_config_cmd_setting_update_failed' => 'Échec de la mise à jour de la configuration \':key\'',
    'token_config_cmd_feature_enabled' => 'Fonctionnalité \':feature\' activée avec succès',
    'token_config_cmd_feature_disabled' => 'Fonctionnalité \':feature\' désactivée avec succès',
    'token_config_cmd_feature_toggle_failed' => 'Échec de l\'activation/désactivation de la fonctionnalité \':feature\'',
    'token_config_cmd_validation_success' => '✓ La configuration est valide',
    'token_config_cmd_validation_failed_title' => 'La validation de la configuration a échoué :',
    'token_config_cmd_cache_cleared' => 'Cache de configuration supprimé avec succès',
    'token_config_cmd_cache_clear_failed' => 'Échec de la suppression du cache de configuration : :error',

    // Error Type Display Names
    'error_type_network_timeout' => 'Délai d\'Attente Réseau Dépassé',
    'error_type_invalid_refresh_token' => 'Token de Renouvellement Invalide',
    'error_type_expired_refresh_token' => 'Token de Renouvellement Expiré',
    'error_type_api_quota_exceeded' => 'Quota API Dépassé',
    'error_type_service_unavailable' => 'Service Indisponible',
    'error_type_unknown_error' => 'Erreur Inconnue',

    // Error Descriptions
    'error_desc_network_timeout' => 'Nous avons rencontré un délai d\'attente réseau en essayant de renouveler votre connexion. C\'est généralement temporaire et le système retentera automatiquement.',
    'error_desc_invalid_refresh_token' => 'Votre token d\'authentification stocké n\'est plus valide. Cela se produit généralement lorsque vous révoquez l\'accès ou changez votre mot de passe sur le service cloud.',
    'error_desc_expired_refresh_token' => 'Votre token d\'authentification a expiré et ne peut pas être renouvelé automatiquement. Vous devrez reconnecter votre compte.',
    'error_desc_api_quota_exceeded' => 'Le service cloud a temporairement limité notre accès en raison d\'une utilisation élevée. Le système retentera automatiquement une fois que la limite sera réinitialisée.',
    'error_desc_service_unavailable' => 'Le service cloud est temporairement indisponible. C\'est généralement un problème temporaire de leur côté, et le système retentera automatiquement.',
    'error_desc_unknown_error' => 'Une erreur inattendue s\'est produite lors du renouvellement de votre connexion. Notre équipe technique a été notifiée et enquêtera.',

    // Retry Information
    'retry_no_automatic' => 'Aucune tentative automatique ne sera effectuée. Veuillez reconnecter manuellement.',
    'retry_max_attempts_reached' => 'Nombre maximum de tentatives atteint. Veuillez reconnecter manuellement.',
    'retry_in_seconds' => 'Le système retentera dans :seconds secondes. :remaining tentatives restantes.',
    'retry_in_minutes' => 'Le système retentera dans :minutes minutes. :remaining tentatives restantes.',
    'retry_in_hours' => 'Le système retentera dans :hours heures. :remaining tentatives restantes.',

    // Provider Display Names
    'provider_google_drive' => 'Google Drive',
    'provider_microsoft_teams' => 'Microsoft Teams',
    'provider_dropbox' => 'Dropbox',

    // Connection Recovery Messages
    'recovery_connection_healthy' => 'La connexion est saine',
    'recovery_connection_health_restored' => 'Santé de la connexion restaurée',
    'recovery_token_refreshed_successfully' => 'Token renouvelé avec succès',
    'recovery_network_connectivity_restored' => 'Connectivité réseau restaurée',
    'recovery_api_quota_restored' => 'Quota API restauré',
    'recovery_service_availability_restored' => 'Disponibilité du service restaurée',
    'recovery_no_action_needed' => 'Aucune action nécessaire',
    'recovery_user_intervention_required' => 'Intervention de l\'utilisateur requise',
    'recovery_manual_action_needed' => 'Action manuelle nécessaire',
    'recovery_failed_due_to_exception' => 'Récupération échouée en raison d\'une exception',
    'recovery_strategy_failed' => 'Stratégie de récupération échouée',
    'recovery_unknown_strategy' => 'Stratégie de récupération inconnue',

    // Recovery Failure Messages
    'recovery_token_refresh_failed' => 'Échec du renouvellement du token',
    'recovery_network_connectivity_still_failing' => 'La connectivité réseau échoue toujours',
    'recovery_api_quota_still_exceeded' => 'Quota API toujours dépassé',
    'recovery_service_still_unavailable' => 'Service toujours indisponible',
    'recovery_connection_still_unhealthy' => 'Connexion toujours malsaine',

    // Recovery Exception Messages
    'recovery_token_refresh_exception' => 'Exception de renouvellement de token',
    'recovery_network_test_exception' => 'Exception de test réseau',
    'recovery_quota_check_exception' => 'Exception de vérification de quota',
    'recovery_service_check_exception' => 'Exception de vérification de service',
    'recovery_health_check_exception' => 'Exception de vérification de santé',

    // Token Status Service Messages
    'token_status_not_connected' => 'Aucun token trouvé - compte non connecté',
    'token_status_requires_intervention' => 'Le token nécessite une reconnexion manuelle en raison d\'échecs répétés',
    'token_status_expired_refreshable' => 'Token expiré mais peut être renouvelé automatiquement',
    'token_status_expired_manual' => 'Token expiré et nécessite une reconnexion manuelle',
    'token_status_expiring_soon' => 'Le token sera renouvelé automatiquement bientôt',
    'token_status_healthy_with_warnings' => 'Token sain mais a :count échec(s) de renouvellement récent(s)',
    'token_status_healthy' => 'Le token est sain et valide',
    'token_status_scheduled_now' => 'Programmé maintenant',
    'token_status_less_than_minute' => 'Moins d\'1 minute',
    'token_status_minute' => 'minute',
    'token_status_minutes' => 'minutes',
    'token_status_hour' => 'heure',
    'token_status_hours' => 'heures',
    'token_status_day' => 'jour',
    'token_status_days' => 'jours',
    'token_status_last_error_intervention' => 'Le token nécessite une reconnexion manuelle en raison d\'échecs répétés',
    'token_status_last_error_generic' => 'Le renouvellement du token a échoué - nouvelle tentative automatique',

    // Upload Recovery Messages
    'recovery_local_file_no_longer_exists' => 'Le fichier local n\'existe plus',
    'recovery_no_target_user_found' => 'Aucun utilisateur cible trouvé',
    'recovery_retry_job_permanently_failed' => 'Tâche de nouvelle tentative définitivement échouée',
    'recovery_upload_retry_failed_for_file' => 'Échec de la nouvelle tentative de téléchargement pour le fichier',

    // Token Monitoring Dashboard
    'token_monitoring' => [
        'dashboard_title' => 'Tableau de Bord de Surveillance des Tokens',
        'dashboard_description' => 'Surveillez la santé des tokens Google Drive, les opérations de renouvellement et les métriques de performance du système.',
        'metrics_reset_success' => 'Métriques réinitialisées pour le fournisseur : :provider',
        'overview_title' => 'Aperçu du Système',
        'performance_metrics_title' => 'Métriques de Performance',
        'token_status_title' => 'Résumé de l\'État des Tokens',
        'recent_operations_title' => 'Opérations Récentes',
        'health_trends_title' => 'Tendances de Santé',
        'user_statistics_title' => 'Statistiques Utilisateur',
        'system_status_title' => 'État du Système',
        'recommendations_title' => 'Recommandations',
        'export_data' => 'Exporter les Données',
        'reset_metrics' => 'Réinitialiser les Métriques',
        'refresh_dashboard' => 'Actualiser le Tableau de Bord',
        'last_updated' => 'Dernière Mise à Jour',
        'total_users' => 'Total des Utilisateurs',
        'connected_users' => 'Utilisateurs Connectés',
        'success_rate' => 'Taux de Réussite',
        'average_refresh_time' => 'Temps Moyen de Renouvellement',
        'active_alerts' => 'Alertes Actives',
        'overall_health' => 'Santé Générale',
        'tokens_expiring_soon' => 'Expirent Bientôt',
        'tokens_requiring_attention' => 'Nécessitent une Attention',
        'healthy' => 'Sain',
        'warning' => 'Avertissement',
        'critical' => 'Critique',
        'unknown' => 'Inconnu',
        'degraded' => 'Dégradé',
        'unhealthy' => 'Malsain',
        'queue_health' => 'Santé de la File d\'Attente',
        'cache_health' => 'Santé du Cache',
        'database_health' => 'Santé de la Base de Données',
        'api_health' => 'Santé de l\'API',
        'overall_system_health' => 'Santé Générale du Système',
        'last_maintenance' => 'Dernière Maintenance',
        'next_maintenance' => 'Prochaine Maintenance',
        'no_alerts' => 'Aucune alerte active',
        'view_details' => 'Voir les Détails',
        'time_period' => 'Période de Temps',
        'last_hour' => 'Dernière Heure',
        'last_6_hours' => 'Dernières 6 Heures',
        'last_24_hours' => 'Dernières 24 Heures',
        'last_week' => 'Dernière Semaine',
        'provider' => 'Fournisseur',
        'google_drive' => 'Google Drive',
        'microsoft_teams' => 'Microsoft Teams',
        'dropbox' => 'Dropbox',
        'loading' => 'Chargement...',
        'loading_dashboard_data' => 'Chargement des données du tableau de bord...',
        'total_users_label' => 'utilisateurs au total',
        'token_refresh_operations' => 'Opérations de renouvellement de token',
        'milliseconds' => 'Millisecondes',
        'overall_system_health' => 'Santé Générale du Système',
        'token_refresh' => 'Renouvellement de Token',
        'api_connectivity' => 'Connectivité API',
        'cache_performance' => 'Performance du Cache',
        'valid' => 'Valide',
        'expiring_soon' => 'Expirent Bientôt',
        'need_attention' => 'Nécessitent une Attention',
        'error_breakdown' => 'Répartition des Erreurs',
        'no_errors_in_period' => 'Aucune erreur dans la période sélectionnée',
        'time' => 'Heure',
        'user' => 'Utilisateur',
        'operation' => 'Opération',
        'status' => 'Statut',
        'duration' => 'Durée',
        'details' => 'Détails',
        'success' => 'Succès',
        'error_loading_dashboard' => 'Erreur de Chargement du Tableau de Bord',
        'try_again' => 'Réessayer',
        'recommended_actions' => 'Actions Recommandées',
    ],

    // Performance Optimized Health Validator Messages
    'health_validation_failed' => 'Validation échouée : :message',
    'health_user_not_found' => 'Utilisateur non trouvé',
    'health_batch_processing_failed' => 'Traitement par lots échoué : :message',
    'health_validation_rate_limited' => 'Validation de santé limitée par débit - veuillez réessayer plus tard',

    // Vérification d'Email
    'verify_email_title' => 'Vérifiez Votre Adresse Email',
    'verify_email_intro' => 'Pour télécharger des fichiers vers :company_name, veuillez vérifier votre adresse email en cliquant sur le lien ci-dessous.',
    'verify_email_sent' => 'Un nouveau lien de vérification a été envoyé à l\'adresse email que vous avez fournie lors de l\'inscription.',
    'verify_email_resend_button' => 'Renvoyer l\'Email de Vérification',
    'verify_email_button' => 'Vérifier l\'Adresse Email',
    'verify_email_ignore' => 'Si vous n\'avez pas demandé cette vérification, vous pouvez ignorer cet email en toute sécurité.',

    // Vérification d'Email Basée sur les Rôles
    // Vérification Administrateur
    'admin_verify_email_subject' => 'Vérifiez Votre Adresse Email d\'Administrateur',
    'admin_verify_email_title' => 'Vérifiez Votre Adresse Email d\'Administrateur',
    'admin_verify_email_intro' => 'Bienvenue dans le système de gestion de fichiers de :company_name. En tant qu\'administrateur, vous avez un accès complet pour gérer les utilisateurs, configurer le stockage cloud et superviser tous les téléchargements de fichiers. Veuillez vérifier votre adresse email pour terminer la configuration de votre compte administrateur.',
    'admin_verify_email_button' => 'Vérifier l\'Accès Administrateur',

    // Vérification Employé  
    'employee_verify_email_subject' => 'Vérifiez Votre Adresse Email d\'Employé',
    'employee_verify_email_title' => 'Vérifiez Votre Adresse Email d\'Employé',
    'employee_verify_email_intro' => 'Bienvenue chez :company_name ! En tant qu\'employé, vous pouvez recevoir les téléchargements de fichiers clients directement dans votre Google Drive et gérer vos propres relations clients. Veuillez vérifier votre adresse email pour commencer à recevoir les fichiers clients.',
    'employee_verify_email_button' => 'Vérifier l\'Accès Employé',

    // Vérification Client
    'client_verify_email_subject' => 'Vérifiez Votre Adresse Email',
    'client_verify_email_title' => 'Vérifiez Votre Adresse Email', 
    'client_verify_email_intro' => 'Pour télécharger des fichiers vers :company_name, veuillez vérifier votre adresse email en cliquant sur le lien ci-dessous. Une fois vérifié, vous pourrez télécharger des fichiers en toute sécurité qui seront livrés directement au membre de l\'équipe approprié.',
    'client_verify_email_button' => 'Vérifier l\'Adresse Email',

    // Éléments Communs
    'thanks_signature' => 'Merci',

    // Profil
    'profile_information' => 'Informations du Profil',
    'profile_update' => 'Mettre à Jour le Profil',
    'profile_saved' => 'Profil mis à jour avec succès.',
    'profile_update_info' => 'Mettez à jour les informations de profil et l\'adresse email de votre compte.',
    'profile_name' => 'Nom',
    'profile_email' => 'Email',
    'profile_save' => 'Enregistrer',
    'profile_email_unverified' => 'Votre adresse email n\'est pas vérifiée.',
    'profile_email_verify_resend' => 'Cliquez ici pour renvoyer l\'email de vérification.',
    'profile_email_verify_sent' => 'Un nouveau lien de vérification a été envoyé à votre adresse email.',

    // Messages de Validation de Sécurité et d'Inscription
    'public_registration_disabled' => 'Seuls les utilisateurs invités peuvent se connecter à ce système. Si vous pensez que vous devriez avoir accès, veuillez contacter l\'administrateur.',
    'email_domain_not_allowed' => 'Ce domaine email n\'est pas autorisé pour les nouvelles inscriptions. Si vous avez déjà un compte, veuillez réessayer ou contacter le support.',
    'security_settings_saved' => 'Les paramètres de sécurité ont été mis à jour avec succès.',
    
    // Messages de vérification améliorés pour les utilisateurs existants vs nouveaux
    'existing_user_verification_sent' => 'Email de vérification envoyé à votre compte existant. Veuillez vérifier votre boîte de réception.',
    'new_user_verification_sent' => 'Email de vérification envoyé. Veuillez vérifier votre boîte de réception pour compléter l\'inscription.',
    'registration_temporarily_unavailable' => 'Impossible de traiter l\'inscription pour le moment. Veuillez réessayer plus tard.',

    // Service de Métriques de Vérification Email
    'email_verification_bypass_spike_alert' => 'Pic inhabituel de contournements d\'utilisateurs existants dans la dernière heure',
    'email_verification_repeated_bypass_alert' => 'L\'utilisateur :user_id a contourné les restrictions :count fois',
    'email_verification_unusual_domain_alert' => 'Multiples contournements du domaine : :domain',
    'email_verification_high_bypass_volume_alert' => 'Volume élevé de contournements d\'utilisateurs existants : :count dans la dernière heure (seuil : :threshold)',
    'email_verification_high_restriction_volume_alert' => 'Volume élevé d\'application de restrictions : :count dans la dernière heure (seuil : :threshold)',
    'email_verification_no_activity_alert' => 'Aucune activité de vérification email détectée pendant les heures ouvrables - problème système possible',
    'email_verification_no_alerts_detected' => 'Aucune alerte détectée',
    'email_verification_no_unusual_activity' => 'Aucune activité inhabituelle détectée',
    'email_verification_no_unusual_activity_24h' => 'Aucune activité inhabituelle détectée dans les dernières 24 heures',
    'email_verification_alert_cooldown_active' => 'Période de refroidissement des alertes active, notifications ignorées',
    'email_verification_alert_email_sent' => 'Email d\'alerte envoyé à :email',
    'email_verification_alert_email_failed' => 'Échec de l\'envoi de l\'email d\'alerte : :error',
    'email_verification_dashboard_all_bypasses' => 'Tous les contournements',
    'email_verification_dashboard_no_bypasses' => 'Aucun contournement',
    'email_verification_dashboard_system_normal' => 'Système fonctionnant normalement',
    'email_verification_dashboard_unusual_activity' => 'Activité inhabituelle détectée',
    'email_verification_dashboard_no_recent_activity' => 'Aucune activité récente',
    'email_verification_dashboard_high_bypass_volume' => 'Volume élevé de contournements',
    'email_verification_dashboard_title' => 'Métriques de Vérification Email',
    'email_verification_dashboard_last_hours' => 'Dernières :hours heures',
    'email_verification_dashboard_existing_user_bypasses' => 'Contournements d\'Utilisateurs Existants',
    'email_verification_dashboard_restrictions_enforced' => 'Restrictions Appliquées',
    'email_verification_dashboard_bypass_ratio' => 'Ratio de Contournement',
    'email_verification_dashboard_unusual_activity_alerts' => 'Alertes d\'Activité Inhabituelle',
    'email_verification_dashboard_bypass_patterns' => 'Modèles de Contournement',
    'email_verification_dashboard_by_user_role' => 'Par Rôle d\'Utilisateur',
    'email_verification_dashboard_by_restriction_type' => 'Par Type de Restriction',
    'email_verification_dashboard_top_bypass_domains' => 'Principaux Domaines de Contournement',
    'email_verification_dashboard_restriction_enforcement' => 'Application des Restrictions',
    'email_verification_dashboard_top_blocked_domains' => 'Principaux Domaines Bloqués',
    'email_verification_dashboard_activity_timeline' => 'Chronologie d\'Activité (Dernières :hours heures)',
    'email_verification_dashboard_bypasses' => 'Contournements',
    'email_verification_dashboard_restrictions' => 'Restrictions',
    'email_verification_dashboard_last_updated' => 'Dernière mise à jour',
    'email_verification_dashboard_refresh' => 'Actualiser',
    'email_verification_dashboard_count' => 'Nombre',

    // Domain Rules Cache Service Messages
    'domain_rules_cache_failed' => 'Échec de la récupération des règles d\'accès de domaine depuis le cache',
    'domain_rules_cache_cleared' => 'Le cache des règles d\'accès de domaine a été vidé',
    'domain_rules_cache_warmed' => 'Le cache des règles d\'accès de domaine a été préchauffé',
    'domain_rules_not_configured' => 'Aucune règle d\'accès de domaine configurée - utilisation des paramètres par défaut',
    'domain_rules_email_check_completed' => 'Validation du domaine de l\'e-mail terminée',
    'domain_rules_cache_statistics' => 'Statistiques du Cache des Règles de Domaine',
    'domain_rules_cache_performance' => 'Performance du Cache',
    'domain_rules_query_performance' => 'Performance des Requêtes de Base de Données',

    // Cache Statistics Labels
    'cache_hit' => 'Succès de Cache',
    'cache_miss' => 'Échec de Cache',
    'cache_key' => 'Clé de Cache',
    'cache_ttl' => 'TTL de Cache (secondes)',
    'rules_loaded' => 'Règles Chargées',
    'rules_mode' => 'Mode des Règles',
    'rules_count' => 'Nombre de Règles',
    'query_time' => 'Temps de Requête (ms)',
    'total_time' => 'Temps Total (ms)',
    'warm_up_time' => 'Temps de Préchauffage (ms)',

    // Domain Rules Cache Command Messages
    'domain_rules_cache_command_invalid_action' => 'Action invalide. Utilisez : stats, clear, ou warm',
    'domain_rules_cache_command_stats_title' => 'Statistiques du Cache des Règles de Domaine',
    'domain_rules_cache_command_property' => 'Propriété',
    'domain_rules_cache_command_value' => 'Valeur',
    'domain_rules_cache_command_yes' => 'Oui',
    'domain_rules_cache_command_no' => 'Non',
    'domain_rules_cache_command_seconds' => 'secondes',

    // Upload Progress Overlay
    'upload_progress_title' => 'Téléchargement de Fichiers',
    'upload_progress_preparing' => 'Préparation du téléchargement...',
    'upload_progress_overall' => 'Progrès Global',
    'upload_progress_cancel_button' => 'Annuler le Téléchargement',
    'upload_progress_cancel_confirm' => 'Êtes-vous sûr de vouloir annuler le téléchargement ?',
    
    // Upload Progress Status Messages (for JavaScript)
    'upload_status_processing' => 'Traitement des téléchargements...',
    'upload_status_uploading_files' => 'Téléchargement de :remaining sur :total fichiers...',
    'upload_status_upload_completed_with_errors' => 'Téléchargement terminé avec :count erreur|Téléchargement terminé avec :count erreurs',
    'upload_button_uploading' => 'Téléchargement de Fichiers...',

    // Google Drive OAuth Callback Messages
    'google_drive_connected_success' => 'Connecté avec succès à Google Drive !',
    'google_drive_pending_uploads_queued' => ':count téléchargements en attente ont été mis en file d\'attente pour une nouvelle tentative.',
    'google_drive_connection_failed' => 'Échec de la connexion à Google Drive',
    'google_drive_auth_code_expired' => 'Le code d\'autorisation a expiré. Veuillez essayer de vous reconnecter.',
    'google_drive_access_denied' => 'L\'accès a été refusé. Veuillez accorder les permissions requises pour connecter Google Drive.',
    'google_drive_invalid_configuration' => 'Configuration Google Drive invalide. Veuillez contacter votre administrateur.',
    'google_drive_authorization_failed' => 'Autorisation échouée : :error',

    // Authentication Messages
    'auth_2fa_verification_required' => 'Veuillez vérifier votre code d\'authentification à deux facteurs.',
    'auth_invalid_login_link' => 'Lien de connexion invalide.',
    'auth_login_successful' => 'Connexion réussie.',

    // Navigation & Email Validation
    'nav_email_label' => 'Adresse E-mail',
    'nav_email_placeholder' => 'Entrez votre adresse e-mail',
    'nav_validate_email_button' => 'Valider l\'E-mail',
    'nav_validate_email_sending' => 'Envoi en cours...',
    'nav_validation_success' => 'Vous recevrez un e-mail avec un lien pour valider votre adresse e-mail. En cliquant sur le lien que nous vous envoyons, vous pourrez télécharger des fichiers vers :company_name.',
    'nav_validation_error' => 'Une erreur s\'est produite lors du traitement de votre demande. Veuillez réessayer.',
    'nav_logo_alt' => 'Logo de :company_name',
    'email_validation_title' => 'Télécharger des fichiers vers :company_name',
    'email_validation_subtitle' => 'Commencez par valider votre adresse e-mail.',
    'already_have_account' => 'Vous avez déjà un compte ?',
    'sign_in' => 'Se Connecter',

    // Google Drive Chunked Upload Service Messages
    'chunked_upload_local_file_not_found' => 'Fichier local non trouvé : :path',
    'chunked_upload_could_not_open_file' => 'Impossible d\'ouvrir le fichier en lecture : :path',
    'chunked_upload_failed_to_read_chunk' => 'Échec de la lecture du fragment de fichier',
    'chunked_upload_no_file_object_returned' => 'Téléchargement terminé mais aucun objet fichier retourné',
    'chunked_upload_starting' => 'Démarrage du téléchargement fragmenté vers Google Drive',
    'chunked_upload_chunk_uploaded' => 'Fragment téléchargé vers Google Drive',
    'chunked_upload_completed_successfully' => 'Téléchargement fragmenté vers Google Drive terminé avec succès',
    'chunked_upload_failed' => 'Téléchargement fragmenté vers Google Drive échoué',
    'chunked_upload_optimal_chunk_size_determined' => 'Taille optimale de fragment déterminée',
    'chunked_upload_decision_made' => 'Décision de téléchargement fragmenté prise',

    // S3 Multipart Upload Messages
    's3_multipart_upload_configured' => 'Téléchargement multipartie S3 configuré',
    's3_multipart_upload_starting' => 'Démarrage du téléchargement multipartie S3',
    's3_multipart_upload_part_uploaded' => 'Partie téléchargée avec succès',
    's3_multipart_upload_completed' => 'Téléchargement multipartie S3 terminé avec succès',
    's3_multipart_upload_failed' => 'Téléchargement multipartie S3 échoué',
    's3_multipart_upload_aborted' => 'Téléchargement multipartie interrompu en raison d\'une erreur',
    's3_multipart_abort_failed' => 'Échec de l\'interruption du téléchargement multipartie',
    's3_upload_optimization_applied' => 'Optimisations de téléchargement S3 appliquées',
    's3_failed_to_open_file' => 'Échec de l\'ouverture du fichier : :path',

    // S3 Configuration Management Messages
    's3_configuration_saved' => 'Configuration S3 enregistrée avec succès',
    's3_configuration_saved_and_verified' => 'Configuration S3 enregistrée et connexion vérifiée avec succès',
    's3_configuration_saved_but_connection_failed' => 'Configuration S3 enregistrée mais la connexion a échoué : :error',
    's3_configuration_saved_but_health_check_failed' => 'Configuration S3 enregistrée mais la vérification de santé a échoué. Veuillez vérifier vos identifiants et l\'accès au bucket.',
    's3_configuration_save_failed' => 'Échec de l\'enregistrement de la configuration S3',
    's3_configuration_deleted' => 'Configuration S3 supprimée avec succès',
    's3_configuration_delete_failed' => 'Échec de la suppression de la configuration S3 : :error',
    's3_configuration_validation_error' => 'Erreur de validation : :error',
    's3_configuration_value_updated' => 'Valeur de configuration S3 \':key\' mise à jour avec succès',
    's3_configuration_update_failed' => 'Échec de la mise à jour de la configuration S3',
    
    // Amazon S3 Disconnect Messages
    's3_disconnected_successfully' => 'Amazon S3 déconnecté avec succès.',
    's3_disconnect_failed' => 'Échec de la déconnexion d\'Amazon S3. Veuillez réessayer.',

    // Amazon S3 Configuration UI
    'save_configuration' => 'Enregistrer la Configuration',
    's3_configure_aws_credentials' => 'Configurer les identifiants AWS pour le stockage S3 à l\'échelle du système',
    's3_aws_access_key_id' => 'ID de Clé d\'Accès AWS',
    's3_aws_secret_access_key' => 'Clé d\'Accès Secrète AWS',
    's3_aws_region' => 'Région AWS',
    's3_bucket_name' => 'Nom du Bucket S3',
    's3_custom_endpoint' => 'Point de Terminaison Personnalisé (Optionnel)',
    's3_select_region' => 'Sélectionnez une région',
    's3_access_key_format' => 'Doit être exactement 20 caractères alphanumériques en majuscules',
    's3_secret_key_format' => 'Doit être exactement 40 caractères. Laisser vide pour conserver la clé secrète existante.',
    's3_region_help' => 'Sélectionnez la région AWS où se trouve votre bucket S3',
    's3_bucket_name_format' => 'Le nom du bucket doit contenir 3-63 caractères, uniquement des lettres minuscules, des chiffres, des tirets et des points',
    's3_custom_endpoint_help' => 'Pour les services compatibles S3 comme Cloudflare R2, Backblaze B2 ou MinIO. Laisser vide pour AWS S3 standard.',
    's3_test_connection' => 'Tester la Connexion',
    's3_testing' => 'Test en cours...',
    's3_connection_successful' => 'Connexion réussie !',
    's3_saving' => 'Enregistrement...',
    's3_access_key_required' => 'L\'ID de Clé d\'Accès est requis',
    's3_access_key_length' => 'L\'ID de Clé d\'Accès doit contenir exactement 20 caractères',
    's3_access_key_format_invalid' => 'L\'ID de Clé d\'Accès ne doit contenir que des lettres majuscules et des chiffres',
    's3_access_key_id_format_invalid' => 'L\'ID de Clé d\'Accès doit être exactement 20 caractères alphanumériques en majuscules',
    's3_secret_key_required' => 'La Clé d\'Accès Secrète est requise',
    's3_secret_key_length' => 'La Clé d\'Accès Secrète doit contenir exactement 40 caractères',
    's3_secret_access_key_length_invalid' => 'La Clé d\'Accès Secrète doit contenir exactement 40 caractères',
    's3_region_required' => 'La région est requise',
    's3_region_format_invalid' => 'Format de région invalide',
    's3_bucket_required' => 'Le nom du bucket est requis',
    's3_bucket_length' => 'Le nom du bucket doit contenir entre 3 et 63 caractères',
    's3_bucket_format_invalid' => 'Le nom du bucket doit commencer et se terminer par une lettre ou un chiffre, et ne contenir que des lettres minuscules, des chiffres, des tirets et des points',
    's3_bucket_name_format_invalid' => 'Le nom du bucket doit suivre les conventions de nommage S3 (3-63 caractères, lettres minuscules, chiffres, tirets et points)',
    's3_endpoint_url_invalid' => 'Le point de terminaison personnalisé doit être une URL valide',
    's3_bucket_consecutive_periods' => 'Le nom du bucket ne peut pas contenir de points consécutifs',
    's3_bucket_ip_format' => 'Le nom du bucket ne peut pas être formaté comme une adresse IP',
    's3_endpoint_format_invalid' => 'Le point de terminaison doit être une URL valide commençant par http:// ou https://',
    's3_connection_test_successful' => 'Test de connexion S3 réussi ! Vos identifiants sont valides et le bucket est accessible.',
    's3_connection_test_failed' => 'Échec du test de connexion. Veuillez réessayer.',
    's3_us_east_virginia' => 'États-Unis Est (N. Virginie)',
    's3_us_east_ohio' => 'États-Unis Est (Ohio)',
    's3_us_west_california' => 'États-Unis Ouest (N. Californie)',
    's3_us_west_oregon' => 'États-Unis Ouest (Oregon)',
    's3_canada_central' => 'Canada (Central)',
    's3_eu_ireland' => 'UE (Irlande)',
    's3_eu_london' => 'UE (Londres)',
    's3_eu_paris' => 'UE (Paris)',
    's3_eu_frankfurt' => 'UE (Francfort)',
    's3_eu_stockholm' => 'UE (Stockholm)',
    's3_asia_mumbai' => 'Asie-Pacifique (Mumbai)',
    's3_asia_tokyo' => 'Asie-Pacifique (Tokyo)',
    's3_asia_seoul' => 'Asie-Pacifique (Séoul)',
    's3_asia_osaka' => 'Asie-Pacifique (Osaka)',
    's3_asia_singapore' => 'Asie-Pacifique (Singapour)',
    's3_asia_sydney' => 'Asie-Pacifique (Sydney)',
    's3_south_america_sao_paulo' => 'Amérique du Sud (São Paulo)',
];