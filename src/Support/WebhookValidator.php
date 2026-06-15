<?php

namespace Emreyilmaz99\SanalPos\Support;

/**
 * Webhook (banka callback) doğrulama yardımcısı.
 *
 * Banka kendi 3D Secure callback'ini success_url/fail_url'e POST eder. Hash
 * doğrulaması gateway'in resolveHostedPayment() metodunda yapılır; bu sınıf ek
 * defansif kontroller sağlar:
 *
 *  - **IP whitelist:** callback'in gerçekten bankadan geldiğini IP kontrolü ile teyit
 *  - **Timestamp window:** webhook eski mi? (replay attack koruması)
 *  - **Order age:** callback'in atfedildiği siparişin oluşturulma zamanı çok mu eski?
 *
 * Hiçbiri tek başına yeterli güvenlik değil — hash doğrulamasına ek katmanlar.
 * Kullanım örneği:
 *
 *   if (! WebhookValidator::ipAllowed($_SERVER['REMOTE_ADDR'], WebhookValidator::garantiTestIps())) {
 *       abort(403);
 *   }
 *   if (WebhookValidator::isStale($paymentLog->created_at, 3600)) {
 *       abort(410); // 1 saat ötede webhook — fishy
 *   }
 */
class WebhookValidator
{
    /**
     * Bir IP'nin verilen CIDR/IP listesi içinde olup olmadığını kontrol eder.
     *
     * @param  array<int, string>  $allowed  IPv4 adresleri veya CIDR notasyonu (örn '193.107.205.0/24')
     */
    public static function ipAllowed(string $remoteAddr, array $allowed): bool
    {
        if (filter_var($remoteAddr, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        foreach ($allowed as $entry) {
            if (str_contains($entry, '/')) {
                if (self::ipInCidr($remoteAddr, $entry)) {
                    return true;
                }
            } elseif (hash_equals($entry, $remoteAddr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verilen timestamp'in $maxAgeSeconds'tan eski olup olmadığını kontrol eder.
     * Webhook 30 dakikadan eski geldiyse muhtemelen replay; reddedilmeli.
     */
    public static function isStale(\DateTimeInterface|int|string $timestamp, int $maxAgeSeconds = 1800): bool
    {
        $ts = match (true) {
            $timestamp instanceof \DateTimeInterface => $timestamp->getTimestamp(),
            is_int($timestamp) => $timestamp,
            default => strtotime((string) $timestamp) ?: 0,
        };

        if ($ts === 0) {
            return true; // Parse edemediysek stale say
        }

        return (time() - $ts) > $maxAgeSeconds;
    }

    /**
     * Bilinen Garanti BBVA test sandbox IP aralığı. Production IP'leri Garanti'den
     * sözleşme sırasında alınır; oraya kadar bu liste sandbox doğrulaması içindir.
     *
     * @return array<int, string>
     */
    public static function garantiTestIps(): array
    {
        return [
            // sanalposprovtest.garantibbva.com.tr CDN aralığı; production'da güncellenmeli
            '193.107.205.0/24',
        ];
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false; // IPv6 desteklenmiyor (Türk bankaları henüz IPv4)
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
