<?php

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Str;
use SMWks\LaravelZenith\Support\Tracing\Tracer;

function makeTracedSyncJob(string $displayName = 'App\Jobs\ExampleJob'): SyncJob
{
    $payload = json_encode([
        'uuid' => (string) Str::uuid(),
        'displayName' => $displayName,
        'job' => 'Illuminate\Queue\CallQueuedHandler@call',
        'maxTries' => null,
        'timeout' => null,
        'data' => ['commandName' => $displayName],
    ]);

    return new SyncJob(app(), $payload, 'sync', 'default');
}

it('starts and closes a trace span for a successfully processed job', function () {
    $tracer = Mockery::mock(Tracer::class);
    $tracer->shouldReceive('startSpan')
        ->once()
        ->with('zenith.job.process', 'App\Jobs\ExampleJob', Mockery::type('array'));
    $tracer->shouldReceive('closeSpan')->once();
    $tracer->shouldNotReceive('tagError');

    app()->instance(Tracer::class, $tracer);

    $job = makeTracedSyncJob();

    event(new JobProcessing('sync', $job));
    event(new JobProcessed('sync', $job));
})->group('job-tracing');

it('tags an error and closes the span when a job will be retried', function () {
    $tracer = Mockery::mock(Tracer::class);
    $tracer->shouldReceive('startSpan')->once();
    $tracer->shouldReceive('tagError')->once()->with(Mockery::type(RuntimeException::class));
    $tracer->shouldReceive('closeSpan')->once();

    app()->instance(Tracer::class, $tracer);

    $job = makeTracedSyncJob();

    event(new JobProcessing('sync', $job));
    event(new JobExceptionOccurred('sync', $job, new RuntimeException('boom')));
})->group('job-tracing');

it('does not double close the span when a job permanently fails', function () {
    $tracer = Mockery::mock(Tracer::class);
    $tracer->shouldReceive('startSpan')->once();
    $tracer->shouldReceive('tagError')->once()->with(Mockery::type(RuntimeException::class));
    $tracer->shouldReceive('closeSpan')->once();

    app()->instance(Tracer::class, $tracer);

    $job = makeTracedSyncJob();
    $exception = new RuntimeException('boom');

    event(new JobProcessing('sync', $job));

    $job->markAsFailed();
    event(new JobFailed('sync', $job, $exception));
    event(new JobExceptionOccurred('sync', $job, $exception));
})->group('job-tracing');
