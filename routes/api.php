<?php

use Illuminate\Support\Facades\Route;
use SMWks\LaravelZenith\Http\Controllers\Api\JobsController;
use SMWks\LaravelZenith\Http\Controllers\Api\MetricsController;
use SMWks\LaravelZenith\Http\Controllers\Api\WorkersController;

Route::prefix('zenith/api')->middleware(config('zenith.route.middleware', ['web', 'auth']))->group(function () {
    // Workers
    Route::get('/workers', [WorkersController::class, 'index'])->name('zenith.api.workers.index');
    Route::get('/workers/{id}', [WorkersController::class, 'show'])->name('zenith.api.workers.show');

    // Jobs
    Route::get('/jobs', [JobsController::class, 'index'])->name('zenith.api.jobs.index');
    Route::get('/jobs/{id}', [JobsController::class, 'show'])->name('zenith.api.jobs.show');
    Route::get('/jobs-history', [JobsController::class, 'history'])->name('zenith.api.jobs.history');
    Route::get('/jobs-failed', [JobsController::class, 'failed'])->name('zenith.api.jobs.failed');

    // Job Actions
    Route::delete('/jobs/{id}', [JobsController::class, 'cancel'])->name('zenith.api.jobs.cancel');
    Route::post('/jobs/{id}/retry', [JobsController::class, 'retry'])->name('zenith.api.jobs.retry');
    Route::post('/jobs/retry-all', [JobsController::class, 'retryAll'])->name('zenith.api.jobs.retryAll');
    Route::delete('/jobs-failed/{id}', [JobsController::class, 'delete'])->name('zenith.api.jobs.delete');

    // Metrics
    Route::get('/metrics', [MetricsController::class, 'index'])->name('zenith.api.metrics.index');
    Route::get('/metrics/workers', [MetricsController::class, 'workers'])->name('zenith.api.metrics.workers');
    Route::get('/metrics/jobs', [MetricsController::class, 'jobs'])->name('zenith.api.metrics.jobs');
    Route::get('/metrics/performance', [MetricsController::class, 'performance'])->name('zenith.api.metrics.performance');
    Route::get('/metrics/queues', [MetricsController::class, 'queues'])->name('zenith.api.metrics.queues');
});
