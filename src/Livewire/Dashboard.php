<?php

namespace SMWks\LaravelZenith\Livewire;

use Livewire\Component;
use SMWks\LaravelZenith\Services\MetricsService;

class Dashboard extends Component
{
    public array $metrics = [];

    public function mount(MetricsService $metricsService)
    {
        $this->loadMetrics($metricsService);
    }

    public function loadMetrics(MetricsService $metricsService)
    {
        $this->metrics = $metricsService->getDashboardMetrics();
    }

    public function render()
    {
        return view('laravel-zenith::livewire.dashboard')
            ->layout('laravel-zenith::layout', ['title' => 'Dashboard']);
    }
}
