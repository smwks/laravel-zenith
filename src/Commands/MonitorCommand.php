<?php

namespace SMWks\LaravelZenith\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SMWks\LaravelZenith\Models\JobProcess;
use SMWks\LaravelZenith\Services\ZenithJobService;

class MonitorCommand extends Command
{
    protected $signature = 'zenith:monitor
                            {--auto-retry : Automatically retry stuck jobs}';

    protected $description = 'Monitor workers and detect stuck jobs';

    public function handle(ZenithJobService $jobService): int
    {
        if (! config('zenith.enabled', true)) {
            return self::SUCCESS;
        }

        $this->info('Monitoring workers...');

        $stuckWorkers = JobProcess::workerType()->stuck()->get();

        if ($stuckWorkers->isEmpty()) {
            $this->info('All workers are healthy.');

            return self::SUCCESS;
        }

        $this->warn("Found {$stuckWorkers->count()} stuck worker(s)");

        foreach ($stuckWorkers as $worker) {
            $this->handleStuckWorker($worker, $jobService);
        }

        return self::SUCCESS;
    }

    protected function handleStuckWorker(JobProcess $worker, ZenithJobService $jobService): void
    {
        $this->line("Worker #{$worker->id} (PID: {$worker->pid}) on {$worker->hostname} is stuck");
        $this->line("  Last heartbeat: {$worker->last_heartbeat_at->diffForHumans()}");

        if ($this->isProcessDead($worker)) {
            $this->line('  Process is dead, marking as terminated');

            if ($worker->current_job_id) {
                $this->handleStuckJob($worker, $jobService);
            }

            $worker->update([
                'status' => 'terminated',
                'current_job_id' => null,
            ]);
        } else {
            $this->line('  Process is still running but not responding');
        }
    }

    protected function handleStuckJob(JobProcess $worker, ZenithJobService $jobService): void
    {
        $this->line("  Job #{$worker->current_job_id} is stuck");

        $autoRetry = $this->option('auto-retry') || config('zenith.auto_retry_stuck_jobs', false);

        if ($autoRetry) {
            try {
                $job = DB::table('jobs')->where('id', $worker->current_job_id)->first();

                if ($job) {
                    DB::table('jobs')
                        ->where('id', $worker->current_job_id)
                        ->update([
                            'reserved_at' => null,
                            'attempts' => $job->attempts,
                        ]);

                    $this->info('  Job released back to queue');
                } else {
                    $this->warn('  Job not found in queue (may have completed)');
                }
            } catch (\Exception $e) {
                $this->error("  Failed to retry job: {$e->getMessage()}");
            }
        }
    }

    protected function isProcessDead(JobProcess $worker): bool
    {
        if (gethostname() !== $worker->hostname) {
            return false;
        }

        if (! function_exists('posix_getpgid')) {
            return false;
        }

        return posix_getpgid($worker->pid) === false;
    }
}
