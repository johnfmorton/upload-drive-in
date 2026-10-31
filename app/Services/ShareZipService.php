<?php

namespace App\Services;

use App\Models\ShareFile;
use Illuminate\Support\Collection;
use ZipStream\CompressionMethod;
use ZipStream\OperationMode;
use ZipStream\ZipStream;

/**
 * Builds a share's "Download all" zip while it downloads.
 *
 * Files are stored uncompressed (most large files are already compressed,
 * and it keeps CPU use flat) and read from cloud storage one at a time, so
 * no zip is ever written to disk. A dry run first works out the zip's exact
 * size, so the download can send a Content-Length and browsers can show
 * progress.
 */
class ShareZipService
{
    public function __construct(
        private ShareStorageService $storage
    ) {}

    /**
     * Prepare the zip and return it with its size in bytes. Nothing is read
     * from cloud storage until executeSimulation() is called on the zip.
     *
     * @param  Collection<int, ShareFile>  $files
     * @param  resource|null  $outputStream  Defaults to php://output
     * @return array{0: ZipStream, 1: int}
     */
    public function prepare(Collection $files, $outputStream = null): array
    {
        $zip = new ZipStream(
            operationMode: OperationMode::SIMULATE_STRICT,
            outputStream: $outputStream,
            defaultCompressionMethod: CompressionMethod::STORE,
            sendHttpHeaders: false,
            flushOutput: true,
        );

        foreach ($this->entryNames($files) as $name => $file) {
            $zip->addFileFromCallback(
                fileName: $name,
                callback: fn () => $this->storage->openFileStream($file),
                lastModificationDateTime: $file->created_at,
                exactSize: (int) $file->file_size,
            );
        }

        return [$zip, $zip->finish()];
    }

    /**
     * A unique name inside the zip for every file. Two files in a share can
     * have the same name, so later ones get " (2)", " (3)" and so on.
     *
     * @param  Collection<int, ShareFile>  $files
     * @return array<string, ShareFile>
     */
    public function entryNames(Collection $files): array
    {
        $entries = [];

        foreach ($files as $file) {
            // Path separators would put the file in a folder inside the zip
            $name = str_replace(['/', '\\'], '_', $file->original_filename);
            $candidate = $name;
            $suffix = 2;

            while (isset($entries[mb_strtolower($candidate)])) {
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $base = $extension === '' ? $name : mb_substr($name, 0, -mb_strlen($extension) - 1);
                $candidate = $extension === '' ? "{$base} ({$suffix})" : "{$base} ({$suffix}).{$extension}";
                $suffix++;
            }

            // Keyed case-insensitively, since Windows and macOS treat "A.pdf" and "a.pdf" as the same file
            $entries[mb_strtolower($candidate)] = [$candidate, $file];
        }

        return collect($entries)->mapWithKeys(fn ($entry) => [$entry[0] => $entry[1]])->all();
    }
}
