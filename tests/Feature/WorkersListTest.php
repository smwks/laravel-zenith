<?php

use Livewire\Livewire;
use SMWks\LaravelZenith\Livewire\WorkersList;
use SMWks\LaravelZenith\Models\ZenithProcess;

it('appends scale_up action for manual balance supervisor', function () {
    $supervisor = ZenithProcess::factory()->create([
        'metadata' => ['balance' => 'manual'],
    ]);

    Livewire::test(WorkersList::class)
        ->call('scaleUp', $supervisor->id);

    expect($supervisor->fresh()->heartbeat_actions)->toContain('scale_up');
});

it('does not append scale_up action for fixed balance supervisor', function () {
    $supervisor = ZenithProcess::factory()->fixedBalance()->create();

    Livewire::test(WorkersList::class)
        ->call('scaleUp', $supervisor->id);

    expect($supervisor->fresh()->heartbeat_actions)->toBeNull();
});

it('does not append scale_up action for automatic balance supervisor', function () {
    $supervisor = ZenithProcess::factory()->automaticBalance()->create();

    Livewire::test(WorkersList::class)
        ->call('scaleUp', $supervisor->id);

    expect($supervisor->fresh()->heartbeat_actions)->toBeNull();
});

it('appends scale_down action for manual balance supervisor', function () {
    $supervisor = ZenithProcess::factory()->create([
        'metadata' => ['balance' => 'manual'],
    ]);

    Livewire::test(WorkersList::class)
        ->call('scaleDown', $supervisor->id);

    expect($supervisor->fresh()->heartbeat_actions)->toContain('scale_down');
});

it('does not append scale_down action for fixed balance supervisor', function () {
    $supervisor = ZenithProcess::factory()->fixedBalance()->create();

    Livewire::test(WorkersList::class)
        ->call('scaleDown', $supervisor->id);

    expect($supervisor->fresh()->heartbeat_actions)->toBeNull();
});
