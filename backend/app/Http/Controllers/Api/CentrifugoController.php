<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CentrifugoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CentrifugoController extends Controller
{
    protected $centrifugoService;

    /**
     * CentrifugoController constructor
     *
     * @param CentrifugoService $centrifugoService
     */
    public function __construct(CentrifugoService $centrifugoService)
    {
        $this->centrifugoService = $centrifugoService;
        $this->middleware('api');
    }

    /**
     * Generate connection and subscription tokens
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTokens(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = (string)$user->id;

        // Generate connection token
        $connectionToken = $this->centrifugoService->generateConnectionToken($userId);

        // If specific channels are requested
        $channels = $request->input('channels', []);
        $subscriptionTokens = [];

        foreach ($channels as $channel) {
            $subscriptionTokens[$channel] = $this->centrifugoService->generateSubscriptionToken(
                $userId,
                $channel
            );
        }

        return response()->json([
            'connection_token' => $connectionToken,
            'subscription_tokens' => $subscriptionTokens,
        ]);
    }

    /**
     * Generate subscription token for a specific cause
     *
     * @param Request $request
     * @param string $causeId
     * @return JsonResponse
     */
    public function getCauseSubscriptionToken(Request $request, string $causeId): JsonResponse
    {
        $user = $request->user();
        $userId = (string)$user->id;
        $channel = "cause.{$causeId}";

        $token = $this->centrifugoService->generateSubscriptionToken($userId, $channel);

        return response()->json([
            'subscription_token' => $token,
            'channel' => $channel,
        ]);
    }
}
