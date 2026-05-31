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

it('returns 403 for job cancel endpoint in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    $this->withoutMiddleware()
        ->delete(route('zenith.api.jobs.cancel', ['id' => 1]))
        ->assertForbidden();
});

it('returns 403 for job retry endpoint in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    $this->withoutMiddleware()
        ->post(route('zenith.api.jobs.retry', ['id' => 1]))
        ->assertForbidden();
});

it('returns 403 for job retry-all endpoint in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    $this->withoutMiddleware()
        ->post(route('zenith.api.jobs.retryAll'))
        ->assertForbidden();
});

it('returns 403 for failed job delete endpoint in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    $this->withoutMiddleware()
        ->delete(route('zenith.api.jobs.delete', ['id' => 1]))
        ->assertForbidden();
});

it('hides Terminate button in read-only mode', function () {
    config()->set('zenith.route.middleware', ['web']);

    ZenithProcess::factory()->create([
        'metadata' => ['balance' => 'manual'],
        'status' => 'idle',
    ]);

    Livewire::test(WorkersList::class)
        ->assertDontSeeHtml('wire:click="terminate(')
        ->assertDontSeeHtml('wire:click="scaleUp(')
        ->assertDontSeeHtml('wire:click="scaleDown(');
});

it('shows Terminate button when auth middleware is configured', function () {
    config()->set('zenith.route.middleware', ['web', 'auth']);

    ZenithProcess::factory()->create([
        'metadata' => ['balance' => 'manual'],
        'status' => 'idle',
    ]);

    Livewire::test(WorkersList::class)
        ->assertSeeHtml('wire:click="terminate(')
        ->assertSeeHtml('wire:click="scaleUp(')
        ->assertSeeHtml('wire:click="scaleDown(');
});
