<?php

namespace SMWks\LaravelZenith\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZenithEvent extends Model
{
    use HasUlids;

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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('zenith.table_names.events') ?: parent::getTable();
    }

    public function getConnectionName()
    {
        return config('zenith.database_connection') ?? parent::getConnectionName();
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(ZenithProcess::class, 'worker_id');
    }

    public function jobHistory(): BelongsTo
    {
        return $this->belongsTo(ZenithHistory::class, 'job_uuid', 'uuid');
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
