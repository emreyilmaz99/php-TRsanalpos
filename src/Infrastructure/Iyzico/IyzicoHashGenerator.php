<?php

namespace EvrenOnur\SanalPos\Infrastructure\Iyzico;

/**
 * Iyzico IYZWSv2 authentication header generator.
 *
 * Algoritma (iyzipay-php IyziAuthV2Generator referansı):
 *   payload  = uriPath + json_body  (json_body boş array değilse)
 *   sigData  = randomString + payload
 *   sig      = hex(hmac_sha256(secret, sigData))
 *   hashStr  = "apiKey:" + apiKey + "&randomKey:" + rnd + "&signature:" + sig
 *   header   = "IYZWSv2 " + base64(hashStr)
 *
 * KRİTİK DETAYLAR:
 *  - `$uri` HER ZAMAN sadece PATH ("/payment/...") — baseUrl/host olmadan.
 *    SDK CheckoutFormInitialize::create() içinde `getHttpHeadersV2($uri, ...)` ile
 *    sadece path geçer. Full URL ile hash hesaplarsanız Iyzico "Geçersiz imza" döner.
 *  - JSON body, HTTP POST'ta gönderilenle birebir aynı string olmalı. Encode flag'leri
 *    IyzicoHttpClient::post() ile uyumlu olmak zorunda (JSON_PRESERVE_ZERO_FRACTION).
 *  - V2 modda `x-iyzi-rnd` header EKLENMEZ — random embedded içinde base64'lü auth
 *    string'in içindedir.
 */
class IyzicoHashGenerator
{
    /**
     * @param  PKISerializable  $request  Hash içine girecek request DTO. Body json_encode($request->toArray()) ile elde edilir.
     * @param  IyzicoOptions  $options  apiKey + secretKey + baseUrl
     * @param  string  $uri  Endpoint PATH ("/payment/..."); host/baseUrl olmadan.
     */
    public static function getHttpHeaders(PKISerializable $request, IyzicoOptions $options, string $uri): array
    {
        $randomString = uniqid();
        $body = method_exists($request, 'toArray') ? $request->toArray() : [];
        $hashContent = self::generateAuthContentV2($uri, $body, $options, $randomString);

        return [
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
            'x-iyzi-client-version' => 'sanalpos-php-1.0',
            'Authorization' => 'IYZWSv2 ' . $hashContent,
        ];
    }

    private static function generateAuthContentV2(string $uri, array $body, IyzicoOptions $options, string $randomString): string
    {
        $signature = self::getHmacSHA256Signature($uri, $body, $options->secretKey, $randomString);

        $hashStr = 'apiKey:' . $options->apiKey
            . '&randomKey:' . $randomString
            . '&signature:' . $signature;

        return base64_encode($hashStr);
    }

    private static function getHmacSHA256Signature(string $uri, array $body, string $secretKey, string $randomString): string
    {
        $payload = self::buildPayload($uri, $body);
        $dataToEncrypt = $randomString . $payload;
        $hash = hash_hmac('sha256', $dataToEncrypt, $secretKey, true);

        return bin2hex($hash);
    }

    /**
     * Hash payload = uri path + json body (boş array değilse).
     * Iyzico SDK getPayload pattern'i. /v2/* için path subset alma kısmı bu kütüphanenin
     * kullandığı endpoint'lerle relevant değil.
     */
    private static function buildPayload(string $uri, array $body): string
    {
        $jsonBody = json_encode($body, JSON_PRESERVE_ZERO_FRACTION);

        if ($jsonBody !== false && $jsonBody !== '[]') {
            return $uri . $jsonBody;
        }

        return $uri;
    }
}
