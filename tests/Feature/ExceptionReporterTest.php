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
