<?php

namespace SMWks\LaravelZenith\Services;

use Illuminate\Support\Facades\DB;
use SMWks\LaravelZenith\Models\ZenithEvent;
use SMWks\LaravelZenith\Models\ZenithHistory;
use SMWks\LaravelZenith\Models\ZenithProcess;

class MetricsService
{
    public function getDashboardMetrics(): array
    {
        return [
            'workers' => $this->getWorkerMetrics(),
            'jobs' => $this->getJobMetrics(),
            'performance' => $this->getPerformanceMetrics(),
        ];
    }

    public function getWorkerMetrics(): array
    {
        return [
            'active' => ZenithProcess::workerType()->active()->count(),
            'working' => ZenithProcess::workerType()->where('status', 'working')->count(),
            'idle' => ZenithProcess::workerType()->where('status', 'idle')->count(),
            'stuck' => ZenithProcess::workerType()->stuck()->count(),
        ];
    }

    public function getJobMetrics(): array
    {
        $now = now();

        return [
            'pending' => DB::table('jobs')->count(),
            'processing' => ZenithProcess::workerType()->where('status', 'working')->count(),
            'completed_today' => ZenithHistory::where('status', 'completed')
                ->whereDate('completed_at', $now->toDateString())
                ->count(),
            'failed_today' => DB::table('failed_jobs')
                ->whereDate('failed_at', $now->toDateString())
                ->count(),
            'total_failed' => DB::table('failed_jobs')->count(),
        ];
    }

    public function getPerformanceMetrics(): array
    {
        $completedToday = ZenithHistory::where('status', 'completed')
            ->whereDate('completed_at', now()->toDateString())
            ->get();

        $avgProcessingTime = $completedToday->avg('processing_time_ms');
        $totalProcessingTime = $completedToday->sum('processing_time_ms');

        return [
            'avg_processing_time_ms' => $avgProcessingTime ? round($avgProcessingTime, 2) : null,
            'total_processing_time_ms' => $totalProcessingTime,
            'jobs_per_hour' => $this->calculateJobsPerHour(),
            'failure_rate' => $this->calculateFailureRate(),
        ];
    }

    protected function calculateJobsPerHour(): float
    {
        $oneHourAgo = now()->subHour();

        $completed = ZenithEvent::where('event_type', 'completed')
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        return round($completed, 2);
    }

    protected function calculateFailureRate(): float
    {
        $today = now()->toDateString();

        $completed = ZenithHistory::where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->count();

        $failed = DB::table('failed_jobs')
            ->whereDate('failed_at', $today)
            ->count();

        $total = $completed + $failed;

        if ($total === 0) {
            return 0.0;
        }

        return round(($failed / $total) * 100, 2);
    }

    public function getQueueMetrics(?string $queue = null): array
    {
        $query = DB::table('jobs');

        if ($queue) {
            $query->where('queue', $queue);
        }

        return [
            'pending' => $query->count(),
            'queues' => DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->pluck('count', 'queue')
                ->toArray(),
        ];
    }
}
