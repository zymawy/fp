<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class HistoricalCauseUpdatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get causes created by the HistoricalDataSeeder
        $causes = DB::table('causes')
            ->whereDate('created_at', '<=', Carbon::now()->subMonth())
            ->select(['id', 'title', 'created_at', 'end_date', 'status'])
            ->get();
        
        if ($causes->isEmpty()) {
            $this->command->error('No causes found. Please run the HistoricalDataSeeder first.');
            return;
        }
        
        $updates = [];
        $totalCount = 0;
        
        $this->command->info('Generating updates for ' . $causes->count() . ' historical causes');
        
        foreach ($causes as $cause) {
            // Determine time period between cause creation and end date (or now if still active)
            $startDate = Carbon::parse($cause->created_at);
            $endDate = $cause->status === 'completed' 
                ? Carbon::parse($cause->end_date) 
                : Carbon::now();
            
            // How many days the cause has been active
            $activeDays = $startDate->diffInDays($endDate);
            
            // Generate 1 update per month on average (more for longer causes)
            $updateCount = max(1, intval($activeDays / 30));
            
            // Generate update dates spread across the active period
            $updateDates = [];
            for ($i = 0; $i < $updateCount; $i++) {
                $daysOffset = intval($activeDays * ($i / $updateCount)) + rand(1, 5);
                $updateDates[] = $startDate->copy()->addDays($daysOffset);
            }
            
            // Generate updates for each date
            foreach ($updateDates as $date) {
                // Skip future dates
                if ($date->isAfter(Carbon::now())) {
                    continue;
                }
                
                $title = $this->generateUpdateTitle($faker, $cause->status, $date, $endDate);
                $content = $this->generateUpdateContent($faker, $cause->status, $date, $endDate);
                
                $updates[] = [
                    'id' => Str::uuid()->toString(),
                    'cause_id' => $cause->id,
                    'title' => $title,
                    'content' => $content,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
                
                $totalCount++;
                
                // Insert in chunks to avoid memory issues
                if (count($updates) >= 100) {
                    DB::table('cause_updates')->insert($updates);
                    $updates = [];
                }
            }
        }
        
        // Insert any remaining updates
        if (!empty($updates)) {
            DB::table('cause_updates')->insert($updates);
        }
        
        $this->command->info("Generated $totalCount historical cause updates");
    }
    
    /**
     * Generate an appropriate update title based on the cause status and timing
     */
    protected function generateUpdateTitle($faker, $causeStatus, $updateDate, $endDate)
    {
        $isNearEnd = $updateDate->diffInDays($endDate) < 30;
        
        $earlyTitles = [
            'Project Kickoff',
            'Initial Progress Report',
            'Getting Started',
            'First Steps Taken',
            'Early Success',
            'Project Launch Update',
        ];
        
        $middleTitles = [
            'Progress Report',
            'Milestone Reached',
            'Halfway Point Update',
            'Ongoing Efforts',
            'Project Development',
            'Monthly Update',
        ];
        
        $lateTitles = [
            'Almost There',
            'Final Stage Update',
            'Approaching Completion',
            'Last Mile Progress',
            'Finishing Touches',
        ];
        
        $completedTitles = [
            'Project Completed',
            'Success Story',
            'Mission Accomplished',
            'Final Report',
            'Goal Achieved',
            'Project Outcomes',
        ];
        
        if ($causeStatus === 'completed' && $isNearEnd) {
            return $faker->randomElement($completedTitles);
        } elseif ($isNearEnd) {
            return $faker->randomElement($lateTitles);
        } elseif ($updateDate->diffInDays($endDate) > $updateDate->diffInDays(Carbon::parse($updateDate)->subYears(5))) {
            return $faker->randomElement($earlyTitles);
        } else {
            return $faker->randomElement($middleTitles);
        }
    }
    
    /**
     * Generate appropriate update content based on the cause status and timing
     */
    protected function generateUpdateContent($faker, $causeStatus, $updateDate, $endDate)
    {
        $isNearEnd = $updateDate->diffInDays($endDate) < 30;
        
        $paragraphs = [];
        
        // First paragraph
        if ($causeStatus === 'completed' && $isNearEnd) {
            $paragraphs[] = $faker->randomElement([
                'We are thrilled to announce that this project has been successfully completed! Thanks to your generous support, we have been able to achieve all of our goals and make a real difference.',
                'Great news! This project is now complete. We want to extend our heartfelt thanks to all supporters who made this possible.',
                'Mission accomplished! We are delighted to share that we have successfully completed this initiative, meeting all our objectives.',
            ]);
        } elseif ($isNearEnd) {
            $paragraphs[] = $faker->randomElement([
                'We are approaching the final stages of this project and wanted to share our progress with you.',
                'As we near completion, we wanted to provide an update on the significant progress we\'ve made.',
                'We\'re in the final stretch of this initiative and wanted to share how your support has made a difference.',
            ]);
        } else {
            $paragraphs[] = $faker->randomElement([
                'We wanted to provide you with an update on how your generous donations are being put to work.',
                'Thanks to your support, we\'ve been making steady progress on this initiative.',
                'We\'re excited to share some updates on how this project has been developing.',
                'Your support has been instrumental in helping us advance this important cause.',
            ]);
        }
        
        // Second paragraph - accomplishments
        $paragraphs[] = $faker->randomElement([
            'So far, we have ' . $faker->sentence(10) . '. This is a significant milestone that helps us ' . $faker->sentence(8),
            'We\'ve successfully ' . $faker->sentence(8) . '. Additionally, we\'ve been able to ' . $faker->sentence(10),
            'Our team has completed ' . $faker->sentence(6) . '. We\'ve also made progress on ' . $faker->sentence(12),
        ]);
        
        // Third paragraph - challenges
        if (!$isNearEnd && $faker->boolean(70)) {
            $paragraphs[] = $faker->randomElement([
                'We\'ve faced some challenges, including ' . $faker->sentence(10) . '. However, we\'re working on solutions by ' . $faker->sentence(8),
                'While we\'ve encountered some obstacles such as ' . $faker->sentence(8) . ', our team is addressing these by ' . $faker->sentence(10),
                'Some unforeseen difficulties have arisen, particularly ' . $faker->sentence(8) . '. We\'re overcoming these by ' . $faker->sentence(10),
            ]);
        }
        
        // Final paragraph - looking ahead
        if ($causeStatus === 'completed' && $isNearEnd) {
            $paragraphs[] = $faker->randomElement([
                'The impact of this project will continue to benefit the community for years to come. We couldn\'t have done it without your support!',
                'Thanks to your generosity, we\'ve created lasting change that will continue to make a difference. We are deeply grateful for your support.',
                'We\'re proud of what we\'ve accomplished together, and the benefits of this project will be felt long into the future. Thank you for being part of this journey!',
            ]);
        } else {
            $paragraphs[] = $faker->randomElement([
                'In the coming weeks, we plan to ' . $faker->sentence(15) . '. We look forward to sharing more updates with you soon.',
                'Our next steps include ' . $faker->sentence(12) . '. We\'ll keep you updated on our progress.',
                'Looking ahead, we will focus on ' . $faker->sentence(10) . '. Thank you for your continued support!',
            ]);
        }
        
        return implode("\n\n", $paragraphs);
    }
} 