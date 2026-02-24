<?php

namespace SMWks\LaravelZenith\Listeners;

use Illuminate\Queue\Events\JobProcessed;
use SMWks\LaravelZenith\Models\JobEvent;
use SMWks\LaravelZenith\Models\JobHistory;
use SMWks\LaravelZenith\Models\JobProcess;

class JobProcessedListener
{
    public function handle(JobProcessed $event): void
    {
        if (! config('zenith.enabled', true)) {
            return;
        }

        $payload = json_decode($event->job->getRawBody(), true);
        $uuid = $payload['uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        $worker = JobProcess::where('pid', getmypid())
            ->where('hostname', gethostname())
            ->where('status', 'working')
            ->first();

        $startEvent = JobEvent::where('job_uuid', $uuid)
            ->where('event_type', 'started')
            ->orderBy('created_at', 'desc')
            ->first();

        $processingTimeMs = $startEvent
            ? $startEvent->created_at->diffInMilliseconds(now())
            : null;

        JobEvent::create([
            'job_id' => $event->job->getJobId(),
            'job_uuid' => $uuid,
            'event_type' => 'completed',
            'worker_id' => $worker?->id,
            'metadata' => [
                'processing_time_ms' => $processingTimeMs,
            ],
            'created_at' => now(),
        ]);

        JobHistory::create([
            'job_id' => $event->job->getJobId(),
            'uuid' => $uuid,
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'payload' => $payload,
            'status' => 'completed',
            'worker_id' => $worker?->id,
            'started_at' => $startEvent?->created_at,
            'completed_at' => now(),
            'processing_time_ms' => $processingTimeMs,
            'attempts' => $event->job->attempts(),
        ]);

        $worker?->update(['status' => 'idle', 'current_job_id' => null]);
        $worker?->increment('jobs_completed');
    }
}
