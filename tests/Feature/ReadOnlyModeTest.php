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

use Illuminate\Support\Facades\Schema;
use SMWks\LaravelZenith\Livewire\FailedJobsList;

beforeEach(function () {
    if (! Schema::hasTable('failed_jobs')) {
        Schema::create('failed_jobs', function ($table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }
})->group('failed-jobs');

it('blocks FailedJobsList retryJob in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    Livewire::test(FailedJobsList::class)
        ->call('retryJob', 1)
        ->assertForbidden();
})->group('failed-jobs');

it('blocks FailedJobsList retryAll in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    Livewire::test(FailedJobsList::class)
        ->call('retryAll')
        ->assertForbidden();
})->group('failed-jobs');

it('blocks FailedJobsList deleteJob in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    Livewire::test(FailedJobsList::class)
        ->call('deleteJob', 1)
        ->assertForbidden();
})->group('failed-jobs');
