<?php

namespace App\Services;

use App\Events\ShareFirstDownloaded;
use App\Events\ShareReady;
use App\Jobs\PurgeShareFiles;
use App\Jobs\UploadShareFileToCloud;
use App\Models\Share;
use App\Models\ShareAccessLog;
use App\Models\ShareFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Creates outgoing file shares and moves them through their lifecycle.
 * See docs/plans/outgoing-file-shares.md.
 */
class ShareService
{
    public function __construct(
        private ShareStorageService $storage
    ) {}

    /**
     * Create a draft share that files can be uploaded into.
     *
     * @param  array{title: string, message?: ?string, password?: ?string, expiry_days: int, recipient_emails?: array}  $data
     * @return array{0: Share, 1: string} The share and its link token (shown once, never stored)
     */
    public function create(User $sender, array $data): array
    {
        $token = $this->generateToken();
        $storage = $this->storage->resolveStorageFor($sender);

        $share = Share::create([
            'user_id' => $sender->id,
            'token_hash' => Share::hashToken($token),
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'password' => ! empty($data['password']) ? $data['password'] : null,
            'recipient_emails' => array_values($data['recipient_emails'] ?? []) ?: null,
            'provider' => $storage['provider'],
            'cloud_user_id' => $storage['cloud_user_id'],
            'status' => Share::STATUS_DRAFT,
            'expires_at' => now()->addDays((int) $data['expiry_days']),
        ]);

        return [$share, $token];
    }

    public function findByToken(string $token): ?Share
    {
        return Share::where('token_hash', Share::hashToken($token))->first();
    }

    /**
     * Check whether a file of the given size can be added to a share.
     *
     * @return string|null An error message, or null if the file is allowed
     */
    public function uploadError(Share $share, int $incomingBytes): ?string
    {
        if (! $share->isDraft()) {
            return __('messages.shares_not_accepting_files');
        }

        if ($incomingBytes > config('shares.max_file_size')) {
            return __('messages.shares_file_too_large', ['max' => format_bytes(config('shares.max_file_size'))]);
        }

        $currentTotal = (int) $share->files()->sum('file_size');
        if ($currentTotal + $incomingBytes > config('shares.max_share_size')) {
            return __('messages.shares_share_too_large', ['max' => format_bytes(config('shares.max_share_size'))]);
        }

        if ($this->freeDiskSpace() - $incomingBytes < config('shares.min_free_disk')) {
            return __('messages.shares_disk_full');
        }

        return null;
    }

    /**
     * Stage a fully uploaded file and queue its copy to cloud storage.
     *
     * The caller must have checked uploadError() first.
     */
    public function addFile(Share $share, UploadedFile $upload, string $originalName): ShareFile
    {
        $directory = $this->storage->stagingDirectory($share);
        $storedName = Str::random(40);
        $size = $upload->getSize();
        $mimeType = $upload->getClientMimeType() ?: 'application/octet-stream';

        // move() renames instead of copying, so a multi-GB file isn't written twice
        $upload->move(Storage::disk('local')->path($directory), $storedName);

        $file = $share->files()->create([
            'original_filename' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $size,
            'local_path' => "{$directory}/{$storedName}",
            'status' => ShareFile::STATUS_PENDING,
        ]);

        UploadShareFileToCloud::dispatch($file);

        return $file;
    }

    /**
     * Remove a file the sender added by mistake, while the share is still a draft.
     */
    public function removeFile(ShareFile $file): void
    {
        if ($file->local_path) {
            Storage::disk('local')->delete($file->local_path);
        }

        if ($file->provider_file_id) {
            // Throws if the cloud copy can't be deleted, so the row isn't lost
            $this->storage->deleteFile($file);
        }

        $file->delete();
    }

    /**
     * The sender has finished adding files. The link becomes available once
     * every file has reached cloud storage.
     */
    public function finalize(Share $share): void
    {
        Share::whereKey($share->id)
            ->where('status', Share::STATUS_DRAFT)
            ->update(['status' => Share::STATUS_UPLOADING]);

        $this->markReadyIfComplete($share->fresh());
    }

    /**
     * Mark the share ready if it is finalized and all its files are in the cloud.
     *
     * Upload jobs for a share finish in any order and may finish together, so
     * the status change is a conditional update: only one caller wins it and
     * fires ShareReady.
     */
    public function markReadyIfComplete(Share $share): bool
    {
        if ($share->status !== Share::STATUS_UPLOADING) {
            return false;
        }

        $files = $share->files();
        if (! $files->exists() || $share->files()->where('status', '!=', ShareFile::STATUS_UPLOADED)->exists()) {
            return false;
        }

        $won = Share::whereKey($share->id)
            ->where('status', Share::STATUS_UPLOADING)
            ->update(['status' => Share::STATUS_READY, 'ready_at' => now()]);

        if ($won) {
            ShareReady::dispatch($share->fresh());
        }

        return (bool) $won;
    }

    /**
     * Kill the link now and delete the files from cloud storage.
     */
    public function revoke(Share $share): void
    {
        if ($share->isEnded()) {
            return;
        }

        $share->update(['status' => Share::STATUS_REVOKED, 'revoked_at' => now()]);

        PurgeShareFiles::dispatch($share);
    }

    /**
     * Record a download, and fire ShareFirstDownloaded the first time only.
     */
    public function recordDownload(Share $share, ShareFile $file, Request $request): void
    {
        $file->increment('download_count');
        Share::whereKey($share->id)->increment('download_count', 1, ['last_accessed_at' => now()]);

        $this->logAccess($share, ShareAccessLog::EVENT_DOWNLOAD, $request, $file);

        // Conditional update so simultaneous first downloads send one email
        $first = Share::whereKey($share->id)
            ->whereNull('first_downloaded_at')
            ->update(['first_downloaded_at' => now()]);

        if ($first) {
            ShareFirstDownloaded::dispatch($share->fresh(), $file);
        }
    }

    public function logAccess(Share $share, string $event, Request $request, ?ShareFile $file = null): void
    {
        ShareAccessLog::create([
            'share_id' => $share->id,
            'share_file_id' => $file?->id,
            'event' => $event,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    /**
     * 32 random bytes, base64url-encoded (43 characters).
     */
    protected function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    protected function freeDiskSpace(): int
    {
        return (int) disk_free_space(Storage::disk('local')->path(''));
    }
}
