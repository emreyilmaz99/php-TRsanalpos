<?php

namespace Emreyilmaz99\SanalPos\Tests\Integration;

/**
 * Canlı sandbox doğrulama test base class.
 *
 * Bu sınıftan türeyen testler **GERÇEK** banka sandbox endpoint'ine HTTP isteği atar.
 * Default'ta CI'da koşmaz — environment variable `SANALPOS_LIVE=1` ile etkinleşir.
 *
 * Amaç: her gateway'in hash recipe'sinin ve payload yapısının banka tarafında kabul
 * edildiğini periyodik olarak doğrulamak. Mock'la yapılabilen unit testlerden farklı —
 * bunlar "şu an Garanti sandbox'ı bizim payload'ımızı kabul ediyor mu?" sorusunu cevaplar.
 *
 * Yardımcılar:
 *  - skipUnlessLive(): SANALPOS_LIVE=1 yoksa test'i skip eder
 *  - assertGatewayAcceptsForm(): bir HostedPaymentResponse'u banka URL'sine POST eder,
 *    HTTP 200 + beklenen sayfa indikatörünü doğrular
 */
abstract class LiveSandboxTestCase
{
    public static function liveTestingEnabled(): bool
    {
        return getenv('SANALPOS_LIVE') === '1';
    }

    public static function skipUnlessLive(): void
    {
        if (! self::liveTestingEnabled()) {
            test()->markTestSkipped('Canlı sandbox testleri yalnızca SANALPOS_LIVE=1 ile koşar.');
        }
    }

    /**
     * Bir form-POST gateway URL'sine istek atar, sayfa içeriğini döner.
     *
     * @return array{status: int, body: string, error: string|null}
     */
    public static function postFormToGateway(string $url, array $fields, int $timeoutSec = 15): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch) ?: null;
        // curl_close PHP 8'de no-op (handle artık object, GC ile kapanır).

        return ['status' => $status, 'body' => (string) $body, 'error' => $err];
    }

    /**
     * Sayfa içinde verilen anahtar kelimelerden HER birinin olduğunu doğrular
     * (banka ödeme sayfasının açıldığını teyit etmek için).
     */
    public static function bodyContainsAll(string $body, array $needles): bool
    {
        foreach ($needles as $n) {
            if (stripos($body, $n) === false) {
                return false;
            }
        }

        return true;
    }
}
