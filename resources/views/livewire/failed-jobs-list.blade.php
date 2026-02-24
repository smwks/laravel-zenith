<div wire:poll.10s>
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Failed Jobs</h2>
                <p class="mt-1 text-sm text-gray-500">Jobs that failed and need attention</p>
            </div>
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
            <div class="mt-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif
    </div>

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
</div>
