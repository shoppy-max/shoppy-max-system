<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditRequestMiddleware
{
    public function __construct(private AuditLogService $auditLog)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $statusCode = match (true) {
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                method_exists($exception, 'status') => $exception->status,
                default => 500,
            };

            if ($this->shouldLog($request) || in_array($statusCode, [401, 403], true)) {
                $this->auditLog->record($this->actionFor($request, true), [
                    'request_data' => $this->payloadFor($request),
                    'status_code' => $statusCode,
                    'metadata' => [
                        'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                        'exception' => class_basename($exception),
                        'message' => $exception->getMessage(),
                    ],
                ]);
            }

            throw $exception;
        }

        if ($this->shouldLog($request, $response)) {
            $this->auditLog->record($this->actionFor($request), [
                'request_data' => $this->payloadFor($request),
                'status_code' => $response->getStatusCode(),
                'metadata' => $this->metadataFor($response, $startedAt),
            ]);
        }

        return $response;
    }

    private function shouldLog(Request $request, ?Response $response = null): bool
    {
        if ($request->is('user-logs') && in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $routeName = (string) $request->route()?->getName();
        $path = $request->path();

        if ($this->isDownloadIntent($request)) {
            return true;
        }

        if ($request->query('export') !== null || auth()->check()) {
            return true;
        }

        if ($response) {
            return in_array($response->getStatusCode(), [401, 403], true)
                || ($response->isRedirection() && str_contains((string) $response->headers->get('Location'), '/login'));
        }

        return false;
    }

    private function actionFor(Request $request, bool $failed = false): string
    {
        if ($failed) {
            return 'request_failed';
        }

        if ($this->isDownloadIntent($request)) {
            return 'downloaded';
        }

        if ($request->isMethod('POST')) {
            return 'submitted';
        }

        if (in_array($request->method(), ['PUT', 'PATCH'], true)) {
            return 'updated_request';
        }

        return $request->isMethod('DELETE') ? 'deleted_request' : 'viewed';
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Request $request): array
    {
        $payload = $request->except([]);

        if ($request->files->count() > 0) {
            $payload['files'] = $request->allFiles();
        }

        $routeParameters = $request->route()?->parameters() ?? [];
        if ($routeParameters !== []) {
            $payload['route_parameters'] = $routeParameters;
        }

        return $payload;
    }

    private function isDownloadIntent(Request $request): bool
    {
        if ($request->query('export') !== null) {
            return true;
        }

        $routeName = (string) $request->route()?->getName();
        $path = $request->path();

        foreach (['export', 'download', 'pdf', 'print', 'barcode', 'barcodes', 'template', 'invoice', 'pick-grn'] as $keyword) {
            if (str_contains($routeName, $keyword) || str_contains($path, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(Response $response, float $startedAt): array
    {
        return array_filter([
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'content_type' => $response->headers->get('Content-Type'),
            'content_disposition' => $response->headers->get('Content-Disposition'),
            'content_length' => $response->headers->get('Content-Length'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
