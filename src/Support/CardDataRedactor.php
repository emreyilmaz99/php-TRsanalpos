<?php

namespace EvrenOnur\SanalPos\Support;

/**
 * Kart verisi maskeleme yardımcısı.
 *
 * Loglama / debug çıktısı / serialization sırasında ham kart numarası ve CVV'nin
 * dışarı sızmasını önler. Plain text PAN/CVV log'a yazılırsa PCI-DSS ihlali olur.
 */
class CardDataRedactor
{
    /**
     * Kart numarasını maskele: ilk 6 + son 4 görünür, ortası `*` ile maskelenir.
     * Örnek: "5454545454545454" → "545454******5454"
     */
    public static function maskPan(?string $pan): string
    {
        if ($pan === null || $pan === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $pan);
        $len = strlen($digits);

        if ($len < 13) {
            return str_repeat('*', $len);
        }

        $first = substr($digits, 0, 6);
        $last = substr($digits, -4);
        $maskedLen = max(0, $len - 10);

        return $first . str_repeat('*', $maskedLen) . $last;
    }

    /**
     * CVV/CVC her zaman tamamen maskelenir; PCI-DSS hiçbir koşulda CVV depolama/loglamaya izin vermez.
     */
    public static function redactCvv(?string $cvv): string
    {
        if ($cvv === null || $cvv === '') {
            return '';
        }

        return str_repeat('*', strlen($cvv));
    }
}
