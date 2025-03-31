<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\Cause;
use App\Events\DonationCreated;
use App\Events\DonationUpdated;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DonationService
{
    protected $centrifugoService;

    /**
     * DonationService constructor
     * 
     * @param CentrifugoService $centrifugoService
     */
    public function __construct(CentrifugoService $centrifugoService)
    {
        $this->centrifugoService = $centrifugoService;
    }

    /**
     * Create a new donation
     *
     * @param array $data
     * @return Donation
     */
    public function createDonation(array $data): Donation
    {
        return DB::transaction(function () use ($data) {
            // Create the donation
            $donation = Donation::create([
                'id' => $data['id'] ?? Str::uuid()->toString(),
                'user_id' => $data['user_id'],
                'cause_id' => $data['cause_id'],
                'donation_amount' => $data['donation_amount'],
                'created_at' => $data['created_at'] ?? now(),
                'updated_at' => $data['updated_at'] ?? now(),
            ]);
            
            // Update the cause's raised amount
            $cause = Cause::findOrFail($data['cause_id']);
            $cause->raised_amount = $cause->raised_amount + $data['donation_amount'];
            $cause->save();
            
            // Load the user relationship for the event
            $donation->load('user');
            
            // Dispatch the DonationCreated event
            event(new DonationCreated($donation));
            
            // Broadcast the donation update for cause progress
            $this->broadcastDonationUpdate($cause);
            
            return $donation;
        });
    }
    
    /**
     * Get all donations for a user
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserDonations(string $userId)
    {
        return Donation::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Get all donations for a cause
     *
     * @param string $causeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCauseDonations(string $causeId)
    {
        return Donation::where('cause_id', $causeId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Get donation by ID
     *
     * @param string $id
     * @return Donation|null
     */
    public function getDonation(string $id): ?Donation
    {
        return Donation::find($id);
    }
    
    /**
     * Broadcast donation update to Centrifugo
     *
     * @param Cause $cause
     * @return bool
     */
    protected function broadcastDonationUpdate(Cause $cause): bool
    {
        $channelName = "cause.{$cause->id}";
        
        $data = [
            'cause_id' => $cause->id,
            'title' => $cause->title,
            'raised_amount' => $cause->raised_amount,
            'target_amount' => $cause->target_amount,
            'progress_percentage' => min(100, round(($cause->raised_amount / $cause->target_amount) * 100, 2)),
            'timestamp' => now()->toIso8601String(),
        ];
        
        return $this->centrifugoService->publish($channelName, $data);
    }
} 