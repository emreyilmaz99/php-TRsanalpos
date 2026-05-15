<?php

namespace EvrenOnur\SanalPos\Support;

use EvrenOnur\SanalPos\Exceptions\HttpRequestException;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * HTTP istekleri için ortak trait.
 * Tüm gateway'ler bu trait'i kullanarak HTTP isteklerini merkezi bir yerden yönetir.
 * Try-catch, timeout ve SSL verify yapılandırması bu trait üzerinden sağlanır.
 *
 * Logging: Gateway LoggerAware trait'i de use ediyorsa (default), her HTTP isteği
 * PSR-3 logger'a kart numarası + CVV maskelenmiş şekilde yazılır. PCI-DSS uyumu için
 * kart bilgisi hiçbir koşulda plain-text log'a düşmez.
 */
trait MakesHttpRequests
{
    private ?Client $httpClient = null;

    /**
     * Son HTTP hata mesajı
     */
    protected ?string $lastHttpError = null;

    /**
     * HTTP timeout süresi (saniye)
     */
    protected int $httpTimeout = 30;

    /**
     * HTTP bağlantı kurma timeout süresi (saniye)
     */
    protected int $httpConnectTimeout = 10;

    /**
     * Config'den timeout ve SSL verify değerlerini yükler.
     */
    private function loadConfigValues(): void
    {
        if (function_exists('config')) {
            $this->httpTimeout = (int) config('sanalpos.timeout', $this->httpTimeout);
            $this->httpConnectTimeout = (int) config('sanalpos.connect_timeout', $this->httpConnectTimeout);
            $this->httpVerifySSL = config('sanalpos.verify_ssl', $this->httpVerifySSL);
        }
    }

    /**
     * SSL doğrulama (production'da true olmalıdır)
     */
    protected bool $httpVerifySSL = true;

    /**
     * Guzzle Client nesnesi döndürür.
     * Tekrarlı oluşturma yerine tek nesne kullanır.
     */
    protected function getHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->loadConfigValues();
            $this->httpClient = new Client([
                'verify' => $this->httpVerifySSL,
                'timeout' => $this->httpTimeout,
                'connect_timeout' => $this->httpConnectTimeout,
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Son HTTP hata mesajını döndürür.
     */
    public function getLastHttpError(): ?string
    {
        return $this->lastHttpError;
    }

    /**
     * Form-encoded POST isteği yapar.
     *
     * @throws HttpRequestException
     */
    protected function httpPostForm(string $url, array $params, array $headers = []): string
    {
        return $this->dispatchPost($url, 'form', $params, $headers, function () use ($url, $params, $headers) {
            $options = ['form_params' => $params];
            if (! empty($headers)) {
                $options['headers'] = $headers;
            }

            return $this->getHttpClient()->post($url, $options);
        });
    }

    /**
     * JSON body ile POST isteği yapar.
     *
     * @throws HttpRequestException
     */
    protected function httpPostJson(string $url, array $body, array $headers = []): string
    {
        $defaultHeaders = ['Content-Type' => 'application/json; charset=utf-8'];
        $mergedHeaders = array_merge($defaultHeaders, $headers);

        return $this->dispatchPost($url, 'json', $body, $mergedHeaders, function () use ($url, $body, $mergedHeaders) {
            return $this->getHttpClient()->post($url, [
                'json' => $body,
                'headers' => $mergedHeaders,
            ]);
        });
    }

    /**
     * XML body ile POST isteği yapar.
     *
     * @throws HttpRequestException
     */
    protected function httpPostXml(string $url, string $xml, string $contentType = 'application/xml; charset=utf-8'): string
    {
        $headers = ['Content-Type' => $contentType];

        return $this->dispatchPost($url, 'xml', $xml, $headers, function () use ($url, $xml, $headers) {
            return $this->getHttpClient()->post($url, [
                'body' => $xml,
                'headers' => $headers,
            ]);
        });
    }

    /**
     * Ham body ile POST isteği yapar (SOAP vb. için).
     *
     * @throws HttpRequestException
     */
    protected function httpPostRaw(string $url, string $body, array $headers = []): string
    {
        return $this->dispatchPost($url, 'raw', $body, $headers, function () use ($url, $body, $headers) {
            return $this->getHttpClient()->post($url, [
                'body' => $body,
                'headers' => $headers,
            ]);
        });
    }

    /**
     * Tüm POST varyantları için ortak yaşam döngüsü: logging + try/catch + lastError.
     */
    private function dispatchPost(string $url, string $type, mixed $payload, array $headers, callable $executor): string
    {
        $this->lastHttpError = null;
        $logger = $this->resolveLogger();
        $started = microtime(true);

        $logger->info('sanalpos.http.request', [
            'url' => $url,
            'type' => $type,
            'payload' => CardDataRedactor::redactPayload($payload),
            'headers' => $this->redactHeaders($headers),
        ]);

        try {
            $response = $executor();
            $body = $response->getBody()->getContents();
            $status = $response->getStatusCode();

            $logger->info('sanalpos.http.response', [
                'url' => $url,
                'status' => $status,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                'body_preview' => CardDataRedactor::redactPayload(substr($body, 0, 2000)),
            ]);

            return $body;
        } catch (\Throwable $e) {
            $this->lastHttpError = $e->getMessage();
            $logger->error('sanalpos.http.error', [
                'url' => $url,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw new HttpRequestException($e->getMessage(), $url, $e->getCode(), $e);
        }
    }

    /**
     * LoggerAware trait varsa onun logger'ını döner, yoksa NullLogger.
     */
    private function resolveLogger(): LoggerInterface
    {
        if (method_exists($this, 'logger')) {
            $candidate = $this->logger();
            if ($candidate instanceof LoggerInterface) {
                return $candidate;
            }
        }

        return new NullLogger;
    }

    /**
     * Header'ları log için maskele — Authorization, auth-hash, X-Api-Key gibi alanları redaktet.
     */
    private function redactHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'auth-hash', 'x-api-key', 'apikey', 'api-key', 'authkey'];
        $result = [];
        foreach ($headers as $k => $v) {
            $key = strtolower((string) $k);
            $result[$k] = in_array($key, $sensitive, true) ? '***' : $v;
        }

        return $result;
    }
}
