<?php

namespace SMWks\LaravelZenith;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SMWks\LaravelZenith\Commands\MonitorCommand;
use SMWks\LaravelZenith\Commands\PruneCommand;
use SMWks\LaravelZenith\Commands\WorkChildCommand;
use SMWks\LaravelZenith\Commands\WorkCommand;
use SMWks\LaravelZenith\Listeners\JobExceptionOccurredListener;
use SMWks\LaravelZenith\Listeners\JobFailedListener;
use SMWks\LaravelZenith\Listeners\JobProcessedListener;
use SMWks\LaravelZenith\Listeners\JobProcessingListener;
use SMWks\LaravelZenith\Listeners\WorkerLoopingListener;
use SMWks\LaravelZenith\Services\MetricsService;
use SMWks\LaravelZenith\Services\ZenithJobService;

class ZenithServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services
        $this->app->singleton(ZenithJobService::class);
        $this->app->singleton(MetricsService::class);

        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/zenith.php', 'zenith');
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/zenith.php' => config_path('zenith.php'),
        ], 'config');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-zenith');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-zenith'),
        ], 'views');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom([
            __DIR__.'/../database/migrations/create_zenith_tables.php',
        ]);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                WorkCommand::class,
                WorkChildCommand::class,
                MonitorCommand::class,
                PruneCommand::class,
            ]);
        }

        // Register Livewire components
        Livewire::component('zenith-dashboard', \SMWks\LaravelZenith\Livewire\Dashboard::class);
        Livewire::component('zenith-workers-list', \SMWks\LaravelZenith\Livewire\WorkersList::class);
        Livewire::component('zenith-jobs-list', \SMWks\LaravelZenith\Livewire\JobsList::class);
        Livewire::component('zenith-failed-jobs-list', \SMWks\LaravelZenith\Livewire\FailedJobsList::class);

        // Register event listeners
        if (config('zenith.enabled', true)) {
            Event::listen(JobProcessing::class, JobProcessingListener::class);
            Event::listen(JobProcessed::class, JobProcessedListener::class);
            Event::listen(JobFailed::class, JobFailedListener::class);
            Event::listen(JobExceptionOccurred::class, JobExceptionOccurredListener::class);
            Event::listen(Looping::class, WorkerLoopingListener::class);
        }

        // Schedule monitoring task
        if (config('zenith.enabled', true)) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('zenith:monitor')->everyMinute();
            });
        }
    }
}
