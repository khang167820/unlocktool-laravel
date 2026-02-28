<?php

namespace App\Helpers;

class OrderHelper
{
    /**
     * Mask a string, showing only the first N characters.
     */
    public static function mask(string $str, int $visible = 1): string
    {
        $len = strlen($str);
        if ($len <= $visible) return str_repeat('*', $len);
        return substr($str, 0, $visible) . str_repeat('*', $len - $visible);
    }

    /**
     * Convert hours to readable package name.
     */
    public static function displayPackageName(int $hours): string
    {
        return match ($hours) {
            24  => '1 ngày',
            48  => '2 ngày',
            72  => '3 ngày',
            168 => '7 ngày',
            360 => '14 ngày',
            720 => '30 ngày',
            default => $hours . ' giờ'
        };
    }

    /**
     * Format money in Vietnamese style.
     */
    public static function formatMoney(int $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get client IP address.
     */
    public static function getClientIP(): string
    {
        return request()->header('X-Forwarded-For')
            ?? request()->header('X-Real-IP')
            ?? request()->ip();
    }

    /**
     * Generate unique tracking code: DH + ddmm + 6 random digits.
     */
    public static function generateTrackingCode(): string
    {
        return 'DH' . date('dm') . random_int(100000, 999999);
    }

    /**
     * Bank information for QR payments.
     */
    public static function getBankInfo(): array
    {
        return [
            'bin' => env('BANK_BIN', 'acb'),
            'account' => env('BANK_ACCOUNT', '20867091'),
            'owner' => env('BANK_OWNER', 'MAI THI THU QUYEN'),
        ];
    }
}
