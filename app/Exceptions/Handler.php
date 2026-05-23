<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        // Use the default reporting
    }

    public function render($request, Throwable $e)
    {
        $shouldReturnJson = $request->expectsJson() || str_starts_with($request->getRequestUri(), '/api');

        if ($shouldReturnJson) {
            // Validation exceptions get 422 and structured errors
            if ($e instanceof ValidationException) {
                $status = 422;
                $payload = [
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Validation Failed',
                    'code' => $status,
                    'errors' => $e->errors(),
                ];

                if (config('app.debug')) {
                    $payload['meta'] = [
                        'exception' => get_class($e),
                    ];
                }

                return response()->json($payload, $status);
            }

            $status = 500;
            $message = 'Server Error';

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: HttpResponse::$statusTexts[$status] ?? 'Error';
            } elseif (method_exists($e, 'getStatusCode')) {
                $status = $e->getStatusCode();
            }

            $payload = [
                'success' => false,
                'message' => $message,
                'code' => $status,
            ];

            // attach more details in debug
            if (config('app.debug')) {
                $payload['meta'] = [
                    'exception' => get_class($e),
                    'trace' => collect($e->getTrace())->map(function ($t) {
                        return Arr::except($t, ['args']);
                    })->all(),
                ];
            }

            return response()->json($payload, $status);
        }

        return parent::render($request, $e);
    }
}
