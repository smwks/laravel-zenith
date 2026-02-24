<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zenith_processes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('type', ['supervisor', 'worker'])->default('worker');
            $table->string('name')->nullable();
            $table->unsignedInteger('supervisor_pid')->nullable();
            $table->unsignedInteger('pid');
            $table->string('hostname');
            $table->string('queue')->index();
            $table->string('connection')->default('database');
            $table->timestamp('started_at');
            $table->timestamp('last_heartbeat_at');
            $table->unsignedBigInteger('current_job_id')->nullable();
            $table->enum('status', ['idle', 'working', 'terminated'])->default('idle')->index();
            $table->unsignedInteger('jobs_completed')->default(0);
            $table->unsignedInteger('jobs_failed')->default(0);
            $table->json('metadata')->nullable();
            $table->json('heartbeat_actions')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_heartbeat_at']);
            $table->index(['queue', 'status']);
        });

        Schema::create('zenith_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('job_id')->nullable()->comment('References jobs.id or jobs_history.id');
            $table->uuid('job_uuid')->index();
            $table->enum('event_type', ['started', 'completed', 'failed', 'retried', 'cancelled'])->index();
            $table->ulid('worker_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['job_uuid', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->foreign('worker_id')->references('id')->on('zenith_processes')->onDelete('set null');
        });

        Schema::create('zenith_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('job_id')->nullable()->comment('References jobs.id before deletion');
            $table->uuid('uuid')->index();
            $table->string('queue')->index();
            $table->string('connection')->default('database');
            $table->longText('payload');
            $table->enum('status', ['completed', 'cancelled'])->index();
            $table->ulid('worker_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'completed_at']);
            $table->index(['queue', 'status']);
            $table->foreign('worker_id')->references('id')->on('zenith_processes')->onDelete('set null');
        });
    }
};
