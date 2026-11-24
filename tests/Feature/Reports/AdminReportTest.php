<?php

namespace Tests\Feature\Reports;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\Exceptions\PixelforgeApiException;
use App\Services\Pixelforge\PixelforgeClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_admin_report(): void
    {
        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('login'));

        $this->get('/reports')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_visiting_legacy_reports_is_redirected_to_admin_reports(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/reports')
            ->assertRedirect('/admin/reports');
    }

    public function test_admin_report_reconciles_daily_and_per_user_spend_costs_and_profit_with_pixelforge_usage(): void
    {
        $alice = User::factory()->withCredits('20.000000000')->create([
            'name' => 'Alice Creator',
            'email' => 'alice@example.com',
        ]);
        $bob = User::factory()->withCredits('15.000000000')->create([
            'name' => 'Bob Designer',
            'email' => 'bob@example.com',
        ]);

        // Alice succeeded generation created on Nov 1 at 23:55 UTC, completed on Nov 2 UTC
        // Pixelforge bills by completed_at UTC day (2026-11-02)
        Generation::factory()->create([
            'user_id' => $alice->id,
            'status' => GenerationStatus::Succeeded,
            'created_at' => '2026-11-01 23:55:00',
            'completed_at' => '2026-11-02 00:05:00',
            'settled_at' => '2026-11-02 00:05:00',
            'actual_input_text_tokens' => 20,
            'actual_input_image_tokens' => 0,
            'actual_output_tokens' => 1100,
            'actual_reasoning_tokens' => 1000,
            'actual_total_tokens' => 2120,
            'actual_provider_cost' => '0.056600000',
            'actual_user_cost' => '0.070750000',
        ]);

        // Bob succeeded generation on 2026-11-01 UTC
        Generation::factory()->create([
            'user_id' => $bob->id,
            'status' => GenerationStatus::Succeeded,
            'created_at' => '2026-11-01 12:00:00',
            'completed_at' => '2026-11-01 12:01:00',
            'settled_at' => '2026-11-01 12:01:00',
            'actual_input_text_tokens' => 10,
            'actual_input_image_tokens' => 765,
            'actual_output_tokens' => 280,
            'actual_reasoning_tokens' => 400,
            'actual_total_tokens' => 1455,
            'actual_provider_cost' => '0.023900000',
            'actual_user_cost' => '0.029875000',
        ]);

        // Bob failed generation on 2026-11-01 UTC (must cost $0 and not inflate spend or provider cost)
        Generation::factory()->failed()->create([
            'user_id' => $bob->id,
            'created_at' => '2026-11-01 14:00:00',
            'completed_at' => '2026-11-01 14:00:30',
            'settled_at' => '2026-11-01 14:00:30',
            'actual_provider_cost' => '0.000000000',
            'actual_user_cost' => '0.000000000',
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('hasApiKey')->once()->andReturn(true);
        $mockClient->shouldReceive('getUsage')
            ->once()
            ->with('2026-11-01', '2026-11-02')
            ->andReturn([
                'object' => 'usage',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-02',
                'data' => [
                    [
                        'date' => '2026-11-01',
                        'generations' => 1,
                        'input_text_tokens' => 10,
                        'input_image_tokens' => 765,
                        'output_tokens' => 280,
                        'reasoning_tokens' => 400,
                        'cost_usd' => '0.023900000',
                    ],
                    [
                        'date' => '2026-11-02',
                        'generations' => 1,
                        'input_text_tokens' => 20,
                        'input_image_tokens' => 0,
                        'output_tokens' => 1100,
                        'reasoning_tokens' => 1000,
                        'cost_usd' => '0.056600000',
                    ],
                ],
                'total' => [
                    'generations' => 2,
                    'input_text_tokens' => 30,
                    'input_image_tokens' => 765,
                    'output_tokens' => 1380,
                    'reasoning_tokens' => 1400,
                    'cost_usd' => '0.080500000',
                ],
            ]);

        $this->app->instance(PixelforgeClientInterface::class, $mockClient);

        $this->actingAs($alice)
            ->get(route('admin.reports.index', [
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-02',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/AdminReport')
                ->where('filters.start_date', '2026-11-01')
                ->where('filters.end_date', '2026-11-02')
                ->where('summary.credit_spend', '0.100625000')
                ->where('summary.local_provider_cost', '0.080500000')
                ->where('summary.pixelforge_provider_cost', '0.080500000')
                ->where('summary.provider_cost', '0.080500000')
                ->where('summary.profit', '0.020125000')
                ->where('summary.discrepancy', '0.000000000')
                ->where('summary.matches_pixelforge', true)
                ->where('summary.succeeded_generations', 2)
                ->where('summary.failed_generations', 1)
                ->has('daily', 2)
                ->where('daily.0.date', '2026-11-02')
                ->where('daily.0.credit_spend', '0.070750000')
                ->where('daily.0.pixelforge_cost', '0.056600000')
                ->where('daily.0.profit', '0.014150000')
                ->where('daily.0.matches_pixelforge', true)
                ->where('daily.1.date', '2026-11-01')
                ->where('daily.1.credit_spend', '0.029875000')
                ->where('daily.1.pixelforge_cost', '0.023900000')
                ->where('daily.1.profit', '0.005975000')
                ->where('daily.1.failed_generations', 1)
                ->where('daily.1.matches_pixelforge', true)
                ->has('by_user', 2)
                ->where('by_user.0.name', 'Alice Creator')
                ->where('by_user.0.credit_spend', '0.070750000')
                ->where('by_user.0.provider_cost', '0.056600000')
                ->where('by_user.0.profit', '0.014150000')
                ->where('by_user.1.name', 'Bob Designer')
                ->where('by_user.1.credit_spend', '0.029875000')
                ->where('by_user.1.provider_cost', '0.023900000')
                ->where('by_user.1.profit', '0.005975000')
                ->where('by_user.1.failed_generations', 1)
            );
    }

    public function test_admin_report_detects_discrepancy_when_pixelforge_usage_differs(): void
    {
        $user = User::factory()->create();

        Generation::factory()->create([
            'user_id' => $user->id,
            'status' => GenerationStatus::Succeeded,
            'completed_at' => '2026-11-05 10:00:00',
            'settled_at' => '2026-11-05 10:00:00',
            'actual_provider_cost' => '0.020000000',
            'actual_user_cost' => '0.025000000',
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('hasApiKey')->once()->andReturn(true);
        $mockClient->shouldReceive('getUsage')
            ->once()
            ->with('2026-11-05', '2026-11-05')
            ->andReturn([
                'object' => 'usage',
                'data' => [
                    [
                        'date' => '2026-11-05',
                        'generations' => 2,
                        'input_text_tokens' => 0,
                        'input_image_tokens' => 0,
                        'output_tokens' => 0,
                        'reasoning_tokens' => 0,
                        'cost_usd' => '0.030000000',
                    ],
                ],
                'total' => [
                    'generations' => 2,
                    'input_text_tokens' => 0,
                    'input_image_tokens' => 0,
                    'output_tokens' => 0,
                    'reasoning_tokens' => 0,
                    'cost_usd' => '0.030000000',
                ],
            ]);

        $this->app->instance(PixelforgeClientInterface::class, $mockClient);

        $this->actingAs($user)
            ->get(route('admin.reports.index', [
                'start_date' => '2026-11-05',
                'end_date' => '2026-11-05',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.matches_pixelforge', false)
                ->where('summary.discrepancy', '-0.010000000')
                ->where('summary.pixelforge_provider_cost', '0.030000000')
                ->where('summary.local_provider_cost', '0.020000000')
            );
    }

    public function test_admin_report_degrades_gracefully_when_pixelforge_usage_fails(): void
    {
        $user = User::factory()->create();

        Generation::factory()->create([
            'user_id' => $user->id,
            'status' => GenerationStatus::Succeeded,
            'completed_at' => '2026-11-05 10:00:00',
            'settled_at' => '2026-11-05 10:00:00',
            'actual_provider_cost' => '0.040000000',
            'actual_user_cost' => '0.050000000',
        ]);

        $mockClient = Mockery::mock(PixelforgeClientInterface::class);
        $mockClient->shouldReceive('hasApiKey')->once()->andReturn(true);
        $mockClient->shouldReceive('getUsage')
            ->once()
            ->andThrow(new PixelforgeApiException('Upstream usage unavailable', 'server_error', 503));

        $this->app->instance(PixelforgeClientInterface::class, $mockClient);

        $this->actingAs($user)
            ->get(route('admin.reports.index', [
                'start_date' => '2026-11-05',
                'end_date' => '2026-11-05',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.pixelforge_available', false)
                ->where('summary.pixelforge_error', 'Upstream usage unavailable')
                ->where('summary.provider_cost', '0.040000000')
                ->where('summary.credit_spend', '0.050000000')
                ->where('summary.profit', '0.010000000')
            );
    }
}
