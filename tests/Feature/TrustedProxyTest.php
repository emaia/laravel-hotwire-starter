<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/proxy-check', fn (Request $request): array => [
        'url' => url('/up'),
        'ip' => $request->ip(),
    ]);
});

test('generates HTTPS URLs with the public port behind the Docker proxy', function (string $proxyAddress): void {
    $response = $this->withServerVariables(['REMOTE_ADDR' => $proxyAddress])
        ->get('http://app:8080/proxy-check', [
            'X-Forwarded-Host' => 'hotwire-starter.localhost:8443',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.10',
            'X-Forwarded-Port' => '9999',
        ]);

    $response->assertOk()->assertExactJson([
        'url' => 'https://hotwire-starter.localhost:8443/up',
        'ip' => '203.0.113.10',
    ]);
})->with(['172.20.0.2', '10.0.0.2', '192.168.0.2']);

test('ignores forwarded headers from an untrusted public address', function (): void {
    $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.2'])
        ->get('http://app:8080/proxy-check', [
            'X-Forwarded-Host' => 'untrusted.example',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.10',
        ]);

    $response->assertOk()->assertExactJson([
        'url' => 'http://app:8080/up',
        'ip' => '198.51.100.2',
    ]);
});
