<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PmgInterestLiquiditySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_annual_interest_mode_creates_late_liquidity_without_capitalizing()
    {
        Carbon::setTestNow('2026-06-20 09:00:00');

        $transaction = $this->createPmgTransaction([
            'date_validation' => '2024-01-15',
            'date_echeance' => '2027-01-15',
            'interest_management' => 'Annuellement a la date anniversaire',
        ]);

        (new ProductController())->syncAnniversaryMovements();
        (new ProductController())->syncAnniversaryMovements();

        $movements = DB::table('financial_movements')
            ->where('transaction_id', $transaction->id)
            ->where('type', 'liquidite_interets')
            ->orderBy('date_operation')
            ->get();

        $this->assertCount(2, $movements);
        $this->assertSame('2025-01-15', Carbon::parse($movements[0]->date_operation)->toDateString());
        $this->assertSame('2026-01-15', Carbon::parse($movements[1]->date_operation)->toDateString());
        $this->assertEquals(12000, (float)$movements[0]->amount);
        $this->assertEquals(12000, (float)$movements[1]->amount);
        $this->assertEquals(100000, (float)$movements[0]->capital_after);

        $transaction->refresh();
        $this->assertEquals(100000, (float)$transaction->amount);
    }

    public function test_monthly_exception_mode_creates_late_liquidity_for_each_due_month()
    {
        Carbon::setTestNow('2026-06-20 09:00:00');

        $transaction = $this->createPmgTransaction([
            'date_validation' => '2026-01-20',
            'date_echeance' => '2026-12-20',
            'interest_management' => 'Chaque mois (mois anniversaire pour les cas exceptionnels)',
        ]);

        (new ProductController())->syncAnniversaryMovements();

        $movements = DB::table('financial_movements')
            ->where('transaction_id', $transaction->id)
            ->where('type', 'liquidite_interets')
            ->orderBy('date_operation')
            ->get();

        $this->assertCount(5, $movements);
        $this->assertSame('2026-02-20', Carbon::parse($movements[0]->date_operation)->toDateString());
        $this->assertSame('2026-06-20', Carbon::parse($movements[4]->date_operation)->toDateString());
        $this->assertEquals(1000, (float)$movements[0]->amount);
        $this->assertEquals(100000, (float)$movements[4]->capital_after);
    }

    private function createPmgTransaction(array $overrides = []): Transaction
    {
        $productId = DB::table('products')->insertGetId([
            'products_category_id' => 2,
            'title' => 'PMG Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Transaction::create(array_merge([
            'title' => 'Mandat PMG',
            'ref' => 'PMG-TEST',
            'payment_mode' => 'Virement',
            'amount' => 100000,
            'status' => 'Succès',
            'product_id' => $productId,
            'vl_buy' => 12,
            'nb_part' => 1,
            'montant_initiale' => 100000,
            'type' => 2,
            'duree' => 36,
        ], $overrides));
    }
}
