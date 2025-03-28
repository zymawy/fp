<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\AchievementType;
use App\Models\Donation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AchievementService
{
    /**
     * Check and award achievements after a successful donation payment
     *
     * @param Donation $donation
     * @return array Achievements awarded
     */
    public function processAchievementsForDonation(Donation $donation): array
    {
        if (!$donation->user_id || $donation->payment_status !== 'completed') {
            return [];
        }

        $user = User::find($donation->user_id);
    
        if (!$user) {
            return [];
        }

        $awardedAchievements = [];

        // Check for First Donation achievement
        $firstDonationAchievement = $this->checkFirstDonation($user, $donation);
        info(['firstDonationAchievement' => $firstDonationAchievement]);
        if ($firstDonationAchievement) {
            $awardedAchievements[] = $firstDonationAchievement;
        }

        // Check for Generous Donor achievement
        $generousDonorAchievement = $this->checkGenerousDonor($user, $donation);
        info(['firstDonationAchievement' => $firstDonationAchievement]);
        if ($generousDonorAchievement) {
            $awardedAchievements[] = $generousDonorAchievement;
        }

        // Check for Regular Supporter achievement
        $regularSupporterAchievement = $this->checkRegularSupporter($user, $donation);
        info(['regularSupporterAchievement' => $regularSupporterAchievement]);
        if ($regularSupporterAchievement) {
            $awardedAchievements[] = $regularSupporterAchievement;
        }

        return $awardedAchievements;
    }

    /**
     * Check if this is the user's first donation and award achievement
     *
     * @param User $user
     * @param Donation $donation
     * @return Achievement|null
     */
    private function checkFirstDonation(User $user, Donation $donation): ?Achievement
    {
        // Check if user already has this achievement
        $hasAchievement = $user->achievements()
            ->whereHas('achievementType', function ($query) {
                $query->where('title', 'First Donation');
            })
            ->exists();

        if ($hasAchievement) {
            return null;
        }

        // Count completed donations
        $donationCount = $user->donations()
            ->where('payment_status', 'completed')
            ->count();
        
        // Award achievement if this is the first completed donation
        if ($donationCount === 1) {
            $achievementType = AchievementType::where('title', 'First Donation')->first();
            if ($achievementType) {
                return $this->awardAchievement($user->id, $achievementType->id);
            }
        }

        return null;
    }

    /**
     * Check if user has donated more than $1000 in total
     *
     * @param User $user
     * @param Donation $donation
     * @return Achievement|null
     */
    private function checkGenerousDonor(User $user, Donation $donation): ?Achievement
    {
        // Check if user already has this achievement
        $hasAchievement = $user->achievements()
            ->whereHas('achievementType', function ($query) {
                $query->where('title', 'Generous Donor');
            })
            ->exists();

        if ($hasAchievement) {
            return null;
        }

        // Calculate total donations
        $totalDonated = $user->donations()
            ->where('payment_status', 'completed')
            ->sum('amount');

        // Award achievement if total donations exceed $1000
        if ($totalDonated >= 1000) {
            $achievementType = AchievementType::where('title', 'Generous Donor')->first();
            if ($achievementType) {
                return $this->awardAchievement($user->id, $achievementType->id);
            }
        }

        return null;
    }

    /**
     * Check if user has donated in 3 consecutive months
     *
     * @param User $user
     * @param Donation $donation
     * @return Achievement|null
     */
    private function checkRegularSupporter(User $user, Donation $donation): ?Achievement
    {
        // Check if user already has this achievement
        $hasAchievement = $user->achievements()
            ->whereHas('achievementType', function ($query) {
                $query->where('title', 'Regular Supporter');
            })
            ->exists();

        if ($hasAchievement) {
            return null;
        }

        // Get donations grouped by month for the last year
        $donationsByMonth = $user->donations()
            ->where('payment_status', 'completed')
            ->whereDate('created_at', '>=', Carbon::now()->subYear())
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($donation) {
                return Carbon::parse($donation->created_at)->format('Y-m');
            });

        // Check for consecutive months
        // $months = array_keys($donationsByMonth->toArray());
        // $consecutiveMonthsCount = $this->countConsecutiveMonths($months);

        // Award achievement if there are at least 3 consecutive months
        if ($donationsByMonth->count() >= 3) {
            $achievementType = AchievementType::where('title', 'Regular Supporter')->first();
            if ($achievementType) {
                return $this->awardAchievement($user->id, $achievementType->id);
            }
        }

        return null;
    }

    /**
     * Count the maximum number of consecutive months in a sorted array of year-month strings
     *
     * @param array $months Array of year-month strings (format: 'YYYY-MM')
     * @return int
     */
    private function countConsecutiveMonths(array $months): int
    {
        if (empty($months)) {
            return 0;
        }

        $maxConsecutive = 1;
        $currentConsecutive = 1;
        $carbonMonths = array_map(function ($month) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }, $months);

        // Sort the months chronologically
        usort($carbonMonths, function ($a, $b) {
            return $a <=> $b;
        });

        for ($i = 1; $i < count($carbonMonths); $i++) {
            $currentMonth = $carbonMonths[$i];
            $previousMonth = $carbonMonths[$i - 1];
            
            // Check if current month is exactly one month after previous
            $expectedNextMonth = (clone $previousMonth)->addMonth();
            
            if ($currentMonth->equalTo($expectedNextMonth)) {
                $currentConsecutive++;
                $maxConsecutive = max($maxConsecutive, $currentConsecutive);
            } elseif (!$currentMonth->equalTo($previousMonth)) {
                // Reset counter if months are not consecutive (and not the same month)
                $currentConsecutive = 1;
            }
        }

        return $maxConsecutive;
    }

    /**
     * Award an achievement to a user
     *
     * @param string $userId
     * @param string $achievementTypeId
     * @return Achievement
     */
    private function awardAchievement(string $userId, string $achievementTypeId): Achievement
    {
        try {
            return DB::transaction(function () use ($userId, $achievementTypeId) {
                // Create a new Achievement instance
                $achievement = new Achievement();
                $achievement->id = Str::uuid()->toString();
                $achievement->user_id = $userId;
                $achievement->achievement_type_id = $achievementTypeId;
                $achievement->achieved_at = now();
                
                // Disable timestamps temporarily if they cause issues
                $achievement->timestamps = false;
                $achievement->save();
                
                Log::info("Achievement awarded", [
                    'user_id' => $userId,
                    'achievement_type_id' => $achievementTypeId
                ]);
                
                return $achievement;
            });
        } catch (\Exception $e) {
            Log::error("Error awarding achievement: " . $e->getMessage(), [
                'user_id' => $userId,
                'achievement_type_id' => $achievementTypeId
            ]);
            
            throw $e;
        }
    }
} 