<div wire:poll.5s>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Queue Workers</h2>
        <p class="mt-1 text-sm text-gray-500">Active and terminated supervisors with their worker processes</p>
    </div>

    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('tab', 'active')" class="{{ $tab === 'active' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active
            </button>
            <button wire:click="$set('tab', 'terminated')" class="{{ $tab === 'terminated' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Terminated
            </button>
        </nav>
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Terminated</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $supervisor->childWorkers->count() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $supervisor->childWorkers->sum('jobs_completed') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $supervisor->last_heartbeat_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $supervisor->started_at->diffForHumans(null, true) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($tab === 'active')
                                    <div class="flex space-x-2">
                                        <button wire:click="scaleUp('{{ $supervisor->id }}')" class="px-2 py-1 text-xs font-medium rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200">
                                            Scale Up
                                        </button>
                                        <button wire:click="scaleDown('{{ $supervisor->id }}')" class="px-2 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200">
                                            Scale Down
                                        </button>
                                        <button wire:click="terminate('{{ $supervisor->id }}')" class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-700 hover:bg-red-200">
                                            Terminate
                                        </button>
                                    </div>
                                @endif
                            </td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $worker->last_heartbeat_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $worker->started_at->diffForHumans(null, true) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap"></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
