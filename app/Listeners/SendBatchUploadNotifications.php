<?php

namespace App\Listeners;

use App\Events\BatchUploadComplete;
use App\Mail\AdminBatchUploadNotification; // To be created
use App\Mail\ClientBatchUploadConfirmation; // To be created
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendBatchUploadNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param BatchUploadComplete $event
     * @return void
     */
    public function handle(BatchUploadComplete $event): void
    {
        Log::info('SendBatchUploadNotifications: Starting batch upload notification processing', [
            'event_user_id' => $event->userId,
            'file_upload_ids' => $event->fileUploadIds,
            'file_count' => count($event->fileUploadIds),
            'timestamp' => now()->toISOString()
        ]);

        $user = User::find($event->userId);
        $fileUploads = FileUpload::whereIn('id', $event->fileUploadIds)->get();

        if (!$user) {
            Log::error('SendBatchUploadNotifications: Failed to find user for batch notification', [
                'user_id' => $event->userId,
                'file_upload_ids' => $event->fileUploadIds,
                'error_type' => 'user_not_found'
            ]);
            return;
        }

        if ($fileUploads->isEmpty()) {
            Log::error('SendBatchUploadNotifications: No file uploads found for batch notification', [
                'user_id' => $event->userId,
                'file_upload_ids' => $event->fileUploadIds,
                'error_type' => 'uploads_not_found'
            ]);
            return;
        }

        Log::info('SendBatchUploadNotifications: Successfully loaded user and file uploads', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role->value ?? 'unknown',
            'loaded_uploads_count' => $fileUploads->count(),
            'expected_uploads_count' => count($event->fileUploadIds)
        ]);

        // Determine intended recipient users (employee/admin) from the batch (unique IDs)
        Log::info('SendBatchUploadNotifications: Starting recipient determination process', [
            'total_uploads' => $fileUploads->count()
        ]);

        $recipientUserIds = [];
        $recipientSelectionLog = [];
        $failedUploads = [];
        
        foreach ($fileUploads as $upload) {
            try {
                $uploadContext = $this->analyzeUploadContext($upload);
                $this->logUploadContextAnalysis($upload, $uploadContext);

                $candidateId = $this->determineRecipientForUpload($upload);
                
                if ($candidateId) {
                    $recipientUserIds[$candidateId] = true; // use map to ensure uniqueness
                    $recipient = User::find($candidateId);
                    
                    $recipientSelectionLog[] = [
                        'upload_id' => $upload->id,
                        'filename' => $upload->filename,
                        'recipient_id' => $candidateId,
                        'recipient_email' => $recipient->email ?? 'unknown',
                        'recipient_name' => $recipient->name ?? 'unknown',
                        'selection_reason' => $this->getRecipientSelectionReason($upload, $candidateId),
                        'upload_context' => $uploadContext
                    ];
                } else {
                    $recipientSelectionLog[] = [
                        'upload_id' => $upload->id,
                        'filename' => $upload->filename,
                        'recipient_id' => null,
                        'error' => 'no_valid_recipient_found',
                        'upload_context' => $uploadContext
                    ];

                    $failedUploads[] = $upload->id;
                    $this->logRecipientDeterminationFailure($upload, $uploadContext);
                }
            } catch (\Exception $e) {
                // Handle any unexpected errors during recipient determination
                $failedUploads[] = $upload->id;
                $this->logRecipientDeterminationException($upload, $e);

                $recipientSelectionLog[] = [
                    'upload_id' => $upload->id,
                    'filename' => $upload->filename ?? 'unknown',
                    'recipient_id' => null,
                    'error' => 'recipient_determination_exception',
                    'error_message' => $e->getMessage(),
                    'upload_context' => ['error' => 'failed_to_analyze']
                ];

                // Continue processing other uploads despite this failure
                continue;
            }
        }
        
        $recipientUserIds = array_keys($recipientUserIds);
        
        Log::info('SendBatchUploadNotifications: Completed recipient determination process', [
            'total_uploads_processed' => $fileUploads->count(),
            'unique_recipients_found' => count($recipientUserIds),
            'failed_uploads_count' => count($failedUploads),
            'recipient_ids' => $recipientUserIds,
            'failed_upload_ids' => $failedUploads,
            'recipient_selection_summary' => $recipientSelectionLog
        ]);

        // If no recipients found at all, log critical error but continue with client confirmation
        if (empty($recipientUserIds)) {
            Log::error('SendBatchUploadNotifications: Critical error - no valid recipients found for any uploads', [
                'total_uploads' => $fileUploads->count(),
                'failed_uploads' => $failedUploads,
                'error_type' => 'no_recipients_found',
                'impact' => 'no_admin_notifications_will_be_sent'
            ]);
        }

        // Send per-recipient notification using Eloquent collections
        Log::info('SendBatchUploadNotifications: Starting recipient notification process', [
            'recipients_to_notify' => count($recipientUserIds)
        ]);

        $notificationResults = [];
        
        foreach ($recipientUserIds as $recipient_user_id) {
            try {
                $recipient = User::find($recipient_user_id);
                
                if (!$recipient) {
                    Log::error('SendBatchUploadNotifications: Recipient user not found', [
                        'recipient_user_id' => $recipient_user_id,
                        'error_type' => 'recipient_user_not_found'
                    ]);
                    $notificationResults[] = [
                        'recipient_id' => $recipient_user_id,
                        'status' => 'failed',
                        'error' => 'recipient_user_not_found'
                    ];
                    continue;
                }

                if (!$recipient->email) {
                    Log::error('SendBatchUploadNotifications: Recipient has no email address', [
                        'recipient_user_id' => $recipient_user_id,
                        'recipient_name' => $recipient->name,
                        'error_type' => 'recipient_no_email'
                    ]);
                    $notificationResults[] = [
                        'recipient_id' => $recipient_user_id,
                        'recipient_name' => $recipient->name,
                        'status' => 'failed',
                        'error' => 'recipient_no_email'
                    ];
                    continue;
                }

                // Fetch uploads for this recipient using context-aware logic
                $uploadsForRecipient = $this->getUploadsForRecipient($event->fileUploadIds, $recipient_user_id);
                
                if ($uploadsForRecipient->isEmpty()) {
                    Log::warning('SendBatchUploadNotifications: No uploads found for recipient', [
                        'recipient_user_id' => $recipient_user_id,
                        'recipient_email' => $recipient->email,
                        'error_type' => 'no_uploads_for_recipient'
                    ]);
                    $notificationResults[] = [
                        'recipient_id' => $recipient_user_id,
                        'recipient_email' => $recipient->email,
                        'status' => 'skipped',
                        'reason' => 'no_uploads_for_recipient'
                    ];
                    continue;
                }

                try {
                    $uploadFilenames = $uploadsForRecipient->pluck('filename')->toArray();
                    
                    Log::info('SendBatchUploadNotifications: Attempting to send notification to recipient', [
                        'recipient_user_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'recipient_name' => $recipient->name,
                        'recipient_role' => $recipient->role->value ?? 'unknown',
                        'file_count' => $uploadsForRecipient->count(),
                        'filenames' => $uploadFilenames,
                        'uploader_user_id' => $user->id,
                        'uploader_email' => $user->email
                    ]);

                    Mail::to($recipient->email)->send(new AdminBatchUploadNotification($uploadsForRecipient, $user));
                    
                    Log::info('SendBatchUploadNotifications: Successfully sent notification to recipient', [
                        'recipient_user_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'file_count' => $uploadsForRecipient->count(),
                        'notification_type' => 'admin_batch_upload_notification'
                    ]);

                    $notificationResults[] = [
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'file_count' => $uploadsForRecipient->count(),
                        'status' => 'success'
                    ];

                } catch (\Exception $e) {
                    Log::error('SendBatchUploadNotifications: Failed to send notification to recipient', [
                        'recipient_user_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'error_type' => 'email_send_failure',
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_trace' => $e->getTraceAsString(),
                        'file_count' => $uploadsForRecipient->count()
                    ]);

                    $notificationResults[] = [
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'status' => 'failed',
                        'error' => 'email_send_failure',
                        'error_message' => $e->getMessage()
                    ];

                    // Continue processing other recipients despite this failure
                    continue;
                }

            } catch (\Exception $e) {
                // Handle any unexpected errors during recipient processing
                Log::error('SendBatchUploadNotifications: Exception during recipient processing', [
                    'recipient_user_id' => $recipient_user_id,
                    'error_type' => 'recipient_processing_exception',
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'error_trace' => $e->getTraceAsString()
                ]);

                $notificationResults[] = [
                    'recipient_id' => $recipient_user_id,
                    'status' => 'failed',
                    'error' => 'recipient_processing_exception',
                    'error_message' => $e->getMessage()
                ];

                // Continue processing other recipients despite this failure
                continue;
            }
        }

        Log::info('SendBatchUploadNotifications: Completed recipient notification process', [
            'total_recipients_processed' => count($recipientUserIds),
            'notification_results' => $notificationResults,
            'successful_notifications' => count(array_filter($notificationResults, fn($r) => $r['status'] === 'success')),
            'failed_notifications' => count(array_filter($notificationResults, fn($r) => $r['status'] === 'failed')),
            'skipped_notifications' => count(array_filter($notificationResults, fn($r) => $r['status'] === 'skipped'))
        ]);

        // --- Send Client Confirmation ---
        Log::info('SendBatchUploadNotifications: Starting client confirmation process', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'receive_notifications_enabled' => $user->receive_upload_notifications,
            'has_email' => !empty($user->email)
        ]);

        if (!$user->receive_upload_notifications) {
            Log::info('SendBatchUploadNotifications: Client confirmation skipped - notifications disabled', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'reason' => 'notifications_disabled_by_user'
            ]);
        } elseif (!$user->email) {
            Log::error('SendBatchUploadNotifications: Client confirmation failed - no email address', [
                'user_id' => $user->id,
                'error_type' => 'client_no_email'
            ]);
        } else {
            try {
                Log::info('SendBatchUploadNotifications: Attempting to send client confirmation', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'file_count' => $fileUploads->count(),
                    'recipient_count' => count($recipientUserIds)
                ]);

                $unsubscribe_url = URL::temporarySignedRoute(
                    'notifications.upload.unsubscribe',
                    now()->addDays(30),
                    ['user' => $user->id]
                );

                // Build recipient names for client visibility
                $recipient_names = collect($recipientUserIds)
                    ->map(fn ($id) => optional(User::find($id))->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                Log::info('SendBatchUploadNotifications: Client confirmation recipient details', [
                    'recipient_names' => $recipient_names,
                    'unsubscribe_url_generated' => !empty($unsubscribe_url)
                ]);

                $mailable = new ClientBatchUploadConfirmation($fileUploads, $unsubscribe_url);
                $mailable->recipientNames = $recipient_names;
                Mail::to($user->email)->send($mailable);
                
                Log::info('SendBatchUploadNotifications: Successfully sent client confirmation', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'file_count' => $fileUploads->count(),
                    'recipient_names' => $recipient_names,
                    'notification_type' => 'client_batch_upload_confirmation'
                ]);

            } catch (\Exception $e) {
                Log::error('SendBatchUploadNotifications: Failed to send client confirmation', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'error_type' => 'client_confirmation_send_failure',
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file_count' => $fileUploads->count()
                ]);
            }
        }

        Log::info('SendBatchUploadNotifications: Batch upload notification processing completed', [
            'total_processing_time' => 'completed',
            'user_id' => $user->id,
            'total_files_processed' => $fileUploads->count(),
            'unique_recipients_notified' => count($recipientUserIds),
            'client_confirmation_attempted' => $user->receive_upload_notifications && $user->email
        ]);
    }

    /**
     * Determine if an upload is a client upload.
     *
     * @param FileUpload $upload
     * @return bool
     */
    private function isClientUpload(FileUpload $upload): bool
    {
        return !empty($upload->client_user_id);
    }

    /**
     * Determine if an upload is an employee/admin upload.
     *
     * @param FileUpload $upload
     * @return bool
     */
    private function isEmployeeAdminUpload(FileUpload $upload): bool
    {
        return !empty($upload->uploaded_by_user_id) && empty($upload->client_user_id);
    }

    /**
     * Get the upload type as a string for logging purposes.
     *
     * @param FileUpload $upload
     * @return string
     */
    private function getUploadType(FileUpload $upload): string
    {
        if ($this->isClientUpload($upload)) {
            return 'client_upload';
        } elseif ($this->isEmployeeAdminUpload($upload)) {
            return 'employee_admin_upload';
        }
        
        return 'unknown_upload_type';
    }

    /**
     * Analyze the context of an upload to understand its type and source.
     *
     * @param FileUpload $upload
     * @return array
     */
    private function analyzeUploadContext(FileUpload $upload): array
    {
        $uploadType = $this->getUploadType($upload);
        
        $context = [
            'upload_type' => $uploadType,
            'has_client_user' => !empty($upload->client_user_id),
            'has_company_user' => !empty($upload->company_user_id),
            'has_uploaded_by_user' => !empty($upload->uploaded_by_user_id),
            'client_user_id' => $upload->client_user_id,
            'company_user_id' => $upload->company_user_id,
            'uploaded_by_user_id' => $upload->uploaded_by_user_id
        ];

        if ($uploadType === 'client_upload') {
            $context['description'] = 'Upload initiated by client user';
        } elseif ($uploadType === 'employee_admin_upload') {
            $context['description'] = 'Upload initiated by employee or admin user';
        } else {
            $context['description'] = 'Upload type could not be determined';
        }

        return $context;
    }

    /**
     * Log the start of recipient determination process.
     *
     * @param FileUpload $upload
     * @return void
     */
    private function logRecipientDeterminationStart(FileUpload $upload): void
    {
        Log::debug('SendBatchUploadNotifications: Starting recipient determination', [
            'upload_id' => $upload->id,
            'upload_type' => $this->getUploadType($upload),
            'client_user_id' => $upload->client_user_id,
            'company_user_id' => $upload->company_user_id,
            'uploaded_by_user_id' => $upload->uploaded_by_user_id
        ]);
    }

    /**
     * Log recipient selection debug information.
     *
     * @param FileUpload $upload
     * @param int|null $candidateId
     * @param string $context
     * @return void
     */
    private function logRecipientSelectionDebug(FileUpload $upload, ?int $candidateId, string $context): void
    {
        Log::debug('SendBatchUploadNotifications: Recipient selection debug', [
            'upload_id' => $upload->id,
            'context' => $context,
            'candidate_id' => $candidateId,
            'upload_type' => $this->getUploadType($upload)
        ]);
    }

    /**
     * Log successful recipient selection.
     *
     * @param FileUpload $upload
     * @param int $recipientId
     * @param string $reason
     * @return void
     */
    private function logRecipientSelection(FileUpload $upload, int $recipientId, string $reason): void
    {
        $recipient = User::find($recipientId);
        
        Log::info('SendBatchUploadNotifications: Recipient selected successfully', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename,
            'recipient_id' => $recipientId,
            'recipient_email' => $recipient->email ?? 'unknown',
            'recipient_name' => $recipient->name ?? 'unknown',
            'selection_reason' => $reason,
            'upload_type' => $this->getUploadType($upload)
        ]);
    }

    /**
     * Log recipient selection warning.
     *
     * @param FileUpload $upload
     * @param int|null $candidateId
     * @param string $reason
     * @return void
     */
    private function logRecipientSelectionWarning(FileUpload $upload, ?int $candidateId, string $reason): void
    {
        Log::warning('SendBatchUploadNotifications: Recipient selection warning', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename,
            'candidate_id' => $candidateId,
            'warning_reason' => $reason,
            'upload_type' => $this->getUploadType($upload),
            'fallback_action' => 'will_attempt_fallback_logic'
        ]);
    }

    /**
     * Log recipient selection error.
     *
     * @param FileUpload $upload
     * @param string $errorType
     * @param string $errorMessage
     * @return void
     */
    private function logRecipientSelectionError(FileUpload $upload, string $errorType, string $errorMessage): void
    {
        Log::error('SendBatchUploadNotifications: Recipient selection error', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename ?? 'unknown',
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'upload_type' => $this->getUploadType($upload),
            'impact' => 'upload_may_not_generate_notification'
        ]);
    }

    /**
     * Log recipient selection exception.
     *
     * @param FileUpload $upload
     * @param \Exception $exception
     * @return void
     */
    private function logRecipientSelectionException(FileUpload $upload, \Exception $exception): void
    {
        Log::error('SendBatchUploadNotifications: Exception during recipient determination', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename ?? 'unknown',
            'error_type' => 'recipient_determination_exception',
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'upload_type' => $this->getUploadType($upload),
            'fallback_action' => 'attempting_admin_fallback'
        ]);
    }

    /**
     * Log critical recipient selection failure.
     *
     * @param FileUpload $upload
     * @param \Exception $originalException
     * @param \Exception $fallbackException
     * @return void
     */
    private function logRecipientSelectionCriticalError(FileUpload $upload, \Exception $originalException, \Exception $fallbackException): void
    {
        Log::error('SendBatchUploadNotifications: Critical recipient selection failure', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename ?? 'unknown',
            'original_error' => $originalException->getMessage(),
            'fallback_error' => $fallbackException->getMessage(),
            'error_type' => 'complete_recipient_determination_failure',
            'upload_type' => $this->getUploadType($upload),
            'impact' => 'upload_will_not_generate_notification'
        ]);
    }

    /**
     * Log upload context analysis.
     *
     * @param FileUpload $upload
     * @param array $uploadContext
     * @return void
     */
    private function logUploadContextAnalysis(FileUpload $upload, array $uploadContext): void
    {
        Log::info('SendBatchUploadNotifications: Upload context analyzed', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename,
            'upload_context' => $uploadContext
        ]);
    }

    /**
     * Log recipient determination failure.
     *
     * @param FileUpload $upload
     * @param array $uploadContext
     * @return void
     */
    private function logRecipientDeterminationFailure(FileUpload $upload, array $uploadContext): void
    {
        Log::warning('SendBatchUploadNotifications: Failed to determine valid recipient for upload', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename,
            'upload_context' => $uploadContext,
            'error_type' => 'recipient_determination_failed',
            'available_data' => [
                'client_user_id' => $upload->client_user_id,
                'company_user_id' => $upload->company_user_id,
                'uploaded_by_user_id' => $upload->uploaded_by_user_id
            ]
        ]);
    }

    /**
     * Log recipient determination exception in main loop.
     *
     * @param FileUpload $upload
     * @param \Exception $exception
     * @return void
     */
    private function logRecipientDeterminationException(FileUpload $upload, \Exception $exception): void
    {
        Log::error('SendBatchUploadNotifications: Exception during recipient determination in main loop', [
            'upload_id' => $upload->id,
            'filename' => $upload->filename ?? 'unknown',
            'error_type' => 'recipient_determination_exception',
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'available_data' => [
                'client_user_id' => $upload->client_user_id ?? null,
                'company_user_id' => $upload->company_user_id ?? null,
                'uploaded_by_user_id' => $upload->uploaded_by_user_id ?? null
            ]
        ]);
    }

    /**
     * Get the reason why a specific recipient was selected for an upload.
     *
     * @param FileUpload $upload
     * @param int $recipientId
     * @return string
     */
    private function getRecipientSelectionReason(FileUpload $upload, int $recipientId): string
    {
        if ($this->isClientUpload($upload)) {
            if ($upload->company_user_id === $recipientId) {
                return 'selected_company_user_for_client_upload';
            } elseif ($recipientId === $this->findPrimaryCompanyUserForClient($upload->client_user_id)) {
                return 'primary_company_user_fallback_for_client';
            } elseif ($recipientId === $this->getFallbackAdminRecipient()) {
                return 'admin_fallback_for_client_upload';
            }
        } elseif ($this->isEmployeeAdminUpload($upload)) {
            if ($upload->uploaded_by_user_id === $recipientId) {
                return 'uploader_notification_for_employee_admin_upload';
            } elseif ($recipientId === $this->getFallbackAdminRecipient()) {
                return 'admin_fallback_for_employee_admin_upload';
            }
        }

        return 'unknown_selection_reason';
    }

    /**
     * Get the primary recipient candidate for a client upload.
     *
     * @param FileUpload $upload
     * @return int|null
     */
    private function getPrimaryRecipientForClientUpload(FileUpload $upload): ?int
    {
        // For client uploads, prioritize the selected company user
        if ($upload->company_user_id) {
            return $upload->company_user_id;
        }

        // If no specific recipient selected, try to find primary company user
        try {
            return $this->findPrimaryCompanyUserForClient($upload->client_user_id);
        } catch (\Exception $e) {
            $this->logRecipientSelectionError($upload, 'primary_company_user_lookup_failed', $e->getMessage());
            return null;
        }
    }

    /**
     * Get the primary recipient candidate for an employee/admin upload.
     *
     * @param FileUpload $upload
     * @return int|null
     */
    private function getPrimaryRecipientForEmployeeAdminUpload(FileUpload $upload): ?int
    {
        // For employee/admin uploads, notify the uploader
        return $upload->uploaded_by_user_id;
    }

    /**
     * Apply fallback logic to find a valid recipient when primary selection fails.
     *
     * @param FileUpload $upload
     * @param int|null $primaryCandidate
     * @return int|null
     */
    private function applyRecipientFallbackLogic(FileUpload $upload, ?int $primaryCandidate): ?int
    {
        // If primary candidate is valid, use it
        if ($primaryCandidate && $this->isValidRecipient($primaryCandidate)) {
            $this->logRecipientSelection($upload, $primaryCandidate, 'primary_candidate_valid');
            return $primaryCandidate;
        }

        // Log why primary candidate failed
        if ($primaryCandidate) {
            $this->logRecipientSelectionWarning($upload, $primaryCandidate, 'primary_candidate_invalid');
        } else {
            $this->logRecipientSelectionWarning($upload, null, 'no_primary_candidate_found');
        }

        // Apply admin fallback
        $adminFallback = $this->getFallbackAdminRecipient();
        
        if ($adminFallback) {
            $this->logRecipientSelection($upload, $adminFallback, 'admin_fallback_applied');
            return $adminFallback;
        }

        $this->logRecipientSelectionError($upload, 'no_fallback_available', 'No admin fallback recipient found');
        return null;
    }

    /**
     * Determine the correct recipient for an upload based on upload context.
     *
     * @param FileUpload $upload
     * @return int|null The recipient user ID, or null if no valid recipient found
     */
    private function determineRecipientForUpload(FileUpload $upload): ?int
    {
        try {
            $this->logRecipientDeterminationStart($upload);

            $candidateId = null;

            if ($this->isClientUpload($upload)) {
                $candidateId = $this->getPrimaryRecipientForClientUpload($upload);
                $this->logRecipientSelectionDebug($upload, $candidateId, 'client_upload_processing');
            } elseif ($this->isEmployeeAdminUpload($upload)) {
                $candidateId = $this->getPrimaryRecipientForEmployeeAdminUpload($upload);
                $this->logRecipientSelectionDebug($upload, $candidateId, 'employee_admin_upload_processing');
            } else {
                $this->logRecipientSelectionWarning($upload, null, 'unknown_upload_type');
            }

            return $this->applyRecipientFallbackLogic($upload, $candidateId);

        } catch (\Exception $e) {
            $this->logRecipientSelectionException($upload, $e);

            // Try to get admin fallback even if there was an exception
            try {
                return $this->getFallbackAdminRecipient();
            } catch (\Exception $fallbackException) {
                $this->logRecipientSelectionCriticalError($upload, $e, $fallbackException);
                return null;
            }
        }
    }

    /**
     * Find the primary company user (employee/admin) for a client.
     *
     * @param int $clientUserId
     * @return int|null
     */
    private function findPrimaryCompanyUserForClient(int $clientUserId): ?int
    {
        try {
            Log::debug('SendBatchUploadNotifications: Finding primary company user for client', [
                'client_user_id' => $clientUserId
            ]);

            // Validate client user ID
            if (!is_numeric($clientUserId) || $clientUserId <= 0) {
                Log::warning('SendBatchUploadNotifications: Invalid client user ID', [
                    'client_user_id' => $clientUserId,
                    'error_type' => 'invalid_client_user_id'
                ]);
                return null;
            }

            // Try to find the primary employee/admin associated with this client
            $clientUser = User::find($clientUserId);
            if (!$clientUser) {
                Log::warning('SendBatchUploadNotifications: Client user not found', [
                    'client_user_id' => $clientUserId,
                    'error_type' => 'client_user_not_found'
                ]);
                return null;
            }

            // Look for primary company user relationship
            try {
                $primaryCompanyUser = $clientUser->primaryCompanyUser();
                if ($primaryCompanyUser) {
                    Log::debug('SendBatchUploadNotifications: Found primary company user', [
                        'client_user_id' => $clientUserId,
                        'primary_company_user_id' => $primaryCompanyUser->id,
                        'primary_company_user_email' => $primaryCompanyUser->email
                    ]);
                    return $primaryCompanyUser->id;
                }
            } catch (\Exception $e) {
                Log::warning('SendBatchUploadNotifications: Error finding primary company user', [
                    'client_user_id' => $clientUserId,
                    'error_message' => $e->getMessage(),
                    'fallback_action' => 'trying_any_company_user'
                ]);
            }

            // Fallback to any company user relationship
            try {
                $companyUser = $clientUser->companyUsers()->first();
                if ($companyUser) {
                    Log::debug('SendBatchUploadNotifications: Using fallback company user', [
                        'client_user_id' => $clientUserId,
                        'fallback_company_user_id' => $companyUser->id,
                        'fallback_company_user_email' => $companyUser->email
                    ]);
                    return $companyUser->id;
                }
            } catch (\Exception $e) {
                Log::warning('SendBatchUploadNotifications: Error finding fallback company user', [
                    'client_user_id' => $clientUserId,
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::warning('SendBatchUploadNotifications: No company user relationship found for client', [
                'client_user_id' => $clientUserId,
                'client_email' => $clientUser->email ?? 'unknown',
                'error_type' => 'no_company_user_relationship'
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('SendBatchUploadNotifications: Exception finding primary company user for client', [
                'client_user_id' => $clientUserId,
                'error_type' => 'primary_company_user_lookup_exception',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * Check if a user ID represents a valid recipient.
     *
     * @param int $userId
     * @return bool
     */
    private function isValidRecipient(int $userId): bool
    {
        try {
            // Validate user ID format
            if (!is_numeric($userId) || $userId <= 0) {
                Log::debug('SendBatchUploadNotifications: Invalid recipient - invalid user ID format', [
                    'user_id' => $userId,
                    'validation_result' => false,
                    'reason' => 'invalid_user_id_format'
                ]);
                return false;
            }

            $user = User::find($userId);
            
            if (!$user) {
                Log::debug('SendBatchUploadNotifications: Invalid recipient - user not found', [
                    'user_id' => $userId,
                    'validation_result' => false,
                    'reason' => 'user_not_found'
                ]);
                return false;
            }

            if (!$user->email) {
                Log::debug('SendBatchUploadNotifications: Invalid recipient - no email address', [
                    'user_id' => $userId,
                    'user_name' => $user->name ?? 'unknown',
                    'validation_result' => false,
                    'reason' => 'no_email'
                ]);
                return false;
            }

            // Validate email format
            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                Log::debug('SendBatchUploadNotifications: Invalid recipient - invalid email format', [
                    'user_id' => $userId,
                    'user_email' => $user->email,
                    'validation_result' => false,
                    'reason' => 'invalid_email_format'
                ]);
                return false;
            }

            try {
                $isValidRole = $user->isEmployee() || $user->isAdmin();
                if (!$isValidRole) {
                    Log::debug('SendBatchUploadNotifications: Invalid recipient - invalid role', [
                        'user_id' => $userId,
                        'user_email' => $user->email,
                        'user_role' => $user->role->value ?? 'unknown',
                        'validation_result' => false,
                        'reason' => 'invalid_role'
                    ]);
                    return false;
                }
            } catch (\Exception $e) {
                Log::warning('SendBatchUploadNotifications: Error checking user role', [
                    'user_id' => $userId,
                    'user_email' => $user->email,
                    'error_message' => $e->getMessage(),
                    'validation_result' => false,
                    'reason' => 'role_check_error'
                ]);
                return false;
            }

            Log::debug('SendBatchUploadNotifications: Valid recipient confirmed', [
                'user_id' => $userId,
                'user_email' => $user->email,
                'user_role' => $user->role->value ?? 'unknown',
                'validation_result' => true
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('SendBatchUploadNotifications: Exception during recipient validation', [
                'user_id' => $userId,
                'error_type' => 'recipient_validation_exception',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'validation_result' => false
            ]);
            return false;
        }
    }

    /**
     * Get the fallback admin recipient.
     *
     * @return int|null
     */
    private function getFallbackAdminRecipient(): ?int
    {
        try {
            Log::debug('SendBatchUploadNotifications: Searching for admin fallback recipient');

            $adminUser = User::where('role', \App\Enums\UserRole::ADMIN)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->first();
                
            if ($adminUser) {
                // Validate the admin user has a proper email
                if (!filter_var($adminUser->email, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('SendBatchUploadNotifications: Admin user has invalid email format', [
                        'admin_user_id' => $adminUser->id,
                        'admin_email' => $adminUser->email,
                        'fallback_action' => 'searching_for_another_admin'
                    ]);
                    
                    // Try to find another admin with valid email
                    $adminUser = User::where('role', \App\Enums\UserRole::ADMIN)
                        ->whereNotNull('email')
                        ->where('email', '!=', '')
                        ->where('id', '!=', $adminUser->id)
                        ->first();
                }

                if ($adminUser && filter_var($adminUser->email, FILTER_VALIDATE_EMAIL)) {
                    Log::info('SendBatchUploadNotifications: Found admin fallback recipient', [
                        'admin_user_id' => $adminUser->id,
                        'admin_email' => $adminUser->email,
                        'admin_name' => $adminUser->name ?? 'unknown',
                        'fallback_reason' => 'no_other_valid_recipient_found'
                    ]);
                    return $adminUser->id;
                }
            }

            // Try to find any employee as fallback if no admin available
            Log::warning('SendBatchUploadNotifications: No valid admin found, trying employee fallback');
            
            $employeeUser = User::where('role', \App\Enums\UserRole::EMPLOYEE)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->first();

            if ($employeeUser && filter_var($employeeUser->email, FILTER_VALIDATE_EMAIL)) {
                Log::info('SendBatchUploadNotifications: Using employee as fallback recipient', [
                    'employee_user_id' => $employeeUser->id,
                    'employee_email' => $employeeUser->email,
                    'employee_name' => $employeeUser->name ?? 'unknown',
                    'fallback_reason' => 'no_valid_admin_found'
                ]);
                return $employeeUser->id;
            }

            Log::error('SendBatchUploadNotifications: Critical error - no valid admin or employee user found for fallback', [
                'error_type' => 'no_fallback_recipient_available',
                'impact' => 'notifications_cannot_be_delivered',
                'admin_count' => User::where('role', \App\Enums\UserRole::ADMIN)->count(),
                'employee_count' => User::where('role', \App\Enums\UserRole::EMPLOYEE)->count()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('SendBatchUploadNotifications: Exception while finding admin fallback recipient', [
                'error_type' => 'admin_fallback_lookup_exception',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'impact' => 'fallback_recipient_unavailable'
            ]);
            return null;
        }
    }

    /**
     * Get uploads that should be sent to a specific recipient.
     *
     * @param array $fileUploadIds
     * @param int $recipientUserId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getUploadsForRecipient(array $fileUploadIds, int $recipientUserId)
    {
        try {
            Log::debug('SendBatchUploadNotifications: Filtering uploads for specific recipient', [
                'recipient_user_id' => $recipientUserId,
                'total_upload_ids' => count($fileUploadIds)
            ]);

            // Validate inputs
            if (empty($fileUploadIds)) {
                Log::warning('SendBatchUploadNotifications: No file upload IDs provided for filtering', [
                    'recipient_user_id' => $recipientUserId
                ]);
                return FileUpload::whereRaw('1 = 0')->get(); // Return empty Eloquent Collection
            }

            if (!is_numeric($recipientUserId) || $recipientUserId <= 0) {
                Log::warning('SendBatchUploadNotifications: Invalid recipient user ID for filtering', [
                    'recipient_user_id' => $recipientUserId,
                    'total_upload_ids' => count($fileUploadIds)
                ]);
                return FileUpload::whereRaw('1 = 0')->get(); // Return empty Eloquent Collection
            }

            $uploads = FileUpload::whereIn('id', $fileUploadIds)->get();
            
            if ($uploads->isEmpty()) {
                Log::warning('SendBatchUploadNotifications: No uploads found for provided IDs', [
                    'recipient_user_id' => $recipientUserId,
                    'file_upload_ids' => $fileUploadIds
                ]);
                return FileUpload::whereRaw('1 = 0')->get(); // Return empty Eloquent Collection
            }

            $filteredUploads = collect();
            $filteringErrors = [];

            foreach ($uploads as $upload) {
                try {
                    $determinedRecipient = $this->determineRecipientForUpload($upload);
                    if ($determinedRecipient === $recipientUserId) {
                        $filteredUploads->push($upload);
                    }
                } catch (\Exception $e) {
                    $filteringErrors[] = [
                        'upload_id' => $upload->id,
                        'error_message' => $e->getMessage()
                    ];
                    
                    Log::warning('SendBatchUploadNotifications: Error determining recipient during filtering', [
                        'upload_id' => $upload->id,
                        'recipient_user_id' => $recipientUserId,
                        'error_message' => $e->getMessage(),
                        'action' => 'excluding_upload_from_results'
                    ]);
                    // Continue processing other uploads
                    continue;
                }
            }

            if (!empty($filteringErrors)) {
                Log::warning('SendBatchUploadNotifications: Some uploads had errors during filtering', [
                    'recipient_user_id' => $recipientUserId,
                    'error_count' => count($filteringErrors),
                    'filtering_errors' => $filteringErrors
                ]);
            }

            Log::debug('SendBatchUploadNotifications: Completed upload filtering for recipient', [
                'recipient_user_id' => $recipientUserId,
                'total_uploads_checked' => $uploads->count(),
                'uploads_for_recipient' => $filteredUploads->count(),
                'filtering_errors' => count($filteringErrors),
                'filtered_upload_ids' => $filteredUploads->pluck('id')->toArray()
            ]);

            // Convert Support Collection to Eloquent Collection
            $eloquentCollection = FileUpload::whereIn('id', $filteredUploads->pluck('id')->toArray())->get();
            
            return $eloquentCollection;

        } catch (\Exception $e) {
            Log::error('SendBatchUploadNotifications: Exception during upload filtering for recipient', [
                'recipient_user_id' => $recipientUserId,
                'total_upload_ids' => count($fileUploadIds ?? []),
                'error_type' => 'upload_filtering_exception',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            
            // Return empty Eloquent collection on error to prevent further issues
            return FileUpload::whereRaw('1 = 0')->get();
        }
    }
}
