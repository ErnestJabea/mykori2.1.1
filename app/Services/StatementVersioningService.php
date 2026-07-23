<?php

namespace App\Services;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ListeClientReleveController;
use App\Models\AssetValue;
use App\Models\Product;
use App\Models\StatementAuditLog;
use App\Models\StatementCorrection;
use App\Models\StatementVersion;
use App\Models\Transaction;
use App\Models\User;
use App\Support\FinancialDecimal;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StatementVersioningService
{
    protected $productController;

    public function __construct(ProductController $productController)
    {
        $this->productController = $productController;
    }

    public function createVersionAfterCorrection(StatementCorrection $correction, int $controllerId): StatementVersion
    {
        $previousVersion = $correction->statement_version_id
            ? StatementVersion::find($correction->statement_version_id)
            : null;

        $statementDate = $previousVersion && $previousVersion->statement_date
            ? Carbon::parse($previousVersion->statement_date)
            : Carbon::today()->startOfMonth()->subDay();

        $periodName = $previousVersion
            ? $previousVersion->period_name
            : $statementDate->format('Y-m');

        $productId = $previousVersion
            ? $previousVersion->product_id
            : $correction->product_id;

        $summaryPayload = $this->buildSummaryPayload($correction->user_id, $productId, $statementDate);
        $payloadHash = hash('sha256', json_encode($summaryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $nextVersionNumber = StatementVersion::where('user_id', $correction->user_id)
            ->where('period_name', $periodName)
            ->where(function ($query) use ($productId) {
                if ($productId) {
                    $query->where('product_id', $productId);
                } else {
                    $query->whereNull('product_id');
                }
            })
            ->max('version_number');

        $previousStatus = $previousVersion ? $previousVersion->status : null;

        $newVersion = StatementVersion::create([
            'user_id' => $correction->user_id,
            'product_id' => $productId,
            'period_name' => $periodName,
            'statement_date' => $statementDate->toDateString(),
            'version_number' => ((int) $nextVersionNumber) + 1,
            'status' => 'Calcule',
            'pdf_path' => null,
            'sha256_hash' => null,
            'payload_sha256_hash' => $payloadHash,
            'summary_payload' => $summaryPayload,
            'created_by' => $correction->operator_id,
            'validated_by' => $controllerId,
            'replaces_version_id' => $previousVersion ? $previousVersion->id : null,
        ]);

        if ($previousVersion && $previousVersion->status !== 'Remplace') {
            $previousVersion->status = 'Remplace';
            $previousVersion->save();
        }

        $newVersion = $this->generatePdfForVersion($newVersion, $productId);

        StatementAuditLog::logEvent([
            'event_type' => 'RECALCUL',
            'statement_version_id' => $newVersion->id,
            'correction_id' => $correction->id,
            'user_id' => $correction->user_id,
            'product_id' => $productId,
            'target_entity' => $correction->target_entity,
            'target_id' => $correction->target_id,
            'field_name' => $correction->field_name,
            'status_before' => $previousStatus,
            'status_after' => 'Calcule',
            'version_number' => $newVersion->version_number,
            'operator_id' => $correction->operator_id,
            'controller_id' => $controllerId,
            'comment' => 'Nouvelle version calculee apres validation de correction.',
            'technical_context' => [
                'payload_sha256_hash' => $payloadHash,
                'pdf_sha256_hash' => $newVersion->sha256_hash,
                'pdf_path' => $newVersion->pdf_path,
                'statement_date' => $statementDate->toDateString(),
                'pdf_generated' => true,
            ],
        ]);

        return $newVersion;
    }

    public function attachPdfHash(StatementVersion $version, string $disk, string $path): StatementVersion
    {
        $contents = Storage::disk($disk)->get($path);

        $version->pdf_path = $path;
        $version->sha256_hash = hash('sha256', $contents);
        $version->save();

        return $version;
    }

    public function recordSentPdf(User $client, string $pdfPath, string $type, Carbon $statementDate, string $periodLabel, ?int $operatorId = null): StatementVersion
    {
        if (!file_exists($pdfPath)) {
            throw new \InvalidArgumentException("PDF de releve introuvable: {$pdfPath}");
        }

        $statementType = strtoupper($type);
        $periodName = $statementDate->format('Y-m') . '-' . strtolower($statementType);
        $relativePath = $this->normalizeStoragePublicPath($pdfPath);
        $pdfHash = hash_file('sha256', $pdfPath);

        $summaryPayload = [
            'user_id' => $client->id,
            'statement_type' => $statementType,
            'period_label' => $periodLabel,
            'statement_date' => $statementDate->toDateString(),
            'pdf_basename' => basename($pdfPath),
            'pdf_path' => $relativePath,
            'pdf_size' => filesize($pdfPath),
            'pdf_sha256_hash' => $pdfHash,
            'sent_at' => now()->toDateTimeString(),
        ];

        $payloadHash = hash('sha256', json_encode($summaryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $nextVersionNumber = StatementVersion::where('user_id', $client->id)
            ->whereNull('product_id')
            ->where('period_name', $periodName)
            ->max('version_number');

        $version = StatementVersion::create([
            'user_id' => $client->id,
            'product_id' => null,
            'period_name' => $periodName,
            'statement_date' => $statementDate->toDateString(),
            'version_number' => ((int) $nextVersionNumber) + 1,
            'status' => 'Envoye',
            'pdf_path' => $relativePath,
            'sha256_hash' => $pdfHash,
            'payload_sha256_hash' => $payloadHash,
            'summary_payload' => $summaryPayload,
            'sent_at' => now(),
            'created_by' => $operatorId,
            'validated_by' => $operatorId,
        ]);

        StatementAuditLog::logEvent([
            'event_type' => 'IMPRESSION_PDF',
            'statement_version_id' => $version->id,
            'user_id' => $client->id,
            'product_id' => null,
            'status_after' => 'Envoye',
            'version_number' => $version->version_number,
            'operator_id' => $operatorId,
            'comment' => "Releve {$statementType} envoye et versionne.",
            'technical_context' => [
                'statement_type' => $statementType,
                'pdf_path' => $relativePath,
                'pdf_sha256_hash' => $pdfHash,
                'payload_sha256_hash' => $payloadHash,
            ],
        ]);

        return $version;
    }

    private function normalizeStoragePublicPath(string $absolutePath): string
    {
        $normalizedPath = str_replace('\\', '/', $absolutePath);
        $publicRoot = str_replace('\\', '/', storage_path('app/public')) . '/';

        if (strpos($normalizedPath, $publicRoot) === 0) {
            return substr($normalizedPath, strlen($publicRoot));
        }

        return $normalizedPath;
    }

    private function generatePdfForVersion(StatementVersion $version, ?int $productId = null): StatementVersion
    {
        $officialVersion = $this->generateOfficialPdfForVersion($version, $productId);

        if ($officialVersion) {
            return $officialVersion;
        }

        return $this->generateTechnicalPdfForVersion($version);
    }

    private function generateOfficialPdfForVersion(StatementVersion $version, ?int $productId = null): ?StatementVersion
    {
        if (!$productId) {
            return null;
        }

        $product = Product::find($productId);
        if (!$product || !in_array((int) $product->products_category_id, [1, 2], true)) {
            return null;
        }

        $statementController = app(ListeClientReleveController::class);
        $pdfPath = (int) $product->products_category_id === 2
            ? $statementController->genererPdfPmg($version->user_id)
            : $statementController->genererPdfFcp($version->user_id);

        if (!file_exists($pdfPath)) {
            return null;
        }

        $version->pdf_path = $this->normalizeStoragePublicPath($pdfPath);
        $version->sha256_hash = hash_file('sha256', $pdfPath);
        $version->save();

        return $version;
    }

    private function generateTechnicalPdfForVersion(StatementVersion $version): StatementVersion
    {
        $version->refresh();

        $client = User::findOrFail($version->user_id);
        $payload = $version->summary_payload ?? [];

        $pdf = Pdf::loadView('control_adjustments.version-pdf', [
            'version' => $version,
            'client' => $client,
            'payload' => $payload,
        ])->setPaper('a4', 'portrait');

        $contents = $pdf->output();
        $period = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $version->period_name);
        $path = "statement-versions/{$version->user_id}/{$period}/version-{$version->id}.pdf";

        Storage::disk('private')->put($path, $contents);

        $version->pdf_path = $path;
        $version->sha256_hash = hash('sha256', $contents);
        $version->save();

        return $version;
    }

    private function buildSummaryPayload(int $userId, ?int $productId, Carbon $statementDate): array
    {
        $pmgItems = $this->buildPmgSummary($userId, $productId, $statementDate);
        $fcpItems = $this->buildFcpSummary($userId, $productId, $statementDate);

        return [
            'user_id' => $userId,
            'product_id' => $productId,
            'statement_date' => $statementDate->toDateString(),
            'generated_at' => now()->toDateTimeString(),
            'pmg' => $pmgItems,
            'fcp' => $fcpItems,
            'totals' => [
                'pmg_valuation' => array_sum(array_column($pmgItems, 'valuation')),
                'fcp_valuation' => array_sum(array_column($fcpItems, 'valuation')),
                'global_valuation' => array_sum(array_column($pmgItems, 'valuation')) + array_sum(array_column($fcpItems, 'valuation')),
            ],
        ];
    }

    private function buildPmgSummary(int $userId, ?int $productId, Carbon $statementDate): array
    {
        $query = Transaction::where('user_id', $userId)
            ->whereIn('status', ['Succès', 'SuccÃ¨s'])
            ->where('date_validation', '<=', $statementDate->toDateString())
            ->whereHas('product', function ($productQuery) {
                $productQuery->where('products_category_id', 2);
            })
            ->with('product');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->get()->map(function ($transaction) use ($statementDate) {
            $valuation = (float) $this->productController->calculatePMGValorization($transaction, $statementDate);

            return [
                'transaction_id' => $transaction->id,
                'product_id' => $transaction->product_id,
                'product_title' => optional($transaction->product)->title,
                'amount' => (float) $transaction->amount,
                'rate' => (float) $transaction->vl_buy,
                'date_validation' => optional($transaction->date_validation)->toDateString(),
                'date_echeance' => $transaction->date_echeance,
                'valuation' => round($valuation, 2),
                'gain' => round($valuation - (float) $transaction->amount, 2),
            ];
        })->values()->all();
    }

    private function buildFcpSummary(int $userId, ?int $productId, Carbon $statementDate): array
    {
        $date = $statementDate->toDateString();

        $productIdsQuery = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('date_operation', '<=', $date)
            ->select('product_id')
            ->distinct();

        if ($productId) {
            $productIdsQuery->where('product_id', $productId);
        }

        return $productIdsQuery->pluck('product_id')->map(function ($currentProductId) use ($userId, $date) {
            $product = Product::find($currentProductId);
            if (!$product) {
                return null;
            }

            $parts = DB::table('fcp_movements')
                ->where('user_id', $userId)
                ->where('product_id', $currentProductId)
                ->where('date_operation', '<=', $date)
                ->sum('nb_parts_change') ?? 0;

            $vl = AssetValue::where('product_id', $currentProductId)
                ->where('date_vl', '<=', $date)
                ->orderBy('date_vl', 'desc')
                ->value('vl') ?? $product->vl;

            $valuation = FinancialDecimal::toFloat(FinancialDecimal::fcpValuation($parts, $vl));

            return [
                'product_id' => $currentProductId,
                'product_title' => $product->title,
                'parts' => (float) $parts,
                'vl' => (float) $vl,
                'valuation' => $valuation,
            ];
        })->filter()->values()->all();
    }
}
