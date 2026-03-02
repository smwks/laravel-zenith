<?php

namespace SMWks\LaravelZenith\Listeners;

use Illuminate\Queue\Events\JobFailed;
use SMWks\LaravelZenith\Models\ZenithEvent;
use SMWks\LaravelZenith\Models\ZenithProcess;

class JobFailedListener
{
    public function handle(JobFailed $event): void
    {
        if (! config('zenith.enabled', true)) {
            return;
        }

        $payload = json_decode($event->job->getRawBody(), true);
        $uuid = $payload['uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        $worker = ZenithProcess::where('pid', getmypid())
            ->where('hostname', gethostname())
            ->whereIn('status', ['idle', 'working'])
            ->first();

        $startEvent = ZenithEvent::where('job_uuid', $uuid)
            ->where('event_type', 'started')
            ->orderBy('created_at', 'desc')
            ->first();

        $processingTimeMs = $startEvent
            ? $startEvent->created_at->diffInMilliseconds(now())
            : null;

        ZenithEvent::create([
            'job_id' => $event->job->getJobId(),
            'job_uuid' => $uuid,
            'event_type' => 'failed',
            'worker_id' => $worker?->id,
            'metadata' => [
                'exception' => get_class($event->exception),
                'message' => $event->exception->getMessage(),
                'processing_time_ms' => $processingTimeMs,
                'attempts' => $event->job->attempts(),
            ],
            'created_at' => now(),
        ]);

        $worker?->update(['status' => 'idle', 'current_job_id' => null]);
        $worker?->increment('jobs_failed');
    }
}
