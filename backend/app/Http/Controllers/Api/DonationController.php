<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateDonationRequest;
use App\Models\Donation;
use App\Transformers\DonationTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DonationController extends BaseController
{
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
        $donation = Donation::create($request->validated());
        $donation->load(['user', 'cause']);
        
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
}
