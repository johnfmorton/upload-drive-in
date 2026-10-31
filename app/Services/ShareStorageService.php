<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Share;
use App\Models\ShareFile;
use App\Models\User;
use Exception;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Moves outgoing share files between the local staging disk and the share's
 * cloud provider, and opens them for download.
 *
 * Every share records the provider and the account whose storage holds its
 * files (cloud_user_id), so uploads, downloads and deletes all talk to the
 * same Google Drive account even when the sender has no Drive connection of
 * their own.
 */
class ShareStorageService
{
    public const PARENT_FOLDER_NAME = 'Outgoing shares';

    public function __construct(
        private CloudStorageManager $storageManager,
        private GoogleDriveService $driveService,
        private GoogleDriveChunkedUploadService $chunkedUploadService
    ) {}

    /**
     * Decide where a new share's files will be stored.
     *
     * @return array{provider: string, cloud_user_id: int}
     *
     * @throws Exception When the provider has no usable account
     */
    public function resolveStorageFor(User $sender): array
    {
        $provider = $this->storageManager->getUserProvider($sender)->getProviderName();

        if ($provider === 'google-drive') {
            $cloudUser = $this->findDriveAccount($sender);

            if (! $cloudUser) {
                throw new Exception(__('messages.shares_no_cloud_storage'));
            }

            return ['provider' => $provider, 'cloud_user_id' => $cloudUser->id];
        }

        return ['provider' => $provider, 'cloud_user_id' => $sender->id];
    }

    /**
     * Copy a staged file to the share's cloud storage.
     *
     * @return string The provider's file ID (Drive file ID or S3 key)
     */
    public function upload(ShareFile $file): string
    {
        $share = $file->share;
        $cloudUser = $this->cloudUserFor($share);
        $localPath = Storage::disk('local')->path($file->local_path);

        if ($share->provider === 'google-drive') {
            return $this->chunkedUploadService->uploadFileChunked(
                $cloudUser,
                $localPath,
                $this->ensureDriveFolder($share, $cloudUser),
                $file->original_filename,
                $file->mime_type ?: 'application/octet-stream'
            );
        }

        return $this->s3Provider($cloudUser)->uploadFile(
            $cloudUser,
            $localPath,
            'outgoing-share-'.$share->ulid,
            [
                'original_filename' => $file->original_filename,
                'mime_type' => $file->mime_type ?: 'application/octet-stream',
            ]
        );
    }

    /**
     * A short-lived direct download URL for an S3 share file, which names the
     * download after the original file.
     */
    public function temporaryS3Url(ShareFile $file, int $minutes = 5): string
    {
        $cloudUser = $this->cloudUserFor($file->share);

        return $this->s3Provider($cloudUser)->generateDownloadUrl(
            $cloudUser,
            $file->provider_file_id,
            $file->original_filename,
            $minutes
        );
    }

    /**
     * Open a Google Drive share file for streaming, optionally for a byte range.
     *
     * The response body is read from the network as it is consumed; nothing is
     * buffered in memory.
     */
    public function openDriveStream(ShareFile $file, ?string $range = null): ResponseInterface
    {
        return $this->driveService->openFileMediaStream(
            $this->cloudUserFor($file->share),
            $file->provider_file_id,
            $range,
            httpErrors: false
        );
    }

    /**
     * Open a share file from either provider as a stream, read from the
     * network as it is consumed. Used to build "Download all" zips.
     *
     * @throws Exception When the provider refuses the download
     */
    public function openFileStream(ShareFile $file): StreamInterface
    {
        if ($file->share->provider === 'google-drive') {
            $response = $this->openDriveStream($file);

            if ($response->getStatusCode() >= 400) {
                throw new Exception("Google Drive refused share file {$file->id} with HTTP {$response->getStatusCode()}");
            }

            return $response->getBody();
        }

        $cloudUser = $this->cloudUserFor($file->share);

        return $this->s3Provider($cloudUser)->downloadFileStream($cloudUser, $file->provider_file_id)['body'];
    }

    /**
     * Delete a share's files from cloud storage and the staging disk.
     *
     * Safe to call more than once: files that are already gone count as deleted.
     *
     * @throws Exception When cloud files could not be deleted (so the caller can retry)
     */
    public function deleteShareFiles(Share $share): void
    {
        $uploaded = $share->files()->whereNotNull('provider_file_id')->get();

        if ($share->provider === 'google-drive') {
            if ($share->cloud_folder_id) {
                // Deleting the folder deletes every file in it
                $this->deleteDriveItem($share, $share->cloud_folder_id);
            } else {
                foreach ($uploaded as $file) {
                    $this->deleteDriveItem($share, $file->provider_file_id);
                }
            }
        } elseif ($uploaded->isNotEmpty()) {
            $cloudUser = $this->cloudUserFor($share);
            $provider = $this->s3Provider($cloudUser);

            foreach ($uploaded as $file) {
                $provider->deleteFile($cloudUser, $file->provider_file_id);
            }
        }

        Storage::disk('local')->deleteDirectory($this->stagingDirectory($share));
    }

    /**
     * Delete one file from cloud storage. Already-deleted files count as deleted.
     */
    public function deleteFile(ShareFile $file): void
    {
        $share = $file->share;

        if ($share->provider === 'google-drive') {
            $this->deleteDriveItem($share, $file->provider_file_id);

            return;
        }

        $cloudUser = $this->cloudUserFor($share);
        $this->s3Provider($cloudUser)->deleteFile($cloudUser, $file->provider_file_id);
    }

    /**
     * Staging directory for a share's files, relative to the "local" disk.
     */
    public function stagingDirectory(Share $share): string
    {
        return config('shares.staging_directory').'/'.$share->ulid;
    }

    /**
     * The Drive account a sender's share files go to: their own if connected,
     * otherwise the first admin with Drive connected (the same fallback the
     * client upload flow uses).
     */
    private function findDriveAccount(?User $sender): ?User
    {
        if ($sender?->hasGoogleDriveConnected()) {
            return $sender;
        }

        return User::where('role', UserRole::ADMIN)
            ->whereHas('googleDriveToken')
            ->orderBy('id')
            ->first();
    }

    private function cloudUserFor(Share $share): User
    {
        $cloudUser = $share->cloudUser;

        // The account may have been deleted since the share was created
        if (! $cloudUser && $share->provider === 'google-drive') {
            $cloudUser = $this->findDriveAccount($share->user);
        }

        if (! $cloudUser) {
            throw new Exception("No cloud storage account available for share {$share->ulid}");
        }

        return $cloudUser;
    }

    private function s3Provider(User $cloudUser): S3Provider
    {
        $provider = $this->storageManager->getProvider('amazon-s3', $cloudUser);

        // getProvider() may fall back to another provider, which can't handle S3 keys
        if (! $provider instanceof S3Provider) {
            throw new Exception('Amazon S3 provider is not available');
        }

        return $provider;
    }

    /**
     * Find or create the share's own Drive folder, inside an "Outgoing shares"
     * folder in the account's root folder.
     */
    private function ensureDriveFolder(Share $share, User $cloudUser): string
    {
        if ($share->cloud_folder_id) {
            return $share->cloud_folder_id;
        }

        // Several files of one share upload in parallel; only one may create the folder
        return Cache::lock("share-drive-folder:{$share->id}", 60)->block(60, function () use ($share, $cloudUser) {
            $share->refresh();

            if ($share->cloud_folder_id) {
                return $share->cloud_folder_id;
            }

            $parentId = Cache::lock("share-drive-parent:{$cloudUser->id}", 60)->block(60, function () use ($cloudUser) {
                $rootId = $this->driveService->getEffectiveRootFolderId($cloudUser);

                return $this->findDriveFolder($cloudUser, self::PARENT_FOLDER_NAME, $rootId)
                    ?? $this->createDriveFolder($cloudUser, self::PARENT_FOLDER_NAME, $rootId);
            });

            $folderName = sprintf('%s (%s)', $share->title, $share->ulid);
            $folderId = $this->createDriveFolder($cloudUser, $folderName, $parentId);

            $share->update(['cloud_folder_id' => $folderId]);

            return $folderId;
        });
    }

    private function findDriveFolder(User $cloudUser, string $name, string $parentId): ?string
    {
        $results = $this->driveService->getDriveService($cloudUser)->files->listFiles([
            'q' => sprintf(
                "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
                addslashes($name),
                addslashes($parentId)
            ),
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        $files = $results->getFiles();

        return $files ? $files[0]->getId() : null;
    }

    private function createDriveFolder(User $cloudUser, string $name, string $parentId): string
    {
        $folder = $this->driveService->getDriveService($cloudUser)->files->create(new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]), ['fields' => 'id']);

        return $folder->getId();
    }

    private function deleteDriveItem(Share $share, string $id): void
    {
        try {
            $this->driveService->getDriveService($this->cloudUserFor($share))->files->delete($id);
        } catch (GoogleServiceException $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }

            Log::info('Share file already gone from Google Drive', ['share' => $share->ulid, 'drive_id' => $id]);
        }
    }
}
