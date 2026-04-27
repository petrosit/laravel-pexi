<?php

namespace Pexi\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class ExceptionReporter
{
    public function __construct(
        private readonly Application $app,
        private readonly HttpFactory $http,
    ) {}

    public function report(Throwable $exception): void
    {
        if (! $this->shouldReport()) {
            return;
        }

        try {
            $this->http
                ->asJson()
                ->acceptJson()
                ->withToken((string) config('pexi.api_key'))
                ->timeout((float) config('pexi.timeout', 2.0))
                ->retry(
                    (int) config('pexi.retry_times', 1),
                    (int) config('pexi.retry_sleep_milliseconds', 100),
                    throw: false,
                )
                ->post($this->endpoint(), $this->payload($exception));
        } catch (Throwable) {
            //
        }
    }

    private function shouldReport(): bool
    {
        if (! config('pexi.enabled', true)) {
            return false;
        }

        if (! config('pexi.api_key') || ! config('pexi.endpoint')) {
            return false;
        }

        return ! $this->app->runningUnitTests() || (bool) config('pexi.report_in_tests', false);
    }

    private function endpoint(): string
    {
        return rtrim((string) config('pexi.endpoint'), '/').'/api/reports';
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Throwable $exception): array
    {
        $request = $this->currentRequest();

        return [
            'exception_class' => $exception::class,
            'message' => $exception->getMessage() ?: $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'occurred_at' => now()->toISOString(),
            'method' => $request?->method(),
            'url' => $request?->fullUrl(),
            'environment' => $this->app->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => $this->app->version(),
            'release' => config('pexi.release'),
            'user_identifier' => $this->userIdentifier($request),
            'fingerprint' => $this->fingerprint($exception),
            'context' => $this->context($request),
        ];
    }

    private function currentRequest(): ?Request
    {
        if (! $this->app->bound('request')) {
            return null;
        }

        $request = $this->app->make('request');

        return $request instanceof Request ? $request : null;
    }

    private function userIdentifier(?Request $request): ?string
    {
        $user = $request?->user();

        if (! $user) {
            return null;
        }

        return (string) ($user->email ?? $user->getAuthIdentifier());
    }

    /**
     * @return array<string, mixed>
     */
    private function context(?Request $request): array
    {
        if (! $request) {
            return [];
        }

        return [
            'route' => $request->route()?->getName(),
            'input' => Arr::except($request->input(), [
                'password',
                'password_confirmation',
                'current_password',
                'token',
                'api_key',
            ]),
        ];
    }

    private function fingerprint(Throwable $exception): string
    {
        return hash('sha256', implode('|', [
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
        ]));
    }
}
