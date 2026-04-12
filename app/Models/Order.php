<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'customer_name',
        'gender',
        'raw_text',
        'normalized_text',
        'source',
        'parsing_confidence',
        'validation_status',
        'status',
        'external_status',
        'external_note',
        'external_updated_at',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'external_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (! $order->order_code) {
                $order->order_code = 'ORD-'.strtoupper((string) Str::ulid());
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function queue(): HasOne
    {
        return $this->hasOne(OrderQueue::class);
    }
}
