<?php

namespace SMWks\LaravelZenith\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use SMWks\LaravelZenith\Services\ZenithJobService;

class FailedJobsList extends Component
{
    use WithPagination;

    public string $queue = '';

    public function retryJob(int $id, ZenithJobService $jobService)
    {
        $jobService->retryFailedJob($id);
        session()->flash('message', 'Job retried successfully');
    }

    public function retryAll(ZenithJobService $jobService)
    {
        $queue = $this->queue ?: null;
        $count = $jobService->retryAllFailedJobs($queue);
        session()->flash('message', "Retried {$count} job(s) successfully");
    }

    public function deleteJob(int $id, ZenithJobService $jobService)
    {
        $jobService->deleteFailedJob($id);
        session()->flash('message', 'Job deleted successfully');
    }

    public function render()
    {
        $query = DB::table('failed_jobs')->orderBy('failed_at', 'desc');

        if ($this->queue) {
            $query->where('queue', $this->queue);
        }

        $jobs = $query->paginate(15);
        $queues = DB::table('failed_jobs')->distinct()->pluck('queue');

        return view('laravel-zenith::livewire.failed-jobs-list', [
            'jobs' => $jobs,
            'queues' => $queues,
        ])->layout('laravel-zenith::layout', ['title' => 'Failed Jobs']);
    }
}
