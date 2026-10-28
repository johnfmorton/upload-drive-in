<?php

namespace App\Http\Controllers;

use App\Jobs\UploadShareFileToCloud;
use App\Models\Share;
use App\Models\ShareFile;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

/**
 * Sender side of outgoing file shares, mounted under both the admin panel
 * (admin.shares.*) and the employee portal (employee.shares.*).
 */
class ShareController extends Controller
{
    public function __construct(
        private ShareService $shares
    ) {}

    /**
     * Employee routes carry a {username} parameter that the actions don't use
     * (EmployeeMiddleware already checks it matches the signed-in user).
     */
    public function callAction($method, $parameters)
    {
        unset($parameters['username']);

        return $this->{$method}(...array_values($parameters));
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $shares = Share::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->ownedBy($user))
            ->with('user:id,name,email')
            ->withCount('files')
            ->withSum('files', 'file_size')
            ->latest()
            ->paginate(25);

        return view('shares.index', [
            'shares' => $shares,
            'showSender' => $user->isAdmin(),
        ] + $this->routeContext($request));
    }

    public function create(Request $request): View
    {
        return view('shares.create', [
            'expiryChoices' => config('shares.expiry_days'),
            'defaultExpiry' => config('shares.default_expiry_days'),
            'maxFileSize' => config('shares.max_file_size'),
            'maxShareSize' => config('shares.max_share_size'),
        ] + $this->routeContext($request));
    }

    /**
     * Create a draft share. The page then uploads files into it and finalizes it.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'expiry_days' => ['required', 'integer', Rule::in(config('shares.expiry_days'))],
            'recipient_emails' => ['nullable', 'array', 'max:20'],
            'recipient_emails.*' => ['email:rfc', 'max:255'],
        ]);

        try {
            [$share] = $this->shares->create($request->user(), $validated);
        } catch (\Exception $e) {
            Log::warning('Could not create share', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'upload_url' => $this->shareRoute($request, 'files.store', $share),
            'finalize_url' => $this->shareRoute($request, 'finalize', $share),
            'show_url' => $this->shareRoute($request, 'show', $share),
        ], 201);
    }

    /**
     * Receive one chunk of a file (Dropzone + pion/laravel-chunk-upload).
     */
    public function uploadChunk(Request $request, Share $share): JsonResponse
    {
        Gate::authorize('update', $share);

        // Only Dropzone chunk uploads, so every chunk can be checked against the declared size
        $chunk = $request->file('file');
        $declaredSize = (int) $request->input('dztotalfilesize', 0);
        $offset = (int) $request->input('dzchunkbyteoffset', 0);

        if (! $request->filled('dzuuid') || ! $chunk || $declaredSize <= 0 || $offset < 0
            || $offset + $chunk->getSize() > $declaredSize) {
            return response()->json(['error' => __('messages.shares_upload_failed')], 422);
        }

        // Check the limits on every chunk, so a too-small declared size can't be
        // used to keep writing chunks to disk; the disk floor counts only what's still to come
        $error = $this->shares->uploadError($share, $declaredSize, diskBytes: $declaredSize - $offset);
        if ($error) {
            return response()->json(['error' => $error], 422);
        }

        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if (! $receiver->isUploaded()) {
            return response()->json(['error' => __('messages.no_file_uploaded')], 400);
        }

        $save = $receiver->receive();

        if (! $save->isFinished()) {
            return response()->json(['status' => true]);
        }

        $upload = $save->getFile();

        // The client-reported size can't be trusted; check the real one
        $error = $upload->getSize() !== $declaredSize
            ? __('messages.shares_upload_failed')
            : $this->shares->uploadError($share, $upload->getSize(), checkDisk: false);
        if ($error) {
            @unlink($upload->getPathname());

            return response()->json(['error' => $error], 422);
        }

        $file = $this->shares->addFile($share, $upload, $upload->getClientOriginalName());

        return response()->json([
            'id' => $file->id,
            'delete_url' => $this->shareRoute($request, 'files.destroy', $share, ['shareFile' => $file->id]),
        ]);
    }

    public function destroyFile(Request $request, Share $share, ShareFile $shareFile): JsonResponse
    {
        Gate::authorize('update', $share);
        abort_unless($shareFile->share_id === $share->id && $share->isDraft(), 404);

        $this->shares->removeFile($shareFile);

        return response()->json(['status' => true]);
    }

    public function retryFile(Request $request, Share $share, ShareFile $shareFile): RedirectResponse
    {
        Gate::authorize('update', $share);
        abort_unless($shareFile->share_id === $share->id && $shareFile->status === ShareFile::STATUS_FAILED, 404);

        $shareFile->update(['status' => ShareFile::STATUS_PENDING, 'last_error' => null]);
        UploadShareFileToCloud::dispatch($shareFile);

        return back()->with('status', __('messages.shares_retry_queued'));
    }

    /**
     * The sender has added every file; the link works once they reach the cloud.
     */
    public function finalize(Request $request, Share $share): JsonResponse
    {
        Gate::authorize('update', $share);

        if (! $share->files()->exists()) {
            return response()->json(['message' => __('messages.shares_add_a_file')], 422);
        }

        $this->shares->finalize($share);

        return response()->json(['show_url' => $this->shareRoute($request, 'show', $share)]);
    }

    public function show(Request $request, Share $share): View
    {
        Gate::authorize('view', $share);

        $share->load(['user:id,name,email', 'files' => fn ($query) => $query->orderBy('original_filename')]);

        return view('shares.show', [
            'share' => $share,
            'recentAccess' => $share->accessLogs()->with('shareFile:id,original_filename')->latest('created_at')->limit(20)->get(),
        ] + $this->routeContext($request));
    }

    /**
     * Small JSON status for the show page to poll while files are still uploading.
     */
    public function status(Request $request, Share $share): JsonResponse
    {
        Gate::authorize('view', $share);

        return response()->json([
            'status' => $share->status,
            'files' => $share->files()->get(['id', 'status'])->pluck('status', 'id'),
        ]);
    }

    public function revoke(Request $request, Share $share): RedirectResponse
    {
        Gate::authorize('update', $share);

        $this->shares->revoke($share);

        return redirect($this->shareRoute($request, 'show', $share))
            ->with('status', __('messages.shares_revoked_message'));
    }

    /**
     * Route name prefix and base parameters for the panel the request came from.
     *
     * @return array{routePrefix: string, routeParams: array}
     */
    private function routeContext(Request $request): array
    {
        return $request->routeIs('employee.*')
            ? ['routePrefix' => 'employee.shares', 'routeParams' => ['username' => $request->user()->username]]
            : ['routePrefix' => 'admin.shares', 'routeParams' => []];
    }

    private function shareRoute(Request $request, string $name, Share $share, array $extra = []): string
    {
        ['routePrefix' => $prefix, 'routeParams' => $params] = $this->routeContext($request);

        return route("{$prefix}.{$name}", $params + ['share' => $share] + $extra);
    }
}
