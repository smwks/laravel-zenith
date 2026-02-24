<?php

use Illuminate\Support\Facades\Route;
use SMWks\LaravelZenith\Livewire\Dashboard;
use SMWks\LaravelZenith\Livewire\JobsList;
use SMWks\LaravelZenith\Livewire\WorkersList;

Route::prefix(config('zenith.route.prefix', 'zenith'))
    ->middleware(config('zenith.route.middleware', ['web', 'auth']))
    ->group(function () {
        Route::get('/', Dashboard::class)->name('zenith.dashboard');
        Route::get('/workers', WorkersList::class)->name('zenith.workers');
        Route::get('/jobs', JobsList::class)->name('zenith.jobs');
    });
