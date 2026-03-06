<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;

    protected $table = 'orders';

    protected $fillable = [
        'tracking_code', 'order_code', 'hours', 'amount', 'status',
        'created_at', 'ip_address', 'account_id',
        'expires_at', 'paid_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // === Scopes ===

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('tracking_code', $code);
    }

    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    // === Relationships ===

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
