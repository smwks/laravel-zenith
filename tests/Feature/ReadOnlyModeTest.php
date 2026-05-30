<?php

use SMWks\LaravelZenith\Http\Policies\ZenithPolicy;

it('returns false from manage when no auth middleware is configured', function () {
    config()->set('zenith.route.middleware', ['web']);

    $policy = new ZenithPolicy;

    expect($policy->manage(null))->toBeFalse();
});

it('returns true from manage when auth middleware is configured', function () {
    config()->set('zenith.route.middleware', ['web', 'auth']);

    $policy = new ZenithPolicy;

    expect($policy->manage(null))->toBeTrue();
});

it('returns true from manage when a namespaced auth middleware is configured', function () {
    config()->set('zenith.route.middleware', ['web', 'auth:sanctum']);

    $policy = new ZenithPolicy;

    expect($policy->manage(null))->toBeTrue();
});

use Livewire\Livewire;
use SMWks\LaravelZenith\Livewire\WorkersList;
use SMWks\LaravelZenith\Models\ZenithProcess;

it('blocks WorkersList scaleUp in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    $supervisor = ZenithProcess::factory()->create(['metadata' => ['balance' => 'manual']]);

    Livewire::test(WorkersList::class)
        ->call('scaleUp', $supervisor->id)
        ->assertForbidden();

    expect($supervisor->fresh()->heartbeat_actions)->toBeNull();
});

it('blocks WorkersList scaleDown in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    $supervisor = ZenithProcess::factory()->create(['metadata' => ['balance' => 'manual']]);

    Livewire::test(WorkersList::class)
        ->call('scaleDown', $supervisor->id)
        ->assertForbidden();

    expect($supervisor->fresh()->heartbeat_actions)->toBeNull();
});

it('blocks WorkersList terminate in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    $supervisor = ZenithProcess::factory()->create(['metadata' => ['balance' => 'manual']]);

    Livewire::test(WorkersList::class)
        ->call('terminate', $supervisor->id)
        ->assertForbidden();

    expect($supervisor->fresh()->heartbeat_actions)->toBeNull();
});
