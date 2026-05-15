<?php

namespace EvrenOnur\SanalPos\Events;

/**
 * Bir ödeme akışı başlatıldığında dispatch edilir: sale(), initializeHostedPayment().
 * Henüz banka cevabı yok — sadece istek hazırlandı / atılmak üzere.
 */
class PaymentInitiated extends PaymentEvent {}
