<?php

// config for SMWks/LaravelZenith
return [

    /*
    |--------------------------------------------------------------------------
    | Zenith Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether Zenith monitoring is enabled. When disabled,
    | no worker tracking or job monitoring will occur.
    |
    */
    'enabled' => env('ZENITH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the names of the database tables used by Zenith. This is
    | useful if you need to avoid table name collisions or prefer a
    | different naming convention.
    |
    */
    'table_names' => [
        'processes' => 'zenith_processes',
        'history' => 'zenith_history',
        'events' => 'zenith_events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the route prefix and middleware for the Zenith dashboard.
    |
    */
    'route' => [
        'domain' => env('ZENITH_ROUTE_DOMAIN', null),
        'prefix' => env('ZENITH_ROUTE_PREFIX', 'zenith'),
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supervisor Groups
    |--------------------------------------------------------------------------
    |
    | Define named supervisor groups. Each group configures the queue(s),
    | balance strategy, and worker bounds for a `zenith:work --name=X` invocation.
    |
    | balance: fixed     - starts at min_workers, no scaling, Scale Up/Down hidden in UI
    | balance: manual    - starts at min_workers, operator scales via dashboard
    | balance: automatic - scales to demand within min/max based on pending jobs
    |
    */
    'supervisors' => [
        'default' => [
            'connection' => env('QUEUE_CONNECTION', 'database'),
            'queue' => 'default',
            'balance' => env('ZENITH_BALANCE', 'fixed'),
            'min_workers' => env('ZENITH_MIN_WORKERS', 1),
            'max_workers' => env('ZENITH_MAX_WORKERS', 1),
            'jobs_per_worker' => env('ZENITH_JOBS_PER_WORKER', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Worker Heartbeat Interval
    |--------------------------------------------------------------------------
    |
    | The interval (in seconds) at which workers send heartbeat signals.
    | Recommended: 30-60 seconds for most applications.
    |
    */
    'heartbeat_interval' => env('ZENITH_HEARTBEAT_INTERVAL', 30),

    /*
    |--------------------------------------------------------------------------
    | Stuck Job Threshold
    |--------------------------------------------------------------------------
    |
    | The time (in seconds) after which a job is considered stuck if no
    | heartbeat is received. Typically 2-3x the heartbeat interval.
    |
    */
    'stuck_job_threshold' => env('ZENITH_STUCK_JOB_THRESHOLD', 120),

    /*
    |--------------------------------------------------------------------------
    | Auto-Retry Stuck Jobs
    |--------------------------------------------------------------------------
    |
    | Automatically retry jobs that are detected as stuck when their
    | worker has stopped responding.
    |
    */
    'auto_retry_stuck_jobs' => env('ZENITH_AUTO_RETRY_STUCK_JOBS', false),

    /*
    |--------------------------------------------------------------------------
    | Job Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep job history records (in days) before pruning.
    |
    */
    'retention' => [
        'completed_jobs' => env('ZENITH_RETAIN_COMPLETED_JOBS', 7),
        'failed_jobs' => env('ZENITH_RETAIN_FAILED_JOBS', 30),
        'job_events' => env('ZENITH_RETAIN_JOB_EVENTS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for Zenith tables. Leave null to use
    | the default application connection.
    |
    */
    'database_connection' => env('ZENITH_DB_CONNECTION', null),

];
