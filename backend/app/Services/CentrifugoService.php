<?php

namespace App\Services;


use Illuminate\Support\Facades\Log;
use Opekunov\Centrifugo\Centrifugo;

class CentrifugoService
{
    protected $centrifugo;
    protected $namespace;

    public function __construct(Centrifugo $centrifugo)
    {
        $this->centrifugo = $centrifugo;
        $this->namespace = config('centrifugo.namespace', 'donations');
    }

    /**
     * Publish data to a channel in the donations namespace
     *
     * @param string $channel
     * @param array $data
     * @return bool
     */
    public function publish(string $channel, array $data): bool
    {
        try {
            $fullChannel = $this->namespace . ':' . $channel;
            $this->centrifugo->publish($fullChannel, $data);
            Log::info('Published to Centrifugo', ['channel' => $fullChannel, 'data' => $data]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error publishing to Centrifugo', [
                'channel' => $channel,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate connection token for the client
     *
     * @param string $userId
     * @param int $expireAt
     * @return string
     */
    public function generateConnectionToken(string $userId, int $expireAt = 0): string
    {
        return $this->centrifugo->generateConnectionToken($userId, $expireAt);
    }

    /**
     * Generate subscription token for a channel
     *
     * @param string $userId
     * @param string $channel
     * @param int $expireAt
     * @return string
     */
    public function generateSubscriptionToken(string $userId, string $channel, int $expireAt = 0): string
    {
        $fullChannel = $this->namespace . ':' . $channel;
        return $this->centrifugo->generateSubscriptionToken($userId, $fullChannel, $expireAt);
    }
}
