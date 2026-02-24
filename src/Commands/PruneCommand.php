<?php

namespace SMWks\LaravelZenith\Commands;

use Illuminate\Console\Command;
use SMWks\LaravelZenith\Services\ZenithJobService;

class PruneCommand extends Command
{
    protected $signature = 'zenith:prune
                            {--completed= : Days to retain completed jobs}
                            {--failed= : Days to retain failed jobs}
                            {--events= : Days to retain job events}
                            {--all : Prune all data types}';

    protected $description = 'Prune old job data from Zenith tables';

    public function handle(ZenithJobService $jobService): int
    {
        if (! config('zenith.enabled', true)) {
            $this->error('Zenith is disabled.');

            return self::FAILURE;
        }

        $this->info('Pruning old job data...');

        $pruned = [];

        if ($this->option('all')) {
            $pruned = $jobService->pruneAll();
        } else {
            if ($this->option('completed') !== null || $this->option('all')) {
                $days = $this->option('completed');
                $count = $jobService->pruneCompletedJobs($days ? (int) $days : null);
                $pruned['completed_jobs'] = $count;
                $this->line("Pruned {$count} completed job(s)");
            }

            if ($this->option('failed') !== null || $this->option('all')) {
                $days = $this->option('failed');
                $count = $jobService->pruneFailedJobs($days ? (int) $days : null);
                $pruned['failed_jobs'] = $count;
                $this->line("Pruned {$count} failed job(s)");
            }

            if ($this->option('events') !== null || $this->option('all')) {
                $days = $this->option('events');
                $count = $jobService->pruneJobEvents($days ? (int) $days : null);
                $pruned['job_events'] = $count;
                $this->line("Pruned {$count} job event(s)");
            }
        }

        if (empty($pruned)) {
            $this->info('No pruning options specified. Use --all or specify individual options.');
            $this->line('');
            $this->line('Examples:');
            $this->line('  zenith:prune --all');
            $this->line('  zenith:prune --completed=7 --failed=30');
            $this->line('  zenith:prune --events=3');

            return self::SUCCESS;
        }

        $total = array_sum($pruned);
        $this->info("Total records pruned: {$total}");

        return self::SUCCESS;
    }
}
