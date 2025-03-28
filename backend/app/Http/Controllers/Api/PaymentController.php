<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatePaymentRequest;
use App\Models\Donation;
use App\Models\Transaction;
use App\Services\MyFatoorahService;
use App\Transformers\PaymentTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AchievementService;

class PaymentController extends BaseController
{
    protected $myFatoorahService;

    public function __construct(MyFatoorahService $myFatoorahService)
    {
        $this->myFatoorahService = $myFatoorahService;
    }

    /**
     * Process a payment for a donation
     *
     * @param ValidatePaymentRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function process(ValidatePaymentRequest $request)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Find the donation or create a new one
            $donationData = $request->validated();

            // Remove transaction data from donation payload
            $transactionData = [
                'payment_provider' => $donationData['payment_provider'] ?? 'myfatoorah',
                'payment_method' => $donationData['payment_method'] ?? null,
                'amount' => $donationData['amount'] ?? $donationData['invoiceValue'],
                'currency_code' => $donationData['currency_code'] ?? config('services.myfatoorah.display_currency', 'USD'),
                'status' => 'processing',
                'payment_data' => $donationData['payment_data'] ?? [],
            ];

            // Remove transaction-specific fields from donation data
            unset($donationData['payment_provider']);
            unset($donationData['payment_method']);
            unset($donationData['payment_data']);

            // Set default payment status
            $donationData['payment_status'] = 'processing';

            // Create the donation
            $donation = Donation::create($donationData);

            // Create the transaction linked to the donation
            $transactionData['donation_id'] = $donation->id;
            $transaction = Transaction::create($transactionData);

            // Process payment with MyFatoorah
            $paymentMethodId = $request->input('payment_method_id');

            $paymentData = [
                'amount' => $transactionData['amount'],
                'currency' => $transactionData['currency_code'],
                'customer_name' => $request->input('recipient_name', 'Donor'),
                'customer_email' => $request->input('recipient_email', 'donor@example.com'),
                'customer_mobile' => $request->input('customer_mobile', ''),
                'callback_url' => $request->input('callBackUrl'),
                'error_url' => $request->input('errorUrl'),
                'reference' => $donation->id,
                'payment_method_id' => $paymentMethodId,
            ];

            $paymentResult = $this->myFatoorahService->initiatePayment($paymentData);

            // Update transaction with payment provider response
            $transaction->update([
                'transaction_id' => $paymentResult['invoiceId'] ?? null,
                'status' => 'initiated',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'payment_url' => $paymentResult['PaymentURL'] ?? null,
                    'invoice_id' => $paymentResult['invoiceId'] ?? null,
                    'payment_methods' => $paymentResult['PaymentMethods'] ?? null,
                ]),
            ]);

            // Update donation status
            $donation->update([
                'payment_status' => 'initiated',
                'payment_id' => $paymentResult['invoiceId'] ?? null,
            ]);

            // Load related models for response
            $donation->load(['user', 'cause', 'transaction']);

            // Commit transaction
            DB::commit();

            // Return the payment URL and data
            return $this->response->array([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'payment_url' => $paymentResult['invoiceURL'] ?? null,
                    'invoice_id' => $paymentResult['invoiceId'] ?? null,
                    'donation_id' => $donation->id,
                ],
            ]);
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
            Log::error('Payment processing failed: ' . $e->getMessage());

            return $this->response->error('Payment processing failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Callback handler for MyFatoorah payments
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request)
    {
        try {
            $paymentId = $request->query('paymentId');

            if (empty($paymentId)) {
                return redirect()->to('/payment/failed?error=missing_payment_id');
            }

            // Get payment status from MyFatoorah
            $paymentStatus = $this->myFatoorahService->getPaymentStatus($paymentId);

            // Find the transaction by invoice id
            $transaction = Transaction::where('transaction_id', $paymentStatus['invoiceId'] ?? '')->first();

            if (!$transaction) {
                Log::error('Transaction not found for paymentId: ' . $paymentId);
                return redirect()->to('/payment/failed?error=transaction_not_found');
            }

            $donation = Donation::find($transaction->donation_id);

            if (!$donation) {
                Log::error('Donation not found for transaction: ' . $transaction->id);
                return redirect()->to('/payment/failed?error=donation_not_found');
            }

            // Check payment status
            $invoiceStatus = $paymentStatus['InvoiceStatus'] ?? null;
            $isSuccess = ($invoiceStatus === 'Paid');

            // Update transaction and donation
            $transaction->update([
                'status' => $isSuccess ? 'completed' : 'failed',
                'payment_data' => array_merge($transaction->payment_data ?? [], $paymentStatus),
            ]);

            $donation->update([
                'payment_status' => $isSuccess ? 'completed' : 'failed',
            ]);

            // If payment is successful, check for achievements
            if ($isSuccess && $donation->user_id) {
                try {
                    // Process achievements
                    $achievementService = app(AchievementService::class);
                    $awardedAchievements = $achievementService->processAchievementsForDonation($donation);

                    if (!empty($awardedAchievements)) {
                        Log::info('Achievements awarded after donation payment (callback)', [
                            'donation_id' => $donation->id,
                            'user_id' => $donation->user_id,
                            'achievements' => count($awardedAchievements)
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail the payment verification
                    Log::error('Error processing achievements (callback): ' . $e->getMessage(), [
                        'donation_id' => $donation->id,
                        'user_id' => $donation->user_id
                    ]);
                }
            }

            // Redirect to success or failure page
            if ($isSuccess) {
                return redirect()->to('/payment/success?donation_id=' . $donation->id);
            } else {
                return redirect()->to('/payment/failed?error=' . ($paymentStatus['Error'] ?? 'payment_failed'));
            }
        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage());
            return redirect()->to('/payment/failed?error=server_error');
        }
    }

    /**
     * Error callback for MyFatoorah payments
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function errorCallback(Request $request)
    {
        Log::error('Payment error callback received', $request->all());
        return redirect()->to('/payment/failed?error=payment_gateway_error');
    }

    /**
     * Verify a payment status
     *
     * @param string $id Payment ID or Transaction ID
     * @return \Dingo\Api\Http\Response
     */
    public function verifyStatus(string $id)
    {
        try {
            $paymentStatus = $this->myFatoorahService->getPaymentStatus($id);

            $isPaid = ($paymentStatus->InvoiceStatus ?? null) === 'Paid';
            $invoiceId = $paymentStatus->InvoiceId ?? null;

            if (!$paymentStatus || !$invoiceId) {
                throw new \Exception('Payment status not found for paymentId: ' . $id);
            }

            /** @var Donation $donation */
            $donation = Donation::where('payment_id', $invoiceId)->first();

            if (!$donation) {
                return $this->response->errorNotFound('Transaction details not found');
            }

            // Update transaction status based on API response
            $invoiceStatus = $paymentStatus->InvoiceStatus ?? null;
            $newStatus = $isPaid ? 'completed' : ($invoiceStatus);
            $donation->update([
                'payment_status' => $newStatus,
            ]);

            // Create or update the transaction
            $transaction = $donation->transaction();
            if ($transaction->exists()) {
                $transaction->update([
                    'payment_method' => data_get($paymentStatus?->InvoiceTransactions[0] ?? [], 'PaymentGateway'),
                    'payment_data' => array_merge($transaction->first()->payment_data ?? [], [
                        'status_check' => (array) $paymentStatus,
                        'checked_at' => now()->toIso8601String(),
                    ]),
                    'payment_status' => $newStatus ?? 'Paid',
                    'transaction_id' => $invoiceId,
                ]);
            } else {
                $transaction->create([
                    'transaction_id' => $invoiceId,
                    'payment_method' => data_get($paymentStatus?->InvoiceTransactions[0] ?? [], 'PaymentGateway'),
                    'payment_data' => [
                        'status_check' => (array) $paymentStatus,
                        'checked_at' => now()->toIso8601String(),
                    ],
                    'payment_status' => $newStatus ?? 'Paid',
                    'amount' => $donation->amount,
                    'currency_code' => $donation->currency_code,
                ]);
            }

            // If payment is successful, check for achievements

            if ($isPaid && $donation->user_id) {
                try {
                    // Process achievements
                    $achievementService = app(AchievementService::class);
                    $awardedAchievements = $achievementService->processAchievementsForDonation($donation);

                    info([$awardedAchievements]);
                    if (!empty($awardedAchievements)) {
                        Log::info('Achievements awarded after donation payment', [
                            'donation_id' => $donation->id,
                            'user_id' => $donation->user_id,
                            'achievements' => count($awardedAchievements)
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail the payment verification
                    Log::error('Error processing achievements: ' . $e->getMessage(), [
                        'donation_id' => $donation->id,
                        'user_id' => $donation->user_id
                    ]);
                }
            }

            // Load the transaction with the donation for the response
            $transaction = $donation->transaction()->with('donation')->first();

            return $this->respondWithData($transaction, new PaymentTransformer, 'transaction', 200);
        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage());
            return $this->response->error('Payment verification failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update payment status (webhook handler)
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function webhook(Request $request)
    {
        try {
            Log::info('Payment webhook received', $request->all());

            // Handle MyFatoorah webhooks
            $data = $request->all();
            $eventType = $data['EventType'] ?? '';
            $resourceId = $data['ResourceId'] ?? '';

            if (empty($resourceId)) {
                return $this->response->array(['success' => false, 'message' => 'Missing resource ID']);
            }

            // Get the transaction by invoice ID
            $transaction = Transaction::where('transaction_id', $resourceId)->first();

            if (!$transaction) {
                return $this->response->array(['success' => false, 'message' => 'Transaction not found']);
            }

            // Get latest status from MyFatoorah
            $paymentStatus = $this->myFatoorahService->getPaymentStatus($resourceId);
            $invoiceStatus = $paymentStatus['InvoiceStatus'] ?? null;

            // Update transaction based on event type
            switch ($eventType) {
                case 'PaymentSucceeded':
                    $transaction->update([
                        'status' => 'completed',
                        'payment_data' => array_merge($transaction->payment_data ?? [], $paymentStatus),
                    ]);

                    // Update donation if exists
                    if ($transaction->donation) {
                        $transaction->donation->update([
                            'payment_status' => 'completed',
                        ]);
                    }
                    break;

                case 'PaymentFailed':
                    $transaction->update([
                        'status' => 'failed',
                        'payment_data' => array_merge($transaction->payment_data ?? [], $paymentStatus),
                    ]);

                    // Update donation if exists
                    if ($transaction->donation) {
                        $transaction->donation->update([
                            'payment_status' => 'failed',
                        ]);
                    }
                    break;

                default:
                    // For other events, update based on invoice status
                    if ($invoiceStatus) {
                        $newStatus = ($invoiceStatus === 'Paid') ? 'completed' :
                                    (($invoiceStatus === 'Failed') ? 'failed' : 'processing');

                        $transaction->update([
                            'status' => $newStatus,
                            'payment_data' => array_merge($transaction->payment_data ?? [], $paymentStatus),
                        ]);

                        // Update donation if exists
                        if ($transaction->donation) {
                            $transaction->donation->update([
                                'payment_status' => $newStatus,
                            ]);
                        }
                    }
                    break;
            }

            return $this->response->array(['success' => true, 'message' => 'Webhook processed']);
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return $this->response->error('Webhook processing failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Execute a payment through MyFatoorah gateway
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function execute(Request $request)
    {
        try {
            $validated = $request->validate([
                'paymentMethodId' => 'required|numeric',
                'invoiceValue' => 'required|numeric|min:1',
                'currencyIso' => 'required|string|size:3',
                'customerName' => 'required|string',
                'customerEmail' => 'required|email',
                'customerPhone' => 'sometimes|string',
                'callBackUrl' => 'required|string',
                'errorUrl' => 'required|string',
//                'language' => 'sometimes|string|in:en,ar',
                'displayCurrencyIso' => 'sometimes|string|size:3',
                'customerReference' => 'sometimes|string',
                'invoiceItems' => 'sometimes|array',
            ]);

            // Format data for MyFatoorah service
            $paymentData = [
                'payment_method_id' => $validated['paymentMethodId'],
                'amount' => $validated['invoiceValue'],
                'currency' => $validated['currencyIso'],
//                'customer_name' => $validated['customerName'],
//                'customer_email' => $validated['customerEmail'],
//                'customer_mobile' => $validated['customerPhone'] ?? '',
                'callback_url' => $validated['callBackUrl'],
                'error_url' => $validated['errorUrl'],
//                'language' => 'en',
//                'reference' => $validated['customerReference'] ?? uniqid('pay_'),
            ];

            // Log incoming request data for debugging
            \Illuminate\Support\Facades\Log::info('Payment execution request:', [
                'paymentData' => $paymentData,
                'rawRequest' => $request->all()
            ]);

            // Execute payment through MyFatoorah service
            $response = $this->myFatoorahService->initiatePayment($paymentData);

            Log::info('Payment execution request response:', [$response]);
            // Return the payment URL and invoice ID
            return $this->response->array([
                'success' => true,
                'message' => 'Payment execution initialized successfully',
                'data' => [
                    'PaymentURL' => $response['invoiceURL'] ?? $response['paymentURL'] ?? null,
                    'invoiceId' => $response['invoiceId'] ?? $response['invoiceId'] ?? null,
                    'paymentId' => $response['invoiceId'] ?? $response['invoiceId'] ?? null,
                ],
                'raw' => $response
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment execution failed: ' . $e->getMessage());
            return $this->response->error('Payment execution failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify a payment status
     *
     * @param string $paymentId
     * @return \Dingo\Api\Http\Response
     */
//    public function verifyStatus($paymentId)
//    {
//        try {
//            $result = $this->myFatoorahService->getPaymentStatus($paymentId);
//
//            return $this->response->array([
//                'success' => true,
//                'data' => $result,
//            ]);
//        } catch (\Exception $e) {
//            return $this->response->error('Payment verification failed: ' . $e->getMessage(), 500);
//        }
//    }
}
