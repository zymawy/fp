<?php

namespace App\Services;

use App\Http\Controllers\MyFatoorahController;
use Illuminate\Support\Facades\Log;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentEmbedded;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;

class MyFatoorahService
{
    private $mfPayment;
    private $mfStatus;
    private $mfConfig;

    /**
     * Initialize MyFatoorah service with configuration
     */
    public function __construct()
    {
        $this->mfConfig = [
            'apiKey'      => config('myfatoorah.api_key'),
            'isTest'      => config('myfatoorah.test_mode'),
            'countryCode' => config('myfatoorah.country_iso'),
        ];

        try {
            $this->mfPayment = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $this->mfStatus = new MyFatoorahPaymentStatus($this->mfConfig);
        } catch (\Exception $e) {
            Log::error('MyFatoorah initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available payment methods
     *
     * @param float $amount The amount for the invoice
     * @param string $currency The currency code (e.g., USD, KWD)
     * @return array Payment methods
     */
    public function getPaymentMethods($amount = 1, $currency = null)
    {
        try {
            $currency = $currency ?? config('services.myfatoorah.display_currency', 'USD');

            // Get the checkout gateways
            $registerApplePay = config('myfatoorah.register_apple_pay', false);
            $paymentMethods = $this->mfPayment->getCheckoutGateways($amount, $currency, $registerApplePay);

            // Return all available payment methods
            return $paymentMethods['all'] ?? [];
        } catch (\Exception $e) {
            Log::error('MyFatoorah getPaymentMethods error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initiate a payment
     *
     * @param array $data Payment data including amount, customer details, etc.
     * @return array Payment session data including redirect URL
     */
    public function initiatePayment(array $data)
    {
        try {
            // Format the data for MyFatoorah API
            $curlData = [
                'CustomerName'       => $data['customer_name'] ?? 'Customer',
                'InvoiceValue'       => $data['amount'],
//                'DisplayCurrencyIso' => $data['currency'] ?? config('services.myfatoorah.display_currency', 'USD'),
//                'CustomerEmail'      => $data['customer_email'] ?? 'customer@example.com',
                'CallBackUrl'        => $data['callback_url'] ?? route('payments.success'),
                'ErrorUrl'           => $data['error_url'] ?? route('payments.failure'),
//                'MobileCountryCode'  => '+' . ($data['country_code'] ?? '965'),
//                'CustomerMobile'     => $data['customer_mobile'] ?? '',
//                'Language'           => $data['language'] ?? app()->getLocale(),
//                'CustomerReference'  =>  uniqid(),
//                'SourceInfo'         => app()::VERSION . ' - Custom Integration'
            ];

            // If using embedded payment
            $userDefinedField = !empty($data['user_id']) ? 'User-' . $data['user_id'] : '';

            // If payment method ID is provided, execute with that method
            if (!empty($data['payment_method_id'])) {
                $paymentId = $data['payment_method_id'];
                $sessionId = $data['session_id'] ?? null;
                $result = $this->mfPayment->getInvoiceURL($curlData, $paymentId, $data['reference'] ?? '-', $sessionId);
            } else {
                // Otherwise create an invoice with multiple payment options
//                $result = $this->mfPayment->getInvoiceURL($curlData);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('MyFatoorah initiatePayment error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get payment status
     *
     * @param string $key The payment ID or invoice ID
     * @param string $keyType Type of the key (PaymentId or InvoiceId)
     * @return object Payment status data
     */
    public function getPaymentStatus($key, $keyType = 'PaymentId')
    {
        try {
            $result = $this->mfStatus->getPaymentStatus($key, $keyType);
            return $result;
        } catch (\Exception $e) {
            Log::error('MyFatoorah getPaymentStatus error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate an embedded payment session
     *
     * @param string $userDefinedField User defined field for card tokenization
     * @return string The session ID
     */
    public function getEmbeddedSession($userDefinedField = '')
    {
        try {
            $sessionId = $this->mfPayment->getEmbeddedSession($userDefinedField);
            return $sessionId;
        } catch (\Exception $e) {
            Log::error('MyFatoorah getEmbeddedSession error: ' . $e->getMessage());
            throw $e;
        }
    }
}
