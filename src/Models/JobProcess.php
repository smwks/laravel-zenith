<?php

namespace SMWks\LaravelZenith\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobProcess extends Model
{
    use HasUlids;

    protected $table = 'zenith_processes';

    protected $fillable = [
        'type',
        'name',
        'pid',
        'supervisor_pid',
        'hostname',
        'queue',
        'connection',
        'started_at',
        'last_heartbeat_at',
        'current_job_id',
        'status',
        'jobs_completed',
        'jobs_failed',
        'metadata',
        'heartbeat_actions',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'metadata' => 'array',
        'heartbeat_actions' => 'array',
    ];

    public function getConnectionName()
    {
        return config('zenith.database_connection') ?? parent::getConnectionName();
    }

    public function childWorkers(): HasMany
    {
        return $this->hasMany(JobProcess::class, 'supervisor_pid', 'pid');
    }

    public function jobHistory(): HasMany
    {
        return $this->hasMany(JobHistory::class, 'worker_id');
    }

    public function jobEvents(): HasMany
    {
        return $this->hasMany(JobEvent::class, 'worker_id');
    }

    public function isHealthy(): bool
    {
        $threshold = config('zenith.stuck_job_threshold', 120);

        return $this->last_heartbeat_at->diffInSeconds(now()) < $threshold;
    }

    public function isWorking(): bool
    {
        return $this->status === 'working' && $this->current_job_id !== null;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['idle', 'working']);
    }

    public function scopeStuck($query)
    {
        $threshold = config('zenith.stuck_job_threshold', 120);

        return $query->where('status', 'working')
            ->where('last_heartbeat_at', '<', now()->subSeconds($threshold));
    }

    public function scopeWorkerType($query)
    {
        return $query->where('type', 'worker');
    }

    public function scopeSupervisorType($query)
    {
        return $query->where('type', 'supervisor');
    }
}
