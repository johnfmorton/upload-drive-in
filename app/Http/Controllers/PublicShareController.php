<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Models\ShareAccessLog;
use App\Models\ShareFile;
use App\Services\ShareService;
use App\Services\ShareStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Recipient side of outgoing file shares: the page behind a share link, its
 * optional password, and file downloads.
 */
class PublicShareController extends Controller
{
    // Wrong passwords allowed per share and IP before a 15-minute lockout
    private const PASSWORD_ATTEMPTS = 5;

    private const PASSWORD_DECAY_SECONDS = 900;

    // Wrong passwords allowed per share from all IPs combined before a one-hour lockout
    private const SHARE_PASSWORD_ATTEMPTS = 20;

    private const SHARE_PASSWORD_DECAY_SECONDS = 3600;

    public function __construct(
        private ShareService $shares,
        private ShareStorageService $storage
    ) {}

    public function show(Request $request, string $token): Response
    {
        $share = $this->shares->findByToken($token);

        if (! $share || ! $share->isAvailable()) {
            return $this->unavailable($share);
        }

        if (! $this->isUnlocked($request, $share)) {
            return $this->noIndex(response()->view('shares.public.password', [
                'share' => $share,
                'token' => $token,
            ]));
        }

        // One view per visit, not one per refresh
        $viewedKey = "shares.viewed.{$share->id}";
        if (! $request->session()->has($viewedKey)) {
            $request->session()->put($viewedKey, true);
            $this->shares->logAccess($share, ShareAccessLog::EVENT_VIEW, $request);
            $share->forceFill(['last_accessed_at' => now()])->saveQuietly();
        }

        $share->load('user:id,name');

        return $this->noIndex(response()->view('shares.public.show', [
            'share' => $share,
            'token' => $token,
            'files' => $share->files()->where('status', ShareFile::STATUS_UPLOADED)->orderBy('original_filename')->paginate(100),
            'totalSize' => (int) $share->files()->sum('file_size'),
        ]));
    }

    public function unlock(Request $request, string $token): RedirectResponse|Response
    {
        $share = $this->shares->findByToken($token);

        if (! $share || ! $share->isAvailable()) {
            return $this->unavailable($share);
        }

        // Per IP, plus per share across all IPs so guesses can't be spread over many addresses
        $limits = [
            "share-password:{$share->id}:{$request->ip()}" => [self::PASSWORD_ATTEMPTS, self::PASSWORD_DECAY_SECONDS],
            "share-password:{$share->id}" => [self::SHARE_PASSWORD_ATTEMPTS, self::SHARE_PASSWORD_DECAY_SECONDS],
        ];

        foreach ($limits as $key => [$maxAttempts]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                return back()->withErrors([
                    'password' => __('messages.shares_password_locked', [
                        'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
                    ]),
                ]);
            }
        }

        $ipKey = array_key_first($limits);

        if (! $share->hasPassword() || Hash::check((string) $request->input('password'), $share->password)) {
            // Only this visitor's count resets; the share-wide count keeps running
            RateLimiter::clear($ipKey);
            $request->session()->put("shares.unlocked.{$share->id}", true);

            return redirect()->route('shares.public.show', ['token' => $token]);
        }

        foreach ($limits as $key => [, $decaySeconds]) {
            RateLimiter::hit($key, $decaySeconds);
        }
        $this->shares->logAccess($share, ShareAccessLog::EVENT_BAD_PASSWORD, $request);

        return back()->withErrors(['password' => __('messages.shares_password_wrong')]);
    }

    public function download(Request $request, string $token, int $fileId): Response
    {
        $share = $this->shares->findByToken($token);

        if (! $share || ! $share->isAvailable()) {
            return $this->unavailable($share);
        }

        if (! $this->isUnlocked($request, $share)) {
            return redirect()->route('shares.public.show', ['token' => $token]);
        }

        $file = $share->files()
            ->whereKey($fileId)
            ->where('status', ShareFile::STATUS_UPLOADED)
            ->firstOrFail();

        // A resumed download asks for a later byte range; count only the first request
        if (! $this->isResumedRequest($request)) {
            $this->shares->recordDownload($share, $file, $request);
        }

        if ($share->provider === 'google-drive') {
            return $this->streamFromDrive($request, $file);
        }

        // S3 serves the file directly, so it never passes through this server
        return redirect()->away($this->storage->temporaryS3Url($file));
    }

    /**
     * Pipe a Drive file to the browser, passing a Range header through so
     * interrupted downloads can resume.
     */
    private function streamFromDrive(Request $request, ShareFile $file): Response
    {
        $range = $request->header('Range');
        $driveResponse = $this->storage->openDriveStream($file, $range);
        $status = $driveResponse->getStatusCode();

        if ($status === 416) {
            return response('', 416, ['Content-Range' => "bytes */{$file->file_size}"]);
        }

        if ($status >= 400) {
            Log::error('Google Drive refused a share download', [
                'share_file_id' => $file->id,
                'status' => $status,
            ]);
            abort(502, __('messages.shares_download_failed'));
        }

        $headers = [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $this->contentDisposition($file->original_filename),
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];

        foreach (['Content-Length', 'Content-Range'] as $header) {
            if ($driveResponse->hasHeader($header)) {
                $headers[$header] = $driveResponse->getHeaderLine($header);
            }
        }

        $body = $driveResponse->getBody();

        return new StreamedResponse(function () use ($body) {
            // A multi-GB download can outlast max_execution_time
            set_time_limit(0);

            while (! $body->eof()) {
                echo $body->read(1024 * 1024);
                flush();

                if (connection_aborted()) {
                    break;
                }
            }

            $body->close();
        }, $status, $headers);
    }

    private function isUnlocked(Request $request, Share $share): bool
    {
        return ! $share->hasPassword() || $request->session()->get("shares.unlocked.{$share->id}") === true;
    }

    private function isResumedRequest(Request $request): bool
    {
        $range = $request->header('Range');

        return $range !== null && ! Str::startsWith($range, 'bytes=0-');
    }

    /**
     * The same page for unknown, expired and revoked links: 404 for a token
     * that never existed, 410 for one that has ended.
     */
    private function unavailable(?Share $share): Response
    {
        return $this->noIndex(response()->view('shares.public.unavailable', [], $share ? 410 : 404));
    }

    private function noIndex(Response $response): Response
    {
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * RFC 6266: ASCII fallback plus UTF-8 filename* for non-ASCII names.
     */
    private function contentDisposition(string $filename): string
    {
        $fallback = str_replace(['"', '\\'], '_', Str::ascii($filename));

        return sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $fallback, rawurlencode($filename));
    }
}
