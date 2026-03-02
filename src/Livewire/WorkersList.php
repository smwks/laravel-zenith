<?php

namespace SMWks\LaravelZenith\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use SMWks\LaravelZenith\Models\ZenithProcess;

class WorkersList extends Component
{
    #[Url]
    public string $tab = 'active';

    public function render()
    {
        $supervisors = $this->tab === 'active'
            ? ZenithProcess::supervisorType()->active()->with('childWorkers')->orderBy('started_at', 'desc')->get()
            : ZenithProcess::supervisorType()->where('status', 'terminated')->with('childWorkers')->orderBy('started_at', 'desc')->get();

        return view('laravel-zenith::livewire.workers-list', [
            'supervisors' => $supervisors,
        ])->layout('laravel-zenith::layout', ['title' => 'Workers']);
    }

    public function scaleUp(string $processId): void
    {
        $process = ZenithProcess::find($processId);
        $actions = $process->heartbeat_actions ?? [];
        $actions[] = 'scale_up';
        $process->update(['heartbeat_actions' => $actions]);
    }

    public function scaleDown(string $processId): void
    {
        $process = ZenithProcess::find($processId);
        $actions = $process->heartbeat_actions ?? [];
        $actions[] = 'scale_down';
        $process->update(['heartbeat_actions' => $actions]);
    }

    public function terminate(string $processId): void
    {
        $process = ZenithProcess::find($processId);
        $actions = $process->heartbeat_actions ?? [];
        $actions[] = 'terminate';
        $process->update(['heartbeat_actions' => $actions]);
    }
}
