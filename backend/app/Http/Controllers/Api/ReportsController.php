<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Cause;
use App\Models\Donation;
use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Get reports data based on time range
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getReports(Request $request): JsonResponse
    {
        $timeRange = $request->input('timeRange', 'week');
        
        // Set date range based on selected time period
        $endDate = Carbon::now();
        $startDate = match($timeRange) {
            'week' => Carbon::now()->subDays(7),
            'month' => Carbon::now()->subDays(30),
            'year' => Carbon::now()->subMonths(12),
            default => Carbon::now()->subDays(7)
        };
        
        // Get donations summary
        $donationsSummary = $this->getDonationsSummary($startDate, $endDate);
        
        // Get causes summary
        $causesSummary = $this->getCausesSummary();
        
        // Get top causes
        $topCauses = $this->getTopCauses($startDate, $endDate);
        
        // Get recent donations
        $recentDonations = $this->getRecentDonations(5);
        
        // Get donation trends
        $donationTrends = $this->getDonationTrends($startDate, $endDate, $timeRange);
        
        // Build and return the response
        return response()->json([
            'data' => [
                'donations' => $donationsSummary,
                'causes' => $causesSummary,
                'topCauses' => $topCauses,
                'recentDonations' => $recentDonations,
                'donationTrends' => $donationTrends,
            ]
        ]);
    }
    
    /**
     * Get donations summary
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getDonationsSummary(Carbon $startDate, Carbon $endDate): array
    {
        // Get current period data
        $currentPeriodAmount = Donation::whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
            
        $currentPeriodCount = Donation::whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        // Calculate average
        $average = $currentPeriodCount > 0 ? $currentPeriodAmount / $currentPeriodCount : 0;
        
        // Get previous period data for percentage change calculation
        $periodDifference = $endDate->diffInDays($startDate);
        $previousPeriodStart = (clone $startDate)->subDays($periodDifference);
        $previousPeriodEnd = (clone $endDate)->subDays($periodDifference);
        
        $previousPeriodAmount = Donation::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('amount');
            
        // Calculate percentage change
        $percentageChange = 0;
        if ($previousPeriodAmount > 0) {
            $percentageChange = (($currentPeriodAmount - $previousPeriodAmount) / $previousPeriodAmount) * 100;
        }
        
        return [
            'total' => round($currentPeriodAmount, 2),
            'count' => $currentPeriodCount,
            'average' => round($average, 2),
            'percentageChange' => round($percentageChange, 2)
        ];
    }
    
    /**
     * Get causes summary
     * 
     * @return array
     */
    private function getCausesSummary(): array
    {
        $activeCauses = Cause::where('status', 'active')->count();
        $totalCauses = Cause::count();
        $completedCauses = Cause::where('status', 'completed')->count();
        
        return [
            'active' => $activeCauses,
            'completed' => $completedCauses,
            'total' => $totalCauses
        ];
    }
    
    /**
     * Get top causes by donation amount
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @return array
     */
    private function getTopCauses(Carbon $startDate, Carbon $endDate, int $limit = 4): array
    {
        $topCauses = Cause::select('causes.id', 'causes.title as name')
            ->leftJoin('donations', 'causes.id', '=', 'donations.cause_id')
            ->whereBetween('donations.created_at', [$startDate, $endDate])
            ->groupBy('causes.id', 'causes.title')
            ->selectRaw('SUM(donations.amount) as amount')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();
            
        $totalDonationsAmount = Donation::whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
            
        return $topCauses->map(function ($cause) use ($totalDonationsAmount) {
            $percentage = $totalDonationsAmount > 0 ? ($cause->amount / $totalDonationsAmount) * 100 : 0;
            return [
                'name' => $cause->name,
                'amount' => round($cause->amount, 2),
                'percentage' => round($percentage, 2)
            ];
        })->toArray();
    }
    
    /**
     * Get recent donations
     * 
     * @param int $limit
     * @return array
     */
    private function getRecentDonations(int $limit = 5): array
    {
        return Donation::with(['user', 'cause'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($donation) {
                return [
                    'id' => $donation->id,
                    'amount' => round($donation->amount, 2),
                    'donor' => optional($donation->user)->name ?? 'Anonymous',
                    'cause' => optional($donation->cause)->title ?? 'Unknown',
                    'date' => $donation->created_at->format('Y-m-d')
                ];
            })
            ->toArray();
    }
    
    /**
     * Get donation trends
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $timeRange
     * @return array
     */
    private function getDonationTrends(Carbon $startDate, Carbon $endDate, string $timeRange): array
    {
        $groupBy = match($timeRange) {
            'week' => "to_char(created_at, 'YYYY-MM-DD')",
            'month' => "to_char(created_at, 'YYYY-MM-DD')",
            'year' => "to_char(created_at, 'YYYY-MM')",
            default => "to_char(created_at, 'YYYY-MM-DD')"
        };
        
        $dateFormat = match($timeRange) {
            'week' => 'D',  // Day name (Mon, Tue, etc.)
            'month' => 'd', // Day of month (01-31)
            'year' => 'M',  // Month name (Jan, Feb, etc.)
            default => 'D'
        };
        
        $trends = Donation::select(DB::raw("{$groupBy} as date"))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw($groupBy))
            ->selectRaw('SUM(amount) as amount')
            ->orderBy('date')
            ->get()
            ->map(function ($trend) use ($dateFormat) {
                return [
                    'date' => Carbon::parse($trend->date)->format($dateFormat),
                    'amount' => round($trend->amount, 2)
                ];
            })
            ->toArray();
        
        // If timerange is week, ensure we have all 7 days
        if ($timeRange === 'week') {
            $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $existingDays = array_column($trends, 'date');
            $filledTrends = [];
            
            foreach ($daysOfWeek as $day) {
                if (in_array($day, $existingDays)) {
                    $key = array_search($day, array_column($trends, 'date'));
                    $filledTrends[] = $trends[$key];
                } else {
                    $filledTrends[] = ['date' => $day, 'amount' => 0];
                }
            }
            
            return $filledTrends;
        }
        
        return $trends;
    }
} 