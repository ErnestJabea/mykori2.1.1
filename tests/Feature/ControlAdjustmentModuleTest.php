<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StatementAuditLog;
use App\Models\StatementVersion;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AdjustmentService;
use App\Services\StatementVersioningService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ControlAdjustmentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected $client;
    protected $operator;
    protected $controller;
    protected $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.features.control_adjustments' => true]);
        Storage::fake('private');

        $this->client = User::create([
            'name' => 'Client Test',
            'email' => 'client_test@example.com',
            'password' => bcrypt('password'),
            'role_id' => 2,
        ]);

        $this->operator = User::create([
            'name' => 'Operator Test',
            'email' => 'operator_test@example.com',
            'password' => bcrypt('password'),
            'role_id' => 6,
        ]);

        $this->controller = User::create([
            'name' => 'Controller Test',
            'email' => 'controller_test@example.com',
            'password' => bcrypt('password'),
            'role_id' => 5,
        ]);

        $product = new Product();
        $product->title = 'FCP Test Product';
        $product->products_category_id = 1;
        $product->vl = '100.000000';
        $product->save();

        $this->transaction = Transaction::create([
            'title' => 'Test Transaction',
            'ref' => 'REF_TEST',
            'payment_mode' => 'Virement',
            'amount' => '10000',
            'fees' => '0',
            'status' => 'Succès',
            'user_id' => $this->client->id,
            'product_id' => $product->id,
            'vl_buy' => '100',
            'nb_part' => '100',
            'date_validation' => '2026-01-01',
            'montant_initiale' => '10000',
            'type' => 1,
        ]);
    }

    public function test_client_cannot_access_control_module()
    {
        $response = $this->actingAs($this->client)
            ->get(route('control-adjustments.index'));

        $response->assertStatus(403);
    }

    public function test_operator_can_submit_correction_request()
    {
        $this->actingAs($this->operator);

        $correction = app(AdjustmentService::class)->requestCorrection([
            'client_id' => $this->client->id,
            'product_id' => $this->transaction->product_id,
            'target_entity' => 'Transaction',
            'target_id' => $this->transaction->id,
            'field_name' => 'vl_buy',
            'new_value' => '105',
            'reason' => 'Ajustement taux contractuel selon avenant #12',
        ]);

        $this->assertEquals('A_controler', $correction->status);
        $this->assertEquals($this->operator->id, $correction->operator_id);
        $this->assertDatabaseHas('statement_corrections', [
            'id' => $correction->id,
            'status' => 'A_controler',
        ]);
        $this->assertDatabaseHas('statement_audit_logs', [
            'correction_id' => $correction->id,
            'event_type' => 'DEMANDE_CORRECTION',
        ]);
    }

    public function test_operator_cannot_validate_own_correction()
    {
        $this->actingAs($this->operator);

        $correction = app(AdjustmentService::class)->requestCorrection([
            'client_id' => $this->client->id,
            'product_id' => $this->transaction->product_id,
            'target_entity' => 'Transaction',
            'target_id' => $this->transaction->id,
            'field_name' => 'fees',
            'new_value' => '1500',
            'reason' => 'Correction des frais de dossiers',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('4 yeux');

        app(AdjustmentService::class)->validateCorrection($correction->id, $this->operator->id);
    }

    public function test_distinct_controller_validates_and_creates_calculated_version()
    {
        $this->actingAs($this->operator);

        $correction = app(AdjustmentService::class)->requestCorrection([
            'client_id' => $this->client->id,
            'product_id' => $this->transaction->product_id,
            'target_entity' => 'Transaction',
            'target_id' => $this->transaction->id,
            'field_name' => 'fees',
            'new_value' => '2000',
            'reason' => 'Ajustement frais de gestion approuve',
        ]);

        $validatedCorrection = app(AdjustmentService::class)
            ->validateCorrection($correction->id, $this->controller->id);

        $this->assertEquals('Valide', $validatedCorrection->status);
        $this->assertEquals($this->controller->id, $validatedCorrection->controller_id);

        $version = StatementVersion::where('user_id', $this->client->id)->first();
        $this->assertNotNull($version);
        $this->assertEquals('Calcule', $version->status);
        $this->assertNotEmpty($version->payload_sha256_hash);
        $this->assertNotEmpty($version->pdf_path);
        $this->assertNotEmpty($version->sha256_hash);
        $this->assertStringStartsWith('releves/', $version->pdf_path);
        if (strpos($version->pdf_path, 'statement-versions/') === 0) {
            Storage::disk('private')->assertExists($version->pdf_path);
        } else {
            $publicPdfPath = storage_path('app/public/' . $version->pdf_path);
            $this->assertFileExists($publicPdfPath);
            $this->assertEquals(hash_file('sha256', $publicPdfPath), $version->sha256_hash);
            @unlink($publicPdfPath);
        }

        $this->assertDatabaseHas('statement_audit_logs', [
            'correction_id' => $correction->id,
            'event_type' => 'VALIDATION',
        ]);
        $this->assertDatabaseHas('statement_audit_logs', [
            'correction_id' => $correction->id,
            'event_type' => 'RECALCUL',
        ]);
    }

    public function test_forbidden_field_is_rejected_server_side()
    {
        $this->actingAs($this->operator);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Champ non autorise');

        app(AdjustmentService::class)->requestCorrection([
            'client_id' => $this->client->id,
            'product_id' => $this->transaction->product_id,
            'target_entity' => 'Transaction',
            'target_id' => $this->transaction->id,
            'field_name' => 'user_id',
            'new_value' => $this->controller->id,
            'reason' => 'Tentative de champ interdit',
        ]);
    }

    public function test_audit_log_is_append_only()
    {
        $log = StatementAuditLog::logEvent([
            'event_type' => 'SIMULATION',
            'user_id' => $this->client->id,
            'operator_id' => $this->operator->id,
            'reason' => 'Test simulation append-only',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("L'historique d'audit est append-only");

        $log->reason = "Tentative d'alteration du journal";
        $log->save();
    }

    public function test_sent_pdf_is_versioned_with_hash()
    {
        $pdfDirectory = storage_path('app/public/releves/tests');
        if (!file_exists($pdfDirectory)) {
            mkdir($pdfDirectory, 0755, true);
        }

        $pdfPath = $pdfDirectory . '/releve-test.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 test statement content');

        $version = app(StatementVersioningService::class)->recordSentPdf(
            $this->client,
            $pdfPath,
            'PMG',
            now()->startOfMonth()->subDay(),
            'Juin 2026',
            $this->operator->id
        );

        $this->assertEquals('Envoye', $version->status);
        $this->assertEquals('releves/tests/releve-test.pdf', $version->pdf_path);
        $this->assertEquals(hash_file('sha256', $pdfPath), $version->sha256_hash);
        $this->assertNotEmpty($version->payload_sha256_hash);

        $this->assertDatabaseHas('statement_audit_logs', [
            'statement_version_id' => $version->id,
            'event_type' => 'IMPRESSION_PDF',
        ]);

        unlink($pdfPath);
    }
}
