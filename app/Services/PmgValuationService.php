<?php

namespace App\Services;

use App\Models\TransactionSupplementaire;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PmgValuationService
{
    private const INTEREST_PAYOUT_TYPES = [
        'precompte_interets',
        'paiement_interets',
        'liquidite_interets',
    ];

    private const LIQUIDITY_TYPES = [
        'liquidite_interets',
        'liquidite_capital',
    ];

    private const LIQUIDITY_PAYMENT_TYPES = [
        'paiement_interets',
        'paiement_capital',
    ];

    public function calculate(Model $transaction, $referenceDate, ?Collection $preloadedMovements = null): float
    {
        if (!$transaction->date_validation || !$transaction->date_echeance) {
            return 0;
        }

        $targetDate = Carbon::parse($referenceDate)
            ->min(Carbon::parse($transaction->date_echeance))
            ->endOfDay();
        $startDate = Carbon::parse($transaction->date_validation)->startOfDay();
        if ($targetDate->lt($startDate)) {
            return 0;
        }

        $isSupplemental = $transaction instanceof TransactionSupplementaire;
        $parentId = $isSupplemental ? $transaction->transaction_id : $transaction->id;
        $allMovements = $preloadedMovements ?: DB::table('financial_movements')
            ->where('transaction_id', $parentId)
            ->orderBy('date_operation')
            ->orderBy('id')
            ->get();
        $movementsAtDate = $allMovements->filter(
            fn ($movement) => Carbon::parse($movement->date_operation)->lte($targetDate)
        );

        if ($movementsAtDate->contains(fn ($movement) => $movement->type === 'rachat_total')) {
            return 0;
        }

        $scopedAllMovements = $this->scopeMovements($allMovements, $transaction, $isSupplemental);
        $scopedMovementsAtDate = $this->scopeMovements($movementsAtDate, $transaction, $isSupplemental);
        $lastMovement = $scopedMovementsAtDate
            ->filter(function ($movement) use ($isSupplemental) {
                if ($isSupplemental) {
                    return $movement->type === 'capitalisation_interets';
                }

                return in_array($movement->type, ['capitalisation_interets', 'rachat_partiel'], true);
            })
            ->last();

        if ($lastMovement && (float) $lastMovement->capital_after <= 0) {
            return 0;
        }

        $baseCapital = $this->originalPrincipal($transaction, $allMovements);
        if ($lastMovement) {
            $baseCapital = (float) $lastMovement->capital_after;
            $startDate = Carbon::parse($lastMovement->date_operation)->startOfDay();
        } else {
            $earliestMovement = $scopedAllMovements->first();
            if ($earliestMovement) {
                $baseCapital = $earliestMovement->type === 'souscription_initiale'
                    ? (float) $earliestMovement->capital_after
                    : (float) $earliestMovement->capital_before;
            }
        }

        $interest = 0;
        if ($targetDate->gt($startDate)) {
            $days = $this->days30_360($startDate, $targetDate);
            $interest = ($baseCapital * ((float) $transaction->vl_buy / 100) * $days) / 360;
        }

        $payouts = $scopedMovementsAtDate
            ->filter(function ($movement) {
                if (!in_array($movement->type, self::INTEREST_PAYOUT_TYPES, true)) {
                    return false;
                }

                if ($movement->type !== 'paiement_interets') {
                    return true;
                }

                return !$this->isLiquidityPaymentComment($movement->comments ?? null);
            })
            ->sum(fn ($movement) => (float) $movement->amount);

        return round(($baseCapital - $payouts) + $interest, 0);
    }

    public function originalPrincipal(
        Model $transaction,
        ?Collection $preloadedMovements = null,
        ?bool $isSupplemental = null
    ): float {
        $isSupplemental = $isSupplemental ?? $transaction instanceof TransactionSupplementaire;
        if ($isSupplemental) {
            return (float) $transaction->amount;
        }

        $movements = $preloadedMovements ?: DB::table('financial_movements')
            ->where('transaction_id', $transaction->id)
            ->orderBy('date_operation')
            ->orderBy('id')
            ->get();
        $firstMovement = $this->scopeMovements($movements, $transaction, false)
            ->first(function ($movement) {
                return !in_array(
                    $movement->type,
                    array_merge(self::LIQUIDITY_TYPES, self::LIQUIDITY_PAYMENT_TYPES),
                    true
                ) && ((float) $movement->capital_before > 0 || (float) $movement->capital_after > 0);
            });

        if (!$firstMovement) {
            return (float) $transaction->amount;
        }

        if ($firstMovement->type === 'souscription_initiale' && (float) $firstMovement->capital_after > 0) {
            return (float) $firstMovement->capital_after;
        }

        return (float) $firstMovement->capital_before > 0
            ? (float) $firstMovement->capital_before
            : (float) $firstMovement->capital_after;
    }

    private function scopeMovements(
        Collection $movements,
        Model $transaction,
        bool $isSupplemental
    ): Collection {
        return $movements->filter(function ($movement) use ($transaction, $isSupplemental) {
            $comment = $this->normalizeComment($movement->comments ?? null);
            $isSupplementalMovement = preg_match('/versement compl.*mentaire id\s+\d+/i', $comment) === 1;

            if ($isSupplemental) {
                return preg_match(
                    '/versement compl.*mentaire id\s+' . preg_quote((string) $transaction->id, '/') . '(?:\D|$)/i',
                    $comment
                ) === 1;
            }

            return !$isSupplementalMovement;
        })->values();
    }

    private function isLiquidityPaymentComment(?string $comment): bool
    {
        return str_starts_with($this->normalizeComment($comment), 'paiement depuis liquidite');
    }

    private function normalizeComment(?string $comment): string
    {
        $comment = mb_strtolower((string) $comment, 'UTF-8');
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $comment);

        return $converted === false ? $comment : $converted;
    }

    private function days30_360(Carbon $start, Carbon $end): int
    {
        $d1 = ($start->day === 31 || ($start->month === 2 && $start->day === $start->daysInMonth))
            ? 30
            : $start->day;
        $d2 = ($end->day === 31 || ($end->month === 2 && $end->day === $end->daysInMonth))
            ? 30
            : $end->day;

        return 360 * ($end->year - $start->year)
            + 30 * ($end->month - $start->month)
            + ($d2 - $d1);
    }
}
