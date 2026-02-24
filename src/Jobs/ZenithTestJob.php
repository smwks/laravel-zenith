<?php

namespace SMWks\LaravelZenith\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ZenithTestJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public bool $enableLogging = false) {}

    public function handle(): void
    {
        if ($this->enableLogging) {
            logger()->info('Hello World from Zenith at '.now());
        }
    }
}
