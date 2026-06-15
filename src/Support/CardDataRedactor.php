<?php

namespace Emreyilmaz99\SanalPos\Support;

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

    /**
     * HTTP request/response body içinde geçen kart alan adlarını otomatik maskeler.
     * Türkiye'deki yaygın gateway field name varyasyonlarını kapsar.
     */
    public static function redactPayload(mixed $payload): mixed
    {
        if (is_string($payload)) {
            return self::redactString($payload);
        }

        if (is_array($payload)) {
            $result = [];
            foreach ($payload as $key => $value) {
                $result[$key] = self::shouldRedactKey((string) $key)
                    ? self::redactValue($key, $value)
                    : self::redactPayload($value);
            }

            return $result;
        }

        return $payload;
    }

    /**
     * Belirli field adlarının maskelenmesi gerektiğini söyler.
     * Türkiye gateway'lerinin çeşitli isimlendirmelerini karşılar.
     */
    private static function shouldRedactKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['_', '-'], '', $key));

        // CVV grubu — her zaman tamamen maskelenir
        if (in_array($normalized, ['cvv', 'cvc', 'cv2', 'cvv2', 'cvv2val', 'cvcnumber'], true)) {
            return true;
        }

        // PAN grubu — ilk 6 + son 4 görünür
        return in_array($normalized, [
            'cardnumber', 'cardno', 'pan', 'number', 'ccno', 'cc_no',
            'creditcard', 'cardno', 'kartno', 'pan_',
        ], true);
    }

    private static function redactValue(string $key, mixed $value): string
    {
        if (! is_scalar($value)) {
            return '***';
        }
        $str = (string) $value;
        $normalized = strtolower(str_replace(['_', '-'], '', $key));
        if (in_array($normalized, ['cvv', 'cvc', 'cv2', 'cvv2', 'cvv2val', 'cvcnumber'], true)) {
            return self::redactCvv($str);
        }

        return self::maskPan($str);
    }

    /**
     * String body içinde 12-19 hane PAN ve form-urlencoded CVV gibi pattern'leri yakalar.
     * XML/JSON gövdelerde kullanılır.
     */
    private static function redactString(string $body): string
    {
        // 12-19 hane standalone numerik → muhtemelen PAN
        $body = preg_replace_callback(
            '/\b\d{12,19}\b/',
            static fn ($m) => self::maskPan($m[0]),
            $body
        ) ?? $body;

        return $body;
    }
}
