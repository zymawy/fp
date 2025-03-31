<?php

namespace App\Events;

use App\Models\Donation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DonationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $donation;
    public $causeId;
    public $userId;
    public $amount;
    public $isAnonymous;
    public $donorName;

    /**
     * Create a new event instance.
     */
    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
        $this->causeId = $donation->cause_id;
        $this->userId = $donation->user_id;
        $this->amount = $donation->amount;
        $this->isAnonymous = $donation->is_anonymous;
        
        // Get donor name - respect anonymity
        if ($this->isAnonymous) {
            $this->donorName = 'Anonymous';
        } else {
            $this->donorName = $donation->user ? $donation->user->name : 'Unnamed Donor';
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Public channel for frontend updates
            new Channel("donations:cause.{$this->causeId}"),
            // Admin channel for administrative notifications
            new PrivateChannel('admin.donations'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'donation.created';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'donation_id' => $this->donation->id,
            'cause_id' => $this->causeId,
            'amount' => $this->amount,
            'donor_name' => $this->donorName,
            'is_anonymous' => $this->isAnonymous,
            'timestamp' => now()->toIso8601String(),
        ];
    }
} 