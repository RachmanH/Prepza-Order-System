<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class OrderQueue extends Model
{
    use HasFactory;

    protected $primaryKey = 'queue_number';

    protected $fillable = [
        'order_id',
        'status',
        'called_at',
        'done_at',
    ];

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
