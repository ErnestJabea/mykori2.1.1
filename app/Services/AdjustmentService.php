<?php

namespace App\Services;

use App\Models\StatementCorrection;
use App\Models\StatementVersion;
use App\Models\StatementAuditLog;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\FinancialMovement;
use App\Models\AssetValue;
use App\Models\Product;
use App\Services\InvestmentService;
use App\Services\StatementVersioningService;
use App\Http\Controllers\ProductController;
use App\Support\FinancialDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class AdjustmentService
{
    private const TARGET_TRANSACTION = 'Transaction';
    private const TARGET_FINANCIAL_MOVEMENT = 'FinancialMovement';

    protected $investmentService;
    protected $productController;
    protected $statementVersioningService;

    public function __construct(
        InvestmentService $investmentService,
        ProductController $productController,
        StatementVersioningService $statementVersioningService
    )
    {
        $this->investmentService = $investmentService;
        $this->productController = $productController;
        $this->statementVersioningService = $statementVersioningService;
    }

    /**
     * White-listed editable fields by product category
     */
    public static function getWhitelistedFields(int $categoryId): array
    {
        if ($categoryId === 2) {
            // PMG
            return [
                'montant_initiale' => 'Capital Initial',
                'date_validation' => 'Date de souscription / valeur',
                'date_echeance' => 'Date d\'échéance',
                'vl_buy' => 'Taux contractuel (%)',
                'interest_management' => 'Mode et fréquence de capitalisation',
                'fees' => 'Frais',
            ];
        } elseif ($categoryId === 1) {
            // FCP
            return [
                'montant_initiale' => 'Montant souscrit',
                'nb_part' => 'Nombre de parts',
                'vl_buy' => 'Valeur Liquidative d\'achat (VL)',
                'date_validation' => 'Date de souscription / valeur',
                'fees' => 'Frais de souscription/rachat',
            ];
        }

        return [];
    }

    private static function getWhitelistedFieldsForTarget(int $categoryId, string $targetEntity): array
    {
        if ($targetEntity === self::TARGET_TRANSACTION) {
            return self::getWhitelistedFields($categoryId);
        }

        if ($targetEntity === self::TARGET_FINANCIAL_MOVEMENT) {
            return [
                'amount' => 'Montant du mouvement',
                'date_operation' => 'Date du mouvement',
                'comments' => 'Commentaire du mouvement',
            ];
        }

        return [];
    }

    private function resolveTarget(array $params): array
    {
        $targetEntity = $params['target_entity'] ?? null;
        $targetId = $params['target_id'] ?? null;

        if (!in_array($targetEntity, [self::TARGET_TRANSACTION, self::TARGET_FINANCIAL_MOVEMENT], true)) {
            throw new Exception("Entite non autorisee pour une correction.");
        }

        if ($targetEntity === self::TARGET_TRANSACTION) {
            $target = Transaction::with('product')->findOrFail($targetId);
            $transaction = $target;
        } else {
            $target = FinancialMovement::with('transaction.product')->findOrFail($targetId);
            $transaction = $target->transaction;

            if (!$transaction) {
                throw new Exception("Mouvement financier sans transaction rattachee.");
            }
        }

        $product = $transaction->product;
        if (!$product) {
            throw new Exception("Produit introuvable pour la cible de correction.");
        }

        if (isset($params['client_id']) && (int) $params['client_id'] !== (int) $transaction->user_id) {
            throw new Exception("La cible de correction n'appartient pas au client indique.");
        }

        if (isset($params['product_id']) && (int) $params['product_id'] !== (int) $transaction->product_id) {
            throw new Exception("La cible de correction n'appartient pas au produit indique.");
        }

        return [
            'target_entity' => $targetEntity,
            'target' => $target,
            'transaction' => $transaction,
            'product' => $product,
            'category_id' => (int) $product->products_category_id,
        ];
    }

    private function ensureFieldIsAllowed(array $resolvedTarget, string $fieldName): void
    {
        $allowedFields = self::getWhitelistedFieldsForTarget(
            $resolvedTarget['category_id'],
            $resolvedTarget['target_entity']
        );

        if (!array_key_exists($fieldName, $allowedFields)) {
            throw new Exception("Champ non autorise pour cette correction.");
        }
    }

    private function normalizeNewValue(string $fieldName, $newValue)
    {
        $numericFields = ['amount', 'montant_initiale', 'vl_buy', 'nb_part', 'fees'];
        $dateFields = ['date_validation', 'date_echeance', 'date_operation'];

        if (in_array($fieldName, $numericFields, true)) {
            if (!is_numeric($newValue)) {
                throw new Exception("La nouvelle valeur du champ {$fieldName} doit etre numerique.");
            }

            if ((float) $newValue < 0) {
                throw new Exception("La nouvelle valeur du champ {$fieldName} ne peut pas etre negative.");
            }

            return (string) $newValue;
        }

        if (in_array($fieldName, $dateFields, true)) {
            return $fieldName === 'date_operation'
                ? Carbon::parse($newValue)->toDateTimeString()
                : Carbon::parse($newValue)->toDateString();
        }

        if (is_string($newValue) && strlen($newValue) > 255) {
            throw new Exception("La nouvelle valeur du champ {$fieldName} est trop longue.");
        }

        return $newValue;
    }

    private function calculateFcpValuation(Transaction $transaction, Carbon $statementDate): float
    {
        $date = $statementDate->toDateString();

        $parts = (float) DB::table('fcp_movements')
            ->where('transaction_id', $transaction->id)
            ->where('date_operation', '<=', $date)
            ->sum('nb_parts_change');

        if (abs($parts) < 0.0000000001) {
            $parts = (float) $transaction->nb_part;
        }

        $latestVl = AssetValue::where('product_id', $transaction->product_id)
            ->where('date_vl', '<=', $date)
            ->orderBy('date_vl', 'desc')
            ->value('vl');

        $vl = $latestVl ?? optional($transaction->product)->vl ?? $transaction->vl_buy;

        return FinancialDecimal::toFloat(FinancialDecimal::fcpValuation($parts, $vl));
    }

    /**
     * Simulate the impact of a correction before saving
     */
    public function simulateCorrection(array $params): array
    {
        $resolvedTarget = $this->resolveTarget($params);
        $targetEntity = $resolvedTarget['target_entity'];
        $fieldName = $params['field_name'];
        $this->ensureFieldIsAllowed($resolvedTarget, $fieldName);
        $newValue = $this->normalizeNewValue($fieldName, $params['new_value']);
        $statementDate = isset($params['statement_date']) ? Carbon::parse($params['statement_date']) : Carbon::now();

        if ($targetEntity === self::TARGET_TRANSACTION) {
            $transaction = $resolvedTarget['transaction'];
            $product = $resolvedTarget['product'];
            $oldValue = $transaction->{$fieldName} ?? null;

            // Clone transaction values into temporary object for simulation
            $simulatedTransaction = clone $transaction;
            $simulatedTransaction->{$fieldName} = $newValue;

            if ($product->products_category_id === 2) {
                // PMG Simulation
                $oldValuation = (float) $this->productController->calculatePMGValorization($transaction, $statementDate);
                $newValuation = (float) $this->productController->calculatePMGValorization($simulatedTransaction, $statementDate);
            } else {
                // FCP Simulation based on movement balance and historical VL.
                $oldValuation = $this->calculateFcpValuation($transaction, $statementDate);
                $newValuation = $this->calculateFcpValuation($simulatedTransaction, $statementDate);
            }
        } elseif ($targetEntity === self::TARGET_FINANCIAL_MOVEMENT) {
            $movement = $resolvedTarget['target'];
            $transaction = $resolvedTarget['transaction'];
            $oldValue = $movement->{$fieldName} ?? null;

            $oldValuation = (float) $this->productController->calculatePMGValorization($transaction, $statementDate);
            // Temporary calculation with movement change
            $newValuation = $oldValuation; // Default fallback, adjustment simulation
            if ($fieldName === 'amount') {
                $diff = (float)$newValue - (float)$movement->amount;
                $newValuation += (in_array($movement->type, ['souscription', 'versement_libre', 'capitalisation_interets'])) ? $diff : -$diff;
            }
        } else {
            throw new Exception("Entité non reconnue pour la simulation.");
        }

        $deltaVal = $newValuation - $oldValuation;
        $deltaPct = $oldValuation > 0 ? ($deltaVal / $oldValuation) * 100 : 0.0;

        return [
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'old_valuation' => round($oldValuation, 2),
            'new_valuation' => round($newValuation, 2),
            'delta_amount' => round($deltaVal, 2),
            'delta_percent' => round($deltaPct, 4),
            'impacted_rubrics' => [
                'Solde / Valorisation globale',
                'Total des plus-values / Intérêts courus',
            ]
        ];
    }

    /**
     * Request a correction (Operator)
     */
    public function requestCorrection(array $data): StatementCorrection
    {
        $operatorId = Auth::id();
        if (!$operatorId) {
            throw new Exception("Utilisateur non authentifié.");
        }

        $resolvedTarget = $this->resolveTarget($data);
        $this->ensureFieldIsAllowed($resolvedTarget, $data['field_name']);
        $data['new_value'] = $this->normalizeNewValue($data['field_name'], $data['new_value']);
        $data['client_id'] = $resolvedTarget['transaction']->user_id;
        $data['product_id'] = $resolvedTarget['transaction']->product_id;

        $simulation = $this->simulateCorrection($data);

        $correction = StatementCorrection::create([
            'statement_version_id' => $data['statement_version_id'] ?? null,
            'user_id' => $data['client_id'],
            'product_id' => $data['product_id'] ?? null,
            'correction_type' => $data['correction_type'] ?? 'source_data',
            'target_entity' => $data['target_entity'],
            'target_id' => $data['target_id'],
            'field_name' => $data['field_name'],
            'old_value' => (string) $simulation['old_value'],
            'new_value' => (string) $data['new_value'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
            'simulation_payload' => $simulation,
            'status' => 'A_controler',
            'operator_id' => $operatorId,
        ]);

        // Audit Log
        StatementAuditLog::logEvent([
            'event_type' => 'DEMANDE_CORRECTION',
            'statement_version_id' => $correction->statement_version_id,
            'correction_id' => $correction->id,
            'user_id' => $correction->user_id,
            'product_id' => $correction->product_id,
            'target_entity' => $correction->target_entity,
            'target_id' => $correction->target_id,
            'field_name' => $correction->field_name,
            'old_value' => $correction->old_value,
            'new_value' => $correction->new_value,
            'reason' => $correction->reason,
            'status_before' => 'N/A',
            'status_after' => 'A_controler',
            'operator_id' => $operatorId,
            'attachment_path' => $correction->attachment_path,
        ]);

        return $correction;
    }

    /**
     * Validate a correction (Controller)
     * STRICT 4-EYES RULE: operator_id != controller_id
     */
    public function validateCorrection(int $correctionId, int $controllerId): StatementCorrection
    {
        $correction = StatementCorrection::findOrFail($correctionId);

        if ((int)$correction->operator_id === (int)$controllerId) {
            throw new Exception("Règle des 4 yeux violée : vous ne pouvez pas valider votre propre demande de correction.");
        }

        if ($correction->status !== 'A_controler') {
            throw new Exception("Cette correction a déjà été traitée (Statut: {$correction->status}).");
        }

        return DB::transaction(function () use ($correction, $controllerId) {
            $resolvedTarget = $this->resolveTarget([
                'client_id' => $correction->user_id,
                'product_id' => $correction->product_id,
                'target_entity' => $correction->target_entity,
                'target_id' => $correction->target_id,
            ]);
            $this->ensureFieldIsAllowed($resolvedTarget, $correction->field_name);
            $normalizedValue = $this->normalizeNewValue($correction->field_name, $correction->new_value);

            // Apply source data change
            if ($correction->target_entity === self::TARGET_TRANSACTION) {
                $transaction = $resolvedTarget['target'];
                $transaction->{$correction->field_name} = $normalizedValue;
                $transaction->save();
            } elseif ($correction->target_entity === self::TARGET_FINANCIAL_MOVEMENT) {
                $movement = $resolvedTarget['target'];
                $movement->{$correction->field_name} = $normalizedValue;
                $movement->save();
            }

            // Mark correction as validated
            $correction->status = 'Valide';
            $correction->controller_id = $controllerId;
            $correction->validated_at = now();
            $correction->save();

            $newVersion = $this->statementVersioningService
                ->createVersionAfterCorrection($correction, $controllerId);

            // Audit Log
            StatementAuditLog::logEvent([
                'event_type' => 'VALIDATION',
                'statement_version_id' => $newVersion->id,
                'correction_id' => $correction->id,
                'user_id' => $correction->user_id,
                'product_id' => $correction->product_id,
                'target_entity' => $correction->target_entity,
                'target_id' => $correction->target_id,
                'field_name' => $correction->field_name,
                'old_value' => $correction->old_value,
                'new_value' => $correction->new_value,
                'status_before' => 'A_controler',
                'status_after' => 'Valide',
                'operator_id' => $correction->operator_id,
                'controller_id' => $controllerId,
            ]);

            return $correction;
        });
    }

    /**
     * Reject a correction (Controller)
     */
    public function rejectCorrection(int $correctionId, int $controllerId, string $rejectionReason): StatementCorrection
    {
        $correction = StatementCorrection::findOrFail($correctionId);

        if ((int)$correction->operator_id === (int)$controllerId) {
            throw new Exception("Règle des 4 yeux violée : vous ne pouvez pas rejeter/traiter votre propre demande.");
        }

        if ($correction->status !== 'A_controler') {
            throw new Exception("Cette correction a déjà été traitée.");
        }

        $correction->status = 'Rejete';
        $correction->controller_id = $controllerId;
        $correction->rejected_at = now();
        $correction->rejection_reason = $rejectionReason;
        $correction->save();

        StatementAuditLog::logEvent([
            'event_type' => 'REJET',
            'statement_version_id' => $correction->statement_version_id,
            'correction_id' => $correction->id,
            'user_id' => $correction->user_id,
            'product_id' => $correction->product_id,
            'target_entity' => $correction->target_entity,
            'target_id' => $correction->target_id,
            'field_name' => $correction->field_name,
            'old_value' => $correction->old_value,
            'new_value' => $correction->new_value,
            'reason' => $rejectionReason,
            'status_before' => 'A_controler',
            'status_after' => 'Rejete',
            'operator_id' => $correction->operator_id,
            'controller_id' => $controllerId,
        ]);

        return $correction;
    }
}
