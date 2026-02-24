<?php

namespace SMWks\LaravelZenith\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use SMWks\LaravelZenith\Models\JobHistory;
use SMWks\LaravelZenith\Services\ZenithJobService;

class JobsController extends Controller
{
    public function __construct(
        protected ZenithJobService $jobService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('jobs');

        if ($queue = $request->get('queue')) {
            $query->where('queue', $queue);
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($jobs);
    }

    public function show(int $id): JsonResponse
    {
        $job = DB::table('jobs')->where('id', $id)->first();

        if (! $job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $payload = json_decode($job->payload, true);

        return response()->json([
            'data' => [
                'id' => $job->id,
                'queue' => $job->queue,
                'payload' => $payload,
                'attempts' => $job->attempts,
                'reserved_at' => $job->reserved_at,
                'available_at' => $job->available_at,
                'created_at' => $job->created_at,
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $query = JobHistory::with('worker');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($queue = $request->get('queue')) {
            $query->where('queue', $queue);
        }

        $jobs = $query->orderBy('completed_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($jobs);
    }

    public function failed(Request $request): JsonResponse
    {
        $query = DB::table('failed_jobs');

        if ($queue = $request->get('queue')) {
            $query->where('queue', $queue);
        }

        $jobs = $query->orderBy('failed_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($jobs);
    }

    public function cancel(int $id): JsonResponse
    {
        $success = $this->jobService->cancelJob($id);

        if (! $success) {
            return response()->json(['error' => 'Job not found or already processed'], 404);
        }

        return response()->json(['message' => 'Job cancelled successfully']);
    }

    public function retry(int $id): JsonResponse
    {
        $success = $this->jobService->retryFailedJob($id);

        if (! $success) {
            return response()->json(['error' => 'Failed job not found'], 404);
        }

        return response()->json(['message' => 'Job retried successfully']);
    }

    public function retryAll(Request $request): JsonResponse
    {
        $queue = $request->get('queue');
        $count = $this->jobService->retryAllFailedJobs($queue);

        return response()->json([
            'message' => "Retried {$count} job(s) successfully",
            'count' => $count,
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        $success = $this->jobService->deleteFailedJob($id);

        if (! $success) {
            return response()->json(['error' => 'Failed job not found'], 404);
        }

        return response()->json(['message' => 'Failed job deleted successfully']);
    }
}
