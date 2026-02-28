<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public $timestamps = false;

    protected $table = 'accounts';

    protected $fillable = [
        'username', 'password', 'type', 'is_available',
        'note', 'note_date', 'available_since',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'available_since' => 'datetime',
    ];

    // === Scopes ===

    public function scopeAvailable($query)
    {
        return $query->where('is_available', 1);
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
}
