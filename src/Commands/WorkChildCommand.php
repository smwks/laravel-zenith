<?php

namespace SMWks\LaravelZenith\Commands;

use Illuminate\Console\Command;
use SMWks\LaravelZenith\Models\JobProcess;

class WorkChildCommand extends Command
{
    protected $signature = 'zenith:child-work
                            {--supervisor-pid= : PID of the parent supervisor process}
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
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                            {--name=default : The name of the worker}';

    protected $description = 'Internal child worker command for Zenith monitoring';

    public function handle(): int
    {
        $supervisorPid = (int) $this->option('supervisor-pid');
        $worker = $this->registerWorker(getmypid(), $supervisorPid);

        $fd3 = @fopen('php://fd/3', 'w');

        if ($fd3) {
            fwrite($fd3, json_encode(['worker_id' => $worker->id])."\n");
            fflush($fd3);
            fclose($fd3);
        }

        return $this->call('queue:work', $this->buildQueueWorkCallOptions());
    }

    protected function registerWorker(int $pid, int $supervisorPid): JobProcess
    {
        $connection = config('queue.default');

        return JobProcess::create([
            'type' => 'worker',
            'pid' => $pid,
            'supervisor_pid' => $supervisorPid,
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

    protected function buildQueueWorkCallOptions(): array
    {
        $connection = config('queue.default');

        $options = [
            'connection' => $connection,
        ];

        if ($this->option('queue')) {
            $options['--queue'] = $this->option('queue');
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
            $options[$flag] = $this->option($option);
        }

        foreach (['force', 'once', 'stop-when-empty'] as $flag) {
            if ($this->option($flag)) {
                $options['--'.$flag] = true;
            }
        }

        return $options;
    }
}
