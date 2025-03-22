<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\FinancialReport;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $donor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        
        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(
            \App\Models\Role::where('role_name', 'Admin')->first()->id
        );
        
        // Create a donor user
        $this->donor = User::factory()->create();
        $this->donor->roles()->attach(
            \App\Models\Role::where('role_name', 'Donor')->first()->id
        );
    }

    public function test_admin_can_list_financial_reports(): void
    {
        // Create some reports
        FinancialReport::factory()
            ->count(5)
            ->create([
                'author_id' => $this->admin->id,
                'report_type' => 'quarterly',
            ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Request list of reports
        $response = $this->getJson('/api/financial-reports');

        // Assert successful response with 5 reports
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_view_single_report(): void
    {
        // Create a report
        $report = FinancialReport::factory()->create([
            'author_id' => $this->admin->id,
            'title' => 'Q2 2023 Financial Report',
            'report_type' => 'quarterly',
            'period_start' => '2023-04-01',
            'period_end' => '2023-06-30',
        ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Request the report
        $response = $this->getJson("/api/financial-reports/{$report->id}");

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.title', 'Q2 2023 Financial Report')
                ->assertJsonPath('data.report_type', 'quarterly')
                ->assertJsonPath('data.period_start', '2023-04-01')
                ->assertJsonPath('data.period_end', '2023-06-30');
    }

    public function test_admin_can_create_financial_report(): void
    {
        // Authenticate as admin
        $this->actingAs($this->admin);

        // Create report content
        $content = [
            'summary' => 'Summary of financial performance for Q1 2023',
            'metrics' => [
                'total_donations' => 50000,
                'donor_count' => 250,
                'average_donation' => 200,
                'successful_transactions' => 245,
                'failed_transactions' => 10,
            ],
            'category_distribution' => [
                'Education' => 35,
                'Health' => 25,
                'Environment' => 20,
                'Disaster Relief' => 20,
            ],
            'payment_methods' => [
                'Credit Card' => 60,
                'PayPal' => 30,
                'Bank Transfer' => 10,
            ],
            'recommendations' => [
                'Increase outreach for Education causes',
                'Optimize mobile payment experience',
            ],
        ];

        // Create a report
        $response = $this->postJson('/api/financial-reports', [
            'title' => 'Q1 2023 Financial Report',
            'report_type' => 'quarterly',
            'period_start' => '2023-01-01',
            'period_end' => '2023-03-31',
            'content' => $content,
        ]);

        // Assert successful response
        $response->assertStatus(201)
                ->assertJsonPath('data.title', 'Q1 2023 Financial Report')
                ->assertJsonPath('data.report_type', 'quarterly');

        // Assert report was created in the database
        $this->assertDatabaseHas('financial_reports', [
            'author_id' => $this->admin->id,
            'title' => 'Q1 2023 Financial Report',
            'report_type' => 'quarterly',
            'period_start' => '2023-01-01',
            'period_end' => '2023-03-31',
        ]);
    }

    public function test_admin_can_update_financial_report(): void
    {
        // Create a report
        $report = FinancialReport::factory()->create([
            'author_id' => $this->admin->id,
            'title' => 'Original Title',
            'report_type' => 'quarterly',
            'period_start' => '2023-01-01',
            'period_end' => '2023-03-31',
        ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Create updated content
        $updatedContent = [
            'summary' => 'Updated summary',
            'metrics' => [
                'total_donations' => 55000,
                'donor_count' => 275,
                'average_donation' => 200,
                'successful_transactions' => 270,
                'failed_transactions' => 5,
            ],
            'recommendations' => ['New recommendation'],
        ];

        // Update the report
        $response = $this->putJson("/api/financial-reports/{$report->id}", [
            'title' => 'Updated Title',
            'content' => $updatedContent,
        ]);

        // Assert successful response
        $response->assertStatus(200)
                ->assertJsonPath('data.title', 'Updated Title');

        // Assert report was updated in the database
        $this->assertDatabaseHas('financial_reports', [
            'id' => $report->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_admin_can_delete_financial_report(): void
    {
        // Create a report
        $report = FinancialReport::factory()->create([
            'author_id' => $this->admin->id,
        ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Delete the report
        $response = $this->deleteJson("/api/financial-reports/{$report->id}");

        // Assert successful response
        $response->assertStatus(200);

        // Assert report was deleted (or soft-deleted) from the database
        $this->assertSoftDeleted('financial_reports', [
            'id' => $report->id,
        ]);
    }

    public function test_non_admin_cannot_view_financial_reports(): void
    {
        // Create a report
        $report = FinancialReport::factory()->create([
            'author_id' => $this->admin->id,
        ]);

        // Authenticate as donor
        $this->actingAs($this->donor);

        // Attempt to view reports
        $response = $this->getJson('/api/financial-reports');

        // Assert forbidden response
        $response->assertStatus(403);

        // Attempt to view a single report
        $response = $this->getJson("/api/financial-reports/{$report->id}");

        // Assert forbidden response
        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_create_financial_report(): void
    {
        // Authenticate as donor
        $this->actingAs($this->donor);

        // Attempt to create a report
        $response = $this->postJson('/api/financial-reports', [
            'title' => 'Unauthorized Report',
            'report_type' => 'quarterly',
            'period_start' => '2023-01-01',
            'period_end' => '2023-03-31',
            'content' => ['summary' => 'Test'],
        ]);

        // Assert forbidden response
        $response->assertStatus(403);

        // Assert report was not created
        $this->assertDatabaseMissing('financial_reports', [
            'title' => 'Unauthorized Report',
        ]);
    }

    public function test_can_filter_reports_by_type(): void
    {
        // Create 3 quarterly reports
        FinancialReport::factory()
            ->count(3)
            ->create([
                'author_id' => $this->admin->id,
                'report_type' => 'quarterly',
            ]);

        // Create 2 annual reports
        FinancialReport::factory()
            ->count(2)
            ->create([
                'author_id' => $this->admin->id,
                'report_type' => 'annual',
            ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Request quarterly reports
        $response = $this->getJson('/api/financial-reports?report_type=quarterly');

        // Assert successful response with 3 quarterly reports
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');

        // Request annual reports
        $response = $this->getJson('/api/financial-reports?report_type=annual');

        // Assert successful response with 2 annual reports
        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_reports_by_date_range(): void
    {
        // Create reports with different periods
        FinancialReport::factory()->create([
            'author_id' => $this->admin->id,
            'period_start' => '2022-01-01',
            'period_end' => '2022-03-31',
        ]);

        FinancialReport::factory()->create([
            'author_id' => $this->admin->id,
            'period_start' => '2022-04-01',
            'period_end' => '2022-06-30',
        ]);

        FinancialReport::factory()->create([
            'author_id' => $this->admin->id,
            'period_start' => '2022-07-01',
            'period_end' => '2022-09-30',
        ]);

        // Authenticate as admin
        $this->actingAs($this->admin);

        // Request reports for first half of 2022
        $response = $this->getJson('/api/financial-reports?start_date=2022-01-01&end_date=2022-06-30');

        // Assert successful response with 2 reports
        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }
} 