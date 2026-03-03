<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Normalize a phone number to EgoSMS international format (256XXXXXXXXX).
     * Strips spaces, dashes, and converts +256/0 prefix to bare 256.
     */
    public static function normalize(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // +256XXXXXXXXX → 256XXXXXXXXX
        if (str_starts_with($cleaned, '+256')) {
            return substr($cleaned, 1);
        }

        // 0XXXXXXXXX (local, 10 digits) → 256XXXXXXXXX
        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
            return '256' . substr($cleaned, 1);
        }

        // Already 256XXXXXXXXX
        return $cleaned;
    }

    /**
     * Returns true if the phone number matches a Ugandan mobile prefix.
     * Checks both local (07X) and international (256X) formats.
     */
    public static function isLikelyMobile(string $phone): bool
    {
        $normalized = self::normalize($phone);

        // Mobile digit blocks after country code 256: 77, 78, 76, 70, 75, 74
        $mobileSuffixes = ['77', '78', '76', '70', '75', '74'];

        $digits = str_starts_with($normalized, '256') ? substr($normalized, 3) : $normalized;

        foreach ($mobileSuffixes as $suffix) {
            if (str_starts_with($digits, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
