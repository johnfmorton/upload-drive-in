<?php

return [
    'welcome' => '¡Bienvenido a nuestra aplicación!',
    'login-message' => 'Los usuarios de <b>' . config('app.company_name') . '</b> pueden iniciar sesión con su correo electrónico y contraseña.',
    'email-validation-message' => 'Recibirás un correo electrónico con un enlace para validar tu dirección de correo. Al hacer clic en el enlace que te enviamos, podrás subir archivos a ' . config('app.company_name') . '.',

    // Navigation & General UI
    'nav_dashboard' => 'Panel de Control',
    'nav_client_users' => 'Gestión de Clientes',
    'nav_cloud_storage' => 'Almacenamiento en la Nube',
    'nav_employee_users' => 'Usuarios Empleados',
    'nav_security_settings' => 'Configuración de Seguridad',
    'nav_access_control' => 'Control de Acceso',
    'nav_security_policies' => 'Políticas de Seguridad',
    'nav_your_files' => 'Tus Archivos',
    'nav_upload_files' => 'Subir Archivos',

    // Token Refresh Error Types - Descriptions
    'token_refresh_error_network_timeout' => 'Tiempo de espera de red agotado durante la renovación del token',
    'token_refresh_error_invalid_refresh_token' => 'Token de renovación inválido proporcionado',
    'token_refresh_error_expired_refresh_token' => 'El token de renovación ha expirado',
    'token_refresh_error_api_quota_exceeded' => 'Cuota de API excedida durante la renovación del token',
    'token_refresh_error_service_unavailable' => 'Servicio OAuth temporalmente no disponible',
    'token_refresh_error_unknown_error' => 'Error desconocido en la renovación del token',

    // Token Refresh Error Types - User Notifications
    'token_refresh_notification_network_timeout' => 'Problemas de red impidieron la renovación del token. Reintentando automáticamente.',
    'token_refresh_notification_invalid_refresh_token' => 'Tu conexión de Google Drive es inválida. Por favor, reconecta tu cuenta.',
    'token_refresh_notification_expired_refresh_token' => 'Tu conexión de Google Drive ha expirado. Por favor, reconecta tu cuenta.',
    'token_refresh_notification_api_quota_exceeded' => 'Límite de API de Google Drive alcanzado. La renovación del token se reintentará automáticamente.',
    'token_refresh_notification_service_unavailable' => 'El servicio de Google Drive está temporalmente no disponible. Reintentando automáticamente.',
    'token_refresh_notification_unknown_error' => 'Ocurrió un error inesperado durante la renovación del token. Por favor, contacta al soporte si esto persiste.',

    // Google Drive Provider-Specific Error Messages
    'google_drive_error_token_expired' => 'Tu conexión de Google Drive ha expirado. Por favor, reconecta tu cuenta de Google Drive para continuar subiendo archivos.',
    'google_drive_error_insufficient_permissions' => 'Permisos insuficientes de Google Drive. Por favor, reconecta tu cuenta y asegúrate de otorgar acceso completo a Google Drive.',
    'google_drive_error_api_quota_exceeded' => 'Límite de API de Google Drive alcanzado. Tus subidas se reanudarán automáticamente en :time. No se requiere acción.',
    'google_drive_error_storage_quota_exceeded' => 'Tu almacenamiento de Google Drive está lleno. Por favor, libera espacio en tu cuenta de Google Drive o actualiza tu plan de almacenamiento.',
    'google_drive_error_file_not_found' => 'El archivo \':filename\' no se pudo encontrar en Google Drive. Puede haber sido eliminado o movido.',
    'google_drive_error_folder_access_denied' => 'Acceso denegado a la carpeta de Google Drive. Por favor, verifica los permisos de tu carpeta o reconecta tu cuenta.',
    'google_drive_error_invalid_file_type' => 'El tipo de archivo de \':filename\' no es compatible con Google Drive. Por favor, intenta con un formato de archivo diferente.',
    'google_drive_error_file_too_large' => 'El archivo \':filename\' es demasiado grande para Google Drive. El tamaño máximo de archivo es 5TB para la mayoría de tipos de archivo.',
    'google_drive_error_network_error' => 'Un problema de conexión de red impidió la subida a Google Drive. La subida se reintentará automáticamente.',
    'google_drive_error_service_unavailable' => 'Google Drive está temporalmente no disponible. Tus subidas se reintentarán automáticamente cuando el servicio se restaure.',
    'google_drive_error_invalid_credentials' => 'Credenciales de Google Drive inválidas. Por favor, reconecta tu cuenta de Google Drive en la configuración.',
    'google_drive_error_timeout' => 'La :operation de Google Drive agotó el tiempo de espera. Esto es usualmente temporal y se reintentará automáticamente.',
    'google_drive_error_invalid_file_content' => 'El archivo \':filename\' parece estar corrupto o tiene contenido inválido. Por favor, intenta subir el archivo nuevamente.',
    'google_drive_error_unknown_error' => 'Ocurrió un error inesperado con Google Drive. :message',

    // Google Drive Error Recovery Actions - Token Expired
    'google_drive_action_token_expired_1' => 'Ve a Configuración → Almacenamiento en la Nube',
    'google_drive_action_token_expired_2' => 'Haz clic en "Reconectar Google Drive"',
    'google_drive_action_token_expired_3' => 'Completa el proceso de autorización',
    'google_drive_action_token_expired_4' => 'Reintenta tu subida',

    // Google Drive Error Recovery Actions - Insufficient Permissions
    'google_drive_action_insufficient_permissions_1' => 'Ve a Configuración → Almacenamiento en la Nube',
    'google_drive_action_insufficient_permissions_2' => 'Haz clic en "Reconectar Google Drive"',
    'google_drive_action_insufficient_permissions_3' => 'Asegúrate de otorgar acceso completo cuando se te solicite',
    'google_drive_action_insufficient_permissions_4' => 'Verifica que tienes permisos de edición para la carpeta de destino',

    // Google Drive Error Recovery Actions - Storage Quota Exceeded
    'google_drive_action_storage_quota_exceeded_1' => 'Libera espacio en tu cuenta de Google Drive',
    'google_drive_action_storage_quota_exceeded_2' => 'Vacía la papelera de tu Google Drive',

    // Employee Management
    'nav_employee_management' => 'Gestión de Empleados',
    'employee_management_title' => 'Gestión de Empleados',
    'create_employee_title' => 'Crear Nuevo Empleado',
    'employees_list_title' => 'Usuarios Empleados',
    'button_create_employee' => 'Crear Empleado',
    'no_employees_match_filter' => 'Ningún empleado coincide con tus criterios de filtro',
    'no_employees_found' => 'No se encontraron empleados',
    'column_reset_url' => 'URL de Restablecimiento',
    'button_copy_reset_url' => 'Copiar URL de Restablecimiento',

    // Employee Creation Messages
    'employee_created_success' => 'Usuario empleado creado exitosamente.',
    'employee_created_and_invited_success' => 'Usuario empleado creado y correo de verificación enviado exitosamente.',
    'employee_created_email_failed' => 'Usuario empleado creado pero el correo de verificación falló al enviarse. Por favor revisa los registros.',
    'employee_creation_failed' => 'Error al crear el usuario empleado. Por favor revisa los registros.',

    // Role-Based Email Verification
    // Admin Verification
    'admin_verify_email_subject' => 'Verifica tu Dirección de Correo de Administrador',
    'admin_verify_email_title' => 'Verifica tu Dirección de Correo de Administrador',
    'admin_verify_email_intro' => 'Bienvenido al sistema de gestión de archivos de :company_name. Como administrador, tienes acceso completo para gestionar usuarios, configurar almacenamiento en la nube y supervisar todas las subidas de archivos. Por favor verifica tu dirección de correo para completar la configuración de tu cuenta de administrador.',
    'admin_verify_email_button' => 'Verificar Acceso de Administrador',

    // Employee Verification  
    'employee_verify_email_subject' => 'Verifica tu Dirección de Correo de Empleado',
    'employee_verify_email_title' => 'Verifica tu Dirección de Correo de Empleado',
    'employee_verify_email_intro' => '¡Bienvenido a :company_name! Como empleado, puedes recibir subidas de archivos de clientes directamente en tu Google Drive y gestionar tus propias relaciones con clientes. Por favor verifica tu dirección de correo para comenzar a recibir archivos de clientes.',
    'employee_verify_email_button' => 'Verificar Acceso de Empleado',

    // Client Verification
    'client_verify_email_subject' => 'Verifica tu Dirección de Correo',
    'client_verify_email_title' => 'Verifica tu Dirección de Correo', 
    'client_verify_email_intro' => 'Para subir archivos a :company_name, por favor verifica tu dirección de correo haciendo clic en el enlace de abajo. Una vez verificado, podrás subir archivos de forma segura que serán entregados directamente al miembro del equipo apropiado.',
    'client_verify_email_button' => 'Verificar Dirección de Correo',

    // Shared elements
    'verify_email_ignore' => 'Si no solicitaste esta verificación, puedes ignorar este correo de forma segura.',
    'thanks_signature' => 'Gracias',

    // Button Loading States
    'button_create_user_loading' => 'Creando Usuario...',
    'button_create_and_invite_loading' => 'Creando y Enviando...',

    // S3 Configuration Management Messages
    's3_file_not_found' => 'Archivo no encontrado: :path (intentado: :tried_path)',

    // Client Management
    'nav_client_management' => 'Gestión de Clientes',
    'client_management_title' => 'Gestión de Clientes',
    'my_clients_title' => 'Mis Clientes',
    'create_client_user' => 'Crear Nuevo Usuario Cliente',

    // Admin User Creation Messages
    'admin_user_created' => 'Usuario cliente creado exitosamente. Puedes proporcionarles el enlace de inicio de sesión manualmente.',
    'admin_user_created_and_invited' => 'Usuario cliente creado e invitación enviada exitosamente.',
    'admin_user_created_email_failed' => 'Usuario cliente creado exitosamente, pero el correo de invitación falló al enviarse. Puedes proporcionarles el enlace de inicio de sesión manualmente.',
    'admin_user_creation_failed' => 'Error al crear el usuario cliente. Por favor intenta de nuevo.',

    // Employee Client Creation Messages
    'employee_client_created' => 'Usuario cliente creado exitosamente. Puedes proporcionarles el enlace de inicio de sesión manualmente.',
    'employee_client_created_and_invited' => 'Usuario cliente creado e invitación enviada exitosamente.',
    'employee_client_created_email_failed' => 'Usuario cliente creado exitosamente, pero el correo de invitación falló al enviarse. Puedes proporcionarles el enlace de inicio de sesión manualmente.',
    'employee_client_creation_failed' => 'Error al crear el usuario cliente. Por favor intenta de nuevo.',

    // Account Deletion Messages
    'account_deletion_request_failed' => 'Error al procesar la solicitud de eliminación. Por favor intenta de nuevo.',
    'account_deletion_link_invalid' => 'El enlace de confirmación de eliminación es inválido o ha expirado.',
    'account_deletion_verification_invalid' => 'Enlace de verificación inválido o expirado.',
    'account_deletion_user_invalid' => 'Cuenta de usuario inválida.',
    'account_deletion_success' => 'Tu cuenta y todos los datos asociados han sido eliminados permanentemente.',
    'account_deletion_error' => 'Ocurrió un error al eliminar tu cuenta. Por favor intenta de nuevo o contacta al soporte.',
    'account_deletion_unexpected_error' => 'Ocurrió un error inesperado. Por favor intenta de nuevo o contacta al soporte.',

    // Google Drive OAuth Error Messages
    'oauth_authorization_code_missing' => 'Código de autorización no proporcionado.',
    'oauth_state_parameter_missing' => 'Parámetro de estado faltante.',
    'oauth_state_parameter_invalid' => 'Parámetro de estado inválido.',
    'oauth_user_not_found' => 'Usuario no encontrado.',
    'oauth_connection_validation_failed' => 'Conexión establecida pero la validación falló. Por favor intenta reconectar de nuevo.',

    // Enhanced Validation Messages
    'validation_name_required' => 'El campo nombre es obligatorio.',
    'validation_name_string' => 'El nombre debe ser una cadena de texto válida.',
    'validation_name_max' => 'El nombre no puede tener más de 255 caracteres.',
    'validation_email_required' => 'El campo correo electrónico es obligatorio.',
    'validation_email_format' => 'El correo electrónico debe tener un formato válido.',
    'validation_action_required' => 'El campo acción es obligatorio.',
    'validation_action_invalid' => 'La acción seleccionada es inválida.',
    'validation_team_members_required' => 'Debe seleccionarse al menos un miembro del equipo.',
    'validation_team_members_min' => 'Debe seleccionarse al menos un miembro del equipo.',
    'validation_team_member_invalid' => 'Uno o más miembros del equipo seleccionados son inválidos.',
    'validation_primary_contact_required' => 'Debe seleccionarse un contacto principal.',
    'validation_primary_contact_invalid' => 'El contacto principal seleccionado es inválido.',
    'validation_primary_contact_not_in_team' => 'El contacto principal debe ser miembro del equipo seleccionado.',
    'validation_team_members_unauthorized' => 'No tienes autorización para asignar uno o más de los miembros del equipo seleccionados.',
    'validation_primary_contact_unauthorized' => 'No tienes autorización para asignar este contacto principal.',

    // Team Assignment Messages
    'team_assignments_updated_success' => 'Asignaciones de equipo actualizadas exitosamente.',
    'team_assignments_update_failed' => 'Error al actualizar las asignaciones de equipo. Por favor intenta de nuevo.',

    // Cloud Storage Settings
    'settings_updated_successfully' => 'Configuración actualizada exitosamente.',
    'settings_update_failed' => 'Error al actualizar la configuración. Por favor intenta de nuevo.',

    // Cloud Storage Status Messages (from CloudStorageStatusMessages class)
    'cloud_storage_rate_limited' => 'Demasiados intentos de renovación de token. Por favor, inténtalo de nuevo más tarde.',
    'cloud_storage_auth_required' => 'Autenticación requerida. Por favor, reconecta tu cuenta.',
    'cloud_storage_connection_healthy' => 'Conectado y funcionando correctamente',
    'cloud_storage_not_connected' => 'Cuenta no conectada. Por favor, configura tu conexión de almacenamiento en la nube.',
    'cloud_storage_connection_issues' => 'Problema de conexión detectado. Por favor, prueba tu conexión.',
    'cloud_storage_multiple_failures' => 'Múltiples fallos de conexión detectados. Por favor, verifica la configuración de tu cuenta.',
    'cloud_storage_status_unknown' => 'Estado desconocido. Por favor, actualiza o contacta al soporte.',
    'cloud_storage_retry_time_message' => '{1} Demasiados intentos. Por favor, inténtalo de nuevo en :minutes minuto.|[2,*] Demasiados intentos. Por favor, inténtalo de nuevo en :minutes minutos.',
    'cloud_storage_retry_seconds_message' => '{1} Demasiados intentos. Por favor, inténtalo de nuevo en :seconds segundo.|[2,*] Demasiados intentos. Por favor, inténtalo de nuevo en :seconds segundos.',
    'cloud_storage_retry_persistent_message' => '{1} Problemas de conexión persistentes con :provider. Por favor, inténtalo de nuevo en :minutes minuto.|[2,*] Problemas de conexión persistentes con :provider. Por favor, inténtalo de nuevo en :minutes minutos.',
    'cloud_storage_retry_multiple_message' => '{1} Múltiples intentos de conexión detectados. Por favor, inténtalo de nuevo en :minutes minuto.|[2,*] Múltiples intentos de conexión detectados. Por favor, inténtalo de nuevo en :minutes minutos.',

    // Cloud Storage Error Messages (from CloudStorageErrorMessageService class)
    'cloud_storage_token_expired' => 'Tu conexión de :provider ha expirado. Por favor, reconecta tu cuenta para continuar.',
    'cloud_storage_token_refresh_rate_limited' => 'Demasiados intentos de conexión :provider. Por favor, espera antes de intentar de nuevo para evitar retrasos prolongados.',
    'cloud_storage_invalid_credentials' => 'Credenciales de :provider inválidas. Por favor, verifica tu configuración y reconecta tu cuenta.',
    'cloud_storage_insufficient_permissions' => 'Permisos insuficientes de :provider. Por favor, reconecta tu cuenta y asegúrate de otorgar acceso completo.',
    'cloud_storage_api_quota_exceeded' => 'Límite de API de :provider alcanzado. Tus operaciones se reanudarán automáticamente cuando se restablezca el límite.',
    'cloud_storage_storage_quota_exceeded' => 'Tu almacenamiento de :provider está lleno. Por favor, libera espacio o actualiza tu plan de almacenamiento.',
    'cloud_storage_network_error' => 'Un problema de conexión de red impidió la operación de :provider. Por favor, verifica tu conexión a internet e inténtalo de nuevo.',
    'cloud_storage_service_unavailable' => ':provider está temporalmente no disponible. Por favor, inténtalo de nuevo en unos minutos.',
    'cloud_storage_timeout' => 'La :operation de :provider agotó el tiempo de espera. Esto es usualmente temporal - por favor, inténtalo de nuevo.',
    'cloud_storage_file_not_found' => 'El archivo \':filename\' no se pudo encontrar en :provider. Puede haber sido eliminado o movido.',
    'cloud_storage_folder_access_denied' => 'Acceso denegado a la carpeta de :provider. Por favor, verifica los permisos de tu carpeta.',
    'cloud_storage_invalid_file_type' => 'El tipo de archivo de \':filename\' no es compatible con :provider. Por favor, intenta con un formato de archivo diferente.',
    'cloud_storage_file_too_large' => 'El archivo \':filename\' es demasiado grande para :provider. Por favor, reduce el tamaño del archivo e inténtalo de nuevo.',
    'cloud_storage_invalid_file_content' => 'El archivo \':filename\' parece estar corrupto. Por favor, intenta subir el archivo nuevamente.',
    'cloud_storage_provider_not_configured' => ':provider no está configurado correctamente. Por favor, verifica tu configuración e inténtalo de nuevo.',
    'cloud_storage_unknown_error' => 'Ocurrió un error inesperado con :provider. Por favor, inténtalo de nuevo o contacta al soporte si el problema persiste.',
    'cloud_storage_default_error' => 'Ocurrió un error durante la :operation de :provider. Por favor, inténtalo de nuevo.',

    // Mensajes de error específicos de S3
    'cloud_storage_bucket_not_found' => 'El bucket de :provider no existe. Por favor, verifica el nombre del bucket en tu configuración.',
    'cloud_storage_bucket_access_denied' => 'Se denegó el acceso al bucket de :provider. Por favor, verifica que tus credenciales de AWS tengan los permisos necesarios.',
    'cloud_storage_invalid_bucket_name' => 'El nombre del bucket de :provider es inválido. Los nombres de bucket deben tener entre 3 y 63 caracteres y seguir las reglas de nomenclatura de S3.',
    'cloud_storage_invalid_region' => 'La región de :provider es inválida. Por favor, verifica que la región coincida con la ubicación de tu bucket.',

    // Connection Issue Context Messages
    'cloud_storage_persistent_failures' => 'Fallos de conexión persistentes detectados. Por favor, verifica la configuración de tu cuenta :provider y tu conexión de red.',
    'cloud_storage_multiple_token_refresh_attempts' => 'Múltiples intentos de renovación de token detectados. Por favor, espera unos minutos antes de intentar de nuevo.',
    'cloud_storage_retry_with_time' => '{1} Demasiados intentos de renovación de token. Por favor, espera :minutes minuto más antes de intentar de nuevo.|[2,*] Demasiados intentos de renovación de token. Por favor, espera :minutes minutos más antes de intentar de nuevo.',

    // Cloud Storage Recovery Instructions - Token Expired
    'cloud_storage_recovery_token_expired_1' => 'Ve a Configuración → Almacenamiento en la Nube',
    'cloud_storage_recovery_token_expired_2' => 'Haz clic en "Reconectar :provider"',
    'cloud_storage_recovery_token_expired_3' => 'Completa el proceso de autorización',
    'cloud_storage_recovery_token_expired_4' => 'Reintenta tu operación',

    // Cloud Storage Recovery Instructions - Rate Limited
    'cloud_storage_recovery_rate_limited_1' => 'Espera a que se restablezca el límite de velocidad',
    'cloud_storage_recovery_rate_limited_2' => 'Evita hacer clic repetidamente en los botones de prueba de conexión',
    'cloud_storage_recovery_rate_limited_3' => 'Las operaciones se reanudarán automáticamente cuando se restablezca el límite',
    'cloud_storage_recovery_rate_limited_4' => 'Contacta al soporte si el problema persiste más allá del tiempo esperado',

    // Cloud Storage Recovery Instructions - Insufficient Permissions
    'cloud_storage_recovery_insufficient_permissions_1' => 'Ve a Configuración → Almacenamiento en la Nube',
    'cloud_storage_recovery_insufficient_permissions_2' => 'Haz clic en "Reconectar :provider"',
    'cloud_storage_recovery_insufficient_permissions_3' => 'Asegúrate de otorgar acceso completo cuando se te solicite',
    'cloud_storage_recovery_insufficient_permissions_4' => 'Verifica que tienes los permisos necesarios',

    // Cloud Storage Recovery Instructions - Storage Quota Exceeded
    'cloud_storage_recovery_storage_quota_exceeded_1' => 'Libera espacio en tu cuenta de :provider',
    'cloud_storage_recovery_storage_quota_exceeded_2' => 'Vacía la papelera de tu :provider',
    'cloud_storage_recovery_storage_quota_exceeded_3' => 'Considera actualizar tu plan de almacenamiento de :provider',
    'cloud_storage_recovery_storage_quota_exceeded_4' => 'Contacta a tu administrador si usas una cuenta empresarial',

    // Cloud Storage Recovery Instructions - API Quota Exceeded
    'cloud_storage_recovery_api_quota_exceeded_1' => 'Espera a que se restablezca la cuota (usualmente dentro de una hora)',
    'cloud_storage_recovery_api_quota_exceeded_2' => 'Las operaciones se reanudarán automáticamente',
    'cloud_storage_recovery_api_quota_exceeded_3' => 'Considera distribuir operaciones grandes a lo largo de varios días',

    // Cloud Storage Recovery Instructions - Network Error
    'cloud_storage_recovery_network_error_1' => 'Verifica tu conexión a internet',
    'cloud_storage_recovery_network_error_2' => 'Inténtalo de nuevo en unos minutos',
    'cloud_storage_recovery_network_error_3' => 'Contacta a tu administrador de red si el problema persiste',

    // Cloud Storage Recovery Instructions - Service Unavailable
    'cloud_storage_recovery_service_unavailable_1' => 'Espera unos minutos e inténtalo de nuevo',
    'cloud_storage_recovery_service_unavailable_2' => 'Verifica la página de estado de :provider para actualizaciones del servicio',
    'cloud_storage_recovery_service_unavailable_3' => 'Las operaciones se reintentarán automáticamente',

    // Cloud Storage Recovery Instructions - Timeout
    'cloud_storage_recovery_timeout_1' => 'Inténtalo de nuevo - los timeouts son usualmente temporales',
    'cloud_storage_recovery_timeout_2' => 'Verifica la velocidad de tu conexión a internet',
    'cloud_storage_recovery_timeout_3' => 'Para archivos grandes, intenta subir durante horas de menor tráfico',

    // Cloud Storage Recovery Instructions - Folder Access Denied
    'cloud_storage_recovery_folder_access_denied_1' => 'Verifica que la carpeta de destino existe en tu :provider',
    'cloud_storage_recovery_folder_access_denied_2' => 'Verifica que tienes permisos de escritura en la carpeta',
    'cloud_storage_recovery_folder_access_denied_3' => 'Intenta reconectar tu cuenta de :provider',

    // Cloud Storage Recovery Instructions - Invalid File Type
    'cloud_storage_recovery_invalid_file_type_1' => 'Convierte el archivo a un formato compatible',
    'cloud_storage_recovery_invalid_file_type_2' => 'Verifica los tipos de archivo compatibles de :provider',
    'cloud_storage_recovery_invalid_file_type_3' => 'Intenta subir un archivo diferente para probar',

    // Cloud Storage Recovery Instructions - File Too Large
    'cloud_storage_recovery_file_too_large_1' => 'Comprime el archivo para reducir su tamaño',
    'cloud_storage_recovery_file_too_large_2' => 'Divide archivos grandes en partes más pequeñas',
    'cloud_storage_recovery_file_too_large_3' => 'Usa la interfaz web de :provider para archivos muy grandes',

    // Cloud Storage Recovery Instructions - Invalid File Content
    'cloud_storage_recovery_invalid_file_content_1' => 'Verifica que el archivo no esté corrupto',
    'cloud_storage_recovery_invalid_file_content_2' => 'Intenta recrear o volver a descargar el archivo',
    'cloud_storage_recovery_invalid_file_content_3' => 'Escanea el archivo en busca de virus o malware',

    // Cloud Storage Recovery Instructions - Provider Not Configured
    'cloud_storage_recovery_provider_not_configured_1' => 'Ve a Configuración → Almacenamiento en la Nube',
    'cloud_storage_recovery_provider_not_configured_2' => 'Verifica tu configuración',
    'cloud_storage_recovery_provider_not_configured_3' => 'Asegúrate de que todos los campos requeridos estén completados correctamente',
    'cloud_storage_recovery_provider_not_configured_4' => 'Contacta al soporte si necesitas asistencia',

    // Cloud Storage Recovery Instructions - Unknown Error
    'cloud_storage_recovery_unknown_error_1' => 'Intenta la operación de nuevo',
    'cloud_storage_recovery_unknown_error_2' => 'Verifica tu conexión a internet',
    'cloud_storage_recovery_unknown_error_3' => 'Contacta al soporte si el problema persiste',
    'cloud_storage_recovery_unknown_error_4' => 'Incluye cualquier detalle del error al contactar al soporte',

    // Cloud Storage Recovery Instructions - Default
    'cloud_storage_recovery_default_1' => 'Intenta la operación de nuevo',
    'cloud_storage_recovery_default_2' => 'Verifica tu conexión y configuración',
    'cloud_storage_recovery_default_3' => 'Contacta al soporte si el problema persiste',

    // Cloud Storage Provider Display Names
    'cloud_storage_provider_google_drive' => 'Google Drive',
    'cloud_storage_provider_amazon_s3' => 'Amazon S3',
    'cloud_storage_provider_azure_blob' => 'Azure Blob Storage',
    'cloud_storage_provider_microsoft_teams' => 'Microsoft Teams',
    'cloud_storage_provider_dropbox' => 'Dropbox',
    'cloud_storage_provider_onedrive' => 'OneDrive',

    // Performance Optimized Health Validator Messages
    'health_validation_failed' => 'Validación falló: :message',
    'health_user_not_found' => 'Usuario no encontrado',
    'health_batch_processing_failed' => 'Procesamiento por lotes falló: :message',
    'health_validation_rate_limited' => 'Validación de salud limitada por velocidad - por favor intenta de nuevo más tarde',

    // Recovery Strategy Messages
    'recovery_strategy_token_refresh' => 'Intentando renovar tokens de autenticación',
    'recovery_strategy_network_retry' => 'Reintentando después de problemas de conectividad de red',
    'recovery_strategy_quota_wait' => 'Esperando que se restaure la cuota de API',
    'recovery_strategy_service_retry' => 'Reintentando después de que el servicio esté disponible',
    'recovery_strategy_health_check_retry' => 'Realizando verificación de salud y reintento',
    'recovery_strategy_user_intervention_required' => 'Se requiere intervención manual del usuario',
    'recovery_strategy_no_action_needed' => 'No se necesita acción, la conexión está saludable',
    'recovery_strategy_unknown' => 'Estrategia de recuperación desconocida',
    'google_drive_action_storage_quota_exceeded_3' => 'Considera actualizar tu plan de almacenamiento de Google Drive',
    'google_drive_action_storage_quota_exceeded_4' => 'Contacta a tu administrador si usas una cuenta empresarial',

    // Google Drive Error Recovery Actions - API Quota Exceeded
    'google_drive_action_api_quota_exceeded_1' => 'Espera a que se restablezca la cuota (usualmente dentro de una hora)',
    'google_drive_action_api_quota_exceeded_2' => 'Las subidas se reanudarán automáticamente',
    'google_drive_action_api_quota_exceeded_3' => 'Considera distribuir las subidas a lo largo de varios días para lotes grandes',

    // Google Drive Error Recovery Actions - Invalid Credentials
    'google_drive_action_invalid_credentials_1' => 'Ve a Configuración → Almacenamiento en la Nube',
    'google_drive_action_invalid_credentials_2' => 'Desconecta y reconecta tu cuenta de Google Drive',
    'google_drive_action_invalid_credentials_3' => 'Asegúrate de que tu cuenta de Google esté activa y accesible',

    // Google Drive Error Recovery Actions - Folder Access Denied
    'google_drive_action_folder_access_denied_1' => 'Verifica que la carpeta de destino existe en tu Google Drive',
    'google_drive_action_folder_access_denied_2' => 'Verifica que tienes permisos de escritura en la carpeta',
    'google_drive_action_folder_access_denied_3' => 'Intenta reconectar tu cuenta de Google Drive',

    // Google Drive Error Recovery Actions - Invalid File Type
    'google_drive_action_invalid_file_type_1' => 'Convierte el archivo a un formato compatible',
    'google_drive_action_invalid_file_type_2' => 'Verifica los tipos de archivo compatibles con Google Drive',
    'google_drive_action_invalid_file_type_3' => 'Intenta subir un archivo diferente para probar',

    // Google Drive Error Recovery Actions - File Too Large
    'google_drive_action_file_too_large_1' => 'Comprime el archivo para reducir su tamaño',
    'google_drive_action_file_too_large_2' => 'Divide archivos grandes en partes más pequeñas',
    'google_drive_action_file_too_large_3' => 'Usa la interfaz web de Google Drive para archivos muy grandes',

    // Time-related messages for quota reset
    'quota_reset_time_1_hour' => '1 hora',
    'quota_reset_time_hours' => ':hours horas',
    'quota_reset_time_minutes' => ':minutes minutos',
    'quota_reset_time_unknown' => 'un tiempo breve',

    // Common error messages
    'error_generic' => 'Ocurrió un error. Por favor, inténtalo de nuevo.',
    'unknown_error' => 'Error desconocido',

    // Token Refresh Result Messages
    'token_refresh_success' => 'Token renovado exitosamente',
    'token_already_valid' => 'El token ya es válido',
    'token_refreshed_by_another_process' => 'El token fue renovado por otro proceso',
    'token_already_valid_description' => 'El token ya era válido y no necesitaba renovación',
    'token_refreshed_by_another_process_description' => 'El token fue renovado por otro proceso concurrente',
    'token_refresh_success_description' => 'El token fue renovado exitosamente',
    'token_refresh_failed_description' => 'Falló la renovación del token: :message',

    // Proactive Token Renewal Messages
    'proactive_refresh_provider_not_supported' => 'Proveedor no compatible con la renovación proactiva',
    'proactive_refresh_no_token_found' => 'No se encontró token de autenticación',
    'proactive_refresh_token_not_expiring' => 'El token no expira pronto y no necesita renovación',
    'proactive_refresh_requires_reauth' => 'El token requiere nueva autenticación del usuario',

    // Upload Page Section Messages
    'your_upload_page' => 'Tu Página de Subida',
    'copy_url' => 'Copiar URL',
    'copied' => '¡Copiado!',
    'copy_failed' => 'Error al copiar',
    'upload_url_label' => 'URL de Subida',
    'url_copied_to_clipboard' => 'URL copiada al portapapeles',
    'copy_url_to_clipboard' => 'Copiar URL al portapapeles',
    'share_this_url_with_clients' => 'Comparte esta URL con tus clientes para recibir subidas de archivos.',
    'cloud_storage' => 'Almacenamiento en la Nube',
    'files_stored_in_organization_storage' => 'Los archivos se almacenan en el :provider de tu organización',
    'contact_admin_for_storage_questions' => 'Contacta a tu administrador para preguntas relacionadas con el almacenamiento',
    'upload_page_not_available' => 'La página de subida no está disponible. Por favor contacta a tu administrador.',
    'storage_configuration_error' => 'El almacenamiento en la nube no está configurado correctamente',
    'contact_admin_to_resolve' => 'Por favor contacta a tu administrador para resolver este problema',
    'cloud_storage_info' => 'Información de Almacenamiento en la Nube',
    'managed_by_administrator' => 'Gestionado por tu administrador',
    'contact_admin_for_storage_configuration' => 'Para configuración o preguntas sobre almacenamiento, por favor contacta a tu administrador.',

    // Health Status Messages
    'health_status_healthy' => 'Saludable',
    'health_status_authentication_required' => 'Autenticación requerida',
    'health_status_connection_issues' => 'Problemas de conexión',
    'health_status_not_connected' => 'No conectado',
    'health_status_token_validation_failed' => 'Falló la validación del token',
    'health_status_api_connectivity_test_failed' => 'Falló la prueba de conectividad API',
    'health_status_authentication_error' => 'Error de autenticación',
    'health_status_connection_error' => 'Error de conexión',
    'health_status_token_error' => 'Error de token',
    'health_status_api_error' => 'Error de API',

    // Token Renewal Notification Service
    'notification_failure_alert_subject' => 'Alerta de Fallo de Notificación - Usuario :email',
    'notification_failure_alert_body' => 'Falló el envío de notificación :type al usuario :email para el proveedor :provider después de :attempts intentos.\n\nÚltimo error: :error\n\nPor favor, verifica la dirección de email del usuario y la configuración del sistema.',

    // Token Expired Email
    'token_expired_subject' => 'Conexión de :provider Expirada - Acción Requerida',
    'token_expired_heading' => 'Conexión de :provider Expirada',
    'token_expired_subheading' => 'Acción Requerida para Reanudar Subidas de Archivos',
    'token_expired_alert' => 'Atención Requerida: Tu conexión de :provider ha expirado y necesita ser renovada.',
    'token_expired_greeting' => 'Hola :name,',
    'token_expired_intro' => 'Te escribimos para informarte que tu conexión de :provider ha expirado. Esto significa que las nuevas subidas de archivos no pueden ser procesadas hasta que reconectes tu cuenta.',
    'token_expired_what_this_means' => 'Qué Significa Esto:',
    'token_expired_impact_uploads' => 'Las nuevas subidas de archivos fallarán hasta que reconectes',
    'token_expired_impact_existing' => 'Los archivos existentes en tu :provider no se ven afectados',
    'token_expired_impact_resume' => 'Tu sistema de subida reanudará operación normal una vez reconectado',
    'token_expired_how_to_reconnect' => 'Cómo Reconectar:',
    'token_expired_step_1' => 'Haz clic en el botón "Reconectar :provider" abajo',
    'token_expired_step_2' => 'Inicia sesión en tu cuenta de :provider cuando se te solicite',
    'token_expired_step_3' => 'Otorga permiso para que el sistema de subida acceda a tu cuenta',
    'token_expired_step_4' => 'Verifica que la conexión funciona en tu panel de control',
    'token_expired_reconnect_button' => 'Reconectar :provider',
    'token_expired_why_happened' => '¿Por Qué Pasó Esto?',
    'token_expired_explanation' => 'Las conexiones de :provider expiran periódicamente por razones de seguridad. Esto es normal y ayuda a proteger tu cuenta. El sistema intentó renovar automáticamente la conexión, pero ahora se requiere intervención manual.',
    'token_expired_need_help' => '¿Necesitas Ayuda?',
    'token_expired_support' => 'Si tienes problemas reconectando o tienes preguntas sobre este proceso, no dudes en contactar a nuestro equipo de soporte en :email.',
    'token_expired_footer_important' => 'Este email fue enviado porque tu conexión de :provider expiró. Si no esperabas este email, por favor contacta al soporte inmediatamente.',
    'token_expired_footer_automated' => 'Este es un mensaje automatizado de tu sistema de subida de archivos. Por favor no respondas directamente a este email.',

    // Token Refresh Failed Email
    'token_refresh_failed_subject' => 'Problema de Conexión de :provider - :urgency',
    'token_refresh_failed_heading' => 'Problema de Conexión de :provider',
    'token_refresh_failed_action_required' => 'Acción Requerida',
    'token_refresh_failed_auto_recovery' => 'Recuperación Automática en Progreso',
    'token_refresh_failed_alert_action' => 'Acción Requerida: Tu conexión de :provider necesita atención manual.',
    'token_refresh_failed_alert_auto' => 'Problema de Conexión: Estamos trabajando para restaurar tu conexión de :provider automáticamente.',
    'token_refresh_failed_greeting' => 'Hola :name,',
    'token_refresh_failed_intro' => 'Encontramos un problema al intentar renovar tu conexión de :provider. Aquí está lo que pasó y lo que estamos haciendo al respecto:',
    'token_refresh_failed_issue_details' => 'Detalles del Problema:',
    'token_refresh_failed_error_type' => 'Tipo de Error: :type',
    'token_refresh_failed_attempt' => 'Intento: :current de :max',
    'token_refresh_failed_description' => 'Descripción: :description',
    'token_refresh_failed_technical_details' => 'Detalles Técnicos: :details',
    'token_refresh_failed_what_to_do' => 'Qué Necesitas Hacer:',
    'token_refresh_failed_manual_required' => 'Este tipo de error requiere intervención manual. Por favor reconecta tu cuenta de :provider para restaurar la funcionalidad de subida de archivos.',
    'token_refresh_failed_reconnect_now' => 'Reconectar :provider Ahora',
    'token_refresh_failed_why_manual' => 'Por Qué Se Necesita Acción Manual:',
    'token_refresh_failed_credentials_invalid' => 'Tus credenciales de autenticación ya no son válidas. Esto típicamente ocurre cuando cambias tu contraseña, revocas acceso, o el token ha estado inactivo por un período extendido.',
    'token_refresh_failed_cannot_resolve' => 'Este tipo de error no puede ser resuelto automáticamente y requiere que restablezcas la conexión.',
    'token_refresh_failed_auto_recovery_status' => 'Estado de Recuperación Automática:',
    'token_refresh_failed_no_action_needed' => 'El sistema continuará intentando restaurar la conexión automáticamente. No necesitas tomar ninguna acción en este momento.',
    'token_refresh_failed_max_attempts' => 'Intentos Máximos Alcanzados:',
    'token_refresh_failed_exhausted' => 'El sistema ha agotado todos los intentos de reintento automático. Por favor reconecta manualmente para restaurar la funcionalidad.',
    'token_refresh_failed_what_happens_next' => 'Qué Pasa Después:',
    'token_refresh_failed_auto_retry' => 'El sistema reintentará automáticamente la conexión',
    'token_refresh_failed_success_email' => 'Si es exitoso, recibirás un email de confirmación',
    'token_refresh_failed_manual_notify' => 'Si todos los intentos fallan, serás notificado para reconectar manualmente',
    'token_refresh_failed_uploads_paused' => 'Las subidas de archivos están temporalmente pausadas hasta que la conexión sea restaurada',
    'token_refresh_failed_impact' => 'Impacto en Tu Servicio:',
    'token_refresh_failed_uploads_impact' => 'Subidas de Archivos: Las nuevas subidas están temporalmente pausadas',
    'token_refresh_failed_existing_impact' => 'Archivos Existentes: Todos los archivos previamente subidos permanecen seguros y accesibles',
    'token_refresh_failed_system_impact' => 'Estado del Sistema: Todas las otras características continúan funcionando normalmente',
    'token_refresh_failed_no_action_required' => 'No se requiere acción de tu parte en este momento. Te mantendremos actualizado sobre el progreso de recuperación.',
    'token_refresh_failed_need_help' => '¿Necesitas Ayuda?',
    'token_refresh_failed_support' => 'Si estás experimentando problemas repetidos de conexión o necesitas asistencia reconectando, por favor contacta a nuestro equipo de soporte en :email. Incluye esta referencia de error: :reference',
    'token_refresh_failed_error_reference' => 'Referencia de Error: :type (Intento :attempt)',
    'token_refresh_failed_timestamp' => 'Marca de Tiempo: :timestamp',
    'token_refresh_failed_footer_automated' => 'Este es un mensaje automatizado de tu sistema de subida de archivos. Por favor no respondas directamente a este email.',

    // Connection Restored Email
    'connection_restored_subject' => 'Conexión de :provider Restaurada',
    'connection_restored_heading' => '✅ Conexión de :provider Restaurada',
    'connection_restored_subheading' => '¡Tu sistema de subida de archivos está de vuelta en línea!',
    'connection_restored_alert' => 'Excelentes Noticias: Tu conexión de :provider ha sido restaurada exitosamente y está funcionando normalmente.',
    'connection_restored_greeting' => 'Hola :name,',
    'connection_restored_intro' => 'Nos complace informarte que el problema de conexión con tu cuenta de :provider ha sido resuelto. Tu sistema de subida de archivos está ahora completamente operacional otra vez.',
    'connection_restored_current_status' => 'Estado Actual:',
    'connection_restored_connection_status' => 'Conexión: ✅ Activa y saludable',
    'connection_restored_uploads_status' => 'Subidas de Archivos: ✅ Aceptando nuevas subidas',
    'connection_restored_pending_status' => 'Archivos Pendientes: ✅ Procesando cualquier subida en cola',
    'connection_restored_system_status' => 'Estado del Sistema: ✅ Todas las características operacionales',
    'connection_restored_what_happened' => 'Qué Pasó:',
    'connection_restored_explanation' => 'El sistema renovó exitosamente tu autenticación de :provider y restauró conectividad completa. Cualquier subida de archivos que fue temporalmente pausada durante el problema de conexión está ahora siendo procesada automáticamente.',
    'connection_restored_whats_happening' => 'Qué Está Pasando Ahora:',
    'connection_restored_processing_queued' => 'El sistema está procesando cualquier subida que fue puesta en cola durante la interrupción',
    'connection_restored_accepting_new' => 'Las nuevas subidas de archivos serán aceptadas y procesadas normalmente',
    'connection_restored_operations_resumed' => 'Todas las operaciones de :provider han sido reanudadas',
    'connection_restored_monitoring_active' => 'El monitoreo de conexión está activo para prevenir problemas futuros',
    'connection_restored_access_dashboard' => 'Accede a Tu Panel de Control:',
    'connection_restored_dashboard_intro' => 'Puedes ver el estado de tus subidas y gestionar tus archivos a través de tu panel de control:',
    'connection_restored_view_dashboard' => 'Ver Panel de Control',
    'connection_restored_preventing_issues' => 'Previniendo Problemas Futuros:',
    'connection_restored_keep_active' => 'Mantén tu cuenta de :provider activa y en buen estado',
    'connection_restored_avoid_password_change' => 'Evita cambiar tu contraseña de :provider sin actualizar la conexión',
    'connection_restored_monitor_email' => 'Monitorea tu email para cualquier alerta de conexión',
    'connection_restored_contact_support' => 'Contacta al soporte si notas cualquier comportamiento inusual',
    'connection_restored_need_assistance' => '¿Necesitas Asistencia?',
    'connection_restored_support' => 'Si experimentas cualquier problema con subidas de archivos o tienes preguntas sobre tu conexión de :provider, no dudes en contactar a nuestro equipo de soporte en :email.',
    'connection_restored_footer_timestamp' => 'Conexión Restaurada: :timestamp',
    'connection_restored_footer_service_status' => 'Estado del Servicio: Todos los sistemas operacionales',
    'connection_restored_footer_thanks' => 'Gracias por tu paciencia durante el problema de conexión. Este es un mensaje automatizado de tu sistema de subida de archivos.',

    // Token Refresh Configuration Validation Messages
    'token_config_proactive_refresh_minutes_min' => 'Los minutos de actualización proactiva deben ser al menos 1',
    'token_config_max_retry_attempts_range' => 'Los intentos máximos de reintento deben estar entre 1 y 10',
    'token_config_retry_base_delay_min' => 'El retraso base de reintento debe ser al menos 1 segundo',
    'token_config_notification_throttle_hours_min' => 'Las horas de limitación de notificaciones deben ser al menos 1',
    'token_config_max_attempts_per_hour_min' => 'Los intentos máximos por hora deben ser al menos 1',
    'token_config_max_health_checks_per_minute_min' => 'Las verificaciones de salud máximas por minuto deben ser al menos 1',
    
    // Token Refresh Admin Controller Validation Messages
    'token_config_proactive_refresh_range' => 'Los minutos de actualización proactiva deben estar entre 1 y 60',
    'token_config_background_refresh_range' => 'Los minutos de actualización en segundo plano deben estar entre 5 y 120',
    'token_config_notification_throttle_range' => 'Las horas de limitación de notificaciones deben estar entre 1 y 168 (1 semana)',
    'token_config_max_attempts_per_hour_range' => 'Los intentos máximos por hora deben estar entre 1 y 100',
    
    // Token Refresh Admin Interface Messages
    'token_config_admin_interface_disabled' => 'La interfaz de administración está deshabilitada',
    'token_config_runtime_changes_disabled' => 'Los cambios en tiempo de ejecución están deshabilitados',
    'token_config_update_failed' => 'Error al actualizar la configuración',
    'token_config_toggle_failed' => 'Error al alternar la función',
    'token_config_cache_clear_failed' => 'Error al limpiar la caché',
    'token_config_setting_updated' => 'Configuración \':key\' actualizada exitosamente.',
    'token_config_feature_enabled' => 'Función \':feature\' habilitada exitosamente.',
    'token_config_feature_disabled' => 'Función \':feature\' deshabilitada exitosamente.',
    'token_config_cache_cleared' => 'Caché de configuración limpiada exitosamente.',
    'token_config_change_requires_confirmation' => 'Cambiar \':key\' requiere confirmación ya que puede afectar el comportamiento del sistema.',
    'token_config_toggle_requires_confirmation' => 'Alternar \':feature\' requiere confirmación ya que puede afectar significativamente el comportamiento del sistema.',
    
    // Token Refresh Configuration Dashboard
    'token_config_dashboard_title' => 'Configuración de Actualización de Tokens',
    'token_config_dashboard_description' => 'Gestionar configuraciones de actualización de tokens y banderas de funciones para despliegue gradual.',
    'token_config_status_title' => 'Estado de Configuración',
    'token_config_refresh_button' => 'Actualizar',
    'token_config_clear_cache_button' => 'Limpiar Caché',
    'token_config_environment' => 'Entorno',
    'token_config_runtime_changes' => 'Cambios en Tiempo de Ejecución',
    'token_config_validation_status' => 'Estado de Validación',
    'token_config_enabled' => 'Habilitado',
    'token_config_disabled' => 'Deshabilitado',
    'token_config_valid' => 'Válido',
    'token_config_issues_found' => 'Problemas Encontrados',
    'token_config_issues_title' => 'Problemas de Configuración',
    'token_config_feature_flags_title' => 'Banderas de Funciones',
    'token_config_timing_title' => 'Configuración de Tiempos',
    'token_config_notifications_title' => 'Configuración de Notificaciones',
    'token_config_rate_limiting_title' => 'Configuración de Limitación de Velocidad',
    'token_config_security_title' => 'Configuración de Seguridad',
    'token_config_confirm_change_title' => 'Confirmar Cambio de Configuración',
    'token_config_confirm_button' => 'Confirmar',
    'token_config_cancel_button' => 'Cancelar',
    
    // Token Refresh Console Command Messages
    'token_config_cmd_unknown_action' => 'Acción desconocida: :action',
    'token_config_cmd_key_value_required' => 'Las opciones --key y --value son requeridas para la acción set',
    'token_config_cmd_feature_enabled_required' => 'Las opciones --feature y --enabled son requeridas para la acción toggle',
    'token_config_cmd_validation_failed' => 'Validación falló: :errors',
    'token_config_cmd_change_confirmation' => 'Cambiar \':key\' puede afectar el comportamiento del sistema. ¿Continuar?',
    'token_config_cmd_toggle_confirmation' => 'Alternar \':feature\' puede afectar significativamente el comportamiento del sistema. ¿Continuar?',
    'token_config_cmd_operation_cancelled' => 'Operación cancelada',
    'token_config_cmd_setting_updated' => 'Configuración \':key\' actualizada exitosamente a: :value',
    'token_config_cmd_setting_update_failed' => 'Error al actualizar la configuración \':key\'',
    'token_config_cmd_feature_enabled' => 'Función \':feature\' habilitada exitosamente',
    'token_config_cmd_feature_disabled' => 'Función \':feature\' deshabilitada exitosamente',
    'token_config_cmd_feature_toggle_failed' => 'Error al alternar la función \':feature\'',
    'token_config_cmd_validation_success' => '✓ La configuración es válida',
    'token_config_cmd_validation_failed_title' => 'La validación de configuración falló:',
    'token_config_cmd_cache_cleared' => 'Caché de configuración limpiada exitosamente',
    'token_config_cmd_cache_clear_failed' => 'Error al limpiar la caché de configuración: :error',

    // Error Type Display Names
    'error_type_network_timeout' => 'Tiempo de Espera de Red Agotado',
    'error_type_invalid_refresh_token' => 'Token de Renovación Inválido',
    'error_type_expired_refresh_token' => 'Token de Renovación Expirado',
    'error_type_api_quota_exceeded' => 'Cuota de API Excedida',
    'error_type_service_unavailable' => 'Servicio No Disponible',
    'error_type_unknown_error' => 'Error Desconocido',

    // Error Descriptions
    'error_desc_network_timeout' => 'Encontramos un tiempo de espera de red mientras intentábamos renovar tu conexión. Esto es usualmente temporal y el sistema reintentará automáticamente.',
    'error_desc_invalid_refresh_token' => 'Tu token de autenticación almacenado ya no es válido. Esto típicamente ocurre cuando revocas acceso o cambias tu contraseña en el servicio en la nube.',
    'error_desc_expired_refresh_token' => 'Tu token de autenticación ha expirado y no puede ser renovado automáticamente. Necesitarás reconectar tu cuenta.',
    'error_desc_api_quota_exceeded' => 'El servicio en la nube ha limitado temporalmente nuestro acceso debido al alto uso. El sistema reintentará automáticamente una vez que el límite se restablezca.',
    'error_desc_service_unavailable' => 'El servicio en la nube está temporalmente no disponible. Esto es usualmente un problema temporal de su parte, y el sistema reintentará automáticamente.',
    'error_desc_unknown_error' => 'Ocurrió un error inesperado mientras renovábamos tu conexión. Nuestro equipo técnico ha sido notificado e investigará.',

    // Retry Information
    'retry_no_automatic' => 'No se intentará reintento automático. Por favor reconecta manualmente.',
    'retry_max_attempts_reached' => 'Intentos máximos de reintento alcanzados. Por favor reconecta manualmente.',
    'retry_in_seconds' => 'El sistema reintentará en :seconds segundos. :remaining intentos restantes.',
    'retry_in_minutes' => 'El sistema reintentará en :minutes minutos. :remaining intentos restantes.',
    'retry_in_hours' => 'El sistema reintentará en :hours horas. :remaining intentos restantes.',

    // Provider Display Names
    'provider_google_drive' => 'Google Drive',
    'provider_microsoft_teams' => 'Microsoft Teams',
    'provider_dropbox' => 'Dropbox',

    // Connection Recovery Messages
    'recovery_connection_healthy' => 'La conexión está saludable',
    'recovery_connection_health_restored' => 'Salud de la conexión restaurada',
    'recovery_token_refreshed_successfully' => 'Token renovado exitosamente',
    'recovery_network_connectivity_restored' => 'Conectividad de red restaurada',
    'recovery_api_quota_restored' => 'Cuota de API restaurada',
    'recovery_service_availability_restored' => 'Disponibilidad del servicio restaurada',
    'recovery_no_action_needed' => 'No se necesita acción',
    'recovery_user_intervention_required' => 'Se requiere intervención del usuario',
    'recovery_manual_action_needed' => 'Se necesita acción manual',
    'recovery_failed_due_to_exception' => 'Recuperación falló debido a una excepción',
    'recovery_strategy_failed' => 'Estrategia de recuperación falló',
    'recovery_unknown_strategy' => 'Estrategia de recuperación desconocida',

    // Recovery Failure Messages
    'recovery_token_refresh_failed' => 'Renovación de token falló',
    'recovery_network_connectivity_still_failing' => 'La conectividad de red sigue fallando',
    'recovery_api_quota_still_exceeded' => 'Cuota de API aún excedida',
    'recovery_service_still_unavailable' => 'Servicio aún no disponible',
    'recovery_connection_still_unhealthy' => 'Conexión aún no saludable',

    // Recovery Exception Messages
    'recovery_token_refresh_exception' => 'Excepción de renovación de token',
    'recovery_network_test_exception' => 'Excepción de prueba de red',
    'recovery_quota_check_exception' => 'Excepción de verificación de cuota',
    'recovery_service_check_exception' => 'Excepción de verificación de servicio',
    'recovery_health_check_exception' => 'Excepción de verificación de salud',

    // Upload Recovery Messages
    'recovery_local_file_no_longer_exists' => 'El archivo local ya no existe',
    'recovery_no_target_user_found' => 'No se encontró usuario objetivo',
    'recovery_retry_job_permanently_failed' => 'Trabajo de reintento falló permanentemente',
    'recovery_upload_retry_failed_for_file' => 'Reintento de subida falló para el archivo',

    // Token Monitoring Dashboard
    'token_monitoring' => [
        'dashboard_title' => 'Panel de Monitoreo de Tokens',
        'dashboard_description' => 'Monitorea la salud de tokens de Google Drive, operaciones de renovación y métricas de rendimiento del sistema.',
        'metrics_reset_success' => 'Métricas reiniciadas para el proveedor: :provider',
        'overview_title' => 'Resumen del Sistema',
        'performance_metrics_title' => 'Métricas de Rendimiento',
        'token_status_title' => 'Resumen del Estado de Tokens',
        'recent_operations_title' => 'Operaciones Recientes',
        'health_trends_title' => 'Tendencias de Salud',
        'user_statistics_title' => 'Estadísticas de Usuario',
        'system_status_title' => 'Estado del Sistema',
        'recommendations_title' => 'Recomendaciones',
        'export_data' => 'Exportar Datos',
        'reset_metrics' => 'Reiniciar Métricas',
        'refresh_dashboard' => 'Actualizar Panel',
        'last_updated' => 'Última Actualización',
        'total_users' => 'Total de Usuarios',
        'connected_users' => 'Usuarios Conectados',
        'success_rate' => 'Tasa de Éxito',
        'average_refresh_time' => 'Tiempo Promedio de Renovación',
        'active_alerts' => 'Alertas Activas',
        'overall_health' => 'Salud General',
        'tokens_expiring_soon' => 'Expiran Pronto',
        'tokens_requiring_attention' => 'Requieren Atención',
        'healthy' => 'Saludable',
        'warning' => 'Advertencia',
        'critical' => 'Crítico',
        'unknown' => 'Desconocido',
        'degraded' => 'Degradado',
        'unhealthy' => 'No Saludable',
        'queue_health' => 'Salud de Cola',
        'cache_health' => 'Salud de Caché',
        'database_health' => 'Salud de Base de Datos',
        'api_health' => 'Salud de API',
        'overall_system_health' => 'Salud General del Sistema',
        'last_maintenance' => 'Último Mantenimiento',
        'next_maintenance' => 'Próximo Mantenimiento',
        'no_alerts' => 'No hay alertas activas',
        'view_details' => 'Ver Detalles',
        'time_period' => 'Período de Tiempo',
        'last_hour' => 'Última Hora',
        'last_6_hours' => 'Últimas 6 Horas',
        'last_24_hours' => 'Últimas 24 Horas',
        'last_week' => 'Última Semana',
        'provider' => 'Proveedor',
        'google_drive' => 'Google Drive',
        'microsoft_teams' => 'Microsoft Teams',
        'dropbox' => 'Dropbox',
        'loading' => 'Cargando...',
        'loading_dashboard_data' => 'Cargando datos del panel...',
        'total_users_label' => 'usuarios totales',
        'token_refresh_operations' => 'Operaciones de renovación de tokens',
        'milliseconds' => 'Milisegundos',
        'overall_system_health' => 'Salud General del Sistema',
        'token_refresh' => 'Renovación de Token',
        'api_connectivity' => 'Conectividad API',
        'cache_performance' => 'Rendimiento de Caché',
        'valid' => 'Válido',
        'expiring_soon' => 'Expiran Pronto',
        'need_attention' => 'Necesitan Atención',
        'error_breakdown' => 'Desglose de Errores',
        'no_errors_in_period' => 'No hay errores en el período seleccionado',
        'time' => 'Hora',
        'user' => 'Usuario',
        'operation' => 'Operación',
        'status' => 'Estado',
        'duration' => 'Duración',
        'details' => 'Detalles',
        'success' => 'Éxito',
        'error_loading_dashboard' => 'Error al Cargar el Panel',
        'try_again' => 'Intentar de Nuevo',
        'recommended_actions' => 'Acciones Recomendadas',
    ],

    // Token Status Service Messages
    'token_status_not_connected' => 'No se encontró token - cuenta no conectada',
    'token_status_requires_intervention' => 'El token requiere reconexión manual debido a fallos repetidos',
    'token_status_expired_refreshable' => 'Token expirado pero puede renovarse automáticamente',
    'token_status_expired_manual' => 'Token expirado y requiere reconexión manual',
    'token_status_expiring_soon' => 'El token se renovará automáticamente pronto',
    'token_status_healthy_with_warnings' => 'Token saludable pero tiene :count fallo(s) de renovación reciente(s)',
    'token_status_healthy' => 'El token está saludable y válido',
    'token_status_scheduled_now' => 'Programado ahora',
    'token_status_less_than_minute' => 'Menos de 1 minuto',
    'token_status_minute' => 'minuto',
    'token_status_minutes' => 'minutos',
    'token_status_hour' => 'hora',
    'token_status_hours' => 'horas',
    'token_status_day' => 'día',
    'token_status_days' => 'días',
    'token_status_last_error_intervention' => 'El token requiere reconexión manual debido a fallos repetidos',
    'token_status_last_error_generic' => 'La renovación del token falló - se reintentará automáticamente',

    // Verificación de Correo Electrónico
    'verify_email_title' => 'Verifica Tu Dirección de Correo Electrónico',
    'verify_email_intro' => 'Para subir archivos a :company_name, por favor verifica tu dirección de correo electrónico haciendo clic en el enlace de abajo.',
    'verify_email_sent' => 'Un nuevo enlace de verificación ha sido enviado a la dirección de correo electrónico que proporcionaste durante el registro.',
    'verify_email_resend_button' => 'Reenviar Correo de Verificación',
    'verify_email_button' => 'Verificar Dirección de Correo Electrónico',
    'verify_email_ignore' => 'Si no solicitaste esta verificación, puedes ignorar este correo electrónico de forma segura.',

    // Verificación de Correo Electrónico Basada en Roles
    // Verificación de Administrador
    'admin_verify_email_subject' => 'Verifica Tu Dirección de Correo Electrónico de Administrador',
    'admin_verify_email_title' => 'Verifica Tu Dirección de Correo Electrónico de Administrador',
    'admin_verify_email_intro' => 'Bienvenido al sistema de gestión de archivos de :company_name. Como administrador, tienes acceso completo para gestionar usuarios, configurar almacenamiento en la nube y supervisar todas las subidas de archivos. Por favor verifica tu dirección de correo electrónico para completar la configuración de tu cuenta de administrador.',
    'admin_verify_email_button' => 'Verificar Acceso de Administrador',

    // Verificación de Empleado  
    'employee_verify_email_subject' => 'Verifica Tu Dirección de Correo Electrónico de Empleado',
    'employee_verify_email_title' => 'Verifica Tu Dirección de Correo Electrónico de Empleado',
    'employee_verify_email_intro' => '¡Bienvenido a :company_name! Como empleado, puedes recibir subidas de archivos de clientes directamente en tu Google Drive y gestionar tus propias relaciones con clientes. Por favor verifica tu dirección de correo electrónico para comenzar a recibir archivos de clientes.',
    'employee_verify_email_button' => 'Verificar Acceso de Empleado',

    // Verificación de Cliente
    'client_verify_email_subject' => 'Verifica Tu Dirección de Correo Electrónico',
    'client_verify_email_title' => 'Verifica Tu Dirección de Correo Electrónico', 
    'client_verify_email_intro' => 'Para subir archivos a :company_name, por favor verifica tu dirección de correo electrónico haciendo clic en el enlace de abajo. Una vez verificado, podrás subir archivos de forma segura que serán entregados directamente al miembro del equipo apropiado.',
    'client_verify_email_button' => 'Verificar Dirección de Correo Electrónico',

    // Elementos Comunes
    'thanks_signature' => 'Gracias',

    // Perfil
    'profile_information' => 'Información del Perfil',
    'profile_update' => 'Actualizar Perfil',
    'profile_saved' => 'Perfil actualizado exitosamente.',
    'profile_update_info' => 'Actualiza la información del perfil y la dirección de correo electrónico de tu cuenta.',
    'profile_name' => 'Nombre',
    'profile_email' => 'Correo Electrónico',
    'profile_save' => 'Guardar',
    'profile_email_unverified' => 'Tu dirección de correo electrónico no está verificada.',
    'profile_email_verify_resend' => 'Haz clic aquí para reenviar el correo de verificación.',
    'profile_email_verify_sent' => 'Un nuevo enlace de verificación ha sido enviado a tu dirección de correo electrónico.',

    // Mensajes de Validación de Seguridad y Registro
    'public_registration_disabled' => 'Solo los usuarios invitados pueden iniciar sesión en este sistema. Si crees que deberías tener acceso, por favor contacta al administrador.',
    'email_domain_not_allowed' => 'Este dominio de correo electrónico no está permitido para nuevos registros. Si ya tienes una cuenta, por favor inténtalo de nuevo o contacta al soporte.',
    'security_settings_saved' => 'La configuración de seguridad se ha actualizado exitosamente.',
    
    // Mensajes de verificación mejorados para usuarios existentes vs nuevos
    'existing_user_verification_sent' => 'Correo de verificación enviado a tu cuenta existente. Por favor revisa tu bandeja de entrada.',
    'new_user_verification_sent' => 'Correo de verificación enviado. Por favor revisa tu bandeja de entrada para completar el registro.',
    'registration_temporarily_unavailable' => 'No se puede procesar el registro en este momento. Por favor, inténtalo de nuevo más tarde.',

    // Servicio de Métricas de Verificación de Email
    'email_verification_bypass_spike_alert' => 'Pico inusual en omisiones de usuarios existentes en la última hora',
    'email_verification_repeated_bypass_alert' => 'El usuario :user_id ha omitido restricciones :count veces',
    'email_verification_unusual_domain_alert' => 'Múltiples omisiones del dominio: :domain',
    'email_verification_high_bypass_volume_alert' => 'Alto volumen de omisiones de usuarios existentes: :count en la última hora (umbral: :threshold)',
    'email_verification_high_restriction_volume_alert' => 'Alto volumen de aplicación de restricciones: :count en la última hora (umbral: :threshold)',
    'email_verification_no_activity_alert' => 'No se detectó actividad de verificación de email durante horario laboral - posible problema del sistema',
    'email_verification_no_alerts_detected' => 'No se detectaron alertas',
    'email_verification_no_unusual_activity' => 'No se detectó actividad inusual',
    'email_verification_no_unusual_activity_24h' => 'No se detectó actividad inusual en las últimas 24 horas',
    'email_verification_alert_cooldown_active' => 'Período de espera de alertas activo, omitiendo notificaciones',
    'email_verification_alert_email_sent' => 'Email de alerta enviado a :email',
    'email_verification_alert_email_failed' => 'Error al enviar email de alerta: :error',
    'email_verification_dashboard_all_bypasses' => 'Todas las omisiones',
    'email_verification_dashboard_no_bypasses' => 'Sin omisiones',
    'email_verification_dashboard_system_normal' => 'Sistema funcionando normalmente',
    'email_verification_dashboard_unusual_activity' => 'Actividad inusual detectada',
    'email_verification_dashboard_no_recent_activity' => 'Sin actividad reciente',
    'email_verification_dashboard_high_bypass_volume' => 'Alto volumen de omisiones',
    'email_verification_dashboard_title' => 'Métricas de Verificación de Email',
    'email_verification_dashboard_last_hours' => 'Últimas :hours horas',
    'email_verification_dashboard_existing_user_bypasses' => 'Omisiones de Usuarios Existentes',
    'email_verification_dashboard_restrictions_enforced' => 'Restricciones Aplicadas',
    'email_verification_dashboard_bypass_ratio' => 'Proporción de Omisiones',
    'email_verification_dashboard_unusual_activity_alerts' => 'Alertas de Actividad Inusual',
    'email_verification_dashboard_bypass_patterns' => 'Patrones de Omisión',
    'email_verification_dashboard_by_user_role' => 'Por Rol de Usuario',
    'email_verification_dashboard_by_restriction_type' => 'Por Tipo de Restricción',
    'email_verification_dashboard_top_bypass_domains' => 'Principales Dominios de Omisión',
    'email_verification_dashboard_restriction_enforcement' => 'Aplicación de Restricciones',
    'email_verification_dashboard_top_blocked_domains' => 'Principales Dominios Bloqueados',
    'email_verification_dashboard_activity_timeline' => 'Cronología de Actividad (Últimas :hours horas)',
    'email_verification_dashboard_bypasses' => 'Omisiones',
    'email_verification_dashboard_restrictions' => 'Restricciones',
    'email_verification_dashboard_last_updated' => 'Última actualización',
    'email_verification_dashboard_refresh' => 'Actualizar',
    'email_verification_dashboard_count' => 'Cantidad',

    // Domain Rules Cache Service Messages
    'domain_rules_cache_failed' => 'Error al recuperar las reglas de acceso de dominio desde la caché',
    'domain_rules_cache_cleared' => 'La caché de reglas de acceso de dominio ha sido limpiada',
    'domain_rules_cache_warmed' => 'La caché de reglas de acceso de dominio ha sido precargada',
    'domain_rules_not_configured' => 'No hay reglas de acceso de dominio configuradas - usando configuración predeterminada',
    'domain_rules_email_check_completed' => 'Validación de dominio de correo electrónico completada',
    'domain_rules_cache_statistics' => 'Estadísticas de Caché de Reglas de Dominio',
    'domain_rules_cache_performance' => 'Rendimiento de Caché',
    'domain_rules_query_performance' => 'Rendimiento de Consulta de Base de Datos',

    // Cache Statistics Labels
    'cache_hit' => 'Acierto de Caché',
    'cache_miss' => 'Fallo de Caché',
    'cache_key' => 'Clave de Caché',
    'cache_ttl' => 'TTL de Caché (segundos)',
    'rules_loaded' => 'Reglas Cargadas',
    'rules_mode' => 'Modo de Reglas',
    'rules_count' => 'Número de Reglas',
    'query_time' => 'Tiempo de Consulta (ms)',
    'total_time' => 'Tiempo Total (ms)',
    'warm_up_time' => 'Tiempo de Precarga (ms)',

    // Domain Rules Cache Command Messages
    'domain_rules_cache_command_invalid_action' => 'Acción inválida. Use: stats, clear, o warm',
    'domain_rules_cache_command_stats_title' => 'Estadísticas de Caché de Reglas de Dominio',
    'domain_rules_cache_command_property' => 'Propiedad',
    'domain_rules_cache_command_value' => 'Valor',
    'domain_rules_cache_command_yes' => 'Sí',
    'domain_rules_cache_command_no' => 'No',
    'domain_rules_cache_command_seconds' => 'segundos',

    // Upload Progress Overlay
    'upload_progress_title' => 'Subiendo Archivos',
    'upload_progress_preparing' => 'Preparando subida...',
    'upload_progress_overall' => 'Progreso General',
    'upload_progress_cancel_button' => 'Cancelar Subida',
    'upload_progress_cancel_confirm' => '¿Estás seguro de que quieres cancelar la subida?',
    
    // Upload Progress Status Messages (for JavaScript)
    'upload_status_processing' => 'Procesando subidas...',
    'upload_status_uploading_files' => 'Subiendo :remaining de :total archivos...',
    'upload_status_upload_completed_with_errors' => 'Subida completada con :count error|Subida completada con :count errores',
    'upload_button_uploading' => 'Subiendo Archivos...',

    // Google Drive OAuth Callback Messages
    'google_drive_connected_success' => '¡Conectado exitosamente a Google Drive!',
    'google_drive_pending_uploads_queued' => ':count subidas pendientes han sido puestas en cola para reintentar.',
    'google_drive_connection_failed' => 'Error al conectar con Google Drive',
    'google_drive_connection_management' => 'Gestión de Conexión de Google Drive',
    'google_drive_auth_code_expired' => 'El código de autorización ha expirado. Por favor, intente conectarse nuevamente.',
    'google_drive_access_denied' => 'Se denegó el acceso. Por favor, otorgue los permisos requeridos para conectar Google Drive.',
    'google_drive_invalid_configuration' => 'Configuración de Google Drive inválida. Por favor, contacte a su administrador.',
    'google_drive_authorization_failed' => 'Autorización fallida: :error',

    // Authentication Messages
    'auth_2fa_verification_required' => 'Por favor, verifique su código de autenticación de dos factores.',
    'auth_invalid_login_link' => 'Enlace de inicio de sesión inválido.',
    'auth_login_successful' => 'Sesión iniciada exitosamente.',

    // Navigation & Email Validation
    'nav_email_label' => 'Dirección de Correo Electrónico',
    'nav_email_placeholder' => 'Ingresa tu dirección de correo electrónico',
    'nav_validate_email_button' => 'Validar Correo',
    'nav_validate_email_sending' => 'Enviando...',
    'nav_validation_success' => 'Recibirás un correo electrónico con un enlace para validar tu dirección de correo. Al hacer clic en el enlace que te enviamos, podrás subir archivos a :company_name.',
    'nav_validation_error' => 'Hubo un error procesando tu solicitud. Por favor intenta de nuevo.',
    'nav_logo_alt' => 'Logo de :company_name',
    'email_validation_title' => 'Subir archivos a :company_name',
    'email_validation_subtitle' => 'Comienza validando tu dirección de correo electrónico.',
    'already_have_account' => '¿Ya tienes una cuenta?',
    'sign_in' => 'Iniciar Sesión',

    // Google Drive Chunked Upload Service Messages
    'chunked_upload_local_file_not_found' => 'Archivo local no encontrado: :path',
    'chunked_upload_could_not_open_file' => 'No se pudo abrir el archivo para lectura: :path',
    'chunked_upload_failed_to_read_chunk' => 'Error al leer el fragmento del archivo',
    'chunked_upload_no_file_object_returned' => 'Subida completada pero no se devolvió objeto de archivo',
    'chunked_upload_starting' => 'Iniciando subida fragmentada a Google Drive',
    'chunked_upload_chunk_uploaded' => 'Fragmento subido a Google Drive',
    'chunked_upload_completed_successfully' => 'Subida fragmentada a Google Drive completada exitosamente',
    'chunked_upload_failed' => 'Subida fragmentada a Google Drive falló',
    'chunked_upload_optimal_chunk_size_determined' => 'Tamaño óptimo de fragmento determinado',
    'chunked_upload_decision_made' => 'Decisión de subida fragmentada tomada',

    // S3 Multipart Upload Messages
    's3_multipart_upload_configured' => 'Subida multiparte S3 configurada',
    's3_multipart_upload_starting' => 'Iniciando subida multiparte S3',
    's3_multipart_upload_part_uploaded' => 'Parte subida exitosamente',
    's3_multipart_upload_completed' => 'Subida multiparte S3 completada exitosamente',
    's3_multipart_upload_failed' => 'Subida multiparte S3 falló',
    's3_multipart_upload_aborted' => 'Subida multiparte abortada debido a un error',
    's3_multipart_abort_failed' => 'Error al abortar la subida multiparte',
    's3_upload_optimization_applied' => 'Optimizaciones de subida S3 aplicadas',
    's3_failed_to_open_file' => 'Error al abrir el archivo: :path',

    // S3 Configuration Management Messages
    's3_configuration_saved' => 'Configuración de S3 guardada exitosamente',
    's3_configuration_saved_and_verified' => 'Configuración de S3 guardada y conexión verificada exitosamente',
    's3_configuration_saved_but_connection_failed' => 'Configuración de S3 guardada pero la conexión falló: :error',
    's3_configuration_saved_but_health_check_failed' => 'Configuración de S3 guardada pero la verificación de salud falló. Por favor verifique sus credenciales y acceso al bucket.',
    's3_configuration_save_failed' => 'Error al guardar la configuración de S3',
    's3_configuration_deleted' => 'Configuración de S3 eliminada exitosamente',
    's3_configuration_delete_failed' => 'Error al eliminar la configuración de S3: :error',
    's3_configuration_validation_error' => 'Error de validación: :error',
    's3_configuration_value_updated' => 'Valor de configuración de S3 \':key\' actualizado exitosamente',
    's3_configuration_update_failed' => 'Error al actualizar la configuración de S3',
    
    // Amazon S3 Disconnect Messages
    's3_disconnected_successfully' => 'Amazon S3 desconectado exitosamente.',
    's3_disconnect_failed' => 'Error al desconectar Amazon S3. Por favor intente nuevamente.',

    // Amazon S3 Configuration UI
    'save_configuration' => 'Guardar Configuración',
    's3_configure_aws_credentials' => 'Configurar credenciales de AWS para almacenamiento S3 a nivel del sistema',
    's3_aws_access_key_id' => 'ID de Clave de Acceso de AWS',
    's3_aws_secret_access_key' => 'Clave de Acceso Secreta de AWS',
    's3_aws_region' => 'Región de AWS',
    's3_bucket_name' => 'Nombre del Bucket de S3',
    's3_custom_endpoint' => 'Endpoint Personalizado (Opcional)',
    's3_select_region' => 'Selecciona una región',
    's3_access_key_format' => 'Debe ser exactamente 20 caracteres alfanuméricos en mayúsculas',
    's3_secret_key_format' => 'Debe ser exactamente 40 caracteres. Dejar en blanco para mantener la clave secreta existente.',
    's3_region_help' => 'Selecciona la región de AWS donde se encuentra tu bucket de S3',
    's3_bucket_name_format' => 'El nombre del bucket debe tener 3-63 caracteres, solo letras minúsculas, números, guiones y puntos',
    's3_custom_endpoint_help' => 'Para servicios compatibles con S3 como Cloudflare R2, Backblaze B2 o MinIO. Dejar en blanco para AWS S3 estándar.',
    // Amazon S3 Configuration Messages
    's3_configuration_title' => 'Amazon S3',
    's3_configuration_description' => 'Configurar credenciales de AWS para almacenamiento S3 en todo el sistema',
    's3_disconnect_confirmation' => '¿Estás seguro de que quieres desconectar Amazon S3? Esto eliminará todas las credenciales almacenadas.',
    's3_disconnected_successfully' => 'Amazon S3 desconectado exitosamente.',
    's3_disconnect_failed' => 'Error al desconectar Amazon S3. Por favor, inténtalo de nuevo.',
    
    // S3 Form Labels
    's3_access_key_id_label' => 'ID de Clave de Acceso de AWS',
    's3_access_key_id_hint' => 'Debe ser exactamente 20 caracteres alfanuméricos en mayúsculas',
    's3_secret_access_key_label' => 'Clave de Acceso Secreta de AWS',
    's3_secret_access_key_hint' => 'Debe ser exactamente 40 caracteres. Déjalo en blanco para mantener la clave secreta existente.',
    's3_region_label' => 'Región de AWS',
    's3_region_hint' => 'Selecciona la región de AWS donde se encuentra tu bucket S3',
    's3_region_select_prompt' => 'Selecciona una región',
    's3_bucket_name_label' => 'Nombre del Bucket S3',
    's3_bucket_name_hint' => 'El nombre del bucket debe tener 3-63 caracteres, letras minúsculas, números, guiones y puntos solamente',
    's3_endpoint_label' => 'Endpoint Personalizado (Opcional)',
    's3_endpoint_hint' => 'Para servicios compatibles con S3 como Cloudflare R2, Backblaze B2 o MinIO. Déjalo en blanco para AWS S3 estándar.',
    
    // S3 Connection Testing
    's3_test_connection' => 'Probar Conexión',
    's3_testing' => 'Probando...',
    's3_testing_connection' => 'Probando...',
    's3_connection_successful' => '¡Conexión exitosa!',
    's3_connection_test_successful' => '¡Conexión exitosa!',
    's3_connection_test_failed' => 'Falló la prueba de conexión. Por favor, verifica tus credenciales e inténtalo de nuevo.',
    's3_saving' => 'Guardando...',
    
    // S3 Configuration Actions
    's3_save_configuration' => 'Guardar Configuración',
    's3_saving_configuration' => 'Guardando...',
    's3_configuration_saved_and_verified' => 'Configuración S3 guardada y conexión verificada exitosamente.',
    's3_configuration_saved_but_connection_failed' => 'Configuración S3 guardada pero la conexión falló: :error',
    's3_configuration_saved_but_health_check_failed' => 'Configuración S3 guardada pero la verificación de salud falló. Por favor, verifica tu configuración.',
    's3_configuration_save_failed' => 'Error al guardar la configuración S3. Por favor, inténtalo de nuevo.',
    's3_configuration_update_failed' => 'Error al actualizar la configuración S3. Por favor, inténtalo de nuevo.',
    
    // S3 Validation Messages
    's3_access_key_required' => 'Se requiere el ID de Clave de Acceso',
    's3_access_key_length' => 'El ID de Clave de Acceso debe tener exactamente 20 caracteres',
    's3_access_key_format_invalid' => 'El ID de Clave de Acceso debe contener solo letras mayúsculas y números',
    's3_access_key_id_format_invalid' => 'El ID de Clave de Acceso debe ser exactamente 20 caracteres alfanuméricos en mayúsculas',
    's3_secret_key_required' => 'Se requiere la Clave de Acceso Secreta',
    's3_secret_key_length' => 'La Clave de Acceso Secreta debe tener exactamente 40 caracteres',
    's3_secret_access_key_length_invalid' => 'La Clave de Acceso Secreta debe tener exactamente 40 caracteres',
    's3_region_required' => 'Se requiere la región',
    's3_region_format_invalid' => 'Formato de región inválido',
    's3_bucket_required' => 'Se requiere el nombre del bucket',
    's3_bucket_length' => 'El nombre del bucket debe tener entre 3 y 63 caracteres',
    's3_bucket_format_invalid' => 'El nombre del bucket debe comenzar y terminar con una letra o número, y contener solo letras minúsculas, números, guiones y puntos',
    's3_bucket_name_format_invalid' => 'El nombre del bucket debe seguir las convenciones de nomenclatura de S3 (3-63 caracteres, letras minúsculas, números, guiones y puntos)',
    's3_endpoint_url_invalid' => 'El endpoint personalizado debe ser una URL válida',
    's3_bucket_consecutive_periods' => 'El nombre del bucket no puede contener puntos consecutivos',
    's3_bucket_ip_format' => 'El nombre del bucket no puede tener formato de dirección IP',
    's3_endpoint_format_invalid' => 'El endpoint debe ser una URL válida que comience con http:// o https://',
    's3_connection_test_successful' => '¡Prueba de conexión S3 exitosa! Tus credenciales son válidas y el bucket es accesible.',
    's3_connection_test_failed' => 'Error al probar la conexión. Por favor, inténtalo de nuevo.',
    's3_us_east_virginia' => 'EE.UU. Este (N. Virginia)',
    's3_us_east_ohio' => 'EE.UU. Este (Ohio)',
    's3_us_west_california' => 'EE.UU. Oeste (N. California)',
    's3_us_west_oregon' => 'EE.UU. Oeste (Oregón)',
    's3_canada_central' => 'Canadá (Central)',
    's3_eu_ireland' => 'UE (Irlanda)',
    's3_eu_london' => 'UE (Londres)',
    's3_eu_paris' => 'UE (París)',
    's3_eu_frankfurt' => 'UE (Fráncfort)',
    's3_eu_stockholm' => 'UE (Estocolmo)',
    's3_asia_mumbai' => 'Asia Pacífico (Bombay)',
    's3_asia_tokyo' => 'Asia Pacífico (Tokio)',
    's3_asia_seoul' => 'Asia Pacífico (Seúl)',
    's3_asia_osaka' => 'Asia Pacífico (Osaka)',
    's3_asia_singapore' => 'Asia Pacífico (Singapur)',
    's3_asia_sydney' => 'Asia Pacífico (Sídney)',
    's3_south_america_sao_paulo' => 'Sudamérica (São Paulo)',

    // Welcome Message Dismissal
    'welcome_message_unauthorized' => 'Acción no autorizada.',
    'welcome_message_dismissed_success' => 'Mensaje de bienvenida descartado exitosamente.',
    'welcome_message_dismiss_failed' => 'Error al descartar el mensaje. Por favor, inténtalo de nuevo.',

    // File Manager Service Messages
    'file_manager_no_user_for_s3_delete' => 'No hay usuario disponible para eliminar el archivo de S3',
    'file_manager_no_google_drive_connection_for_delete' => 'No hay conexión de Google Drive disponible para eliminar archivos. Asegúrese de que un administrador haya conectado su cuenta de Google Drive.',
    'file_manager_no_files_for_download' => 'No se encontraron archivos para descargar.',
    'file_manager_zip_creation_failed' => 'No se puede crear el archivo ZIP: :error',
    'file_manager_no_files_added_to_zip' => 'No se pudieron agregar archivos al archivo. Todos los archivos pueden estar almacenados en el almacenamiento en la nube o ser inaccesibles.',
];