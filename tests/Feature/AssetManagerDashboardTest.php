<?php

namespace Tests\Feature;

use App\Mail\AnniversaryNotificationMail;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AssetManagerDashboardService;
use App\Services\AssetManagerHistoryService;
use App\Services\PmgValuationService;
use App\Support\PmgAlertRecipients;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AssetManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metrics_are_based_on_ledgers_and_separate_active_fcp_and_pmg_clients(): void
    {
        Carbon::setTestNow('2026-07-22 12:00:00');
        $pmgProductId = $this->createProduct(2, 'MG Test');
        $fcpProductId = $this->createProduct(1, 'FCP Test');
        $firstCustomer = $this->createCustomer('first@example.test');
        $secondCustomer = $this->createCustomer('second@example.test');

        $capitalizedPmg = $this->createPmgTransaction(
            $firstCustomer->id,
            $pmgProductId,
            10800000,
            '2025-07-10',
            '2027-07-10'
        );
        DB::table('financial_movements')->insert([
            'transaction_id' => $capitalizedPmg->id,
            'type' => 'capitalisation_interets',
            'amount' => 800000,
            'capital_before' => 10000000,
            'capital_after' => 10800000,
            'date_operation' => '2026-07-10 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createPmgTransaction(
            $secondCustomer->id,
            $pmgProductId,
            5000000,
            '2025-01-05',
            '2026-06-30'
        );
        $expiringPmg = $this->createPmgTransaction(
            $secondCustomer->id,
            $pmgProductId,
            2000000,
            '2026-01-25',
            '2026-07-25'
        );

        DB::table('asset_values')->insert([
            'product_id' => $fcpProductId,
            'vl' => '120.123456',
            'date_vl' => '2026-07-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertFcpMovement($firstCustomer->id, $fcpProductId, 'souscription', '1000.00', '10.0000000000', '10.00');
        $this->insertFcpMovement($firstCustomer->id, $fcpProductId, 'versement_libre', '500.00', '5.0000000000', '5.00');
        $this->insertFcpMovement($firstCustomer->id, $fcpProductId, 'rachat', '-720.74', '-6.0000000000', '0.00');

        $metrics = app(AssetManagerDashboardService::class)->build(
            Carbon::now(),
            function ($transaction) use ($capitalizedPmg, $expiringPmg) {
                if ($transaction->id === $capitalizedPmg->id) {
                    return 10900000;
                }

                return $transaction->id === $expiringPmg->id ? 2010000 : 0;
            }
        );

        $this->assertSame('17001515.00', $metrics['historical_investment']);
        $this->assertSame('12000909.00', $metrics['active_investment']);
        $this->assertSame('5000606.00', $metrics['inactive_investment']);
        $this->assertSame('12911081.11', $metrics['active_valuation']);
        $this->assertSame('910172.11', $metrics['active_performance']);
        $this->assertSame('1081.11', $metrics['fcp_aum']);
        $this->assertSame(1, $metrics['active_fcp_clients_count']);
        $this->assertSame(2, $metrics['active_pmg_clients_count']);
        $this->assertSame(2, $metrics['active_clients_count']);
        $this->assertSame(1, $metrics['expiring_pmg_count']);
        $this->assertSame(1, $metrics['anniversary_pmg_count']);
        $this->assertSame(5, $metrics['historical_placements_count']);
        $this->assertSame(3, $metrics['active_positions_count']);
        $this->assertSame(2, $metrics['fallback_records_count']);
        $this->assertSame(0, $metrics['missing_fcp_vl_count']);

        $history = app(AssetManagerHistoryService::class)->build(Carbon::now());
        $lastIndex = count($history['labels']) - 1;

        $this->assertSame('Janv. 25', $history['labels'][0]);
        $this->assertSame('Juil. 26', $history['labels'][$lastIndex]);
        $this->assertSame(12908548.11, $history['aum']['total'][$lastIndex]);
        $this->assertSame(2, $history['clients']['unique'][$lastIndex]);
        $this->assertSame(17001515.0, $history['summary']['gross_subscriptions']);
        $this->assertSame(720.74, $history['summary']['capital_outflows']);
        $this->assertSame(17000794.26, $history['summary']['net_collection']);
    }

    public function test_reusable_pmg_valuation_uses_the_preloaded_movement_ledger(): void
    {
        Carbon::setTestNow('2026-07-22 12:00:00');
        $pmgProductId = $this->createProduct(2, 'MG Ledger');
        $customer = $this->createCustomer('ledger@example.test');
        $transaction = $this->createPmgTransaction(
            $customer->id,
            $pmgProductId,
            10800000,
            '2025-07-10',
            '2027-07-10'
        );
        DB::table('financial_movements')->insert([
            'transaction_id' => $transaction->id,
            'type' => 'capitalisation_interets',
            'amount' => 800000,
            'capital_before' => 10000000,
            'capital_after' => 10800000,
            'date_operation' => '2026-07-10 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $movements = DB::table('financial_movements')
            ->where('transaction_id', $transaction->id)
            ->orderBy('date_operation')
            ->get();

        $valuation = app(PmgValuationService::class)->calculate($transaction, Carbon::now(), $movements);

        $this->assertSame(10828800.0, $valuation);
    }

    public function test_full_fcp_redemption_removes_the_position_but_keeps_historical_investment(): void
    {
        Carbon::setTestNow('2026-07-22 12:00:00');
        $fcpProductId = $this->createProduct(1, 'FCP Sold');
        $customer = $this->createCustomer('sold@example.test');

        $this->insertFcpMovement($customer->id, $fcpProductId, 'souscription', '2500.00', '25.0000000000', '25.00');
        $this->insertFcpMovement($customer->id, $fcpProductId, 'rachat_total', '-2800.00', '-25.0000000000', '0.00');

        $metrics = app(AssetManagerDashboardService::class)->build(Carbon::now(), fn () => 0);

        $this->assertSame('2525.00', $metrics['historical_investment']);
        $this->assertSame('0.00', $metrics['active_investment']);
        $this->assertSame('2525.00', $metrics['inactive_investment']);
        $this->assertSame(0, $metrics['active_fcp_clients_count']);
        $this->assertSame(0, $metrics['active_positions_count']);
    }

    public function test_pmg_alert_recipients_are_read_from_the_voyager_setting_first(): void
    {
        DB::table('settings')
            ->where('key', 'site.anniversary_emails')
            ->update(['value' => 'alerts@example.test,invalid-address,alerts@example.test,kam@example.test']);

        $configuration = PmgAlertRecipients::resolve();

        $this->assertSame(['alerts@example.test', 'kam@example.test'], $configuration['emails']);
        $this->assertTrue($configuration['managed_in_voyager']);
        $this->assertStringContainsString('Administration', $configuration['source']);
    }

    public function test_anniversary_alert_excludes_new_expired_and_non_customer_transactions(): void
    {
        Carbon::setTestNow('2026-07-22 08:00:00');
        Mail::fake();
        $pmgProductId = $this->createProduct(2, 'MG Alert');
        $customer = $this->createCustomer('alerts-customer@example.test');
        $nonCustomer = User::factory()->create(['email' => 'staff@example.test']);
        $nonCustomer->forceFill(['role_id' => 1])->save();

        $valid = $this->createPmgTransaction(
            $customer->id,
            $pmgProductId,
            1000000,
            '2025-07-29',
            '2027-07-29'
        );
        $this->createPmgTransaction($customer->id, $pmgProductId, 2000000, '2026-07-29', '2027-07-29');
        $this->createPmgTransaction($customer->id, $pmgProductId, 3000000, '2025-07-29', '2026-07-25');
        $this->createPmgTransaction($nonCustomer->id, $pmgProductId, 4000000, '2025-07-29', '2027-07-29');

        DB::table('settings')
            ->where('key', 'site.anniversary_emails')
            ->update(['value' => 'alerts@example.test']);

        $this->artisan('pmg:notify-anniversaries')->assertExitCode(0);

        Mail::assertSent(AnniversaryNotificationMail::class, function ($mail) use ($valid) {
            return count($mail->transactions) === 1
                && $mail->transactions[0]['reference'] === $valid->ref;
        });
    }

    private function createCustomer(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->forceFill(['role_id' => 2])->save();

        return $user;
    }

    private function createProduct(int $categoryId, string $title): int
    {
        DB::table('products_categories')->updateOrInsert(
            ['id' => $categoryId],
            ['title' => $categoryId === 1 ? 'FCP' : 'PMG', 'slug' => 'category-' . $categoryId]
        );

        return DB::table('products')->insertGetId([
            'products_category_id' => $categoryId,
            'title' => $title,
            'slug' => strtolower(str_replace(' ', '-', $title)),
            'vl' => 100,
            'nb_action' => 0,
            'duree' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPmgTransaction(
        int $userId,
        int $productId,
        float $amount,
        string $valueDate,
        string $expiryDate
    ): Transaction {
        return Transaction::create([
            'title' => 'Souscription PMG',
            'ref' => uniqid('PMG-', true),
            'payment_mode' => 'Virement',
            'amount' => $amount,
            'status' => 'Succès',
            'user_id' => $userId,
            'product_id' => $productId,
            'vl_buy' => 8,
            'nb_part' => 0,
            'date_validation' => $valueDate,
            'montant_initiale' => 1,
            'type' => 2,
            'duree' => 12,
            'date_echeance' => $expiryDate,
        ]);
    }

    private function insertFcpMovement(
        int $userId,
        int $productId,
        string $type,
        string $amount,
        string $parts,
        string $fees
    ): void {
        DB::table('fcp_movements')->insert([
            'transaction_id' => null,
            'user_id' => $userId,
            'product_id' => $productId,
            'type' => $type,
            'amount_xaf' => $amount,
            'fees' => $fees,
            'vl_applied' => '100.000000',
            'nb_parts_change' => $parts,
            'nb_parts_total' => 0,
            'date_operation' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
