<?php

use Illuminate\Support\Facades\Http;
use Pexi\Laravel\ExceptionReporter;

test('it does not report when disabled', function () {
    Http::fake();

    config([
        'pexi.enabled' => false,
        'pexi.api_key' => 'pxi_test',
        'pexi.endpoint' => 'https://pexi.test',
        'pexi.report_in_tests' => true,
    ]);

    app(ExceptionReporter::class)->report(new RuntimeException('Boom'));

    Http::assertNothingSent();
});

test('it sends exception payloads to pexi', function () {
    Http::fake([
        'https://pexi.test/api/reports' => Http::response(['id' => 1], 201),
    ]);

    config([
        'pexi.enabled' => true,
        'pexi.api_key' => 'pxi_test',
        'pexi.endpoint' => 'https://pexi.test',
        'pexi.report_in_tests' => true,
    ]);

    app(ExceptionReporter::class)->report(new RuntimeException('Boom'));

    Http::assertSent(fn ($request): bool => $request->url() === 'https://pexi.test/api/reports'
        && $request->hasHeader('Authorization', 'Bearer pxi_test')
        && $request['exception_class'] === RuntimeException::class
        && $request['message'] === 'Boom');
});

function reportWithConfig(array $config): void
{
    Http::fake();

    config([
        'pexi.enabled' => true,
        'pexi.api_key' => 'pxi_test',
        'pexi.endpoint' => 'https://pexi.test',
        'pexi.report_in_tests' => true,
        ...$config,
    ]);

    app(ExceptionReporter::class)->report(new RuntimeException('Boom'));
}

test('an explicit release wins over the app version', function () {
    reportWithConfig(['pexi.release' => 'v9.9.9', 'app.version' => '1.2.3']);

    Http::assertSent(fn ($request): bool => $request['release'] === 'v9.9.9');
});

test('the release falls back to the app version', function () {
    reportWithConfig(['pexi.release' => null, 'app.version' => '1.2.3']);

    Http::assertSent(fn ($request): bool => $request['release'] === '1.2.3');
});

test('the release falls back to the VERSION file', function () {
    $versionFile = base_path('VERSION');
    file_put_contents($versionFile, "2.0.1\n");

    try {
        reportWithConfig(['pexi.release' => null, 'app.version' => null]);

        Http::assertSent(fn ($request): bool => $request['release'] === '2.0.1');
    } finally {
        unlink($versionFile);
    }
});
