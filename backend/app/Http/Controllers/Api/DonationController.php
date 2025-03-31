<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateDonationRequest;
use App\Models\Donation;
use App\Models\Transaction;
use App\Services\MyFatoorahService;
use App\Transformers\DonationTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Events\DonationUpdated;
use App\Events\DonationCreated;

class DonationController extends BaseController
{
    protected $myFatoorahService;

    public function __construct(MyFatoorahService $myFatoorahService)
    {
        $this->myFatoorahService = $myFatoorahService;
    }

    /**
     * Check if current user is admin
     *
     * @throws AccessDeniedHttpException
     */
    protected function checkAdmin()
    {
        $user = auth()->user();

        info($user);
        if (!$user || !$user->isAdmin()) {
            throw new AccessDeniedHttpException('Admin access required');
        }
    }

    /**
     * Display a listing of the resource with filtering options.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        // Base relationships to always load
        $relationships = ['user', 'cause'];

        // Check if additional relationships were requested via includes parameter
        if ($request->has('include')) {
            $requestedIncludes = explode(',', $request->input('include'));
            $validIncludes = ['transaction'];

            foreach ($requestedIncludes as $include) {
                if (in_array($include, $validIncludes) && !in_array($include, $relationships)) {
                    $relationships[] = $include;
                }
            }
        }

        $query = Donation::query()->with($relationships);

        // Apply filters
        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by cause
        if ($request->has('cause_id')) {
            $query->where('cause_id', $request->input('cause_id'));
        }

        // Filter by amount (range)
        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->input('min_amount'));
        }

        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->input('max_amount'));
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Filter by anonymity
        if ($request->has('is_anonymous')) {
            $query->where('is_anonymous', $request->boolean('is_anonymous'));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate results
        $perPage = (int) $request->input('per_page', 10);
        $donations = $query->paginate($perPage);

        return $this->respondWithPagination($donations, new DonationTransformer, 'donation', 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ValidateDonationRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(ValidateDonationRequest $request)
    {
        // Find the donation or create a new one
        $donationData = $request->validated();

        // Remove transaction data from donation payload
        $transactionData = [
            'payment_provider' => $donationData['payment_provider'] ?? 'myfatoorah',
            'payment_method' => $donationData['payment_method'] ?? null,
            'amount' => $donationData['amount'],
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
        $transaction = Transaction::create($transactionData + ['payment_method' => $donationData['payment_method_id']]);

        // Process payment with MyFatoorah
        $paymentMethodId = $request->input('payment_method_id');

        $paymentData = [
            'amount' => $transactionData['amount'],
            'currency' => $transactionData['currency_code'],
            'customer_name' => $request->input('recipient_name', 'Donor'),
            'customer_email' => $request->input('recipient_email', 'donor@example.com'),
            'customer_mobile' => $request->input('customer_mobile', ''),
            'callback_url' => route('payments.success'),
            'error_url' => route('payments.failure'),
            'reference' => $donation->id,
            'payment_method_id' => $paymentMethodId,
        ];

        $paymentResult = $this->myFatoorahService->initiatePayment($paymentData);

        // Update transaction with payment provider response
        $transaction->update([
            'transaction_id' => $paymentResult['InvoiceId'] ?? null,
            'payment_method' => $paymentMethodId,
            'status' => 'initiated',
            'payment_data' => array_merge($transaction->payment_data ?? [], [
                'payment_url' => $paymentResult['PaymentURL'] ?? null,
                'invoice_id' => $paymentResult['InvoiceId'] ?? null,
                'payment_methods' => $paymentResult['PaymentMethods'] ?? null,
            ]),
        ]);

        // Update donation status
        $donation->update([
            'payment_status' => 'initiated',
            'payment_id' => $paymentResult['InvoiceId'] ?? null,
        ]);

        // Load related models for response
        $donation->load(['user', 'cause', 'transaction']);


        return $this->respondWithData($donation, new DonationTransformer, 'donation', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function show(string $id, Request $request)
    {
        // Base relationships to always load
        $relationships = ['user', 'cause'];

        // Check if additional relationships were requested via includes parameter
        if ($request->has('include')) {
            $requestedIncludes = explode(',', $request->input('include'));
            $validIncludes = ['transaction'];

            foreach ($requestedIncludes as $include) {
                if (in_array($include, $validIncludes) && !in_array($include, $relationships)) {
                    $relationships[] = $include;
                }
            }
        }

        $donation = Donation::with($relationships)->findOrFail($id);

        return $this->respondWithData($donation, new DonationTransformer, 'donation', 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ValidateDonationRequest $request
     * @param string $id
     * @return \Dingo\Api\Http\Response
     */
    public function update(ValidateDonationRequest $request, string $id)
    {
        $this->checkAdmin();

        $donation = Donation::findOrFail($id);
        $donation->update($request->validated());
        $donation->load(['user', 'cause', 'transaction']);

        return $this->respondWithData($donation, new DonationTransformer, 'donation', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroy(string $id)
    {
        $this->checkAdmin();

        $donation = Donation::findOrFail($id);
        $donation->delete();

        return $this->response->noContent();
    }

    /**
     * Send real-time donation update for a cause
     * 
     * @param string $causeId The ID of the cause to update
     * @return bool Success status
     */
    private function sendRealTimeUpdate($causeId)
    {
        try {
            // Get the latest cause data
            $cause = \App\Models\Cause::findOrFail($causeId);
            
            // Calculate progress percentage
            $progressPercentage = $cause->target_amount > 0 
                ? min(100, round(($cause->raised_amount / $cause->target_amount) * 100)) 
                : 0;
            
            // Broadcast the event using Laravel's event system
            event(new DonationUpdated(
                $causeId,
                (float)$cause->raised_amount,
                (float)$progressPercentage
            ));
            
            \Log::info('Real-time donation update broadcast successfully', [
                'cause_id' => $causeId,
                'raised_amount' => $cause->raised_amount,
                'progress_percentage' => $progressPercentage
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Exception when broadcasting donation update', [
                'cause_id' => $causeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Handle payment callback from payment processor
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function paymentCallback(Request $request)
    {
        $paymentId = $request->input('paymentId');
        
        try {
            $paymentStatus = $this->myFatoorahService->getPaymentStatus($paymentId);
            
            // Find donation by payment ID
            $donation = Donation::where('payment_id', $paymentId)->firstOrFail();
            
            // Update transaction status if it exists
            $transaction = Transaction::where('donation_id', $donation->id)->first();
            
            if ($transaction) {
                $transaction->update([
                    'status' => $paymentStatus['IsSuccess'] ? 'completed' : 'failed',
                    'payment_data' => array_merge($transaction->payment_data ?? [], [
                        'payment_status' => $paymentStatus,
                    ]),
                ]);
            }
            
            // Update donation status
            $donation->update([
                'payment_status' => $paymentStatus['IsSuccess'] ? 'completed' : 'failed',
            ]);
            
            // If payment was successful, update cause raised amount
            if ($paymentStatus['IsSuccess']) {
                $cause = $donation->cause;
                $cause->raised_amount += $donation->amount;
                $cause->save();
                
                // Load necessary relationships
                $donation->load(['user', 'cause']);
                
                // Fire the donation created event
                event(new DonationCreated($donation));
                
                // Send real-time update for this cause
                $this->sendRealTimeUpdate($cause->id);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => [
                    'donation_id' => $donation->id,
                    'payment_status' => $donation->payment_status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment callback: ' . $e->getMessage(),
            ], 500);
        }
    }
}
