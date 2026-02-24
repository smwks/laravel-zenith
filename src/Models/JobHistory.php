<?php

namespace SMWks\LaravelZenith\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobHistory extends Model
{
    use HasUlids;

    protected $table = 'zenith_history';

    protected $fillable = [
        'job_id',
        'uuid',
        'queue',
        'connection',
        'payload',
        'status',
        'worker_id',
        'started_at',
        'completed_at',
        'processing_time_ms',
        'attempts',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
        'payload' => 'array',
    ];

    public function getConnectionName()
    {
        return config('zenith.database_connection') ?? parent::getConnectionName();
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(JobProcess::class, 'worker_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(JobEvent::class, 'job_uuid', 'uuid');
    }

    public function getProcessingTimeAttribute(): ?string
    {
        if ($this->processing_time_ms === null) {
            return null;
        }

        if ($this->processing_time_ms < 1000) {
            return $this->processing_time_ms.'ms';
        }

        return round($this->processing_time_ms / 1000, 2).'s';
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForQueue($query, string $queue)
    {
        return $query->where('queue', $queue);
    }
}
