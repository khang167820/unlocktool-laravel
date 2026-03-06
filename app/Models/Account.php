<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public $timestamps = false;

    protected $table = 'accounts';

    protected $fillable = [
        'username', 'password', 'type', 'is_available',
        'note', 'note_date', 'password_changed',
        'rental_expires_at', 'rental_order_code', 'expires_at',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'password_changed' => 'boolean',
        'rental_expires_at' => 'datetime',
        'expires_at' => 'date',
    ];

    // === Scopes ===

    public function scopeAvailable($query)
    {
        return $query->where('is_available', 1)
            ->where(function ($q) {
                $q->whereNull('note')->orWhere('note', '');
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // === Relationships ===

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the currently active rental order for this account.
     */
    public function activeRental()
    {
        return $this->hasOne(Order::class)
            ->whereIn('status', ['paid', 'completed'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->latest('paid_at');
    }
}
