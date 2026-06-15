<?php

namespace Emreyilmaz99\SanalPos\Gateways\Banks;

use Emreyilmaz99\SanalPos\Contracts\Capabilities\SupportsHostedPayment;
use Emreyilmaz99\SanalPos\Contracts\Capabilities\SupportsRefund;
use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\CancelRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\RefundRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\Sale3DResponse;
use Emreyilmaz99\SanalPos\DTOs\Requests\SaleRequest;
use Emreyilmaz99\SanalPos\DTOs\Responses\CancelResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\HostedPaymentResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\RefundResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\SaleResponse;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\AbstractGateway;
use Emreyilmaz99\SanalPos\Support\StringHelper;

class GarantiBBVAGateway extends AbstractGateway implements SupportsHostedPayment, SupportsRefund
{
    private string $urlAPITest = 'https://sanalposprovtest.garantibbva.com.tr/VPServlet';

    private string $urlAPILive = 'https://sanalposprov.garanti.com.tr/VPServlet';

    private string $url3DTest = 'https://sanalposprovtest.garantibbva.com.tr/servlet/gt3dengine';

    private string $url3DLive = 'https://sanalposprov.garanti.com.tr/servlet/gt3dengine';

    public function sale(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        if ($request->payment_3d?->confirm === true) {
            return $this->sale3D($request, $auth);
        }

        $amount = StringHelper::toKurus($request->sale_info->amount);
        $installment = $request->sale_info->installment > 1 ? $request->sale_info->installment : '';

        $hashedPassword = strtoupper($this->getSHA1($auth->merchant_password . str_pad((string) ((int) $auth->merchant_user), 9, '0', STR_PAD_LEFT)));
        $hash = strtoupper($this->getSHA1(
            $request->order_number . $auth->merchant_user . $request->sale_info->card_number . $amount . $hashedPassword
        ));

        $param = [
            'Mode' => $auth->test_platform ? 'TEST' : 'PROD',
            'Version' => 'v0.00',
            'Terminal' => [
                'ProvUserID' => 'PROVAUT',
                'HashData' => $hash,
                'MerchantID' => $auth->merchant_id,
                'UserID' => 'PROVAUT',
                'ID' => $auth->merchant_user,
            ],
            'Customer' => [
                'IPAddress' => $request->customer_ip_address,
                'EmailAddress' => $request->invoice_info?->email_address ?? '',
            ],
            'Card' => [
                'Number' => $request->sale_info->card_number,
                'ExpireDate' => str_pad($request->sale_info->card_expiry_month, 2, '0', STR_PAD_LEFT) . substr((string) $request->sale_info->card_expiry_year, 2),
                'CVV2' => $request->sale_info->card_cvv,
            ],
            'Order' => [
                'OrderID' => $request->order_number,
                'GroupID' => '',
            ],
            'Transaction' => [
                'Type' => 'sales',
                'InstallmentCnt' => (string) $installment,
                'Amount' => $amount,
                'CurrencyCode' => (string) $request->sale_info->currency->value,
                'CardholderPresentCode' => '0',
                'MotoInd' => 'N',
            ],
        ];

        $xml = StringHelper::toXml($param, 'GVPSRequest', 'utf-8');
        $resp = $this->httpPostRaw($auth->test_platform ? $this->urlAPITest : $this->urlAPILive, $xml, ['Content-Type' => 'application/x-www-form-urlencoded']);
        $dic = StringHelper::xmlToDictionary($resp, 'GVPSResponse');
        $dic['originalResponseXML'] = $resp;

        if (isset($dic['Transaction']['Response']['Code'])) {
            if ($dic['Transaction']['Response']['Code'] === '00') {
                return new SaleResponse(
                    status: SaleResponseStatus::Success,
                    message: 'İşlem başarılı',
                    order_number: $request->order_number,
                    transaction_id: $dic['Transaction']['RetrefNum'] ?? '',
                    private_response: $dic,
                );
            }

            return new SaleResponse(
                status: SaleResponseStatus::Error,
                message: $dic['Transaction']['Response']['ErrorMsg'] ?? 'İşlem sırasında bir hata oluştu',
                order_number: $request->order_number,
                private_response: $dic,
            );
        }

        return new SaleResponse(
            status: SaleResponseStatus::Error,
            message: 'İşlem sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.',
            order_number: $request->order_number,
            private_response: $dic,
        );
    }

    private function sale3D(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        $amount = StringHelper::toKurus($request->sale_info->amount);
        $installment = $request->sale_info->installment > 1 ? (string) $request->sale_info->installment : '';

        $hashedPassword = strtoupper($this->getSHA1($auth->merchant_password . str_pad((string) ((int) $auth->merchant_user), 9, '0', STR_PAD_LEFT)));
        $hash = strtoupper($this->getSHA1(
            $auth->merchant_user . $request->order_number . $amount .
                $request->payment_3d->return_url . $request->payment_3d->return_url .
                'sales' . $installment . $auth->merchant_storekey . $hashedPassword
        ));

        $param = [
            'mode' => $auth->test_platform ? 'TEST' : 'PROD',
            'apiversion' => 'v0.01',
            'version' => 'v0.01',
            'secure3dsecuritylevel' => '3D',
            'terminalprovuserid' => 'PROVAUT',
            'terminaluserid' => 'PROVAUT',
            'terminalmerchantid' => $auth->merchant_id,
            'terminalid' => $auth->merchant_user,
            'txntype' => 'sales',
            'txnamount' => $amount,
            'txncurrencycode' => (string) $request->sale_info->currency->value,
            'txninstallmentcount' => $installment,
            'customeripaddress' => $request->customer_ip_address,
            'customeremailaddress' => $request->invoice_info?->email_address ?? '',
            'orderid' => $request->order_number,
            'cardnumber' => $request->sale_info->card_number,
            'cardexpiredatemonth' => str_pad($request->sale_info->card_expiry_month, 2, '0', STR_PAD_LEFT),
            'cardexpiredateyear' => substr((string) $request->sale_info->card_expiry_year, 2),
            'cardcvv2' => $request->sale_info->card_cvv,
            'successurl' => $request->payment_3d->return_url,
            'errorurl' => $request->payment_3d->return_url,
            'secure3dhash' => $hash,
        ];

        $resp = $this->httpPostForm($auth->test_platform ? $this->url3DTest : $this->url3DLive, $param);
        $cleanResp = str_replace(' value ="', ' value="', $resp);
        $form = StringHelper::getFormParams($cleanResp);
        $form['originalResponseHTML'] = $resp;

        if (isset($form['response']) && strtolower($form['response']) === 'error') {
            return new SaleResponse(
                status: SaleResponseStatus::Error,
                message: $form['errmsg'] ?? 'İşlem sırasında hata oluştu.',
                order_number: $request->order_number,
                private_response: $form,
            );
        }

        if (str_contains($resp, 'action="' . $request->payment_3d->return_url . '"')) {
            return $this->sale3DResponse(new Sale3DResponse(
                responseArray: $form,
                currency: $request->sale_info->currency,
            ), $auth);
        }

        if ((isset($form['TermUrl']) && isset($form['MD']) && isset($form['PaReq'])) || (str_contains($resp, '<form ') && str_contains($resp, 'action='))) {
            return new SaleResponse(
                status: SaleResponseStatus::RedirectHTML,
                message: $resp,
                order_number: $request->order_number,
                private_response: $form,
            );
        }

        return new SaleResponse(
            status: SaleResponseStatus::Error,
            message: 'İşlem sırasında hata oluştu. Lütfen daha sonra tekrar deneyiniz.',
            order_number: $request->order_number,
            private_response: $form,
        );
    }

    public function sale3DResponse(Sale3DResponse $request, MerchantAuth $auth): SaleResponse
    {
        $ra = $request->responseArray;

        if (! isset($ra['mdstatus']) || $ra['mdstatus'] !== '1') {
            $messages = [
                '0' => '3-D doğrulama başarısız',
                '2' => 'Kart sahibi veya bankası sisteme kayıtlı değil',
                '3' => 'Kartın bankası sisteme kayıtlı değil',
                '4' => 'Doğrulama denemesi, kart sahibi sisteme daha sonra kayıt olmayı seçmiş',
                '5' => 'Doğrulama yapılamıyor',
                '6' => '3-D Secure hatası',
                '7' => 'Sistem hatası',
                '8' => 'Bilinmeyen kart no',
                '9' => 'Üye İşyeri 3D-Secure sistemine kayıtlı değil',
            ];
            $message = $messages[$ra['mdstatus'] ?? ''] ?? '3-D Secure doğrulanamadı';

            return new SaleResponse(
                status: SaleResponseStatus::Error,
                message: $message,
                order_number: $ra['oid'] ?? '',
                private_response: $ra,
            );
        }

        $amount = $ra['txnamount'] ?? '';
        $hashedPassword = strtoupper($this->getSHA1($auth->merchant_password . str_pad((string) ((int) $auth->merchant_user), 9, '0', STR_PAD_LEFT)));
        $hash = strtoupper($this->getSHA1(($ra['oid'] ?? '') . $auth->merchant_user . $amount . $hashedPassword));

        $param = [
            'Mode' => $auth->test_platform ? 'TEST' : 'PROD',
            'Version' => 'v0.00',
            'Terminal' => [
                'ProvUserID' => 'PROVAUT',
                'HashData' => $hash,
                'MerchantID' => $auth->merchant_id,
                'UserID' => 'PROVAUT',
                'ID' => $auth->merchant_user,
            ],
            'Customer' => [
                'IPAddress' => $ra['customeripaddress'] ?? '',
                'EmailAddress' => $ra['customeremailaddress'] ?? '',
            ],
            'Card' => ['Number' => '', 'ExpireDate' => '', 'CVV2' => ''],
            'Order' => ['OrderID' => $ra['oid'] ?? '', 'GroupID' => '', 'Description' => ''],
            'Transaction' => [
                'Type' => 'sales',
                'InstallmentCnt' => $ra['txninstallmentcount'] ?? '',
                'Amount' => $amount,
                'CurrencyCode' => $ra['txncurrencycode'] ?? '',
                'CardholderPresentCode' => '13',
                'MotoInd' => 'N',
                'Secure3D' => [
                    'AuthenticationCode' => $ra['cavv'] ?? '',
                    'SecurityLevel' => $ra['eci'] ?? '',
                    'TxnID' => $ra['xid'] ?? '',
                    'Md' => $ra['md'] ?? '',
                ],
            ],
        ];

        $xml = StringHelper::toXml($param, 'GVPSRequest', 'utf-8');
        $resp = $this->httpPostRaw($auth->test_platform ? $this->urlAPITest : $this->urlAPILive, $xml, ['Content-Type' => 'application/x-www-form-urlencoded']);
        $dic = StringHelper::xmlToDictionary($resp, 'GVPSResponse');

        if (isset($dic['Transaction']['Response']['Code'])) {
            if ($dic['Transaction']['Response']['Code'] === '00') {
                return new SaleResponse(
                    status: SaleResponseStatus::Success,
                    message: 'İşlem başarılı',
                    order_number: $ra['oid'] ?? '',
                    transaction_id: $dic['Transaction']['RetrefNum'] ?? '',
                    private_response: $dic,
                );
            }

            return new SaleResponse(
                status: SaleResponseStatus::Error,
                message: $dic['Transaction']['Response']['ErrorMsg'] ?? 'İşlem sırasında bir hata oluştu',
                order_number: $ra['oid'] ?? '',
                private_response: $dic,
            );
        }

        return new SaleResponse(
            status: SaleResponseStatus::Error,
            message: 'İşlem sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.',
            order_number: $ra['oid'] ?? '',
            private_response: $dic,
        );
    }

    public function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse
    {
        // Garanti GVPS "void" — aynı gün/uzlaşma öncesi iptal.
        // Amount sıfır gönderiliyor (tam iptal), CurrencyCode opsiyonel ama göndermek güvenli.
        $currency = $request->currency ?? Currency::TRY;
        $amount = '0';

        $hashedPassword = strtoupper($this->getSHA1(
            $auth->merchant_password . str_pad((string) ((int) $auth->merchant_user), 9, '0', STR_PAD_LEFT)
        ));
        $hash = strtoupper($this->getSHA1(
            $request->order_number . $auth->merchant_user . $amount . $hashedPassword
        ));

        $param = [
            'Mode' => $auth->test_platform ? 'TEST' : 'PROD',
            'Version' => 'v0.00',
            'Terminal' => [
                'ProvUserID' => 'PROVRFN',
                'HashData' => $hash,
                'MerchantID' => $auth->merchant_id,
                'UserID' => 'PROVRFN',
                'ID' => $auth->merchant_user,
            ],
            'Customer' => [
                'IPAddress' => $request->customer_ip_address ?: '127.0.0.1',
                'EmailAddress' => '',
            ],
            'Order' => ['OrderID' => $request->order_number, 'GroupID' => ''],
            'Transaction' => [
                'Type' => 'void',
                'Amount' => $amount,
                'CurrencyCode' => (string) $currency->value,
                'CardholderPresentCode' => '0',
                'MotoInd' => 'N',
                'OriginalRetrefNum' => $request->transaction_id,
            ],
        ];

        $xml = StringHelper::toXml($param, 'GVPSRequest', 'utf-8');
        $resp = $this->httpPostRaw(
            $auth->test_platform ? $this->urlAPITest : $this->urlAPILive,
            $xml,
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );
        $dic = StringHelper::xmlToDictionary($resp, 'GVPSResponse');

        $code = $dic['Transaction']['Response']['Code'] ?? '';
        if ($code === '00') {
            return new CancelResponse(
                status: ResponseStatus::Success,
                message: 'İptal işlemi başarılı',
                private_response: $dic,
            );
        }

        return new CancelResponse(
            status: ResponseStatus::Error,
            message: $dic['Transaction']['Response']['ErrorMsg'] ?? 'İptal işlemi başarısız',
            private_response: $dic,
        );
    }

    public function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse
    {
        // Garanti GVPS "refund" — uzlaşma sonrası iade. Aynı gün için cancel() kullanın.
        $currency = $request->currency ?? Currency::TRY;
        $amount = StringHelper::toKurus($request->refund_amount);

        $hashedPassword = strtoupper($this->getSHA1(
            $auth->merchant_password . str_pad((string) ((int) $auth->merchant_user), 9, '0', STR_PAD_LEFT)
        ));
        $hash = strtoupper($this->getSHA1(
            $request->order_number . $auth->merchant_user . $amount . $hashedPassword
        ));

        $param = [
            'Mode' => $auth->test_platform ? 'TEST' : 'PROD',
            'Version' => 'v0.00',
            'Terminal' => [
                'ProvUserID' => 'PROVRFN',
                'HashData' => $hash,
                'MerchantID' => $auth->merchant_id,
                'UserID' => 'PROVRFN',
                'ID' => $auth->merchant_user,
            ],
            'Customer' => [
                'IPAddress' => $request->customer_ip_address ?: '127.0.0.1',
                'EmailAddress' => '',
            ],
            'Order' => ['OrderID' => $request->order_number, 'GroupID' => ''],
            'Transaction' => [
                'Type' => 'refund',
                'Amount' => $amount,
                'CurrencyCode' => (string) $currency->value,
                'CardholderPresentCode' => '0',
                'MotoInd' => 'N',
                'OriginalRetrefNum' => $request->transaction_id,
            ],
        ];

        $xml = StringHelper::toXml($param, 'GVPSRequest', 'utf-8');
        $resp = $this->httpPostRaw(
            $auth->test_platform ? $this->urlAPITest : $this->urlAPILive,
            $xml,
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );
        $dic = StringHelper::xmlToDictionary($resp, 'GVPSResponse');

        $code = $dic['Transaction']['Response']['Code'] ?? '';
        if ($code === '00') {
            return new RefundResponse(
                status: ResponseStatus::Success,
                message: 'İade işlemi başarılı',
                refund_amount: $request->refund_amount,
                private_response: $dic,
            );
        }

        return new RefundResponse(
            status: ResponseStatus::Error,
            message: $dic['Transaction']['Response']['ErrorMsg'] ?? 'İade işlemi başarısız',
            private_response: $dic,
        );
    }

    // Hosted mode docs: Garanti BBVA "Güvenli Ortak Ödeme Sayfası" (3D_OOS_PAY)
    // — kart bilgisi Garanti tarafında girilir. Hash SHA512 (apiversion=512).
    // Resmi dok: https://dev.garantibbva.com.tr/sanal-pos-ortak-odeme-pesin
    // MerchantAuth mapping: merchant_id=MerchantID, merchant_user=TerminalID,
    // merchant_password=ProvisionPassword, merchant_storekey=StoreKey.
    public function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse
    {
        $response = new HostedPaymentResponse(order_number: $request->order_number);

        $amount = StringHelper::toKurus($request->sale_info->amount ?? 0);
        // Garanti OOS tek çekim için "1" gönderilmesini ister (boş null hata veriyor).
        $installRaw = (int) ($request->sale_info->installment ?? 1);
        $installCount = $installRaw <= 1 ? '1' : (string) $installRaw;

        $terminalId = $auth->merchant_user;
        $merchantId = $auth->merchant_id;
        $provUserId = 'PROVAUT';

        // securityData = upper(SHA1(password + zeropad9(terminalId)))
        $securityData = strtoupper($this->getSHA1(
            $auth->merchant_password . str_pad($terminalId, 9, '0', STR_PAD_LEFT)
        ));

        $param = [
            'mode' => $auth->test_platform ? 'TEST' : 'PROD',
            'apiversion' => '512',
            'secure3dsecuritylevel' => '3D_OOS_PAY',
            'terminalprovuserid' => $provUserId,
            'terminaluserid' => $provUserId,
            'terminalmerchantid' => $merchantId,
            'terminalid' => $terminalId,
            'txntype' => 'sales',
            'txnamount' => $amount,
            'txncurrencycode' => (string) ($request->sale_info->currency?->value ?? 949),
            'txninstallmentcount' => $installCount,
            'orderid' => $request->order_number,
            'successurl' => $request->success_url,
            'errorurl' => $request->fail_url,
            'customeripaddress' => $request->customer_ip_address,
            'customeremailaddress' => $request->invoice_info?->email_address ?? 'noreply@example.com',
            'companyname' => $request->invoice_info?->name ?? 'Merchant',
            'lang' => $request->language ?: 'tr',
            'txntimestamp' => gmdate('Y-m-d\TH:i:s.000\Z'),
        ];

        // secure3dhash = upper(SHA512(
        //   terminalid + orderid + txnamount + txncurrencycode + successurl +
        //   errorurl + txntype + txninstallmentcount + storeKey + securityData
        // ))
        $param['secure3dhash'] = strtoupper(hash('sha512', implode('', [
            $param['terminalid'],
            $param['orderid'],
            $param['txnamount'],
            $param['txncurrencycode'],
            $param['successurl'],
            $param['errorurl'],
            $param['txntype'],
            $param['txninstallmentcount'],
            $auth->merchant_storekey,
            $securityData,
        ])));

        $response->status = ResponseStatus::Success;
        $response->message = 'Hosted ödeme formu hazırlandı';
        $response->redirect_method = 'POST';
        $response->redirect_url = $auth->test_platform ? $this->url3DTest : $this->url3DLive;
        $response->form_fields = $param;

        return $response;
    }

    // Hosted mode docs: Garanti 3D_OOS_PAY callback — hashparamsval+storeKey üzerinden
    // base64(SHA1(...)) hash doğrulaması. Başarı kriteri: hash valid + mdstatus∈{1,2,3,4}
    // + procreturncode='00' + response='Approved'.
    public function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse
    {
        $payload = $callback->payload;

        $response = new SaleResponse(
            status: SaleResponseStatus::Error,
            order_number: (string) ($payload['oid'] ?? ($payload['orderid'] ?? $callback->order_number)),
            private_response: $payload,
        );

        $hashValid = $this->validateCallbackHash($payload, $auth->merchant_storekey);

        $mdStatus = (string) ($payload['mdstatus'] ?? '');
        $procReturnCode = (string) ($payload['procreturncode'] ?? '');
        $responseField = strtolower((string) ($payload['response'] ?? ''));

        $isSuccess = $hashValid
            && in_array($mdStatus, ['1', '2', '3', '4'], true)
            && $procReturnCode === '00'
            && $responseField === 'approved';

        if ($isSuccess) {
            $response->status = SaleResponseStatus::Success;
            $response->message = 'İşlem başarılı';
            $response->transaction_id = (string) ($payload['authcode'] ?? '');
        } elseif (! $hashValid) {
            $response->message = 'Hash doğrulaması başarısız';
        } else {
            $response->message = (string) ($payload['mderrormessage'] ?? ($payload['errmsg'] ?? 'Ödeme başarısız'));
        }

        return $response;
    }

    /**
     * Garanti OOS callback hash doğrulaması.
     * Algoritma: base64(SHA1(hashparamsval + storeKey)).
     * Garanti `hashparamsval`'i çoğunlukla hazır gönderir; yoksa hashparams field
     * listesindeki değerleri concat ederek inşa ederiz.
     */
    private function validateCallbackHash(array $payload, string $storeKey): bool
    {
        $providedHash = (string) ($payload['hash'] ?? '');
        if ($providedHash === '') {
            return false;
        }

        $hashParamsVal = (string) ($payload['hashparamsval'] ?? '');
        if ($hashParamsVal === '') {
            $hashParams = (string) ($payload['hashparams'] ?? '');
            foreach (explode(':', $hashParams) as $field) {
                if ($field === '') {
                    continue;
                }
                $hashParamsVal .= (string) ($payload[$field] ?? '');
            }
        }

        if ($hashParamsVal === '') {
            return false;
        }

        $expected = base64_encode(sha1($hashParamsVal . $storeKey, true));

        return hash_equals($expected, $providedHash);
    }

    // --- Private helpers ---

    /**
     * Garanti'de TerminalID ayrı bir alan. $auth->extra['terminal_id'] varsa onu kullan,
     * yoksa merchant_user'a fallback (backward compat).
     */
    private function terminalId(MerchantAuth $auth): string
    {
        return (string) $auth->getExtra('terminal_id', $auth->merchant_user);
    }

    /**
     * Provisioning user (PROVAUT veya hesap-spesifik). $auth->extra['prov_user_id']'den
     * okur, default 'PROVAUT'.
     */
    private function provUserId(MerchantAuth $auth): string
    {
        return (string) $auth->getExtra('prov_user_id', 'PROVAUT');
    }

    /**
     * Refund provisioning user. Default 'PROVRFN'.
     */
    private function refundProvUserId(MerchantAuth $auth): string
    {
        return (string) $auth->getExtra('refund_prov_user_id', 'PROVRFN');
    }

    private function getSHA1(string $data): string
    {
        return hash('sha1', $data);
    }
}
