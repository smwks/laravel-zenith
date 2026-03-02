<?php

namespace SMWks\LaravelZenith\Listeners;

use Illuminate\Queue\Events\Looping;
use SMWks\LaravelZenith\Models\ZenithProcess;

class WorkerLoopingListener
{
    protected static float $lastHeartbeat = 0.0;

    public function handle(Looping $event): void
    {
        if (! config('zenith.enabled', true)) {
            return;
        }

        $now = microtime(true);
        $interval = config('zenith.heartbeat_interval', 30);

        if ($now - self::$lastHeartbeat < $interval) {
            return;
        }

        $worker = ZenithProcess::where('pid', getmypid())
            ->where('hostname', gethostname())
            ->whereIn('status', ['idle', 'working'])
            ->first();

        if (! $worker) {
            return;
        }

        $worker->update(['last_heartbeat_at' => now()]);

        ZenithProcess::where('pid', $worker->supervisor_pid)
            ->where('hostname', $worker->hostname)
            ->update(['last_heartbeat_at' => now()]);

        self::$lastHeartbeat = $now;
    }
}
