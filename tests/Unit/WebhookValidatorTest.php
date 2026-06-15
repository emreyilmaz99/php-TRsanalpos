<?php

use Emreyilmaz99\SanalPos\Support\WebhookValidator;

it('IP whitelist tam eşleşmeyi tanır', function () {
    expect(WebhookValidator::ipAllowed('1.2.3.4', ['1.2.3.4', '5.6.7.8']))->toBeTrue();
    expect(WebhookValidator::ipAllowed('9.9.9.9', ['1.2.3.4']))->toBeFalse();
});

it('IP whitelist CIDR aralığını tanır', function () {
    expect(WebhookValidator::ipAllowed('193.107.205.50', ['193.107.205.0/24']))->toBeTrue();
    expect(WebhookValidator::ipAllowed('193.107.205.255', ['193.107.205.0/24']))->toBeTrue();
    expect(WebhookValidator::ipAllowed('193.107.206.1', ['193.107.205.0/24']))->toBeFalse();
});

it('Geçersiz IP reddedilir', function () {
    expect(WebhookValidator::ipAllowed('not-an-ip', ['1.2.3.4']))->toBeFalse();
    expect(WebhookValidator::ipAllowed('', ['1.2.3.4']))->toBeFalse();
});

it('isStale eski timestamp\'leri yakalar', function () {
    // 1 saat önce → stale
    expect(WebhookValidator::isStale(time() - 3700, 3600))->toBeTrue();

    // 10 dakika önce → stale değil
    expect(WebhookValidator::isStale(time() - 600, 3600))->toBeFalse();

    // ISO timestamp parse
    expect(WebhookValidator::isStale('2020-01-01T00:00:00Z', 3600))->toBeTrue();

    // DateTimeImmutable
    expect(WebhookValidator::isStale(new DateTimeImmutable('-1 day'), 3600))->toBeTrue();
});

it('garantiTestIps en az bir kayıt döner', function () {
    expect(WebhookValidator::garantiTestIps())->not->toBeEmpty();
});
