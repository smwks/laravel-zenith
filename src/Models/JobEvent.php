<?php

namespace SMWks\LaravelZenith\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobEvent extends Model
{
    use HasUlids;

    protected $table = 'zenith_events';

    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'job_uuid',
        'event_type',
        'worker_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function getConnectionName()
    {
        return config('zenith.database_connection') ?? parent::getConnectionName();
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(JobProcess::class, 'worker_id');
    }

    public function jobHistory(): BelongsTo
    {
        return $this->belongsTo(JobHistory::class, 'job_uuid', 'uuid');
    }

    public function scopeForJob($query, string $uuid)
    {
        return $query->where('job_uuid', $uuid);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
