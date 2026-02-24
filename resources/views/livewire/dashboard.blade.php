<div wire:poll.5s="loadMetrics">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
        <p class="mt-1 text-sm text-gray-500">Real-time overview of your queue system</p>
    </div>

    <!-- Worker Metrics -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Workers</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    {{ $metrics['workers']['active'] ?? 0 }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Working</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    {{ $metrics['workers']['working'] ?? 0 }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Idle</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    {{ $metrics['workers']['idle'] ?? 0 }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Stuck</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold {{ ($metrics['workers']['stuck'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $metrics['workers']['stuck'] ?? 0 }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Metrics -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="text-sm font-medium text-gray-500">Pending Jobs</div>
                    </div>
                </div>
                <div class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ $metrics['jobs']['pending'] ?? 0 }}
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="text-sm font-medium text-gray-500">Processing</div>
                    </div>
                </div>
                <div class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ $metrics['jobs']['processing'] ?? 0 }}
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="text-sm font-medium text-gray-500">Completed Today</div>
                    </div>
                </div>
                <div class="mt-1 text-3xl font-semibold text-green-600">
                    {{ $metrics['jobs']['completed_today'] ?? 0 }}
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="text-sm font-medium text-gray-500">Total Failed</div>
                    </div>
                </div>
                <div class="mt-1 text-3xl font-semibold {{ ($metrics['jobs']['total_failed'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $metrics['jobs']['total_failed'] ?? 0 }}
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Performance</h3>
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="px-4 py-5 bg-gray-50 rounded-lg">
                    <dt class="text-sm font-medium text-gray-500 truncate">Avg Processing Time</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900">
                        @if(isset($metrics['performance']['avg_processing_time_ms']))
                            @if($metrics['performance']['avg_processing_time_ms'] < 1000)
                                {{ round($metrics['performance']['avg_processing_time_ms']) }}ms
                            @else
                                {{ round($metrics['performance']['avg_processing_time_ms'] / 1000, 2) }}s
                            @endif
                        @else
                            N/A
                        @endif
                    </dd>
                </div>

                <div class="px-4 py-5 bg-gray-50 rounded-lg">
                    <dt class="text-sm font-medium text-gray-500 truncate">Jobs per Hour</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ $metrics['performance']['jobs_per_hour'] ?? 0 }}
                    </dd>
                </div>

                <div class="px-4 py-5 bg-gray-50 rounded-lg">
                    <dt class="text-sm font-medium text-gray-500 truncate">Failure Rate</dt>
                    <dd class="mt-1 text-2xl font-semibold {{ ($metrics['performance']['failure_rate'] ?? 0) > 10 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $metrics['performance']['failure_rate'] ?? 0 }}%
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
