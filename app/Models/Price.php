<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    public $timestamps = false;

    protected $table = 'prices';

    protected $fillable = ['hours', 'price', 'type', 'night_price', 'night_start', 'night_end'];

    /**
     * Check if current time is within the night discount window.
     * Uses Asia/Ho_Chi_Minh timezone.
     */
    public function isNightTime(): bool
    {
        if (empty($this->night_price) || empty($this->night_start) || empty($this->night_end)) {
            return false;
        }

        $now = now('Asia/Ho_Chi_Minh');
        $currentMinutes = $now->hour * 60 + $now->minute;

        $startParts = explode(':', $this->night_start);
        $endParts = explode(':', $this->night_end);
        $startMinutes = (int)$startParts[0] * 60 + (int)($startParts[1] ?? 0);
        $endMinutes = (int)$endParts[0] * 60 + (int)($endParts[1] ?? 0);

        // Handle overnight range (e.g., 21:00 → 09:00)
        if ($startMinutes > $endMinutes) {
            return $currentMinutes >= $startMinutes || $currentMinutes < $endMinutes;
        }

        // Same-day range (e.g., 22:00 → 23:00)
        return $currentMinutes >= $startMinutes && $currentMinutes < $endMinutes;
    }

    /**
     * Get the effective price based on current time.
     * Returns night_price during night hours, otherwise regular price.
     */
    public function getEffectivePrice(): int
    {
        if ($this->isNightTime()) {
            return (int) $this->night_price;
        }
        return (int) $this->price;
    }
}
