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
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the route prefix and middleware for the Zenith dashboard.
    |
    */
    'route' => [
        'prefix' => env('ZENITH_ROUTE_PREFIX', 'zenith'),
        'middleware' => ['web', 'auth'],
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
