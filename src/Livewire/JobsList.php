<?php

namespace SMWks\LaravelZenith\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use SMWks\LaravelZenith\Jobs\ZenithTestJob;
use SMWks\LaravelZenith\Models\ZenithHistory;
use SMWks\LaravelZenith\Services\ZenithJobService;

class JobsList extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'pending';

    public string $queue = '';

    public bool $singleLogging = false;

    public bool $batchLogging = false;

    public int $batchCount = 5;

    public function dispatchTestJob(): void
    {
        ZenithTestJob::dispatch($this->singleLogging);
        session()->flash('message', 'Test job dispatched successfully');
    }

    public function dispatchTestBatch(): void
    {
        $jobs = array_fill(0, $this->batchCount, new ZenithTestJob($this->batchLogging));
        Bus::batch($jobs)->name('Zenith Test Batch')->dispatch();
        session()->flash('message', "Test batch of {$this->batchCount} jobs dispatched successfully");
    }

    public function retryJob(int $id, ZenithJobService $jobService): void
    {
        $jobService->retryFailedJob($id);
        session()->flash('message', 'Job retried successfully');
    }

    public function retryAll(ZenithJobService $jobService): void
    {
        $count = $jobService->retryAllFailedJobs($this->queue ?: null);
        session()->flash('message', "Retried {$count} job(s) successfully");
    }

    public function deleteJob(int $id, ZenithJobService $jobService): void
    {
        $jobService->deleteFailedJob($id);
        session()->flash('message', 'Job deleted successfully');
    }

    public function render()
    {
        $jobs = match ($this->tab) {
            'completed' => ZenithHistory::completed()->orderBy('completed_at', 'desc')->paginate(15),
            'failed' => DB::table('failed_jobs')
                ->when($this->queue, fn ($q) => $q->where('queue', $this->queue))
                ->orderBy('failed_at', 'desc')->paginate(15),
            'batches' => $this->fetchBatches(),
            default => DB::table('jobs')
                ->when($this->queue, fn ($q) => $q->where('queue', $this->queue))
                ->orderBy('created_at', 'desc')->paginate(15),
        };

        $queues = match ($this->tab) {
            'pending' => DB::table('jobs')->distinct()->pluck('queue'),
            'failed' => DB::table('failed_jobs')->distinct()->pluck('queue'),
            default => collect(),
        };

        $batchMap = $this->resolveBatchNames($jobs);

        return view('laravel-zenith::livewire.jobs-list', [
            'jobs' => $jobs,
            'queues' => $queues,
            'tab' => $this->tab,
            'batchMap' => $batchMap,
        ])->layout('laravel-zenith::layout', ['title' => 'Jobs']);
    }

    protected function fetchBatches(): LengthAwarePaginator
    {
        try {
            return DB::table('job_batches')->orderBy('created_at', 'desc')->paginate(15);
        } catch (\Exception) {
            return new LengthAwarePaginator([], 0, 15);
        }
    }

    protected function resolveBatchNames($jobs): Collection
    {
        if ($this->tab === 'pending') {
            $batchIds = collect($jobs->items())->map(function ($job) {
                $payload = json_decode($job->payload, true);

                return $payload['batchId'] ?? null;
            })->filter()->unique()->values();

            if ($batchIds->isEmpty()) {
                return collect();
            }

            try {
                return DB::table('job_batches')->whereIn('id', $batchIds)->get()->keyBy('id');
            } catch (\Exception) {
                return collect();
            }
        }

        if ($this->tab === 'completed') {
            $batchIds = collect($jobs->items())->map(function ($job) {
                return $job->payload['batchId'] ?? null;
            })->filter()->unique()->values();

            if ($batchIds->isEmpty()) {
                return collect();
            }

            try {
                return DB::table('job_batches')->whereIn('id', $batchIds)->get()->keyBy('id');
            } catch (\Exception) {
                return collect();
            }
        }

        return collect();
    }
}
