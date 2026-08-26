<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use SMWks\LaravelZenith\Jobs\ZenithTestJob;
use SMWks\LaravelZenith\Livewire\JobsList;

beforeEach(function () {
    if (! Schema::hasTable('jobs')) {
        Schema::create('jobs', function ($table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }
})->group('jobs-list');

it('shows a Processing badge for a job currently reserved by a worker', function () {
    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'ReservedJob']),
        'attempts' => 1,
        'reserved_at' => time(),
        'available_at' => time(),
        'created_at' => time(),
    ]);

    Livewire::test(JobsList::class)
        ->assertSee('Processing');
})->group('jobs-list');

it('does not show a Processing badge for an unclaimed pending job', function () {
    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'UnclaimedJob']),
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => time(),
        'created_at' => time(),
    ]);

    Livewire::test(JobsList::class)
        ->assertDontSee('Processing');
})->group('jobs-list');

it('passes the requested duration to a dispatched single test job', function () {
    config()->set('zenith.allow_test_dispatching', true);
    Bus::fake();

    Livewire::test(JobsList::class)
        ->set('tab', 'tests')
        ->set('singleDuration', 5)
        ->call('dispatchTestJob');

    Bus::assertDispatched(ZenithTestJob::class, fn ($job) => $job->sleepSeconds === 5);
})->group('jobs-list');

it('clamps single job duration to the 0-30 range', function () {
    config()->set('zenith.allow_test_dispatching', true);
    Bus::fake();

    Livewire::test(JobsList::class)
        ->set('tab', 'tests')
        ->set('singleDuration', 999)
        ->call('dispatchTestJob');

    Bus::assertDispatched(ZenithTestJob::class, fn ($job) => $job->sleepSeconds === 30);
})->group('jobs-list');

it('passes the requested duration to dispatched batch test jobs', function () {
    config()->set('zenith.allow_test_dispatching', true);
    Bus::fake();

    Livewire::test(JobsList::class)
        ->set('tab', 'tests')
        ->set('batchDuration', 10)
        ->set('batchCount', 2)
        ->call('dispatchTestBatch');

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 2
        && $batch->jobs->every(fn ($job) => $job->sleepSeconds === 10));
})->group('jobs-list');
