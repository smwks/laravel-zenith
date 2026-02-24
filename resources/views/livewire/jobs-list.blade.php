<div wire:poll.10s>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Jobs</h2>
        <p class="mt-1 text-sm text-gray-500">Queue jobs and batches</p>
    </div>

    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('tab', 'pending')" class="{{ $tab === 'pending' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pending
            </button>
            <button wire:click="$set('tab', 'completed')" class="{{ $tab === 'completed' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Completed
            </button>
            <button wire:click="$set('tab', 'failed')" class="{{ $tab === 'failed' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Failed
            </button>
            <button wire:click="$set('tab', 'batches')" class="{{ $tab === 'batches' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Batches
            </button>
            <button wire:click="$set('tab', 'tests')" class="{{ $tab === 'tests' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Tests
            </button>
        </nav>
    </div>

    @if($tab === 'pending')
        <div class="mb-4 flex justify-end">
            <select wire:model.live="queue" class="block w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option value="">All Queues</option>
                @foreach($queues as $q)
                    <option value="{{ $q }}">{{ $q }}</option>
                @endforeach
            </select>
        </div>

        @if($jobs->total() === 0)
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">No pending jobs in the queue</p>
            </div>
        @else
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($jobs as $job)
                            @php
                                $payload = json_decode($job->payload, true);
                                $displayName = $payload['displayName'] ?? 'Unknown Job';
                                $batch = $batchMap->get($payload['batchId'] ?? '');
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $job->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->queue }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $displayName }}
                                    @if($batch)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-600">
                                            Batch: {{ $batch->name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->attempts }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::createFromTimestamp($job->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        @endif
    @endif

    @if($tab === 'completed')
        @if($jobs->total() === 0)
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">No completed jobs found</p>
            </div>
        @else
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processing Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($jobs as $job)
                            @php
                                $batch = $batchMap->get($job->payload['batchId'] ?? '');
                            @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $job->payload['displayName'] ?? 'Unknown Job' }}
                                    @if($batch)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-600">
                                            Batch: {{ $batch->name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->queue }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->attempts }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->processing_time ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->completed_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        @endif
    @endif

    @if($tab === 'failed')
        <div class="mb-4 flex justify-between items-center">
            <div class="flex gap-2">
                <select wire:model.live="queue" class="block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">All Queues</option>
                    @foreach($queues as $q)
                        <option value="{{ $q }}">{{ $q }}</option>
                    @endforeach
                </select>
                @if($jobs->total() > 0)
                    <button wire:click="retryAll" wire:confirm="Are you sure you want to retry all failed jobs?" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Retry All
                    </button>
                @endif
            </div>
        </div>

        @if(session()->has('message'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif

        @if($jobs->total() === 0)
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">No failed jobs. Great job!</p>
            </div>
        @else
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <ul class="divide-y divide-gray-200">
                    @foreach($jobs as $job)
                        @php
                            $payload = json_decode($job->payload, true);
                            $displayName = $payload['displayName'] ?? 'Unknown Job';
                        @endphp
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Failed
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $displayName }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Queue: {{ $job->queue }} | UUID: {{ $job->uuid }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-sm text-red-600 font-mono">
                                            {{ Str::limit($job->exception, 200) }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Failed {{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 flex space-x-2">
                                    <button wire:click="retryJob({{ $job->id }})" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Retry
                                    </button>
                                    <button wire:click="deleteJob({{ $job->id }})" wire:confirm="Are you sure you want to delete this failed job?" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        @endif
    @endif

    @if($tab === 'tests')
        @if(session()->has('message'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Single Job</h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="singleLogging" id="singleLogging" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="singleLogging" class="ml-2 block text-sm text-gray-700">Enable Logging</label>
                    </div>
                    <button wire:click="dispatchTestJob" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Dispatch Job
                    </button>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Batch Job</h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="batchLogging" id="batchLogging" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="batchLogging" class="ml-2 block text-sm text-gray-700">Enable Logging</label>
                    </div>
                    <div>
                        <label for="batchCount" class="block text-sm font-medium text-gray-700">Job Count</label>
                        <input type="number" wire:model="batchCount" id="batchCount" min="1" max="100" class="mt-1 block w-24 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <button wire:click="dispatchTestBatch" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Dispatch Batch
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($tab === 'batches')
        @if($jobs->total() === 0)
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">No batches found. The job_batches table may not exist.</p>
            </div>
        @else
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Finished</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($jobs as $batch)
                            @php
                                $progress = (int) (($batch->total_jobs - $batch->pending_jobs - $batch->failed_jobs) / max($batch->total_jobs, 1) * 100);
                            @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $batch->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $batch->total_jobs }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $batch->pending_jobs }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $batch->failed_jobs }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400 mt-1 block">{{ $progress }}%</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($batch->cancelled_at)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Cancelled</span>
                                    @elseif($batch->finished_at)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-600">Finished</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-600">Running</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::createFromTimestamp($batch->created_at)->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $batch->finished_at ? \Carbon\Carbon::createFromTimestamp($batch->finished_at)->diffForHumans() : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        @endif
    @endif
</div>
