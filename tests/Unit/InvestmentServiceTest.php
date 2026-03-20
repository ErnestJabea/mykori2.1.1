<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Transaction;
use App\Models\FinancialMovement;
use App\Services\InvestmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvestmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $investmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->investmentService = new InvestmentService();
    }

    /**
     * Test getValuation returns correct capital and accrued interest
     */
    public function test_get_valuation_calculates_capital_and_interest()
    {
        // Create a transaction (initial subscription)
        $transaction = Transaction::create([
            'title' => 'Test Product',
            'ref' => 'REF001',
            'payment_mode' => 'Virement',
            'amount' => '10000',
            'status' => 'Succès',
            'user_id' => 1,
            'product_id' => '1',
            'vl_buy' => '5', // 5% annual interest rate
            'nb_part' => '100',
            'date_validation' => '2026-01-01',
            'montant_initiale' => '10000',
            'type' => 2, // PMG
            'duree' => 12,
            'date_echeance' => '2026-12-31',
        ]);

        // Create a subscription movement
        FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'souscription',
            'amount' => 10000.00,
            'capital_before' => 0.00,
            'capital_after' => 10000.00,
            'date_operation' => '2026-01-01',
        ]);

        // Get valuation at the same date (no interest accrued yet)
        $valuation = $this->investmentService->getValuation($transaction->id, '2026-01-01');

        $this->assertEquals(10000.00, $valuation['capital']);
        $this->assertEquals(0.00, $valuation['accrued_interest']);
        $this->assertEquals(10000.00, $valuation['valuation']);
    }

    /**
     * Test getValuation with interest accrual over multiple days
     */
    public function test_get_valuation_with_interest_accrual()
    {
        // Create a transaction with 5% annual interest
        $transaction = Transaction::create([
            'title' => 'Test Product',
            'ref' => 'REF002',
            'payment_mode' => 'Virement',
            'amount' => '10000',
            'status' => 'Succès',
            'user_id' => 1,
            'product_id' => '1',
            'vl_buy' => '5', // 5% annual
            'nb_part' => '100',
            'date_validation' => '2026-01-01',
            'montant_initiale' => '10000',
            'type' => 2,
            'duree' => 12,
            'date_echeance' => '2026-12-31',
        ]);

        // Create subscription movement
        FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'souscription',
            'amount' => 10000.00,
            'capital_before' => 0.00,
            'capital_after' => 10000.00,
            'date_operation' => '2026-01-01',
        ]);

        // Get valuation 360 days later (1 full year)
        $valuation = $this->investmentService->getValuation($transaction->id, '2027-01-01');

        $this->assertEquals(10000.00, $valuation['capital']);
        // 5% annual interest on 10000 = 500
        $this->assertGreaterThan(0, $valuation['accrued_interest']);
    }

    /**
     * Test executeRedemption creates a financial movement
     */
    public function test_execute_redemption_partial()
    {
        $transaction = Transaction::create([
            'title' => 'Test Product',
            'ref' => 'REF003',
            'payment_mode' => 'Virement',
            'amount' => '10000',
            'status' => 'Succès',
            'user_id' => 1,
            'product_id' => '1',
            'vl_buy' => '0',
            'nb_part' => '100',
            'date_validation' => '2026-01-01',
            'montant_initiale' => '10000',
            'type' => 2,
            'duree' => 12,
            'date_echeance' => '2026-12-31',
        ]);

        // Create subscription movement
        FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'souscription',
            'amount' => 10000.00,
            'capital_before' => 0.00,
            'capital_after' => 10000.00,
            'date_operation' => '2026-01-01',
        ]);

        // Execute partial redemption
        $redemption = $this->investmentService->executeRedemption($transaction->id, 2500.00);

        $this->assertNotNull($redemption);
        $this->assertEquals('rachat_partiel', $redemption->type);
        $this->assertEquals(2500.00, $redemption->amount);
        $this->assertEquals(10000.00, $redemption->capital_before);
        $this->assertEquals(7500.00, $redemption->capital_after);

        // Verify it was recorded in DB
        $this->assertDatabaseHas('financial_movements', [
            'transaction_id' => $transaction->id,
            'type' => 'rachat_partiel',
            'amount' => 2500.00,
        ]);
    }

    /**
     * Test executeRedemption creates rachat_total when redeeming full amount
     */
    public function test_execute_redemption_total()
    {
        $transaction = Transaction::create([
            'title' => 'Test Product',
            'ref' => 'REF004',
            'payment_mode' => 'Virement',
            'amount' => '5000',
            'status' => 'Succès',
            'user_id' => 1,
            'product_id' => '1',
            'vl_buy' => '0',
            'nb_part' => '100',
            'date_validation' => '2026-01-01',
            'montant_initiale' => '5000',
            'type' => 2,
            'duree' => 12,
            'date_echeance' => '2026-12-31',
        ]);

        // Create subscription movement
        FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'souscription',
            'amount' => 5000.00,
            'capital_before' => 0.00,
            'capital_after' => 5000.00,
            'date_operation' => '2026-01-01',
        ]);

        // Execute full redemption
        $redemption = $this->investmentService->executeRedemption($transaction->id, 5000.00);

        $this->assertNotNull($redemption);
        $this->assertEquals('rachat_total', $redemption->type);
        $this->assertEquals(5000.00, $redemption->amount);
        $this->assertEquals(5000.00, $redemption->capital_before);
        $this->assertEquals(0.00, $redemption->capital_after);
    }

    /**
     * Test executeRedemption throws exception for insufficient balance
     */
    public function test_execute_redemption_insufficient_balance()
    {
        $transaction = Transaction::create([
            'title' => 'Test Product',
            'ref' => 'REF005',
            'payment_mode' => 'Virement',
            'amount' => '5000',
            'status' => 'Succès',
            'user_id' => 1,
            'product_id' => '1',
            'vl_buy' => '0',
            'nb_part' => '100',
            'date_validation' => '2026-01-01',
            'montant_initiale' => '5000',
            'type' => 2,
            'duree' => 12,
            'date_echeance' => '2026-12-31',
        ]);

        // Create subscription movement
        FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'souscription',
            'amount' => 5000.00,
            'capital_before' => 0.00,
            'capital_after' => 5000.00,
            'date_operation' => '2026-01-01',
        ]);

        // Try to redeem more than available
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solde insuffisant');

        $this->investmentService->executeRedemption($transaction->id, 10000.00);
    }

    /**
     * Test executeRedemption throws exception for invalid amount
     */
    public function test_execute_redemption_invalid_amount()
    {
        $transaction = Transaction::create([
            'title' => 'Test Product',
            'ref' => 'REF006',
            'payment_mode' => 'Virement',
            'amount' => '5000',
            'status' => 'Succès',
            'user_id' => 1,
            'product_id' => '1',
            'vl_buy' => '0',
            'nb_part' => '100',
            'date_validation' => '2026-01-01',
            'montant_initiale' => '5000',
            'type' => 2,
            'duree' => 12,
            'date_echeance' => '2026-12-31',
        ]);

        // Try to redeem with invalid amount (0 or negative)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Montant invalide');

        $this->investmentService->executeRedemption($transaction->id, 0);
    }
}
