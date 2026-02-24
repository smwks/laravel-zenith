<?php

namespace SMWks\LaravelZenith\Services;

use Illuminate\Support\Facades\DB;
use SMWks\LaravelZenith\Models\JobEvent;
use SMWks\LaravelZenith\Models\JobHistory;

class ZenithJobService
{
    /**
     * Cancel a job from the queue
     */
    public function cancelJob(int $jobId): bool
    {
        $job = DB::table('jobs')->where('id', $jobId)->first();

        if (! $job) {
            return false;
        }

        // Extract UUID for tracking
        $payload = json_decode($job->payload, true);
        $uuid = $payload['uuid'] ?? null;

        // Record cancellation event
        if ($uuid) {
            JobEvent::create([
                'job_id' => $jobId,
                'job_uuid' => $uuid,
                'event_type' => 'cancelled',
                'metadata' => ['cancelled_by' => 'user'],
                'created_at' => now(),
            ]);

            // Move to history
            JobHistory::create([
                'job_id' => $jobId,
                'uuid' => $uuid,
                'queue' => $job->queue,
                'payload' => $payload,
                'status' => 'cancelled',
                'attempts' => $job->attempts,
                'completed_at' => now(),
            ]);
        }

        // Delete from queue
        return DB::table('jobs')->where('id', $jobId)->delete() > 0;
    }

    /**
     * Cancel multiple jobs
     */
    public function cancelJobs(array $jobIds): int
    {
        $cancelled = 0;

        foreach ($jobIds as $jobId) {
            if ($this->cancelJob($jobId)) {
                $cancelled++;
            }
        }

        return $cancelled;
    }

    /**
     * Retry a failed job
     */
    public function retryFailedJob(int $failedJobId): bool
    {
        $failedJob = DB::table('failed_jobs')->where('id', $failedJobId)->first();

        if (! $failedJob) {
            return false;
        }

        // Re-queue the job
        DB::table('jobs')->insert([
            'queue' => $failedJob->queue,
            'payload' => $failedJob->payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Record retry event
        $payload = json_decode($failedJob->payload, true);
        $uuid = $payload['uuid'] ?? null;

        if ($uuid) {
            JobEvent::create([
                'job_uuid' => $uuid,
                'event_type' => 'retried',
                'metadata' => ['retried_from' => 'failed_jobs'],
                'created_at' => now(),
            ]);
        }

        // Remove from failed jobs
        DB::table('failed_jobs')->where('id', $failedJobId)->delete();

        return true;
    }

    /**
     * Retry multiple failed jobs
     */
    public function retryFailedJobs(array $failedJobIds): int
    {
        $retried = 0;

        foreach ($failedJobIds as $failedJobId) {
            if ($this->retryFailedJob($failedJobId)) {
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * Retry all failed jobs
     */
    public function retryAllFailedJobs(?string $queue = null): int
    {
        $query = DB::table('failed_jobs');

        if ($queue) {
            $query->where('queue', $queue);
        }

        $failedJobs = $query->pluck('id');

        return $this->retryFailedJobs($failedJobs->toArray());
    }

    /**
     * Release a reserved job back to the queue
     */
    public function releaseJob(int $jobId): bool
    {
        return DB::table('jobs')
            ->where('id', $jobId)
            ->update([
                'reserved_at' => null,
                'available_at' => time(),
            ]) > 0;
    }

    /**
     * Pause a job (mark as unavailable)
     */
    public function pauseJob(int $jobId): bool
    {
        // Set available_at far in the future
        return DB::table('jobs')
            ->where('id', $jobId)
            ->update([
                'reserved_at' => null,
                'available_at' => time() + (86400 * 365), // 1 year
            ]) > 0;
    }

    /**
     * Resume a paused job
     */
    public function resumeJob(int $jobId): bool
    {
        return DB::table('jobs')
            ->where('id', $jobId)
            ->update([
                'available_at' => time(),
            ]) > 0;
    }

    /**
     * Delete a failed job
     */
    public function deleteFailedJob(int $failedJobId): bool
    {
        return DB::table('failed_jobs')->where('id', $failedJobId)->delete() > 0;
    }

    /**
     * Delete multiple failed jobs
     */
    public function deleteFailedJobs(array $failedJobIds): int
    {
        return DB::table('failed_jobs')->whereIn('id', $failedJobIds)->delete();
    }

    /**
     * Prune old completed jobs from history
     */
    public function pruneCompletedJobs(?int $days = null): int
    {
        $days = $days ?? config('zenith.retention.completed_jobs', 7);
        $cutoff = now()->subDays($days);

        return JobHistory::where('status', 'completed')
            ->where('completed_at', '<', $cutoff)
            ->delete();
    }

    /**
     * Prune old failed jobs
     */
    public function pruneFailedJobs(?int $days = null): int
    {
        $days = $days ?? config('zenith.retention.failed_jobs', 30);
        $cutoff = now()->subDays($days);

        return DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoff)
            ->delete();
    }

    /**
     * Prune old job events
     */
    public function pruneJobEvents(?int $days = null): int
    {
        $days = $days ?? config('zenith.retention.job_events', 7);
        $cutoff = now()->subDays($days);

        return JobEvent::where('created_at', '<', $cutoff)->delete();
    }

    /**
     * Prune all old data
     */
    public function pruneAll(): array
    {
        return [
            'completed_jobs' => $this->pruneCompletedJobs(),
            'failed_jobs' => $this->pruneFailedJobs(),
            'job_events' => $this->pruneJobEvents(),
        ];
    }
}
