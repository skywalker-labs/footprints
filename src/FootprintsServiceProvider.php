<?php

declare(strict_types=1);

namespace Skywalker\Footprints;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Skywalker\Support\Providers\PackageServiceProvider;

class FootprintsServiceProvider extends PackageServiceProvider
{
    /**
     * Vendor name.
     *
     * @var string
     */
    protected $vendor = 'skywalker';

    /**
     * Package name.
     *
     * @var string
     */
    protected $package = 'footprints';

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        parent::boot();

        $this->publishAll();
        $this->bootMacros();
    }

    /**
     * Publish Footprints configuration.
     *
     * @param  string|null  $path
     */
    protected function publishConfig(?string $path = null): void
    {
        // Publish config files
        $this->publishes([
            $this->getBasePath() . '/config/footprints.php' => $path ?: config_path('footprints.php'),
        ], 'config');
    }

    /**
     * Publish Footprints migration.
     *
     * @param  string|null  $path
     */
    protected function publishMigrations(?string $path = null): void
    {
        $published_migration = glob(database_path('/migrations/*_create_footprints_table.php'));
        if (is_array($published_migration) && count($published_migration) === 0) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_footprints_table.php' => $path ?: database_path('/migrations/' . date('Y_m_d_His') . '_create_footprints_table.php'),
            ], 'migrations');
        }
    }

    protected function bootMacros(): void
    {
        Request::macro('footprint', function () {
            /** @var \Skywalker\Footprints\FootprinterInterface $footprinter */
            $footprinter = App::make(FootprinterInterface::class);
            return $footprinter->footprint($this);
        });
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        parent::register();

        $this->registerConfig();

        $this->app->bind(TrackingFilterInterface::class, function ($app) {
            return $app->make(config('footprints.tracking_filter'));
        });

        $this->app->bind(TrackingLoggerInterface::class, function ($app) {
            return $app->make(config('footprints.tracking_logger'));
        });

        $this->app->singleton(FootprinterInterface::class, function ($app) {
            return $app->make(config('footprints.footprinter'));
        });

        $this->commands([
            Console\PruneCommand::class,
        ]);
    }
}
