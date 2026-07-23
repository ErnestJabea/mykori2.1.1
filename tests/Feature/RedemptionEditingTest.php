<?php

namespace Tests\Feature;

use App\Models\FinancialMovement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RedemptionEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_redemption_cannot_use_the_subscription_editing_endpoint(): void
    {
        [$user, $productId] = $this->createCustomerAndProduct(2);
        $transaction = $this->createTransaction($user->id, $productId);
        $movement = FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'rachat_partiel',
            'amount' => 1000000,
            'capital_before' => 10000000,
            'capital_after' => 9000000,
            'date_operation' => '2026-06-15 10:00:00',
            'comments' => 'Rachat PMG',
        ]);

        $response = $this->withoutMiddleware()->post(route('customer.transaction.edit'), [
            'trans_id' => $transaction->id,
            'is_supp' => 'false',
            'op_type' => 'rachat',
            'amount' => 1000000,
            'vl_buy' => 0,
            'date_validation' => '2026-06-01',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'Succès',
            'amount' => 10000000,
            'vl_buy' => 8,
            'date_validation' => '2025-05-05 00:00:00',
        ]);
        $this->assertDatabaseHas('financial_movements', ['id' => $movement->id]);
    }

    public function test_pmg_redemption_edit_updates_the_movement_without_altering_the_subscription(): void
    {
        [$user, $productId] = $this->createCustomerAndProduct(2);
        $transaction = $this->createTransaction($user->id, $productId, 862400);

        FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'capitalisation_interets',
            'amount' => 800000,
            'capital_before' => 10000000,
            'capital_after' => 10800000,
            'date_operation' => '2026-05-05 00:00:00',
            'comments' => 'Capitalisation anniversaire',
        ]);
        $redemption = FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type' => 'rachat_partiel',
            'amount' => 10000000,
            'capital_before' => 10987200,
            'capital_after' => 987200,
            'date_operation' => '2026-07-22 10:00:00',
            'comments' => 'Rachat PMG',
        ]);

        $response = $this->withoutMiddleware()->post(route('financial-movement.edit'), [
            'op_id' => $redemption->id,
            'op_category' => 'PMG',
            'amount' => 10000000,
            'date_operation' => '2026-06-01',
            'comments' => 'Date corrigee',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('financial_movements', 2);
        $this->assertDatabaseHas('financial_movements', [
            'id' => $redemption->id,
            'type' => 'rachat_partiel',
            'amount' => 10000000,
            'capital_before' => 10862400,
            'capital_after' => 862400,
            'date_operation' => '2026-06-01 10:00:00',
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'Succès',
            'amount' => 862400,
            'vl_buy' => 8,
            'date_validation' => '2025-05-05 00:00:00',
            'date_echeance' => '2027-05-05',
        ]);
    }

    public function test_fcp_redemption_edit_uses_the_liquidation_value_at_the_new_date(): void
    {
        [$user, $productId] = $this->createCustomerAndProduct(1);
        $transaction = $this->createTransaction($user->id, $productId);

        DB::table('asset_values')->insert([
            [
                'product_id' => $productId,
                'vl' => 10000,
                'date_vl' => '2026-05-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $productId,
                'vl' => 11000,
                'date_vl' => '2026-06-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('fcp_movements')->insert([
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'product_id' => $productId,
            'type' => 'souscription',
            'amount_xaf' => 1000000,
            'fees' => 0,
            'vl_applied' => 10000,
            'nb_parts_change' => 100,
            'nb_parts_total' => 100,
            'date_operation' => '2026-05-01 09:00:00',
            'comment' => 'Souscription FCP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $redemptionId = DB::table('fcp_movements')->insertGetId([
            'transaction_id' => null,
            'user_id' => $user->id,
            'product_id' => $productId,
            'type' => 'rachat',
            'amount_xaf' => 100000,
            'fees' => 0,
            'vl_applied' => 10000,
            'nb_parts_change' => -10,
            'nb_parts_total' => 90,
            'date_operation' => '2026-05-15 11:00:00',
            'comment' => 'Rachat FCP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->post(route('financial-movement.edit'), [
            'op_id' => $redemptionId,
            'op_category' => 'FCP',
            'amount' => 220000,
            'vl_applied' => 10000,
            'date_operation' => '2026-06-01',
            'comments' => 'Date corrigee',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('fcp_movements', 2);
        $this->assertDatabaseHas('fcp_movements', [
            'id' => $redemptionId,
            'amount_xaf' => 220000,
            'vl_applied' => 11000,
            'nb_parts_change' => -20,
            'nb_parts_total' => 80,
            'date_operation' => '2026-06-01 11:00:00',
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'Succès',
            'date_validation' => '2025-05-05 00:00:00',
        ]);
    }

    private function createCustomerAndProduct(int $categoryId): array
    {
        $user = User::factory()->create();
        DB::table('products_categories')->insert([
            'id' => $categoryId,
            'title' => $categoryId === 1 ? 'FCP' : 'PMG',
            'slug' => $categoryId === 1 ? 'fcp' : 'pmg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'products_category_id' => $categoryId,
            'title' => $categoryId === 1 ? 'FCP Test' : 'PMG Test',
            'slug' => $categoryId === 1 ? 'fcp-test' : 'pmg-test',
            'vl' => $categoryId === 1 ? 10000 : 8,
            'nb_action' => 1000,
            'duree' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $productId];
    }

    private function createTransaction(int $userId, int $productId, float $amount = 10000000): Transaction
    {
        return Transaction::create([
            'title' => 'Placement test',
            'ref' => 'TEST-' . $userId . '-' . $productId,
            'payment_mode' => 'Virement',
            'amount' => $amount,
            'status' => 'Succès',
            'user_id' => $userId,
            'product_id' => $productId,
            'vl_buy' => 8,
            'nb_part' => 0,
            'date_validation' => '2025-05-05',
            'montant_initiale' => 10000000,
            'type' => 1,
            'duree' => 24,
            'date_echeance' => '2027-05-05',
            'is_compliance_validated' => 1,
            'is_backoffice_validated' => 1,
            'is_dg_validated' => 1,
        ]);
    }
}
