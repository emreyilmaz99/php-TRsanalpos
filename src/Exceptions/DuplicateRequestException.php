<?php

namespace Emreyilmaz99\SanalPos\Exceptions;

/**
 * Aynı idempotency_key ile kısa süre içinde tekrar istek atıldığında fırlatılır.
 * Müşteri "Öde" butonuna iki kez bastığında, browser back+forward ile aynı POST'u tekrarladığında
 * banka'ya çift kayıt gitmesini önler.
 */
class DuplicateRequestException extends \RuntimeException
{
    public function __construct(
        public readonly string $idempotencyKey,
        ?string $message = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message ?? "Aynı idempotency_key (`{$idempotencyKey}`) ile tekrar istek atıldı.",
            0,
            $previous
        );
    }
}
