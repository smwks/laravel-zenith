<?php

namespace SMWks\LaravelZenith\Listeners;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Str;
use SMWks\LaravelZenith\Models\ZenithEvent;
use SMWks\LaravelZenith\Models\ZenithProcess;
use SMWks\LaravelZenith\Support\Tracing\Tracer;

class JobProcessingListener
{
    public function __construct(
        protected Tracer $tracer
    ) {}

    public function handle(JobProcessing $event): void
    {
        if (! config('zenith.enabled', true)) {
            return;
        }

        $payload = json_decode($event->job->getRawBody(), true);
        $uuid = $payload['uuid'] ?? Str::uuid()->toString();

        $this->tracer->startSpan('zenith.job.process', $event->job->resolveName(), [
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'job.uuid' => $uuid,
            'job.attempts' => $event->job->attempts(),
        ]);

        $worker = ZenithProcess::where('pid', getmypid())
            ->where('hostname', gethostname())
            ->whereIn('status', ['idle', 'working'])
            ->first();

        $worker?->update(['status' => 'working', 'current_job_id' => $event->job->getJobId()]);

        ZenithEvent::create([
            'job_id' => $event->job->getJobId(),
            'job_uuid' => $uuid,
            'event_type' => 'started',
            'worker_id' => $worker?->id,
            'metadata' => [
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'attempts' => $event->job->attempts(),
                'timeout' => $event->job->timeout(),
            ],
            'created_at' => now(),
        ]);
    }
}
