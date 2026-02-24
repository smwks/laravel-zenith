<?php

namespace SMWks\LaravelZenith\Listeners;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Support\Facades\Log;

class JobExceptionOccurredListener
{
    public function handle(JobExceptionOccurred $event): void
    {
        if (! config('zenith.enabled', true)) {
            return;
        }

        $payload = json_decode($event->job->getRawBody(), true);
        $uuid = $payload['uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        // Log the exception for debugging
        Log::warning('Zenith: Job exception occurred', [
            'uuid' => $uuid,
            'job_id' => $event->job->getJobId(),
            'exception' => get_class($event->exception),
            'message' => $event->exception->getMessage(),
            'attempts' => $event->job->attempts(),
        ]);
    }
}
