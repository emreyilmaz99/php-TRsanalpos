<?php

namespace EvrenOnur\SanalPos\Infrastructure\Iyzico;

use EvrenOnur\SanalPos\Support\MakesHttpRequests;

/**
 * Iyzico REST HTTP istemcisi.
 */
class IyzicoHttpClient
{
    use MakesHttpRequests;

    private static ?self $instance = null;

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function post(string $url, array $headers, array $body): array
    {
        try {
            // IyzicoHashGenerator ile aynı JSON flag'i kullan (default + PRESERVE_ZERO_FRACTION).
            // UNESCAPED_SLASHES/UNICODE flag'leri Iyzico server'ı hash uyuşmazlığına götürür.
            $jsonBody = json_encode($body, JSON_PRESERVE_ZERO_FRACTION);
            $content = self::getInstance()->httpPostRaw($url, $jsonBody, $headers);

            return json_decode($content, true) ?? [];
        } catch (\Throwable $e) {
            return ['status' => 'failure', 'errorMessage' => $e->getMessage()];
        }
    }
}
