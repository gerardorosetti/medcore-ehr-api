<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;

it('renders the MedCore clinical architecture and command dashboard on the root route', function (): void {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('MedCore EHR API');
});

it('returns healthy system telemetry and HIPAA compliance metadata on the health check probe', function (): void {
    $redisMock = Mockery::mock();
    $redisMock->shouldReceive('ping')->andReturn('PONG');
    Redis::shouldReceive('connection')->andReturn($redisMock);

    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'healthy',
            'environment' => 'testing',
            'services' => [
                'database' => 'connected',
                'redis' => 'connected',
            ],
            'version' => [
                'api' => 'v1.0.0',
            ],
            'compliance' => [
                'phi_encryption' => 'AES-256-CBC (Eloquent Encrypted Casts)',
                'hipaa_audit_trail' => 'Immutable audit_logs active',
            ],
        ])
        ->assertJsonStructure([
            'status',
            'timestamp',
            'environment',
            'services' => [
                'database',
                'redis',
            ],
            'version' => [
                'php',
                'laravel',
                'api',
            ],
            'compliance' => [
                'phi_encryption',
                'hipaa_audit_trail',
            ],
        ]);
});

it('reports degraded health status when the Redis telemetry probe fails', function (): void {
    $redisMock = Mockery::mock();
    $redisMock->shouldReceive('ping')->andThrow(new RuntimeException('Connection refused'));
    Redis::shouldReceive('connection')->andReturn($redisMock);

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(503)
        ->assertJson([
            'status' => 'degraded',
            'services' => [
                'database' => 'connected',
                'redis' => 'disconnected',
            ],
        ]);
});

it('isolates compiled Blade views and log files by POSIX effective UID to prevent root vs www-data permission collisions', function (): void {
    $euid = function_exists('posix_geteuid') ? posix_geteuid() : (int) getmyuid();

    expect((string) config('view.compiled'))->toContain('medcore_views_'.$euid)
        ->and((string) config('logging.channels.single.path'))->toContain('laravel-uid-'.$euid.'.log')
        ->and(is_dir((string) config('view.compiled')))->toBeTrue();
});
