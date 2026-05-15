<?php

namespace EvrenOnur\SanalPos\Support;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * PSR-3 logger entegrasyonu için kütüphane içi trait.
 *
 * Gateway'ler bu trait'i use ettiklerinde opsiyonel bir PSR-3 logger inject edilebilir.
 * Logger varsayılan olarak NullLogger'dır, BC kırılmaz.
 *
 * Loglama kartsız yapılır — SaleInfo zaten __debugInfo ve jsonSerialize'da maskelenir;
 * gateway'ler ham request payload'ını logger'a vermek yerine `$saleInfo` referansını
 * geçer, logger çıktısı otomatik maskeler.
 */
trait LoggerAware
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    protected function logger(): LoggerInterface
    {
        return $this->logger ??= new NullLogger;
    }
}
