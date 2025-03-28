<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentMethod;
use App\Services\MyFatoorahService;
use App\Transformers\PaymentMethodTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentMethodController extends BaseController
{
    protected $myFatoorahService;

    public function __construct(MyFatoorahService $myFatoorahService)
    {
        $this->myFatoorahService = $myFatoorahService;
    }

    /**
     * Get all available payment methods
     * 
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Get optional amount and currency params
            $amount = $request->query('amount', 1);
            $currency = $request->query('currency', config('services.myfatoorah.display_currency', 'USD'));
            
            // Get payment methods directly from cache or API without using database
            $paymentMethods = $this->getPaymentMethods(false, $amount, $currency);
            
            return $this->response->array([
                'success' => true,
                'data' => [
                    'payment_methods' => $paymentMethods,
                    'count' => count($paymentMethods)
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->error('Failed to fetch payment methods: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific payment method by ID
     * 
     * @param string $id
     * @return \Dingo\Api\Http\Response
     */
    public function show(string $id)
    {
        try {
            // Get all payment methods
            $methods = $this->getPaymentMethods();
            
            // Find the method with the matching ID
            $method = null;
            foreach ($methods as $paymentMethod) {
                if ($paymentMethod['id'] == $id || $paymentMethod['payment_method_id'] == $id) {
                    $method = $paymentMethod;
                    break;
                }
            }
            
            if (!$method) {
                return $this->response->errorNotFound('Payment method not found');
            }
            
            return $this->response->array([
                'success' => true,
                'data' => $method
            ]);
        } catch (\Exception $e) {
            return $this->response->error('Failed to fetch payment method: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Initialize a payment session
     * 
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function initiatePayment(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'sometimes|string',
            'customer_name' => 'sometimes|string',
            'customer_email' => 'sometimes|email',
            'customer_mobile' => 'sometimes|string',
            'cause_id' => 'required|string|exists:causes,id',
            'user_id' => 'sometimes|string|exists:users,id',
            'is_anonymous' => 'sometimes|boolean',
        ]);

        // Transform to the format expected by MyFatoorah
        $paymentData = [
            'amount' => $validated['amount'],
            'currency' => config('services.myfatoorah.display_currency', 'USD'),
            'customer_name' => $validated['customer_name'] ?? 'Customer',
            'customer_email' => $validated['customer_email'] ?? 'customer@example.com',
            'customer_mobile' => $validated['customer_mobile'] ?? '',
            'language' => app()->getLocale(),
            'callback_url' => route('payments.success'),
            'error_url' => route('payments.failure'),
            'reference' => uniqid('pay_'),
        ];

        // Add payment method ID if provided
        if (!empty($validated['payment_method_id'])) {
            $paymentData['payment_method_id'] = $validated['payment_method_id'];
        }

        // Make the API request to MyFatoorah
        try {
            $response = $this->myFatoorahService->initiatePayment($paymentData);
            return $this->response->array([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->response->array([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Callback for successful payment
     * 
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function paymentCallback(Request $request)
    {
        $paymentId = $request->input('paymentId');
        
        try {
            $result = $this->myFatoorahService->getPaymentStatus($paymentId);
            
            // Process the payment status
            // TODO: Update donation and transaction records based on response
            
            return $this->response->array([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->response->array([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Refresh payment methods from MyFatoorah API
     * 
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function refresh(Request $request)
    {
        try {
            // Get optional amount and currency params
            $amount = $request->query('amount', 1);
            $currency = $request->query('currency', config('services.myfatoorah.display_currency', 'USD'));
            
            // Clear all payment method caches
            $this->clearPaymentMethodCaches();
            
            // Get fresh methods with the specified amount and currency
            $paymentMethods = $this->getPaymentMethods(true, $amount, $currency);
            
            return $this->response->array([
                'success' => true,
                'data' => [
                    'payment_methods' => $paymentMethods,
                    'count' => count($paymentMethods)
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->error('Failed to refresh payment methods: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Clear all payment method caches
     */
    protected function clearPaymentMethodCaches()
    {
        // Get all cache keys that start with payment_methods_
        $keys = Cache::getStore()->getPrefix();
        if (method_exists(Cache::getStore(), 'keys')) {
            $keys = Cache::getStore()->keys('payment_methods_*');
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            // If we can't get keys directly, just forget the general key patterns
            Cache::forget('payment_methods');
            
            // Also try to clear for common currencies
            $currencies = ['USD', 'EUR', 'GBP', 'KWD', 'SAR', 'AED', 'QAR', 'BHD', 'OMR'];
            foreach ($currencies as $curr) {
                Cache::forget("payment_methods_1_{$curr}");
                Cache::forget("payment_methods_10_{$curr}");
                Cache::forget("payment_methods_100_{$curr}");
                Cache::forget("payment_methods_1000_{$curr}");
            }
        }
    }

    /**
     * Get payment methods from cache or API
     * 
     * @param bool $forceRefresh Whether to force refresh from API
     * @param float $amount Amount for payment methods display
     * @param string $currency Currency code
     * @return array Payment methods
     */
    protected function getPaymentMethods($forceRefresh = false, $amount = 1, $currency = null)
    {
        // Set default currency if not provided
        $currency = $currency ?? config('services.myfatoorah.display_currency', 'USD');
        
        // Create a cache key based on amount and currency
        $cacheKey = "payment_methods_{$amount}_{$currency}";
        
        // Check if we have payment methods in cache
        if (!$forceRefresh && Cache::has($cacheKey)) {
            $methods = Cache::get($cacheKey);
            // Apply formatting for frontend consistency
            return $this->formatPaymentMethodsForFrontend($methods);
        }
        
        try {
            // Get payment methods from MyFatoorah API using the official package
            $methods = $this->myFatoorahService->getPaymentMethods($amount, $currency);
            
            // Cache the raw methods for 6 hours
            Cache::put($cacheKey, $methods, now()->addHours(6));
            
            // Apply formatting for frontend consistency
            return $this->formatPaymentMethodsForFrontend($methods);
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('Failed to get payment methods from API: ' . $e->getMessage());
            
            // Return empty array if API call fails
            return [];
        }
    }

    /**
     * Format payment methods for frontend consistency
     * 
     * @param array $methods Raw payment methods from MyFatoorah API
     * @return array Formatted payment methods
     */
    protected function formatPaymentMethodsForFrontend($methods)
    {
        $formattedMethods = [];
        
        foreach ($methods as $method) {
            // Convert object to array if needed
            if (is_object($method)) {
                $method = (array) $method;
            }
            
            // Handle array access safely
            $formattedMethods[] = [
                'id' => $method['PaymentMethodId'] ?? ($method->PaymentMethodId ?? null), 
                'payment_method_id' => $method['PaymentMethodId'] ?? ($method->PaymentMethodId ?? null),
                'name' => $method['PaymentMethodEn'] ?? ($method->PaymentMethodEn ?? ''),
                'short_name' => $method['PaymentMethodCode'] ?? ($method->PaymentMethodCode ?? null),
                'image_url' => $method['ImageUrl'] ?? ($method->ImageUrl ?? null),
                'is_active' => true,
                'is_default' => false,
                'currency' => $method['CurrencyIso'] ?? config('services.myfatoorah.display_currency', 'USD'),
                'service_charge' => $method['ServiceCharge'] ?? 0,
                'total_amount' => $method['TotalAmount'] ?? 0,
                'provider' => 'myfatoorah',
                'display_order' => $method['PaymentMethodId'],
                // Include all original fields for compatibility
                'PaymentMethodId' => $method['PaymentMethodId'],
                'PaymentMethodEn' => $method['PaymentMethodEn'],
                'PaymentMethodAr' => $method['PaymentMethodAr'] ?? '',
                'PaymentMethodCode' => $method['PaymentMethodCode'] ?? '',
                'ImageUrl' => $method['ImageUrl'] ?? '',
                'TotalAmount' => $method['TotalAmount'] ?? 0,
                'CurrencyIso' => $method['CurrencyIso'] ?? '',
                'ServiceCharge' => $method['ServiceCharge'] ?? 0,
            ];
        }
        
        return $formattedMethods;
    }
} 