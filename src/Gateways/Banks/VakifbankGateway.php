<?php

namespace EvrenOnur\SanalPos\Gateways\Banks;

use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsHostedPayment;
use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsRefund;
use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\CancelRequest;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\Requests\RefundRequest;
use EvrenOnur\SanalPos\DTOs\Requests\Sale3DResponse;
use EvrenOnur\SanalPos\DTOs\Requests\SaleRequest;
use EvrenOnur\SanalPos\DTOs\Responses\CancelResponse;
use EvrenOnur\SanalPos\DTOs\Responses\HostedPaymentResponse;
use EvrenOnur\SanalPos\DTOs\Responses\RefundResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleResponse;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\Gateways\AbstractGateway;
use EvrenOnur\SanalPos\Support\StringHelper;

class VakifbankGateway extends AbstractGateway implements SupportsHostedPayment, SupportsRefund
{
    private string $urlAPITest = 'https://onlineodemetest.vakifbank.com.tr:4443/VposService/v3/Vposreq.aspx';

    private string $urlAPILive = 'https://onlineodeme.vakifbank.com.tr:4443/VposService/v3/Vposreq.aspx';

    private string $url3DTest = 'https://3dsecuretest.vakifbank.com.tr:4443/MPIAPI/MPI_Enrollment.aspx';

    private string $url3DLive = 'https://3dsecure.vakifbank.com.tr:4443/MPIAPI/MPI_Enrollment.aspx';

    // Common Payment (Ortak Ödeme) — gerçek hosted akış, kart Vakıfbank sayfasında
    private string $urlCPRegisterTest = 'https://cptest.vakifbank.com.tr/CommonPayment/api/RegisterTransaction';

    private string $urlCPRegisterLive = 'https://cpweb.vakifbank.com.tr/CommonPayment/api/RegisterTransaction';

    private string $urlCPQueryTest = 'https://cptest.vakifbank.com.tr/CommonPayment/api/VposTransaction';

    private string $urlCPQueryLive = 'https://cpweb.vakifbank.com.tr/CommonPayment/api/VposTransaction';

    public function sale(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        if ($request->payment_3d?->confirm === true) {
            return $this->sale3D($request, $auth);
        }

        $response = new SaleResponse(order_number: $request->order_number);

        $amount = StringHelper::formatAmount($request->sale_info->amount);
        $installment = $request->sale_info->installment > 1 ? $request->sale_info->installment : 0;
        $expiry = $request->sale_info->card_expiry_year . str_pad($request->sale_info->card_expiry_month, 2, '0', STR_PAD_LEFT);

        $xmlParts = [
            'MerchantId' => $auth->merchant_id,
            'Password' => $auth->merchant_password,
            'TerminalNo' => $auth->merchant_user,
            'TransactionType' => 'Sale',
            'TransactionId' => '',
            'CurrencyAmount' => $amount,
            'CurrencyCode' => (string) $request->sale_info->currency->value,
            'Pan' => $request->sale_info->card_number,
            'Cvv' => $request->sale_info->card_cvv,
            'Expiry' => $expiry,
            'OrderId' => $request->order_number,
            'ClientIp' => $request->customer_ip_address,
            'TransactionDeviceSource' => '0',
        ];

        if ($installment > 0) {
            $xmlParts['NumberOfInstallments'] = (string) $installment;
        }

        $xml = StringHelper::toXml($xmlParts, 'VposRequest');
        $url = $auth->test_platform ? $this->urlAPITest : $this->urlAPILive;
        $resp = $this->prmStrRequest($xml, $url);
        $dic = StringHelper::xmlToDictionary($resp, 'VposResponse');

        $response->private_response = $dic;

        if (($dic['ResultCode'] ?? '') === '0000') {
            $response->status = SaleResponseStatus::Success;
            $response->message = 'İşlem başarılı';
            $response->transaction_id = $dic['TransactionId'] ?? '';
        } else {
            $response->status = SaleResponseStatus::Error;
            $response->message = $dic['ResultDetail'] ?? 'İşlem sırasında bir hata oluştu';
        }

        return $response;
    }

    private function sale3D(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        $response = new SaleResponse(order_number: $request->order_number);

        $amount = StringHelper::formatAmount($request->sale_info->amount);
        $installment = $request->sale_info->installment > 1 ? (string) $request->sale_info->installment : '';
        $expDate = substr((string) $request->sale_info->card_expiry_year, 2) .
            str_pad($request->sale_info->card_expiry_month, 2, '0', STR_PAD_LEFT);

        $req = [
            'MerchantId' => $auth->merchant_id,
            'MerchantPassword' => $auth->merchant_password,
            'VerifyEnrollmentRequestId' => bin2hex(random_bytes(16)),
            'Pan' => $request->sale_info->card_number,
            'ExpiryDate' => $expDate,
            'PurchaseAmount' => $amount,
            'Currency' => (string) $request->sale_info->currency->value,
            'SuccessUrl' => $request->payment_3d->return_url,
            'FailureUrl' => $request->payment_3d->return_url,
            'SessionInfo' => $request->order_number,
        ];

        if (! empty($installment)) {
            $req['InstallmentCount'] = $installment;
        }

        $url3D = $auth->test_platform ? $this->url3DTest : $this->url3DLive;
        $resp = $this->httpPostForm($url3D, $req);
        $dic = StringHelper::xmlToDictionary($resp);

        $response->private_response = $dic;

        $status = $dic['Message']['VERes']['Status'] ?? $dic['IPaySecure']['Message']['VERes']['Status'] ?? '';

        if ($status === 'Y') {
            $pareq = $dic['Message']['VERes']['PaReq'] ?? $dic['IPaySecure']['Message']['VERes']['PaReq'] ?? '';
            $acsUrl = $dic['Message']['VERes']['ACSUrl'] ?? $dic['IPaySecure']['Message']['VERes']['ACSUrl'] ?? '';
            $termUrl = $request->payment_3d->return_url;
            $md = $req['VerifyEnrollmentRequestId'];

            $html = '<html><body onload="document.frm.submit();">';
            $html .= '<form name="frm" method="POST" action="' . htmlspecialchars($acsUrl) . '">';
            $html .= '<input type="hidden" name="PaReq" value="' . htmlspecialchars($pareq) . '">';
            $html .= '<input type="hidden" name="TermUrl" value="' . htmlspecialchars($termUrl) . '">';
            $html .= '<input type="hidden" name="MD" value="' . htmlspecialchars($md) . '">';
            $html .= '</form></body></html>';

            $response->status = SaleResponseStatus::RedirectHTML;
            $response->message = $html;
        } else {
            $response->status = SaleResponseStatus::Error;
            $response->message = 'Bu kart 3D Secure ile kullanılamaz';
        }

        return $response;
    }

    public function sale3DResponse(Sale3DResponse $request, MerchantAuth $auth): SaleResponse
    {
        $response = new SaleResponse;
        $response->private_response = ['response_1' => $request->responseArray];

        $status = $request->responseArray['Status'] ?? '';

        if ($status !== 'Y') {
            $response->status = SaleResponseStatus::Error;
            $response->message = '3D doğrulaması başarısız';

            return $response;
        }

        $orderId = $request->responseArray['SessionInfo'] ?? $request->responseArray['order_number'] ?? '';
        $eci = $request->responseArray['Eci'] ?? '';
        $cavv = $request->responseArray['Cavv'] ?? '';
        $mpiTransactionId = $request->responseArray['VerifyEnrollmentRequestId'] ?? '';
        $purchAmount = $request->responseArray['PurchAmount'] ?? '0';
        $amount = StringHelper::formatAmount((float) $purchAmount / 100);
        $installment = $request->responseArray['InstallmentCount'] ?? '';

        $expiry = $request->responseArray['Expiry'] ?? '';
        if (strlen($expiry) === 4) {
            $expiry = '20' . $expiry;
        }

        $xmlParts = [
            'MerchantId' => $auth->merchant_id,
            'Password' => $auth->merchant_password,
            'TerminalNo' => $auth->merchant_user,
            'TransactionType' => 'Sale',
            'TransactionId' => '',
            'CurrencyAmount' => $amount,
            'CurrencyCode' => (string) ($request->currency?->value ?? 949),
            'Pan' => $request->responseArray['Pan'] ?? '',
            'Cvv' => '',
            'Expiry' => $expiry,
            'OrderId' => $orderId,
            'ECI' => $eci,
            'CAVV' => $cavv,
            'MpiTransactionId' => $mpiTransactionId,
            'ClientIp' => '1.1.1.1',
            'TransactionDeviceSource' => '0',
        ];

        if (! empty($installment) && $installment !== '0') {
            $xmlParts['NumberOfInstallments'] = $installment;
        }

        $xml = StringHelper::toXml($xmlParts, 'VposRequest');
        $url = $auth->test_platform ? $this->urlAPITest : $this->urlAPILive;
        $resp = $this->prmStrRequest($xml, $url);
        $dic = StringHelper::xmlToDictionary($resp, 'VposResponse');

        $response->private_response['response_2'] = $dic;
        $response->order_number = $orderId;

        if (($dic['ResultCode'] ?? '') === '0000') {
            $response->status = SaleResponseStatus::Success;
            $response->message = 'İşlem başarılı';
            $response->transaction_id = $dic['TransactionId'] ?? '';
        } else {
            $response->status = SaleResponseStatus::Error;
            $response->message = $dic['ResultDetail'] ?? 'İşlem sırasında bir hata oluştu';
        }

        return $response;
    }

    public function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse
    {
        $response = new CancelResponse(status: ResponseStatus::Error);

        $xmlParts = [
            'MerchantId' => $auth->merchant_id,
            'Password' => $auth->merchant_password,
            'TerminalNo' => $auth->merchant_user,
            'TransactionType' => 'Cancel',
            'ReferenceTransactionId' => $request->transaction_id,
            'ClientIp' => $request->customer_ip_address,
        ];

        $xml = StringHelper::toXml($xmlParts, 'VposRequest');
        $url = $auth->test_platform ? $this->urlAPITest : $this->urlAPILive;
        $resp = $this->prmStrRequest($xml, $url);
        $dic = StringHelper::xmlToDictionary($resp, 'VposResponse');

        $response->private_response = $dic;

        if (($dic['ResultCode'] ?? '') === '0000') {
            $response->status = ResponseStatus::Success;
            $response->message = 'İşlem başarılı';
        } else {
            $response->message = $dic['ResultDetail'] ?? 'İşlem iptal edilemedi';
        }

        return $response;
    }

    public function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse
    {
        $response = new RefundResponse(status: ResponseStatus::Error);

        $xmlParts = [
            'MerchantId' => $auth->merchant_id,
            'Password' => $auth->merchant_password,
            'TerminalNo' => $auth->merchant_user,
            'TransactionType' => 'Refund',
            'ReferenceTransactionId' => $request->transaction_id,
            'CurrencyAmount' => StringHelper::formatAmount($request->refund_amount),
            'ClientIp' => $request->customer_ip_address,
        ];

        $xml = StringHelper::toXml($xmlParts, 'VposRequest');
        $url = $auth->test_platform ? $this->urlAPITest : $this->urlAPILive;
        $resp = $this->prmStrRequest($xml, $url);
        $dic = StringHelper::xmlToDictionary($resp, 'VposResponse');

        $response->private_response = $dic;

        if (($dic['ResultCode'] ?? '') === '0000') {
            $response->status = ResponseStatus::Success;
            $response->message = 'İşlem başarılı';
            $response->refund_amount = $request->refund_amount;
        } else {
            $response->message = $dic['ResultDetail'] ?? 'İşlem iade edilemedi';
        }

        return $response;
    }

    // Hosted mode docs: Vakıfbank "Common Payment" (Ortak Ödeme) PayFlex CP V4.
    // İki aşama: (1) RegisterTransaction → PaymentToken + CommonPaymentUrl,
    //           (2) Müşteri GET ile {CommonPaymentUrl}?Ptkn={PaymentToken} adresine yönlendirilir.
    // Hash recipe (mews/pos PayFlexCPV4Crypt::createHash referansı):
    //   base64(sha1(HostMerchantId + AmountCode + Amount + MerchantPassword + '' + 'VBank3DPay2014'))
    // MerchantAuth mapping: merchant_id=HostMerchantId, merchant_user=HostTerminalId,
    //                       merchant_password=MerchantPassword.
    public function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse
    {
        $response = new HostedPaymentResponse(order_number: $request->order_number);

        $amount = StringHelper::formatAmount($request->sale_info->amount ?? 0);
        $amountCode = (string) ($request->sale_info->currency?->value ?? 949);

        $body = [
            'HostMerchantId' => $auth->merchant_id,
            'MerchantPassword' => $auth->merchant_password,
            'HostTerminalId' => (string) $auth->getExtra('terminal_id', $auth->merchant_user),
            'TransactionType' => 'Sale',
            'AmountCode' => $amountCode,
            'Amount' => $amount,
            'OrderID' => $request->order_number,
            'IsSecure' => 'true',
            'AllowNotEnrolledCard' => 'false',
            'SuccessUrl' => $request->success_url,
            'FailUrl' => $request->fail_url,
            'RequestLanguage' => strtolower($request->language ?: 'tr') === 'en' ? 'en-US' : 'tr-TR',
            'Extract' => '',
            'CustomItems' => '',
        ];

        if ($request->sale_info && $request->sale_info->installment > 1) {
            $body['InstallmentCount'] = (string) $request->sale_info->installment;
        }

        // Hash: HostMerchantId + AmountCode + Amount + MerchantPassword + '' + 'VBank3DPay2014'
        $body['HashedData'] = base64_encode(sha1(
            $body['HostMerchantId'] . $body['AmountCode'] . $body['Amount'] . $body['MerchantPassword'] . '' . 'VBank3DPay2014',
            true
        ));

        $registerUrl = $auth->test_platform ? $this->urlCPRegisterTest : $this->urlCPRegisterLive;
        $resp = $this->httpPostForm($registerUrl, $body);

        // Yanıt XML formatında gelir: <CommonPaymentRegistrationResponse>...</CommonPaymentRegistrationResponse>
        $dic = StringHelper::xmlToDictionary($resp, 'CommonPaymentRegistrationResponse');
        $response->private_response = $dic;

        $paymentToken = $dic['PaymentToken'] ?? null;
        $commonPaymentUrl = $dic['CommonPaymentUrl'] ?? null;
        $errorCode = $dic['ErrorCode'] ?? null;

        if (! empty($paymentToken) && ! empty($commonPaymentUrl) && empty($errorCode)) {
            $response->status = ResponseStatus::Success;
            $response->message = 'Hosted ödeme oturumu hazırlandı';
            $response->redirect_method = 'GET';
            $response->redirect_url = $commonPaymentUrl . '?Ptkn=' . urlencode($paymentToken);
            $response->token = $paymentToken;
        } else {
            $response->status = ResponseStatus::Error;
            $response->message = $dic['ResponseMessage'] ?? ($dic['ResponseInfo'] ?? 'Vakıfbank CP register başarısız');
        }

        return $response;
    }

    // Hosted callback: Vakıfbank müşteriyi success/fail URL'sine GET ile döner (Rc, AuthCode, Tid, vs.).
    // Nihai durum için PaymentToken ile VposTransaction status query atılır.
    public function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse
    {
        $payload = $callback->payload;

        $response = new SaleResponse(
            status: SaleResponseStatus::Error,
            order_number: (string) ($payload['OrderId'] ?? $payload['OrderID'] ?? $callback->order_number),
            private_response: $payload,
        );

        // Vakıfbank CP başarı kodu '0000' (PROCEDURE_SUCCESS_CODE)
        $rc = (string) ($payload['Rc'] ?? '');
        if ($rc !== '' && $rc !== '0000') {
            $response->message = $payload['ErrorMessage'] ?? ($payload['Message'] ?? "Hata kodu: $rc");

            return $response;
        }

        // Final status query (opsiyonel — token ile kesin durum sorgulanır)
        $token = $callback->token ?? ($payload['Ptkn'] ?? null);
        if (! empty($token)) {
            $statusBody = [
                'HostMerchantId' => $auth->merchant_id,
                'Password' => $auth->merchant_password,
                'TransactionId' => '',
                'PaymentToken' => $token,
            ];
            $statusUrl = $auth->test_platform ? $this->urlCPQueryTest : $this->urlCPQueryLive;
            $statusResp = $this->httpPostForm($statusUrl, $statusBody);
            $statusDic = StringHelper::xmlToDictionary($statusResp, 'VposTransactionResponse');
            $response->private_response = ['callback' => $payload, 'status' => $statusDic];

            if (($statusDic['ResultCode'] ?? '') === '0000') {
                $response->status = SaleResponseStatus::Success;
                $response->message = 'Ödeme başarılı';
                $response->transaction_id = (string) ($statusDic['TransactionId'] ?? '');

                return $response;
            }

            $response->message = $statusDic['ResultDetail'] ?? 'Durum sorgusu başarısız';

            return $response;
        }

        // Token yoksa callback'in kendi alanlarını dene
        $authCode = $payload['AuthCode'] ?? '';
        if (! empty($authCode)) {
            $response->status = SaleResponseStatus::Success;
            $response->message = 'Ödeme başarılı';
            $response->transaction_id = (string) ($payload['Tid'] ?? $payload['TransactionId'] ?? $authCode);

            return $response;
        }

        $response->message = 'Ödeme doğrulanamadı (token yok, AuthCode yok)';

        return $response;
    }

    // --- Private helpers ---

    private function prmStrRequest(string $xml, string $url): string
    {
        return $this->httpPostForm($url, ['prmstr' => $xml]);
    }
}
