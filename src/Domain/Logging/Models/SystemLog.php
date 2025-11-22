<?php

namespace TradingPlatform\Domain\Logging\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = [
        'trace_id',
        'level',
        'component',
        'message',
        'context',
        'environment',
        'logged_at',
    ];

    protected $casts = [
        'context' => 'array',
        'logged_at' => 'datetime',
    ];

    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeComponent($query, string $component)
    {
        return $query->where('component', $component);
    }

    public function scopeTrace($query, string $traceId)
    {
        return $query->where('trace_id', $traceId);
    }
}
