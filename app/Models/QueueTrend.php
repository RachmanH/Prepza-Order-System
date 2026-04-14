<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTrend extends Model
{
    protected $fillable = [
        'title',
        'image_url',
        'caption',
        'score',
        'gender_target',
        'source_timestamp',
        'expires_at',
        'is_active',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'source_payload' => 'array',
            'source_timestamp' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
