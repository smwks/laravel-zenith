<?php

namespace SMWks\LaravelZenith\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use SMWks\LaravelZenith\Models\JobProcess;

class WorkersController extends Controller
{
    public function index(): JsonResponse
    {
        $workers = JobProcess::workerType()->with('jobHistory')
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function (JobProcess $worker) {
                return [
                    'id' => $worker->id,
                    'pid' => $worker->pid,
                    'hostname' => $worker->hostname,
                    'queue' => $worker->queue,
                    'connection' => $worker->connection,
                    'status' => $worker->status,
                    'is_healthy' => $worker->isHealthy(),
                    'is_working' => $worker->isWorking(),
                    'current_job_id' => $worker->current_job_id,
                    'started_at' => $worker->started_at->toIso8601String(),
                    'last_heartbeat_at' => $worker->last_heartbeat_at->toIso8601String(),
                    'uptime_seconds' => $worker->started_at->diffInSeconds(now()),
                    'metadata' => $worker->metadata,
                ];
            });

        return response()->json([
            'data' => $workers,
            'meta' => [
                'total' => $workers->count(),
                'active' => $workers->where('status', '!=', 'terminated')->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $worker = JobProcess::workerType()->with(['jobHistory', 'jobEvents'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $worker->id,
                'pid' => $worker->pid,
                'hostname' => $worker->hostname,
                'queue' => $worker->queue,
                'connection' => $worker->connection,
                'status' => $worker->status,
                'is_healthy' => $worker->isHealthy(),
                'is_working' => $worker->isWorking(),
                'current_job_id' => $worker->current_job_id,
                'started_at' => $worker->started_at->toIso8601String(),
                'last_heartbeat_at' => $worker->last_heartbeat_at->toIso8601String(),
                'metadata' => $worker->metadata,
                'completed_jobs' => $worker->jobHistory()->count(),
                'recent_events' => $worker->jobEvents()->latest()->limit(10)->get(),
            ],
        ]);
    }
}
