<?php

namespace Emreyilmaz99\SanalPos\Exceptions;

use RuntimeException;

/**
 * Circuit breaker açık olduğunda fırlatılır — ardışık başarısızlık eşiği aşıldı,
 * bir süre boyunca bu host'a istek yapılmayacak.
 */
class CircuitOpenException extends RuntimeException
{
    public function __construct(
        public readonly string $host,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct(
            sprintf('Circuit open for "%s"; retry after %ds.', $host, $retryAfterSeconds)
        );
    }
}
