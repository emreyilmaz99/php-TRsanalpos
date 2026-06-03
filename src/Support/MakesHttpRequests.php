<?php

namespace EvrenOnur\SanalPos\Support;

use EvrenOnur\SanalPos\Exceptions\CircuitOpenException;
use EvrenOnur\SanalPos\Exceptions\HttpRequestException;
use EvrenOnur\SanalPos\Support\Retry\CircuitBreaker;
use EvrenOnur\SanalPos\Support\Retry\RetryPolicy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
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
 *
 * Retry: Default kapalı (BC). Idempotent çağrılar için `$this->withRetry(RetryPolicy)`
 * ile geçici aktive edilebilir. Aktivasyon yalnızca bir sonraki httpPost*() çağrısı
 * için geçerlidir.
 */
trait MakesHttpRequests
{
    private ?Client $httpClient = null;

    protected ?string $lastHttpError = null;

    protected int $httpTimeout = 30;

    protected int $httpConnectTimeout = 10;

    /**
     * SSL doğrulama (production'da true olmalıdır)
     */
    protected bool $httpVerifySSL = true;

    /**
     * Bir sonraki HTTP çağrısı için retry policy (one-shot).
     */
    private ?RetryPolicy $pendingRetryPolicy = null;

    /**
     * Trait scope'unda paylaşılan circuit breaker (gateway başına).
     */
    private ?CircuitBreaker $circuitBreaker = null;

    private function loadConfigValues(): void
    {
        if (function_exists('config')) {
            $this->httpTimeout = (int) config('sanalpos.timeout', $this->httpTimeout);
            $this->httpConnectTimeout = (int) config('sanalpos.connect_timeout', $this->httpConnectTimeout);
            $this->httpVerifySSL = config('sanalpos.verify_ssl', $this->httpVerifySSL);
        }
    }

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

    public function getLastHttpError(): ?string
    {
        return $this->lastHttpError;
    }

    /**
     * Bir sonraki HTTP çağrısını verilen retry policy ile yap. Tek seferlik;
     * çağrı sonrası policy temizlenir.
     *
     * Sadece **idempotent** çağrılarda kullan (saleQuery, installmentQuery).
     */
    public function withRetry(RetryPolicy $policy): static
    {
        $this->pendingRetryPolicy = $policy;

        return $this;
    }

    /**
     * Circuit breaker enjekte et. Null ise devre kullanılmaz.
     */
    public function setCircuitBreaker(?CircuitBreaker $breaker): void
    {
        $this->circuitBreaker = $breaker;
    }

    /**
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

    private function dispatchPost(string $url, string $type, mixed $payload, array $headers, callable $executor): string
    {
        $this->lastHttpError = null;
        $logger = $this->resolveLogger();
        $policy = $this->pendingRetryPolicy;
        $this->pendingRetryPolicy = null; // one-shot

        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $breaker = $this->circuitBreaker;
        if ($breaker !== null) {
            $breaker->guard($host); // CircuitOpenException
        }

        $maxAttempts = $policy?->maxAttempts ?? 1;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            $started = microtime(true);

            $logger->info('sanalpos.http.request', [
                'url' => $url,
                'type' => $type,
                'attempt' => $attempt,
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
                    'attempt' => $attempt,
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                    'body_preview' => CardDataRedactor::redactPayload(substr($body, 0, 2000)),
                ]);

                $breaker?->recordSuccess($host);

                return $body;
            } catch (BadResponseException $e) {
                $status = $e->getResponse()?->getStatusCode() ?? 0;
                $this->lastHttpError = $e->getMessage();
                $logger->error('sanalpos.http.error', [
                    'url' => $url,
                    'status' => $status,
                    'attempt' => $attempt,
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                $lastException = $e;
                if ($policy !== null && $attempt < $maxAttempts && in_array($status, $policy->retryOnStatus, true)) {
                    usleep($policy->delayMs($attempt) * 1000);

                    continue;
                }

                $breaker?->recordFailure($host);
                throw new HttpRequestException($e->getMessage(), $url, $e->getCode(), $e);
            } catch (ConnectException $e) {
                $this->lastHttpError = $e->getMessage();
                $logger->error('sanalpos.http.error', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                $lastException = $e;
                if ($policy !== null && $attempt < $maxAttempts && $policy->retryOnNetworkError) {
                    usleep($policy->delayMs($attempt) * 1000);

                    continue;
                }

                $breaker?->recordFailure($host);
                throw new HttpRequestException($e->getMessage(), $url, $e->getCode(), $e);
            } catch (\Throwable $e) {
                $this->lastHttpError = $e->getMessage();
                $logger->error('sanalpos.http.error', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                $breaker?->recordFailure($host);
                throw new HttpRequestException($e->getMessage(), $url, $e->getCode(), $e);
            }
        }

        // teorik olarak ulaşılmaz — for döngüsü ya return ya throw ile çıkar
        $breaker?->recordFailure($host);
        throw new HttpRequestException(
            $lastException?->getMessage() ?? 'Retry exhausted',
            $url,
            $lastException?->getCode() ?? 0,
            $lastException,
        );
    }

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
