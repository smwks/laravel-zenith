<div wire:poll.5s>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Queue Workers</h2>
        <p class="mt-1 text-sm text-gray-500">Active and terminated supervisors with their worker processes</p>
    </div>

    <div class="border-b border-gray-200 mb-6 flex items-end justify-between">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('tab', 'active')" class="{{ $tab === 'active' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active
            </button>
            <button wire:click="$set('tab', 'terminated')" class="{{ $tab === 'terminated' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Terminated
            </button>
        </nav>
        @if($hasStale)
            @can('manage', \SMWks\LaravelZenith\Zenith::class)
                <div class="pb-3">
                    <button wire:click="cleanUpStale" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:confirm="Mark all stale processes as terminated?" class="px-3 py-1.5 text-xs font-medium rounded bg-red-100 text-red-700 hover:bg-red-200">
                        <span wire:loading.remove wire:target="cleanUpStale">Clean Up Stale</span>
                        <span wire:loading wire:target="cleanUpStale">Cleaning…</span>
                    </button>
                </div>
            @endcan
        @endif
    </div>

    @if($supervisors->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <p class="text-gray-500">
                @if($tab === 'terminated')
                    No terminated processes.
                @else
                    No supervisors found. Start a worker with <code class="bg-gray-100 px-2 py-1 rounded">php artisan zenith:work</code>
                @endif
            </p>
        </div>
    @else
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supervisor / Worker</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Workers</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Heartbeat</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uptime</th>
                        @if($tab === 'active')
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($supervisors as $supervisor)
                        <tr class="bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ $supervisor->name ?? 'Unnamed' }} <span class="font-normal text-gray-500">(PID {{ $supervisor->pid }})</span>
                                </div>
                                <div class="text-xs text-gray-500">{{ $supervisor->hostname }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $supervisor->queue }}</div>
                                <div class="text-xs text-gray-500">{{ $supervisor->connection }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($supervisor->status === 'idle')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Idle</span>
                                @elseif($supervisor->status === 'working')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Working</span>
                                @elseif($supervisor->status === 'abandoned')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Abandoned</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Terminated</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $supervisor->childWorkers->count() }}
                                @if(!empty($supervisor->heartbeat_actions))
                                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-yellow-100 text-yellow-700 animate-pulse">pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $supervisor->childWorkers->sum('jobs_completed') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" title="{{ $supervisor->last_heartbeat_at->toDateTimeString() }}">
                                {{ $supervisor->last_heartbeat_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" title="{{ $supervisor->started_at->toDateTimeString() }}">
                                {{ $supervisor->started_at->diffForHumans(null, true) }}
                            </td>
                            @if($tab === 'active')
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @can('manage', \SMWks\LaravelZenith\Zenith::class)
                                        <div class="flex space-x-2">
                                                            @if(($supervisor->metadata['balance'] ?? 'fixed') === 'manual')
                                                @php
                                                    $workerCount = $supervisor->childWorkers->count();
                                                    $pending = array_count_values($supervisor->heartbeat_actions ?? []);
                                                    $effectiveCount = $workerCount + ($pending['scale_up'] ?? 0) - ($pending['scale_down'] ?? 0);
                                                    $atMax = $effectiveCount >= (int) ($supervisor->metadata['max_workers'] ?? PHP_INT_MAX);
                                                    $atMin = $effectiveCount <= (int) ($supervisor->metadata['min_workers'] ?? 0);
                                                @endphp
                                                <button wire:click="scaleUp('{{ $supervisor->id }}')" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" @disabled($atMax) class="px-2 py-1 text-xs font-medium rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span wire:loading.remove wire:target="scaleUp('{{ $supervisor->id }}')">Scale Up</span>
                                                    <span wire:loading wire:target="scaleUp('{{ $supervisor->id }}')">Queuing…</span>
                                                </button>
                                                <button wire:click="scaleDown('{{ $supervisor->id }}')" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" @disabled($atMin) class="px-2 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span wire:loading.remove wire:target="scaleDown('{{ $supervisor->id }}')">Scale Down</span>
                                                    <span wire:loading wire:target="scaleDown('{{ $supervisor->id }}')">Queuing…</span>
                                                </button>
                                            @endif
                                            <button wire:click="terminate('{{ $supervisor->id }}')" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-700 hover:bg-red-200 disabled:opacity-50">
                                                <span wire:loading.remove wire:target="terminate('{{ $supervisor->id }}')">Terminate</span>
                                                <span wire:loading wire:target="terminate('{{ $supervisor->id }}')">Queuing…</span>
                                            </button>
                                        </div>
                                    @endcan
                            </td>
                            @endif
                        </tr>
                        @foreach($supervisor->childWorkers as $worker)
                            <tr class="{{ !$worker->isHealthy() ? 'bg-red-50' : 'bg-white' }}">
                                <td class="pl-16 pr-6 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">PID {{ $worker->pid }}</div>
                                    <div class="text-xs text-gray-400">{{ $worker->hostname }}</div>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap"></td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    @if($worker->status === 'working')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Working</span>
                                    @elseif($worker->status === 'idle')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Idle</span>
                                    @elseif($worker->status === 'abandoned')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Abandoned</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Terminated</span>
                                    @endif

                                    @if(!$worker->isHealthy() && $worker->status !== 'terminated')
                                        <span class="ml-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Stuck</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap"></td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $worker->jobs_completed }}
                                    @if($worker->jobs_failed > 0)
                                        <span class="text-red-500">/ {{ $worker->jobs_failed }} failed</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500" title="{{ $worker->last_heartbeat_at->toDateTimeString() }}">
                                    {{ $worker->last_heartbeat_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500" title="{{ $worker->started_at->toDateTimeString() }}">
                                    {{ $worker->started_at->diffForHumans(null, true) }}
                                </td>
                                @if($tab === 'active')
                                    <td class="px-6 py-3 whitespace-nowrap"></td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
