<?php

namespace SMWks\LaravelZenith\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use SMWks\LaravelZenith\Models\ZenithProcess;
use SMWks\LaravelZenith\Zenith;

class WorkersList extends Component
{
    #[Url]
    public string $tab = 'active';

    public function render()
    {
        $staleThreshold = now()->subSeconds(config('zenith.heartbeat_interval', 30) * 2);

        $hasStale = ZenithProcess::whereNotIn('status', ['terminated', 'abandoned'])
            ->where('last_heartbeat_at', '<', $staleThreshold)
            ->exists();

        if ($this->tab === 'active') {
            $supervisors = ZenithProcess::supervisorType()->active()
                ->with(['childWorkers' => fn ($q) => $q->whereNotIn('status', ['terminated', 'abandoned'])])
                ->orderBy('started_at', 'desc')
                ->get()
                ->each(fn ($s) => $s->setRelation(
                    'childWorkers',
                    $s->childWorkers->where('hostname', $s->hostname)->values()
                ));
        } else {
            $terminatedSupervisors = ZenithProcess::supervisorType()
                ->whereIn('status', ['terminated', 'abandoned'])
                ->with('childWorkers')
                ->orderBy('started_at', 'desc')
                ->get()
                ->each(fn ($s) => $s->setRelation(
                    'childWorkers',
                    $s->childWorkers->where('hostname', $s->hostname)->values()
                ));

            $activeSupervisorsWithTerminatedWorkers = ZenithProcess::supervisorType()
                ->active()
                ->with(['childWorkers' => fn ($q) => $q->whereIn('status', ['terminated', 'abandoned'])])
                ->orderBy('started_at', 'desc')
                ->get()
                ->map(fn ($s) => $s->setRelation(
                    'childWorkers',
                    $s->childWorkers->where('hostname', $s->hostname)->values()
                ))
                ->filter(fn ($s) => $s->childWorkers->isNotEmpty());

            $supervisors = $activeSupervisorsWithTerminatedWorkers->merge($terminatedSupervisors);
        }

        return view('laravel-zenith::livewire.workers-list', [
            'supervisors' => $supervisors,
            'hasStale' => $hasStale,
        ])->layout('laravel-zenith::layout', ['title' => 'Workers']);
    }

    public function cleanUpStale(): void
    {
        $this->authorize('manage', Zenith::class);

        ZenithProcess::whereNotIn('status', ['terminated', 'abandoned'])
            ->where('last_heartbeat_at', '<', now()->subSeconds(config('zenith.heartbeat_interval', 30) * 2))
            ->update(['status' => 'abandoned']);
    }

    public function scaleUp(string $processId): void
    {
        $this->authorize('manage', Zenith::class);

        $process = ZenithProcess::with(['childWorkers' => fn ($q) => $q->whereNotIn('status', ['terminated', 'abandoned'])])->find($processId);
        $process->setRelation('childWorkers', $process->childWorkers->where('hostname', $process->hostname)->values());

        if (($process->metadata['balance'] ?? 'fixed') !== 'manual') {
            return;
        }

        $maxWorkers = (int) ($process->metadata['max_workers'] ?? PHP_INT_MAX);
        $pending = array_count_values($process->heartbeat_actions ?? []);
        $effectiveCount = $process->childWorkers->count() + ($pending['scale_up'] ?? 0) - ($pending['scale_down'] ?? 0);

        if ($effectiveCount >= $maxWorkers) {
            return;
        }

        $actions = $process->heartbeat_actions ?? [];
        $actions[] = 'scale_up';
        $process->update(['heartbeat_actions' => $actions]);
    }

    public function scaleDown(string $processId): void
    {
        $this->authorize('manage', Zenith::class);

        $process = ZenithProcess::with(['childWorkers' => fn ($q) => $q->whereNotIn('status', ['terminated', 'abandoned'])])->find($processId);
        $process->setRelation('childWorkers', $process->childWorkers->where('hostname', $process->hostname)->values());

        if (($process->metadata['balance'] ?? 'fixed') !== 'manual') {
            return;
        }

        $minWorkers = (int) ($process->metadata['min_workers'] ?? 0);
        $pending = array_count_values($process->heartbeat_actions ?? []);
        $effectiveCount = $process->childWorkers->count() + ($pending['scale_up'] ?? 0) - ($pending['scale_down'] ?? 0);

        if ($effectiveCount <= $minWorkers) {
            return;
        }

        $actions = $process->heartbeat_actions ?? [];
        $actions[] = 'scale_down';
        $process->update(['heartbeat_actions' => $actions]);
    }

    public function terminate(string $processId): void
    {
        $this->authorize('manage', Zenith::class);

        $process = ZenithProcess::find($processId);
        $actions = $process->heartbeat_actions ?? [];
        $actions[] = 'terminate';
        $process->update(['heartbeat_actions' => $actions]);
    }
}
