<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle FileManagerException and its subclasses
        $this->renderable(function (FileManagerException $e, Request $request) {
            return $e->render($request);
        });

        // Handle Google API exceptions
        $this->renderable(function (\Google\Service\Exception $e, Request $request) {
            $exception = GoogleDriveException::apiError($e, 'api_request');
            return $exception->render($request);
        });
    }
}