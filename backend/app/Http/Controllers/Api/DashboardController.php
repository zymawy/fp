<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardRecentDonationResource;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\DashboardTrendsResource;
use App\Models\Cause;
use App\Models\Donation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        // Get current month/year data
        $currentMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        
        // Total donations amount
        $totalDonationsAmount = Donation::sum('amount');
        
        // Total users count
        $totalUsersCount = User::count();
        
        // Total causes count
        $totalCausesCount = Cause::count();
        
        // Calculate growth
        $currentMonthDonations = Donation::where('created_at', '>=', $currentMonthStart)
            ->sum('amount');
        $lastMonthDonations = Donation::whereBetween('created_at', [$lastMonthStart, $currentMonthStart])
            ->sum('amount');
        
        $currentMonthUsers = User::where('created_at', '>=', $currentMonthStart)
            ->count();
        $lastMonthUsers = User::whereBetween('created_at', [$lastMonthStart, $currentMonthStart])
            ->count();
        
        $currentMonthCauses = Cause::where('created_at', '>=', $currentMonthStart)
            ->count();
        $lastMonthCauses = Cause::whereBetween('created_at', [$lastMonthStart, $currentMonthStart])
            ->count();
        
        // Calculate growth percentages
        $donationsGrowth = $this->calculateGrowth($currentMonthDonations, $lastMonthDonations);
        $usersGrowth = $this->calculateGrowth($currentMonthUsers, $lastMonthUsers);
        $causesGrowth = $this->calculateGrowth($currentMonthCauses, $lastMonthCauses);
        
        // Create response data
        $stats = [
            'total_donations_amount' => $totalDonationsAmount,
            'total_users_count' => $totalUsersCount,
            'total_causes_count' => $totalCausesCount,
            'donations_growth' => $donationsGrowth,
            'users_growth' => $usersGrowth,
            'causes_growth' => $causesGrowth
        ];
        
        return (new DashboardStatsResource($stats))->response();
    }
    
    /**
     * Get dashboard trends data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrends()
    {
        // Get weekly trends
        $weeklyTrends = $this->getWeeklyTrends();
        
        // Get monthly trends
        $monthlyTrends = $this->getMonthlyTrends();
        
        $trends = [
            'weekly' => $weeklyTrends,
            'monthly' => $monthlyTrends
        ];
        
        return (new DashboardTrendsResource($trends))->response();
    }
    
    /**
     * Get recent donations with pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentDonations(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        
        $donations = Donation::with(['user', 'cause'])
            ->latest()
            ->paginate($perPage);
        
        return DashboardRecentDonationResource::collection($donations)
            ->response();
    }
    
    /**
     * Calculate growth percentage
     *
     * @param float $current
     * @param float $previous
     * @return float
     */
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
    
    /**
     * Get weekly donation trends
     *
     * @return array
     */
    private function getWeeklyTrends()
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $weeklyData = Donation::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(amount) as amount')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
        
        // Fill in any missing days with zero values
        $result = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dayData = $weeklyData->firstWhere('date', $dateString);
            
            $result[] = [
                'date' => $dateString,
                'amount' => $dayData ? floatval($dayData->amount) : 0
            ];
            
            $currentDate->addDay();
        }
        
        return $result;
    }
    
    /**
     * Get monthly donation trends
     *
     * @return array
     */
    private function getMonthlyTrends()
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $monthlyData = Donation::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as date'),
            DB::raw('SUM(amount) as amount')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('date')
            ->get();
        
        // Fill in any missing months with zero values
        $result = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m');
            $monthData = $monthlyData->firstWhere('date', $dateString);
            
            $result[] = [
                'date' => $dateString,
                'amount' => $monthData ? floatval($monthData->amount) : 0
            ];
            
            $currentDate->addMonth();
        }
        
        return $result;
    }
} 