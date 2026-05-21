# Changelog

## [0.1.4](https://github.com/emreyilmaz99/php-TRsanalpos/compare/v0.1.3...v0.1.4) (2026-05-21)

## [0.1.3](https://github.com/emreyilmaz99/php-TRsanalpos/compare/v0.1.2...v0.1.3) (2026-05-21)

### 📝 Dokümantasyon

* fix composer require command to new package name ([e979082](https://github.com/emreyilmaz99/php-TRsanalpos/commit/e97908245bb2ed180edf881a55242c538fa1ed39))

## [0.1.2](https://github.com/emreyilmaz99/php-TRsanalpos/compare/v0.1.1...v0.1.2) (2026-05-21)

### 💄 Kod Stili

* pint normalize Throwable namespace in smoke test ([0aa6dde](https://github.com/emreyilmaz99/php-TRsanalpos/commit/0aa6dde709c9d035eed9227bdfe78096e41d70ca))

## [0.1.1](https://github.com/emreyilmaz99/php-TRsanalpos/compare/v0.1.0...v0.1.1) (2026-05-21)

### 📝 Dokümantasyon

* soften README — drop comparison table, list this fork's additions neutrally ([a239df0](https://github.com/emreyilmaz99/php-TRsanalpos/commit/a239df0ab75be2f2f3ba1e81bba47b962d844fa1))

## 0.1.0 (2026-05-21)

### 🚀 Yeni Özellikler

* add hosted (bank-side card entry) payment mode ([417d76b](https://github.com/emreyilmaz99/php-TRsanalpos/commit/417d76bb86277bf2fd55eee28919f64cc07a4133))
* **deps:** update PHP requirement to 8.3 and improve composer.json formatting ([1132880](https://github.com/emreyilmaz99/php-TRsanalpos/commit/113288085ab9168c35bd5aea81ae33c26b8cbc1f))
* **deps:** upgrade to Laravel 13 and PHP 8.3 ([901b784](https://github.com/emreyilmaz99/php-TRsanalpos/commit/901b7849ea7dddefe3fd1ed81bb31d0a5469cc91))
* **hosted:** Sprint 2 — add hosted-payment to PayNKolay, Vakıfbank, Vakıf Katılım ([ba4af20](https://github.com/emreyilmaz99/php-TRsanalpos/commit/ba4af20bc8507a4e2b588f2202de8d87112e2e97))
* **http:** introduce MakesHttpRequests trait for centralized HTTP handling ([93cfd14](https://github.com/emreyilmaz99/php-TRsanalpos/commit/93cfd14fd002fcf37c26536a022cb4f888fb60f2))
* **license:** add MIT License file to the repository ([814a2aa](https://github.com/emreyilmaz99/php-TRsanalpos/commit/814a2aa3d4292a56d758ab005fde3871cd1ea96b))
* **nestpay:** add callbackUrl (server-to-server webhook) to hosted-payment form ([b6c6046](https://github.com/emreyilmaz99/php-TRsanalpos/commit/b6c60460f12eeee27fc206ad98412e4e1a1a8110))
* port CP.VPOS provider updates for v1.2.0 ([430d0a1](https://github.com/emreyilmaz99/php-TRsanalpos/commit/430d0a155d585ed0b5371154f405c0c1588b8cac))
* refactor payment gateway classes to improve structure and readability ([bd4883e](https://github.com/emreyilmaz99/php-TRsanalpos/commit/bd4883eed5ee98634a6da33384d6af7814abe164))
* Sprint 1 — architectural foundation for production hardening ([c556ee2](https://github.com/emreyilmaz99/php-TRsanalpos/commit/c556ee2ad6925187222fd96e63e5e59a01c2487f))
* Sprint 3 — production hardening (HTTP logging, events, idempotency, webhook validation, PHPStan) ([428a080](https://github.com/emreyilmaz99/php-TRsanalpos/commit/428a080a77c170715ecfef675465b953caa0fdbf))
* **StringHelper:** add utility methods for string manipulation and validation ([69a944c](https://github.com/emreyilmaz99/php-TRsanalpos/commit/69a944ca368953c07388acda45e3eeebece4e2bb))
* **tests:** add comprehensive ExampleUsageTest for SanalPos library usage ([44e0d94](https://github.com/emreyilmaz99/php-TRsanalpos/commit/44e0d94b69513f78367804f80dba15d73e9f1494))
* **tests:** add comprehensive VposDocumentationTest for SanalPos library ([4542db2](https://github.com/emreyilmaz99/php-TRsanalpos/commit/4542db2c3e1350938e13d8509025ea1823f2aa10))

### 🐛 Hata Düzeltmeleri

* **ci:** fix test matrix compatibility issues ([1e50a9f](https://github.com/emreyilmaz99/php-TRsanalpos/commit/1e50a9ffb362f1eb4fdbb20ef775b6b1c018cacf))
* **ci:** release workflow changelog interpolation shell syntax hatası düzeltildi\n\nChangelog içeriği doğrudan shell'e interpolate edildiğinde parantez\niçeren commit hash'leri syntax error oluşturuyordu. Changelog artık\nenvironment variable olarak geçiriliyor." ([8241435](https://github.com/emreyilmaz99/php-TRsanalpos/commit/824143579cef3a29f404c2afa6b3bf71b2f291b9))
* **gateway:** fix snake_case field names in API requests for Akbank, Ahlpay and Tami ([cf255e2](https://github.com/emreyilmaz99/php-TRsanalpos/commit/cf255e2f2ca87cd6be00bd470f6f515b7a63b121))
* **iyzico:** correct field names and hash compute order for 3D Secure ([8f34b51](https://github.com/emreyilmaz99/php-TRsanalpos/commit/8f34b519473549fb95b0ae39e9087be9f9101c5f))
* **iyzico:** migrate auth header to IYZWSv2 algorithm with path-only signing ([abc1ead](https://github.com/emreyilmaz99/php-TRsanalpos/commit/abc1eaddf4762f0bbb3981f36be95d02b808c983))
* update ZiraatPayGateway test URLs ([5ccfcdd](https://github.com/emreyilmaz99/php-TRsanalpos/commit/5ccfcddfd8f94407083aa690ac0996d288ff4081))

### ♻️ Yeniden Düzenleme

* AbstractGateway base class, hata yönetimi ve kod kalitesi iyileştirmeleri\n\n- AbstractGateway base class oluşturuldu (stub metotlar tek noktada)\n- 14 gateway refactor edildi (tekrar kod kaldırıldı)\n- HttpRequestException eklendi (sessiz hata yutma kaldırıldı)\n- MakesHttpRequests: SSL verify ve connect_timeout config'den okunuyor\n- SaleQueryResponse: transactionStatu → transactionStatus, statu → status\n- detectCardType: bilinmeyen kartlar için Unknown döner\n- BankService::allBanks() static cache eklendi\n- YapiKredi currency kontrolü gateway'e taşındı\n- Nestpay: kullanılmayan URL property'leri kaldırıldı\n- README: yapılandırma ve hata yönetimi bölümleri eklendi\n- Yeni testler: AbstractGatewayTest, HttpRequestExceptionTest" ([2d037ed](https://github.com/emreyilmaz99/php-TRsanalpos/commit/2d037eda87fad214042cf9659493c0ad66f6587c))

### 📝 Dokümantasyon

* add contribution guidelines, PR template and issue templates ([c75eadb](https://github.com/emreyilmaz99/php-TRsanalpos/commit/c75eadbabfdb1919ed3adaa7d0f103ca86841090))

### 💄 Kod Stili

* apply pint formatting and restore composer constraint ([11d52ec](https://github.com/emreyilmaz99/php-TRsanalpos/commit/11d52ec73a376110e0569d87029be66d773d83d1))
* pint ile kod stili düzeltmeleri ([246a1f1](https://github.com/emreyilmaz99/php-TRsanalpos/commit/246a1f13c8258b301e1f9bcef2c0de3497bb21d5))

### 🧪 Testler

* **garanti:** add live full-flow smoke test (sale → cancel) with VPServlet timeout tolerance ([2806be5](https://github.com/emreyilmaz99/php-TRsanalpos/commit/2806be5b0dbe2c115eb6397401ca27d21162772a))
* **integration:** add live sandbox smoke tests for NestPay, Akbank, Payten ([5460f0e](https://github.com/emreyilmaz99/php-TRsanalpos/commit/5460f0e12439b1dcbb7864cc9102006f8998052c))
* **ziraat:** live-validate hosted payment via Payten public sandbox ([4056529](https://github.com/emreyilmaz99/php-TRsanalpos/commit/4056529f8f842a78a49ec88667a7c0246219f5b9))

## [1.2.0](https://github.com/evrenonur/sanalpos/compare/v1.1.0...v1.2.0) (2026-04-27)

### 🚀 Yeni Özellikler

* Paynet sanal POS entegrasyonu eklendi
* CCPayment tabanlı ödeme kuruluşları için taksit komisyon politikası desteği eklendi
* CCPayment 3D ödeme akışı `/payment/complete` tamamlama adımı ile referans implementasyonla hizalandı

### 🐛 Hata Düzeltmeleri

* Iyzico 3D response private response yapısı iki aşamalı detay dönecek şekilde düzeltildi
* README Paynet ve komisyon politikası desteğini kapsayacak şekilde güncellendi

## [1.1.0](https://github.com/evrenonur/sanalpos/compare/v1.0.5...v1.1.0) (2026-04-12)

### 🚀 Yeni Özellikler

* **deps:** update PHP requirement to 8.3 and improve composer.json formatting ([1132880](https://github.com/evrenonur/sanalpos/commit/113288085ab9168c35bd5aea81ae33c26b8cbc1f))
* **deps:** upgrade to Laravel 13 and PHP 8.3 ([901b784](https://github.com/evrenonur/sanalpos/commit/901b7849ea7dddefe3fd1ed81bb31d0a5469cc91))

## [1.0.5](https://github.com/evrenonur/sanalpos/compare/v1.0.4...v1.0.5) (2026-03-06)

### 🐛 Hata Düzeltmeleri

* update ZiraatPayGateway test URLs ([5ccfcdd](https://github.com/evrenonur/sanalpos/commit/5ccfcddfd8f94407083aa690ac0996d288ff4081))

## [1.0.4](https://github.com/evrenonur/sanalpos/compare/v1.0.3...v1.0.4) (2026-02-25)

### 🐛 Hata Düzeltmeleri

* **ci:** release workflow changelog interpolation shell syntax hatası düzeltildi\n\nChangelog içeriği doğrudan shell'e interpolate edildiğinde parantez\niçeren commit hash'leri syntax error oluşturuyordu. Changelog artık\nenvironment variable olarak geçiriliyor." ([8241435](https://github.com/evrenonur/sanalpos/commit/824143579cef3a29f404c2afa6b3bf71b2f291b9))

Tüm önemli değişiklikler bu dosyada belgelenir.

Bu proje [Conventional Commits](https://www.conventionalcommits.org/) standardını takip eder
ve [Semantic Versioning](https://semver.org/) kullanır.

## [v1.0.0] - 2026-02-24

### 🚀 İlk Kararlı Sürüm

#### Desteklenen İşlemler
- Satış (3D'siz direkt satış)
- 3D Secure Satış (RedirectURL + RedirectHTML desteği)
- İptal (gün sonu öncesi işlem iptali)
- İade (tam ve kısmi iade)
- BIN Sorgulama (kart bilgi sorgulama)
- Taksit Sorgulama (BIN bazlı taksit seçenekleri)
- Tüm Taksit Listesi (banka bazlı tüm taksit seçenekleri)

#### Desteklenen Bankalar ve Ödeme Kuruluşları (37+)
Akbank, Akbank Nestpay, Alternatif Bank, Anadolubank, Denizbank, QNB Finansbank,
Finansbank Nestpay, Garanti BBVA, Halkbank, ING Bank, İş Bankası, Şekerbank, TEB,
Türkiye Finans, Vakıfbank, Yapı Kredi, Ziraat Bankası, Kuveyt Türk, Vakıf Katılım,
Cardplus, Paratika, Payten (MSU), ZiraatPay, VakıfPayS, Iyzico, Sipay, QNBpay,
ParamPos, PayBull, Parolapara, IQmoney, Ahlpay, Moka, Vepara, Tami, HalkÖde, PayNKolay

#### Framework & Altyapı
- PHP 8.1+ desteği
- Laravel 10, 11 ve 12 uyumu
- ServiceProvider, Facade ve Config ile tam Laravel entegrasyonu
- Laravel olmadan `SanalPosClient` ile bağımsız kullanım
- MIT Lisans
- 217 test, 808 assertion

[v1.0.0]: https://github.com/evrenonur/sanalpos/releases/tag/v1.0.0
