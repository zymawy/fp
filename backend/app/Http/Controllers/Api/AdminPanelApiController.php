<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Cause;
use App\Models\Donation;
use App\Models\Transaction;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminPanelApiController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @return JsonResponse
     */
    public function getDashboardStats(): JsonResponse
    {
        $stats = [
            'totalDonations' => Donation::sum('amount'),
            'totalUsers' => User::count(),
            'totalCauses' => Cause::count(),
            'totalPartners' => Partner::count(),
            'recentDonations' => Donation::with(['user', 'cause'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($donation) {
                    return [
                        'id' => $donation->id,
                        'amount' => $donation->amount,
                        'donor' => optional($donation->user)->name ?? 'Anonymous',
                        'cause' => optional($donation->cause)->title?? 'Unknown',
                        'date' => $donation->created_at->format('Y-m-d')
                    ];
                })
        ];

        return response()->json($stats);
    }

    /**
     * Get donation trends
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDonationTrends(Request $request): JsonResponse
    {
        $period = $request->input('period', 'monthly');
        $limit = $request->input('limit', 7);

        if ($period === 'monthly') {
            $donations = Donation::select(
                DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take($limit)
            ->get()
            ->sortBy(function ($donation) {
                return sprintf('%04d%02d', $donation->year, $donation->month);
            });

            $labels = $donations->map(function ($donation) {
                return Carbon::createFromDate($donation->year, $donation->month, 1)
                    ->format('F Y');
            })->toArray();

            $data = $donations->pluck('total')->toArray();
        } else {
            // Weekly data
            $donations = Donation::select(
                DB::raw('EXTRACT(WEEK FROM created_at) as week'),
                DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'week')
            ->orderBy('year', 'desc')
            ->orderBy('week', 'desc')
            ->take($limit)
            ->get()
            ->sortBy(function ($donation) {
                return sprintf('%04d%02d', $donation->year, $donation->week);
            });

            $labels = $donations->map(function ($donation) {
                $date = new Carbon();
                $date->setISODate($donation->year, $donation->week);
                return $date->startOfWeek()->format('M d') . ' - ' .
                       $date->endOfWeek()->format('M d');
            })->toArray();

            $data = $donations->pluck('total')->toArray();
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    /**
     * Get recent activity for the dashboard
     *
     * @return JsonResponse
     */
    public function getRecentActivity(): JsonResponse
    {
        $donations = Donation::with(['user', 'cause'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($donation) {
                return [
                    'id' => $donation->id,
                    'type' => 'donation',
                    'amount' => $donation->amount,
                    'user' => optional($donation->user)->name ?? 'Anonymous',
                    'cause' => optional($donation->cause)->name ?? 'Unknown',
                    'date' => $donation->created_at->format('Y-m-d H:i:s'),
                    'status' => $donation->status
                ];
            });

        $transactions = Transaction::with(['user'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => 'transaction',
                    'amount' => $transaction->amount,
                    'user' => optional($transaction->user)->name ?? 'Unknown',
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'status' => $transaction->status
                ];
            });

        $activity = $donations->merge($transactions)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->all();

        return response()->json($activity);
    }

    /**
     * Get user growth over time
     *
     * @return JsonResponse
     */
    public function getUserGrowth(): JsonResponse
    {
        $users = User::select(
            DB::raw('EXTRACT(MONTH FROM created_at) as month'),
            DB::raw('EXTRACT(YEAR FROM created_at) as year'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();

        $cumulativeCount = 0;
        $userData = $users->map(function ($item) use (&$cumulativeCount) {
            $cumulativeCount += $item->count;
            return [
                'date' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                'count' => $cumulativeCount
            ];
        });

        return response()->json($userData);
    }
}
