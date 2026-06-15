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
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\AbstractGateway;
use Emreyilmaz99\SanalPos\Support\StringHelper;

class DenizbankGateway extends AbstractGateway implements SupportsHostedPayment, SupportsRefund
{
    private string $urlAPITest = 'https://test.inter-vpos.com.tr/mpi/Default.aspx';

    private string $urlAPILive = 'https://inter-vpos.com.tr/mpi/Default.aspx';

    public function sale(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        if ($request->payment_3d?->confirm === true) {
            return $this->sale3D($request, $auth);
        }

        $response = new SaleResponse(order_number: $request->order_number);

        $req = [
            'ShopCode' => $auth->merchant_id,
            'UserCode' => $auth->merchant_user,
            'UserPass' => $auth->merchant_password,
            'PurchAmount' => StringHelper::formatAmount($request->sale_info->amount),
            'Currency' => (string) $request->sale_info->currency->value,
            'OrderId' => $request->order_number,
            'TxnType' => 'Auth',
            'InstallmentCount' => $request->sale_info->installment > 1 ? (string) $request->sale_info->installment : '0',
            'SecureType' => 'NonSecure',
            'Pan' => $request->sale_info->card_number,
            'Cvv2' => $request->sale_info->card_cvv,
            'Expiry' => str_pad($request->sale_info->card_expiry_month, 2, '0', STR_PAD_LEFT) . substr((string) $request->sale_info->card_expiry_year, 2),
            'Lang' => 'TR',
        ];

        $res = $this->httpPostForm($auth->test_platform ? $this->urlAPITest : $this->urlAPILive, $req);
        $dic = StringHelper::parseSemicolonResponse($res);

        $response->private_response = $dic;

        if (($dic['ProcReturnCode'] ?? '') === '00') {
            $response->status = SaleResponseStatus::Success;
            $response->message = 'İşlem başarıyla tamamlandı';
        } else {
            $response->status = SaleResponseStatus::Error;
            $response->message = ! empty($dic['ErrorMessage']) ? $dic['ErrorMessage'] : 'İşlem sırasında bir hata oluştu';
        }

        if (isset($dic['TransId'])) {
            $response->transaction_id = $dic['TransId'];
        }

        return $response;
    }

    private function sale3D(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        $response = new SaleResponse(order_number: $request->order_number);

        $rnd = str_replace('-', '', bin2hex(random_bytes(16)));

        $req = [
            'ShopCode' => $auth->merchant_id,
            'PurchAmount' => StringHelper::formatAmount($request->sale_info->amount),
            'Currency' => (string) $request->sale_info->currency->value,
            'OrderId' => $request->order_number,
            'OkUrl' => $request->payment_3d->return_url,
            'FailUrl' => $request->payment_3d->return_url,
            'Rnd' => $rnd,
            'TxnType' => 'Auth',
            'InstallmentCount' => $request->sale_info->installment > 1 ? (string) $request->sale_info->installment : '0',
            'SecureType' => '3DPay',
            'Pan' => $request->sale_info->card_number,
            'Cvv2' => $request->sale_info->card_cvv,
            'Expiry' => str_pad($request->sale_info->card_expiry_month, 2, '0', STR_PAD_LEFT) . substr((string) $request->sale_info->card_expiry_year, 2),
        ];

        $hashText = StringHelper::sha1Base64(
            $req['ShopCode'] . $req['OrderId'] . $req['PurchAmount'] . $req['OkUrl'] .
                $req['FailUrl'] . $req['TxnType'] . $req['InstallmentCount'] . $req['Rnd'] .
                $auth->merchant_storekey
        );

        $req['Hash'] = $hashText;

        $res = $this->httpPostForm($auth->test_platform ? $this->urlAPITest : $this->urlAPILive, $req);
        $form = StringHelper::getFormParams($res);

        $response->private_response = $form;

        if (isset($form['ErrorMessage']) || isset($form['ErrorCode'])) {
            $errorMsg = ($form['ErrorCode'] ?? '') . ' - ' . ($form['ErrorMessage'] ?? '');
            $response->status = SaleResponseStatus::Error;
            $response->message = $errorMsg;
        } else {
            $response->status = SaleResponseStatus::RedirectHTML;
            $response->message = $res;
        }

        return $response;
    }

    public function sale3DResponse(Sale3DResponse $request, MerchantAuth $auth): SaleResponse
    {
        $response = new SaleResponse;
        $response->private_response = $request->responseArray;

        if (isset($request->responseArray['TransId'])) {
            $response->transaction_id = $request->responseArray['TransId'];
        }
        if (isset($request->responseArray['OrderId'])) {
            $response->order_number = $request->responseArray['OrderId'];
        }

        if (($request->responseArray['ProcReturnCode'] ?? '') === '00') {
            $response->status = SaleResponseStatus::Success;
            $response->message = 'İşlem başarılı';
        } elseif (! empty($request->responseArray['ErrorMessage'])) {
            $response->status = SaleResponseStatus::Error;
            $response->message = $request->responseArray['ErrorMessage'];
        } else {
            $response->status = SaleResponseStatus::Error;
            $response->message = 'İşlem sırasında bir hata oluştu';
        }

        return $response;
    }

    public function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse
    {
        $response = new CancelResponse(status: ResponseStatus::Error);

        $req = [
            'ShopCode' => $auth->merchant_id,
            'UserCode' => $auth->merchant_user,
            'UserPass' => $auth->merchant_password,
            'orgOrderId' => $request->order_number,
            'TxnType' => 'Void',
            'SecureType' => 'NonSecure',
            'Lang' => 'TR',
        ];

        $res = $this->httpPostForm($auth->test_platform ? $this->urlAPITest : $this->urlAPILive, $req);
        $dic = StringHelper::parseSemicolonResponse($res);
        $response->private_response = $dic;

        if (($dic['ProcReturnCode'] ?? '') === '00') {
            $response->status = ResponseStatus::Success;
            $response->message = 'İşlem başarılı';
        } elseif (! empty($dic['ErrorMessage'])) {
            $response->message = $dic['ErrorMessage'];
        } else {
            $response->message = 'İşlem iptal edilemedi';
        }

        return $response;
    }

    public function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse
    {
        $response = new RefundResponse(status: ResponseStatus::Error);

        $req = [
            'ShopCode' => $auth->merchant_id,
            'UserCode' => $auth->merchant_user,
            'UserPass' => $auth->merchant_password,
            'PurchAmount' => StringHelper::formatAmount($request->refund_amount),
            'Currency' => (string) ($request->currency?->value ?? 949),
            'orgOrderId' => $request->order_number,
            'TxnType' => 'Refund',
            'SecureType' => 'NonSecure',
            'Lang' => 'TR',
        ];

        $res = $this->httpPostForm($auth->test_platform ? $this->urlAPITest : $this->urlAPILive, $req);
        $dic = StringHelper::parseSemicolonResponse($res);
        $response->private_response = $dic;

        if (($dic['ProcReturnCode'] ?? '') === '00') {
            $response->status = ResponseStatus::Success;
            $response->message = 'İşlem başarılı';
            $response->refund_amount = $request->refund_amount;
        } elseif (! empty($dic['ErrorMessage'])) {
            $response->message = $dic['ErrorMessage'];
        } else {
            $response->message = 'İşlem iade edilemedi';
        }

        return $response;
    }

    // Hosted mode docs: Intertech VPOS 3DPayHosting — sale3D ile aynı endpoint ve hash recipe,
    // SecureType='3DPayHosting' ve kart alanları (Pan/Cvv2/Expiry) yok.
    public function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse
    {
        $response = new HostedPaymentResponse(order_number: $request->order_number);
        $rnd = str_replace('-', '', bin2hex(random_bytes(16)));

        $req = [
            'ShopCode' => $auth->merchant_id,
            'PurchAmount' => StringHelper::formatAmount($request->sale_info->amount ?? 0),
            'Currency' => (string) ($request->sale_info->currency?->value ?? 949),
            'OrderId' => $request->order_number,
            'OkUrl' => $request->success_url,
            'FailUrl' => $request->fail_url,
            'Rnd' => $rnd,
            'TxnType' => 'Auth',
            'InstallmentCount' => ($request->sale_info && $request->sale_info->installment > 1)
                ? (string) $request->sale_info->installment
                : '0',
            'SecureType' => '3DPayHosting',
            'Lang' => strtoupper($request->language ?: 'tr'),
        ];

        $req['Hash'] = StringHelper::sha1Base64(
            $req['ShopCode'] . $req['OrderId'] . $req['PurchAmount'] . $req['OkUrl'] .
            $req['FailUrl'] . $req['TxnType'] . $req['InstallmentCount'] . $req['Rnd'] .
            $auth->merchant_storekey
        );

        $response->status = ResponseStatus::Success;
        $response->message = 'Hosted ödeme formu hazırlandı';
        $response->redirect_method = 'POST';
        $response->redirect_url = $auth->test_platform ? $this->urlAPITest : $this->urlAPILive;
        $response->form_fields = $req;

        return $response;
    }

    // Hosted mode docs: Intertech VPOS callback ProcReturnCode=00 = başarı, sale3DResponse ile aynı pattern.
    public function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse
    {
        $payload = $callback->payload;

        $response = new SaleResponse(
            status: SaleResponseStatus::Error,
            order_number: (string) ($payload['OrderId'] ?? $callback->order_number),
            transaction_id: (string) ($payload['TransId'] ?? ''),
            private_response: $payload,
        );

        if (($payload['ProcReturnCode'] ?? '') === '00') {
            $response->status = SaleResponseStatus::Success;
            $response->message = 'İşlem başarılı';
        } else {
            $response->message = $payload['ErrorMessage'] ?? '3D doğrulaması başarısız';
        }

        return $response;
    }

    // --- Private Helpers ---

    // sha1Base64, parseSemicolonResponse ve formRequest
    // StringHelper ve MakesHttpRequests trait'ü üzerinden sağlanır.
}
