<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Varsayılan Test Modu
    |--------------------------------------------------------------------------
    |
    | true olarak ayarlandığında tüm işlemler test ortamına yönlendirilir.
    |
    */
    'test_mode' => env('SANALPOS_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP İstek Zaman Aşımı (saniye)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('SANALPOS_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | SSL Doğrulama
    |--------------------------------------------------------------------------
    |
    | Production ortamında true olmalıdır.
    |
    */
    'verify_ssl' => env('SANALPOS_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Bağlantı Zaman Aşımı (saniye)
    |--------------------------------------------------------------------------
    */
    'connect_timeout' => env('SANALPOS_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retry Policy (yalnızca idempotent çağrılar için kullanılır)
    |--------------------------------------------------------------------------
    |
    | Gateway'ler bu policy'yi `$this->withRetry(...)` ile aktive eder.
    | Burada sadece varsayılan değerler tutulur; client kodu kendi RetryPolicy'sini
    | inject edebilir.
    |
    */
    'retry' => [
        'max_attempts' => env('SANALPOS_RETRY_MAX_ATTEMPTS', 3),
        'base_delay_ms' => env('SANALPOS_RETRY_BASE_DELAY_MS', 200),
        'max_delay_ms' => env('SANALPOS_RETRY_MAX_DELAY_MS', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Host başına ardışık fail eşiği aşılırsa devre `open_seconds` süre boyunca
    | kapatılır; bu süre içinde tüm istekler `CircuitOpenException` ile reddedilir.
    | Default kapalıdır — etkinleştirmek için `enabled` => true ve uygun bir
    | CircuitBreakerStore implementasyonu kullan (Laravel cache önerilir).
    |
    */
    'circuit_breaker' => [
        'enabled' => env('SANALPOS_CB_ENABLED', false),
        'failure_threshold' => env('SANALPOS_CB_THRESHOLD', 5),
        'open_seconds' => env('SANALPOS_CB_OPEN_SECONDS', 30),
    ],

];
