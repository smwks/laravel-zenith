<?php

namespace SMWks\LaravelZenith\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SMWks\LaravelZenith\Services\MetricsService;

class MetricsController extends Controller
{
    public function __construct(
        protected MetricsService $metricsService
    ) {}

    public function index(): JsonResponse
    {
        $metrics = $this->metricsService->getDashboardMetrics();

        return response()->json(['data' => $metrics]);
    }

    public function workers(): JsonResponse
    {
        $metrics = $this->metricsService->getWorkerMetrics();

        return response()->json(['data' => $metrics]);
    }

    public function jobs(): JsonResponse
    {
        $metrics = $this->metricsService->getJobMetrics();

        return response()->json(['data' => $metrics]);
    }

    public function performance(): JsonResponse
    {
        $metrics = $this->metricsService->getPerformanceMetrics();

        return response()->json(['data' => $metrics]);
    }

    public function queues(Request $request): JsonResponse
    {
        $queue = $request->get('queue');
        $metrics = $this->metricsService->getQueueMetrics($queue);

        return response()->json(['data' => $metrics]);
    }
}
