<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AppointmentScheduled;
use App\Listeners\DispatchAppointmentNotificationListener;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\MedicalRecordPolicy;
use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\GeneratorResult;
use Dedoc\Scramble\OpenApiVersion;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\ProNudge\ProNudgeCollector;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 *
 * Bootstraps application-level authorization policies, domain event listeners,
 * and OpenAPI 3.1 Scramble security definitions.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Isolate compiled Blade views by POSIX Effective UID (e.g., www-data UID 82 vs root CLI UID 0)
        // to prevent Linux sticky-bit utime() / touch() permission collisions when git checkout or CLI tests run.
        $euid = function_exists('posix_geteuid') ? posix_geteuid() : (int) getmyuid();
        $isolatedViewPath = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'medcore_views_'.$euid;

        if (! is_dir($isolatedViewPath)) {
            @mkdir($isolatedViewPath, 0777, true);
        }

        config([
            'view.compiled' => $isolatedViewPath,
            'logging.channels.single.path' => storage_path('logs/laravel-uid-'.$euid.'.log'),
            'logging.channels.daily.path' => storage_path('logs/laravel-uid-'.$euid.'.log'),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicitly map clinical policies to domain models
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(MedicalRecord::class, MedicalRecordPolicy::class);

        // Register domain event listeners
        Event::listen(AppointmentScheduled::class, DispatchAppointmentNotificationListener::class);

        // Allow access to the interactive Scramble OpenAPI 3.1 specification portal
        Gate::define('viewApiDocs', fn (?User $user = null): bool => true);

        // Configure OpenAPI Bearer JWT security scheme and Redis AST spec caching if Scramble is installed
        if (class_exists(Scramble::class)) {
            Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });

            $this->app->singleton(CacheableGenerator::class, function (Application $app): CacheableGenerator {
                return new class($app->make(Generator::class)) extends CacheableGenerator
                {
                    public function generate(GeneratorConfig $config): GeneratorResult
                    {
                        $cacheKey = 'medcore.openapi.spec.v1:'.$config->name;

                        /** @var array<string, mixed> $cachedSpec */
                        $cachedSpec = Cache::remember($cacheKey, 3600, function () use ($config): array {
                            return parent::generate($config)->spec($config->openApiVersion());
                        });

                        return new class($cachedSpec) extends GeneratorResult
                        {
                            /** @param array<string, mixed> $cachedSpec */
                            public function __construct(private readonly array $cachedSpec)
                            {
                                parent::__construct(
                                    new OpenApi('3.1.0'),
                                    new Collection,
                                    new ProNudgeCollector,
                                );
                            }

                            /** @return array<string, mixed> */
                            public function spec(OpenApiVersion $version = OpenApiVersion::V3_1): array
                            {
                                return $this->cachedSpec;
                            }
                        };
                    }
                };
            });
        }

        // Configure granular rate limiters for authentication, API traffic, and appointment scheduling
        RateLimiter::for('auth', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email', 'anonymous'));

            return Limit::perMinute(5)->by($request->ip().'|'.$email);
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('appointments', function (Request $request): Limit {
            return Limit::perMinute(15)->by((string) ($request->user()?->id ?: $request->ip()));
        });
    }
}
