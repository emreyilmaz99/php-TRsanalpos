<?php

namespace Emreyilmaz99\SanalPos\Events;

/**
 * Hafif event dispatcher — Laravel container varsa onun event dispatcher'ını kullanır,
 * yoksa lokal listener registry üzerinden dispatch eder.
 *
 * Kullanım:
 *   SanalPos\Events\EventDispatcher::listen(PaymentSucceeded::class, fn ($e) => Log::info(...));
 *   EventDispatcher::dispatch(new PaymentSucceeded(...));
 *
 * Laravel'de Event::listen(PaymentSucceeded::class, ...) da çalışır — kütüphane
 * otomatik olarak Laravel dispatcher'ına da bildirir.
 */
class EventDispatcher
{
    /** @var array<class-string, callable[]> */
    private static array $listeners = [];

    /**
     * Event tipine bir listener ekle. Aynı event'e birden fazla listener bağlanabilir.
     *
     * @param  class-string<PaymentEvent>  $eventClass
     */
    public static function listen(string $eventClass, callable $listener): void
    {
        self::$listeners[$eventClass][] = $listener;
    }

    /**
     * Event'i hem lokal listener'lara hem (varsa) Laravel dispatcher'ına gönderir.
     */
    public static function dispatch(PaymentEvent $event): void
    {
        // 1) Lokal kayıtlı listener'lar
        foreach (self::$listeners[$event::class] ?? [] as $listener) {
            try {
                $listener($event);
            } catch (\Throwable $e) {
                // Listener hataları payment akışını bozmasın — sessizce yut.
                // (PSR-3 logger'a yazdırmak için kütüphane çapında app('log') tercih edebilir.)
            }
        }

        // 2) Laravel dispatcher (varsa)
        if (function_exists('app') && function_exists('event')) {
            try {
                /** @phpstan-ignore-next-line */
                event($event);
            } catch (\Throwable) {
                // Laravel yoksa veya event() farklı bir signature ile çakışırsa sessiz.
            }
        }
    }

    /**
     * Test/teardown için tüm listener'ları temizler.
     */
    public static function flushListeners(): void
    {
        self::$listeners = [];
    }
}
