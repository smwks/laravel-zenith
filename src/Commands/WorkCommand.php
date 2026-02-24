<?php

namespace SMWks\LaravelZenith\Commands;

use Illuminate\Console\Command;
use SMWks\LaravelZenith\Models\JobProcess;
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

        $supervisor = null;
        $workers = [];
        $minWorkers = 1;
        $maxWorkers = 1;
        $sp = new SuperProcess;

        $sp->command($this->buildQueueWorkCommand())
            ->scaleLimits(min: 1, max: 1)
            ->heartbeat(
                intervalSeconds: config('zenith.heartbeat_interval', 30),
                callback: function () use (&$supervisor, &$workers, &$minWorkers, &$maxWorkers, $sp): void {
                    $supervisor?->update(['last_heartbeat_at' => now()]);

                    foreach ($workers as $worker) {
                        $worker?->update(['last_heartbeat_at' => now()]);
                    }

                    $supervisor?->refresh();

                    foreach ($supervisor?->heartbeat_actions ?? [] as $action) {
                        if ($action === 'scale_up') {
                            $minWorkers++;
                            $maxWorkers++;
                            $sp->scaleLimits($minWorkers, $maxWorkers)->scaleUp();
                        } elseif ($action === 'scale_down') {
                            $minWorkers = max(0, $minWorkers - 1);
                            $maxWorkers = max(0, $maxWorkers - 1);
                            $sp->scaleLimits($minWorkers, $maxWorkers)->scaleDown();
                        } elseif ($action === 'terminate') {
                            $sp->scaleLimits(0, 0);
                            posix_kill(getmypid(), SIGTERM);
                        }
                    }

                    $supervisor?->update(['heartbeat_actions' => null]);
                }
            )
            ->onChildCreate(function (Child $child, CreateReason $reason) use (&$workers): void {
                $workers[$child->pid] = null;
            })
            ->onChildMessage(function (Child $child, array $message) use (&$workers): void {
                if (isset($message['worker_id'])) {
                    $workers[$child->pid] = JobProcess::find($message['worker_id']);
                }
            })
            ->onChildExit(function (Child $child, ExitReason $reason) use ($sp, &$workers): void {
                $process = $workers[$child->pid]
                    ?? JobProcess::workerType()->where('pid', $child->pid)->where('hostname', gethostname())->first();

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
                        ?? JobProcess::workerType()->where('pid', $pid)->where('hostname', gethostname())->first();
                    $process?->update(['status' => 'terminated', 'current_job_id' => null]);
                }

                $supervisor?->update(['status' => 'terminated', 'current_job_id' => null]);
            });

        $supervisor = $this->registerSupervisor();

        $sp->run();

        return self::SUCCESS;
    }

    protected function registerSupervisor(): JobProcess
    {
        $connection = config('queue.default');

        return JobProcess::create([
            'type' => 'supervisor',
            'name' => $this->option('name'),
            'pid' => getmypid(),
            'supervisor_pid' => null,
            'hostname' => gethostname(),
            'queue' => $this->option('queue') ?: config("queue.connections.{$connection}.queue", 'default'),
            'connection' => $connection,
            'started_at' => now(),
            'last_heartbeat_at' => now(),
            'status' => 'idle',
            'metadata' => [
                'memory_limit' => $this->option('memory'),
                'timeout' => $this->option('timeout'),
                'sleep' => $this->option('sleep'),
                'tries' => $this->option('tries'),
            ],
        ]);
    }

    protected function buildQueueWorkCommand(): string
    {
        $args = [PHP_BINARY, base_path('artisan'), 'zenith:child-work', '--supervisor-pid='.getmypid()];

        if ($this->option('queue')) {
            $args[] = '--queue='.$this->option('queue');
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
