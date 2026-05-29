<?php

namespace SMWks\LaravelZenith\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use SMWks\LaravelZenith\Models\ZenithProcess;
use SMWks\SuperProcess\Child;
use SMWks\SuperProcess\CreateReason;
use SMWks\SuperProcess\ExitReason;
use SMWks\SuperProcess\SuperProcess;

class WorkCommand extends Command
{
    protected $signature = 'zenith:work
                            {--name=default : The name of the worker}
                            {--queue= : The names of the queues to work}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--max-jobs=0 : The number of jobs to process before stopping}
                            {--max-time=0 : The maximum number of seconds the worker should run}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--rest=0 : Number of seconds to rest between jobs}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}';

    protected $description = 'Start processing jobs on the queue with Zenith monitoring';

    public function handle(): int
    {
        if (! config('zenith.enabled', true)) {
            $this->error('Zenith is disabled. Use queue:work instead or enable Zenith in config.');

            return self::FAILURE;
        }

        $config = $this->resolveSupervisorConfig();
        $balance = $config['balance'];
        $minWorkers = $config['minWorkers'];
        $maxWorkers = $config['maxWorkers'];
        $jobsPerWorker = $config['jobsPerWorker'];
        $resolvedQueue = $config['queue'];
        $resolvedConnection = $config['connection'];

        $supervisor = null;
        $workers = [];
        $sp = new SuperProcess;

        $sp->command($this->buildQueueWorkCommand($resolvedQueue))
            ->scaleLimits(min: $minWorkers, max: $maxWorkers)
            ->heartbeat(
                intervalSeconds: config('zenith.heartbeat_interval', 30),
                callback: function () use (&$supervisor, &$workers, &$minWorkers, &$maxWorkers, $balance, $jobsPerWorker, $resolvedQueue, $resolvedConnection, $sp): void {
                    $supervisor?->update(['last_heartbeat_at' => now()]);

                    foreach ($workers as $worker) {
                        $worker?->update(['last_heartbeat_at' => now()]);
                    }

                    $supervisor?->refresh();

                    foreach ($supervisor?->heartbeat_actions ?? [] as $action) {
                        if ($action === 'scale_up' && $balance === 'manual') {
                            $minWorkers++;
                            $maxWorkers++;
                            $sp->scaleLimits($minWorkers, $maxWorkers)->scaleUp();
                        } elseif ($action === 'scale_down' && $balance === 'manual') {
                            $minWorkers = max(0, $minWorkers - 1);
                            $maxWorkers = max(0, $maxWorkers - 1);
                            $sp->scaleLimits($minWorkers, $maxWorkers)->scaleDown();
                        } elseif ($action === 'terminate') {
                            $sp->scaleLimits(0, 0);
                            posix_kill(getmypid(), SIGTERM);
                        }
                    }

                    $supervisor?->update(['heartbeat_actions' => null]);

                    if ($balance === 'automatic') {
                        $pendingJobs = Queue::connection($resolvedConnection)->size($resolvedQueue ?? 'default');
                        $activeWorkers = count($workers);
                        $targetWorkers = max($minWorkers, min($maxWorkers, (int) ceil($pendingJobs / $jobsPerWorker)));

                        if ($targetWorkers > $activeWorkers) {
                            $stepsUp = $targetWorkers - $activeWorkers;
                            for ($i = 0; $i < $stepsUp; $i++) {
                                $sp->scaleLimits($minWorkers, $maxWorkers)->scaleUp();
                            }
                        } elseif ($targetWorkers < $activeWorkers && $pendingJobs === 0) {
                            $sp->scaleLimits($minWorkers, $maxWorkers)->scaleDown();
                        }
                    }
                }
            )
            ->onChildCreate(function (Child $child, CreateReason $reason) use (&$workers): void {
                $workers[$child->pid] = null;
            })
            ->onChildMessage(function (Child $child, array $message) use (&$workers): void {
                if (isset($message['worker_id'])) {
                    $workers[$child->pid] = ZenithProcess::find($message['worker_id']);
                }
            })
            ->onChildExit(function (Child $child, ExitReason $reason) use ($sp, &$workers): void {
                $process = $workers[$child->pid]
                    ?? ZenithProcess::workerType()->where('pid', $child->pid)->where('hostname', gethostname())->first();

                $process?->update(['status' => 'terminated', 'current_job_id' => null]);
                unset($workers[$child->pid]);

                if ($reason === ExitReason::Killed) {
                    return;
                }

                $sp->scaleLimits(0, 0);
                posix_kill(getmypid(), SIGTERM);
            })
            ->onShutdown(function () use (&$workers, &$supervisor): void {
                foreach ($workers as $pid => $worker) {
                    $process = $worker
                        ?? ZenithProcess::workerType()->where('pid', $pid)->where('hostname', gethostname())->first();
                    $process?->update(['status' => 'terminated', 'current_job_id' => null]);
                }

                $supervisor?->update(['status' => 'terminated', 'current_job_id' => null]);
            });

        $supervisor = $this->registerSupervisor($balance, $minWorkers, $maxWorkers, $resolvedQueue, $resolvedConnection);

        $sp->run();

        return self::SUCCESS;
    }

    protected function resolveSupervisorConfig(): array
    {
        $supervisorConfig = config("zenith.supervisors.{$this->option('name')}", []);
        $balance = $supervisorConfig['balance'] ?? 'fixed';
        $minWorkers = (int) ($supervisorConfig['min_workers'] ?? 1);
        $maxWorkers = ($balance === 'fixed') ? $minWorkers : (int) ($supervisorConfig['max_workers'] ?? 1);
        $jobsPerWorker = (int) ($supervisorConfig['jobs_per_worker'] ?? 5);
        $queue = $this->option('queue') ?: ($supervisorConfig['queue'] ?? null);
        $connection = $supervisorConfig['connection'] ?? config('queue.default');

        return compact('balance', 'minWorkers', 'maxWorkers', 'jobsPerWorker', 'queue', 'connection');
    }

    protected function registerSupervisor(string $balance, int $minWorkers, int $maxWorkers, ?string $resolvedQueue, string $resolvedConnection): ZenithProcess
    {
        return ZenithProcess::create([
            'type' => 'supervisor',
            'name' => $this->option('name'),
            'pid' => getmypid(),
            'supervisor_pid' => null,
            'hostname' => gethostname(),
            'queue' => $resolvedQueue ?: config("queue.connections.{$resolvedConnection}.queue", 'default'),
            'connection' => $resolvedConnection,
            'started_at' => now(),
            'last_heartbeat_at' => now(),
            'status' => 'idle',
            'metadata' => [
                'memory_limit' => $this->option('memory'),
                'timeout' => $this->option('timeout'),
                'sleep' => $this->option('sleep'),
                'tries' => $this->option('tries'),
                'balance' => $balance,
                'min_workers' => $minWorkers,
                'max_workers' => $maxWorkers,
            ],
        ]);
    }

    protected function buildQueueWorkCommand(?string $resolvedQueue = null): string
    {
        $args = [PHP_BINARY, base_path('artisan'), 'zenith:child-work', '--supervisor-pid='.getmypid()];

        if ($resolvedQueue) {
            $args[] = '--queue='.$resolvedQueue;
        }

        foreach ([
            'name' => '--name',
            'memory' => '--memory',
            'timeout' => '--timeout',
            'sleep' => '--sleep',
            'tries' => '--tries',
            'backoff' => '--backoff',
            'max-jobs' => '--max-jobs',
            'max-time' => '--max-time',
            'rest' => '--rest',
        ] as $option => $flag) {
            $args[] = $flag.'='.$this->option($option);
        }

        foreach (['force', 'once', 'stop-when-empty'] as $flag) {
            if ($this->option($flag)) {
                $args[] = '--'.$flag;
            }
        }

        return 'exec '.implode(' ', array_map('escapeshellarg', $args));
    }
}
