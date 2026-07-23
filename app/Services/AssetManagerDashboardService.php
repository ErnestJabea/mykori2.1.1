<?php

namespace App\Services;

use App\Models\AssetValue;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\User;
use App\Support\FinancialDecimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssetManagerDashboardService
{
    private const FCP_SUBSCRIPTION_TYPES = ['souscription', 'souscription_initiale', 'versement_libre'];
    private const FCP_REDEMPTION_TYPES = ['rachat', 'rachat_partiel', 'rachat_total'];
    private const PMG_REDEMPTION_TYPES = ['rachat_partiel', 'rachat_total'];

    /**
     * Build auditable dashboard metrics as of one precise business date.
     *
     * @param callable $pmgValuation fn (Transaction|TransactionSupplementaire $transaction, Carbon $asOf): float
     */
    public function build(Carbon $asOf, callable $pmgValuation): array
    {
        $referenceDate = $asOf->copy()->endOfDay();
        $customerIds = User::where('role_id', 2)->pluck('id');

        $pmg = $this->calculatePmgMetrics($customerIds, $referenceDate, $pmgValuation);
        $fcp = $this->calculateFcpMetrics($customerIds, $referenceDate);

        $activeClientIds = collect($pmg['active_client_ids'])
            ->merge($fcp['active_client_ids'])
            ->unique()
            ->values();

        $historicalInvestment = FinancialDecimal::add(
            $pmg['historical_investment'],
            $fcp['historical_investment']
        );
        $activeInvestment = FinancialDecimal::add(
            $pmg['active_investment'],
            $fcp['active_investment']
        );
        $activeValuation = FinancialDecimal::add($pmg['aum'], $fcp['aum']);
        $activePerformance = FinancialDecimal::subtract($activeValuation, $activeInvestment);
        $inactiveInvestment = FinancialDecimal::subtract($historicalInvestment, $activeInvestment);

        if (FinancialDecimal::of($inactiveInvestment)->isNegative()) {
            $inactiveInvestment = FinancialDecimal::money(0);
        }

        return [
            'reference_date' => $referenceDate->copy()->startOfDay(),
            'total_customers' => $customerIds->count(),
            'active_clients_count' => $activeClientIds->count(),
            'active_fcp_clients_count' => count($fcp['active_client_ids']),
            'active_pmg_clients_count' => count($pmg['active_client_ids']),
            'active_investment' => $activeInvestment,
            'historical_investment' => $historicalInvestment,
            'inactive_investment' => $inactiveInvestment,
            'active_valuation' => $activeValuation,
            'active_performance' => $activePerformance,
            'fcp_aum' => $fcp['aum'],
            'pmg_aum' => $pmg['aum'],
            'fcp_active_investment' => $fcp['active_investment'],
            'pmg_active_investment' => $pmg['active_investment'],
            'expiring_pmg_count' => $pmg['expiring_count'],
            'anniversary_pmg_count' => $pmg['anniversary_count'],
            'historical_placements_count' => $pmg['historical_placements_count'] + $fcp['historical_placements_count'],
            'active_positions_count' => $pmg['active_positions_count'] + $fcp['active_positions_count'],
            'fallback_records_count' => $pmg['fallback_records_count'] + $fcp['fallback_records_count'],
            'missing_fcp_vl_count' => $fcp['missing_vl_count'],
            'missing_pmg_expiry_count' => $pmg['missing_expiry_count'],
        ];
    }

    private function calculatePmgMetrics(Collection $customerIds, Carbon $asOf, callable $pmgValuation): array
    {
        $mainTransactions = Transaction::query()
            ->with('product')
            ->whereIn('user_id', $customerIds)
            ->where('status', 'Succès')
            ->whereHas('product', fn ($query) => $query->where('products_category_id', 2))
            ->get()
            ->map(fn ($transaction) => ['transaction' => $transaction, 'supplemental' => false]);

        $supplementalTransactions = TransactionSupplementaire::query()
            ->with('product')
            ->whereIn('user_id', $customerIds)
            ->where('status', 'Succès')
            ->whereHas('product', fn ($query) => $query->where('products_category_id', 2))
            ->get()
            ->map(fn ($transaction) => ['transaction' => $transaction, 'supplemental' => true]);

        $positions = $mainTransactions->concat($supplementalTransactions);
        $historicalInvestment = BigDecimal::zero();
        $activeInvestment = BigDecimal::zero();
        $aum = BigDecimal::zero();
        $activeClientIds = [];
        $expiringCount = 0;
        $anniversaryCount = 0;
        $historicalPlacementsCount = 0;
        $activePositionsCount = 0;
        $fallbackRecordsCount = 0;
        $missingExpiryCount = 0;
        $monthStart = $asOf->copy()->startOfMonth();
        $monthEnd = $asOf->copy()->endOfMonth();

        foreach ($positions as $position) {
            /** @var Transaction|TransactionSupplementaire $transaction */
            $transaction = $position['transaction'];
            $isSupplemental = $position['supplemental'];
            $startDate = $this->transactionStartDate($transaction);

            if (!$startDate || $startDate->gt($asOf)) {
                continue;
            }

            $scopedMovements = $this->scopedPmgMovements($transaction, $isSupplemental, $asOf);
            $principal = $this->resolvePmgOriginalPrincipal($transaction, $scopedMovements);
            if ($principal['fallback']) {
                $fallbackRecordsCount++;
            }

            $principalAmount = FinancialDecimal::of($principal['amount']);
            if ($principalAmount->isLessThanOrEqualTo(0)) {
                continue;
            }

            $historicalPlacementsCount++;
            $historicalInvestment = $historicalInvestment->plus($principalAmount);

            $expiryDate = $transaction->date_echeance
                ? Carbon::parse($transaction->date_echeance)->endOfDay()
                : null;

            if (!$expiryDate) {
                $missingExpiryCount++;
                continue;
            }

            if ($expiryDate->betweenIncluded($monthStart, $monthEnd)
                && !$this->isPmgClosed($scopedMovements)) {
                $expiringCount++;
            }

            $anniversaryThisYear = $startDate->copy()->year($asOf->year)->startOfDay();
            if ($startDate->year < $asOf->year
                && $startDate->month === $asOf->month
                && $anniversaryThisYear->lte($expiryDate)) {
                $anniversaryCount++;
            }

            if ($expiryDate->lt($asOf->copy()->startOfDay())) {
                continue;
            }

            $valuation = max(0, (float) $pmgValuation($transaction, $asOf->copy()));
            if ($valuation <= 0) {
                continue;
            }

            $outstandingPrincipal = $this->calculatePmgOutstandingPrincipal(
                $scopedMovements,
                $principalAmount,
            );

            $activePositionsCount++;
            $activeClientIds[(int) $transaction->user_id] = true;
            $activeInvestment = $activeInvestment->plus($outstandingPrincipal);
            $aum = $aum->plus(FinancialDecimal::of($valuation));
        }

        return [
            'historical_investment' => $historicalInvestment->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'active_investment' => $activeInvestment->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'aum' => $aum->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'active_client_ids' => array_keys($activeClientIds),
            'expiring_count' => $expiringCount,
            'anniversary_count' => $anniversaryCount,
            'historical_placements_count' => $historicalPlacementsCount,
            'active_positions_count' => $activePositionsCount,
            'fallback_records_count' => $fallbackRecordsCount,
            'missing_expiry_count' => $missingExpiryCount,
        ];
    }

    private function calculateFcpMetrics(Collection $customerIds, Carbon $asOf): array
    {
        $movements = DB::table('fcp_movements')
            ->whereIn('user_id', $customerIds)
            ->where('date_operation', '<=', $asOf->toDateTimeString())
            ->orderBy('date_operation')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($movement) => $movement->user_id . ':' . $movement->product_id);

        $historicalInvestment = BigDecimal::zero();
        $activeInvestment = BigDecimal::zero();
        $aum = BigDecimal::zero();
        $activeClientIds = [];
        $historicalPlacementsCount = 0;
        $activePositionsCount = 0;
        $missingVlCount = 0;
        $fallbackRecordsCount = 0;

        foreach ($movements as $group) {
            $parts = BigDecimal::zero();
            $costBasis = BigDecimal::zero();

            foreach ($group as $movement) {
                $partsChange = FinancialDecimal::of($movement->nb_parts_change);

                if (in_array($movement->type, self::FCP_SUBSCRIPTION_TYPES, true)) {
                    $contribution = FinancialDecimal::of($movement->amount_xaf)
                        ->plus(FinancialDecimal::of($movement->fees ?? 0));
                    $historicalInvestment = $historicalInvestment->plus($contribution);
                    $costBasis = $costBasis->plus($contribution);
                    $historicalPlacementsCount++;
                } elseif (in_array($movement->type, self::FCP_REDEMPTION_TYPES, true)
                    && $parts->isGreaterThan(0)) {
                    $redeemedParts = $partsChange->abs();
                    $ratio = $redeemedParts->dividedBy($parts, 18, RoundingMode::HALF_UP);
                    if ($ratio->isGreaterThan(1)) {
                        $ratio = BigDecimal::one();
                    }
                    $costBasis = $costBasis->minus($costBasis->multipliedBy($ratio));
                }

                $parts = $parts->plus($partsChange);
                if ($parts->isLessThanOrEqualTo('0.0000000001')) {
                    $parts = BigDecimal::zero();
                    $costBasis = BigDecimal::zero();
                }
            }

            if ($parts->isLessThanOrEqualTo(0)) {
                continue;
            }

            $lastMovement = $group->last();
            $latestVl = AssetValue::where('product_id', $lastMovement->product_id)
                ->where('date_vl', '<=', $asOf->toDateString())
                ->orderBy('date_vl', 'desc')
                ->first();

            if (!$latestVl) {
                $missingVlCount++;
                $valuation = BigDecimal::zero();
            } else {
                $valuation = FinancialDecimal::of($parts)
                    ->multipliedBy(FinancialDecimal::of($latestVl->vl));
            }

            $activePositionsCount++;
            $activeClientIds[(int) $lastMovement->user_id] = true;
            $activeInvestment = $activeInvestment->plus($costBasis);
            $aum = $aum->plus($valuation);
        }

        $fallback = $this->calculateFcpFallbackMetrics($customerIds, $asOf, $movements->keys());

        return [
            'historical_investment' => $historicalInvestment
                ->plus(FinancialDecimal::of($fallback['historical_investment']))
                ->toScale(2, RoundingMode::HALF_UP)
                ->__toString(),
            'active_investment' => $activeInvestment
                ->plus(FinancialDecimal::of($fallback['active_investment']))
                ->toScale(2, RoundingMode::HALF_UP)
                ->__toString(),
            'aum' => $aum
                ->plus(FinancialDecimal::of($fallback['aum']))
                ->toScale(2, RoundingMode::HALF_UP)
                ->__toString(),
            'active_client_ids' => array_keys($activeClientIds + array_fill_keys($fallback['active_client_ids'], true)),
            'historical_placements_count' => $historicalPlacementsCount + $fallback['historical_placements_count'],
            'active_positions_count' => $activePositionsCount + $fallback['active_positions_count'],
            'fallback_records_count' => $fallbackRecordsCount + $fallback['fallback_records_count'],
            'missing_vl_count' => $missingVlCount + $fallback['missing_vl_count'],
        ];
    }

    private function calculateFcpFallbackMetrics(Collection $customerIds, Carbon $asOf, Collection $movementKeys): array
    {
        $main = Transaction::query()
            ->with('product')
            ->whereIn('user_id', $customerIds)
            ->where('status', 'Succès')
            ->whereHas('product', fn ($query) => $query->where('products_category_id', 1))
            ->get();
        $supplemental = TransactionSupplementaire::query()
            ->with('product')
            ->whereIn('user_id', $customerIds)
            ->where('status', 'Succès')
            ->whereHas('product', fn ($query) => $query->where('products_category_id', 1))
            ->get();

        $groups = $main->concat($supplemental)
            ->filter(function ($transaction) use ($asOf, $movementKeys) {
                $startDate = $this->transactionStartDate($transaction);
                $key = $transaction->user_id . ':' . $transaction->product_id;

                return $startDate && $startDate->lte($asOf) && !$movementKeys->contains($key);
            })
            ->groupBy(fn ($transaction) => $transaction->user_id . ':' . $transaction->product_id);

        $historicalInvestment = BigDecimal::zero();
        $activeInvestment = BigDecimal::zero();
        $aum = BigDecimal::zero();
        $activeClientIds = [];
        $historicalPlacementsCount = 0;
        $activePositionsCount = 0;
        $missingVlCount = 0;
        $fallbackRecordsCount = 0;

        foreach ($groups as $group) {
            $groupInvestment = BigDecimal::zero();
            $parts = BigDecimal::zero();

            foreach ($group as $transaction) {
                $amount = FinancialDecimal::of($transaction->amount);
                $transactionParts = FinancialDecimal::of($transaction->nb_part);

                if ($transactionParts->isLessThanOrEqualTo(0)
                    && FinancialDecimal::of($transaction->vl_buy)->isGreaterThan(0)) {
                    $netAmount = $amount->minus(FinancialDecimal::of($transaction->fees ?? 0));
                    $transactionParts = $netAmount->dividedBy(
                        FinancialDecimal::of($transaction->vl_buy),
                        FinancialDecimal::PARTS_SCALE,
                        RoundingMode::HALF_UP
                    );
                }

                $groupInvestment = $groupInvestment->plus($amount);
                $parts = $parts->plus($transactionParts);
                $historicalPlacementsCount++;
                $fallbackRecordsCount++;
            }

            $historicalInvestment = $historicalInvestment->plus($groupInvestment);
            if ($parts->isLessThanOrEqualTo(0)) {
                continue;
            }

            $last = $group->last();
            $latestVl = AssetValue::where('product_id', $last->product_id)
                ->where('date_vl', '<=', $asOf->toDateString())
                ->orderBy('date_vl', 'desc')
                ->first();

            $valuation = BigDecimal::zero();
            if ($latestVl) {
                $valuation = $parts->multipliedBy(FinancialDecimal::of($latestVl->vl));
            } else {
                $missingVlCount++;
            }

            $activePositionsCount++;
            $activeClientIds[(int) $last->user_id] = true;
            $activeInvestment = $activeInvestment->plus($groupInvestment);
            $aum = $aum->plus($valuation);
        }

        return [
            'historical_investment' => $historicalInvestment->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'active_investment' => $activeInvestment->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'aum' => $aum->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'active_client_ids' => array_keys($activeClientIds),
            'historical_placements_count' => $historicalPlacementsCount,
            'active_positions_count' => $activePositionsCount,
            'fallback_records_count' => $fallbackRecordsCount,
            'missing_vl_count' => $missingVlCount,
        ];
    }

    private function resolvePmgOriginalPrincipal(Model $transaction, Collection $movements): array
    {
        $subscription = $movements->first(function ($movement) {
            return in_array($movement->type, ['souscription', 'souscription_initiale', 'versement_libre'], true)
                && FinancialDecimal::of($movement->amount)->isGreaterThan(0);
        });
        if ($subscription) {
            return ['amount' => FinancialDecimal::money($subscription->amount), 'fallback' => false];
        }

        $capitalization = $movements->first(function ($movement) {
            return $movement->type === 'capitalisation_interets'
                && FinancialDecimal::of($movement->capital_before)->isGreaterThan(0);
        });
        if ($capitalization) {
            return ['amount' => FinancialDecimal::money($capitalization->capital_before), 'fallback' => false];
        }

        $interestSettlement = $movements->first(function ($movement) {
            return in_array($movement->type, ['precompte_interets', 'paiement_interets'], true)
                && (FinancialDecimal::of($movement->capital_after)->isGreaterThan(0)
                    || FinancialDecimal::of($movement->capital_before)->isGreaterThan(0));
        });
        if ($interestSettlement) {
            $base = FinancialDecimal::of($interestSettlement->capital_after)->isGreaterThan(0)
                ? $interestSettlement->capital_after
                : $interestSettlement->capital_before;

            return ['amount' => FinancialDecimal::money($base), 'fallback' => false];
        }

        $redemption = $movements->first(function ($movement) {
            return in_array($movement->type, self::PMG_REDEMPTION_TYPES, true)
                && FinancialDecimal::of($movement->capital_before)->isGreaterThan(0);
        });
        if ($redemption) {
            return ['amount' => FinancialDecimal::money($redemption->capital_before), 'fallback' => true];
        }

        return ['amount' => FinancialDecimal::money($transaction->amount), 'fallback' => true];
    }

    private function calculatePmgOutstandingPrincipal(
        Collection $movements,
        BigDecimal $originalPrincipal
    ): BigDecimal {
        if ($movements->contains(fn ($movement) => $movement->type === 'rachat_total')) {
            return BigDecimal::zero();
        }

        $partialRedemptions = $movements
            ->where('type', 'rachat_partiel')
            ->reduce(
                fn (BigDecimal $total, $movement) => $total->plus(FinancialDecimal::of($movement->amount)),
                BigDecimal::zero()
            );
        $outstanding = $originalPrincipal->minus($partialRedemptions);

        return $outstanding->isNegative() ? BigDecimal::zero() : $outstanding;
    }

    private function isPmgClosed(Collection $movements): bool
    {
        return $movements->contains(function ($movement) {
            return $movement->type === 'rachat_total'
                || ($movement->type === 'liquidite_capital'
                    && FinancialDecimal::of($movement->capital_after)->isLessThanOrEqualTo(0));
        });
    }

    private function scopedPmgMovements(Model $transaction, bool $isSupplemental, Carbon $asOf): Collection
    {
        $parentId = $isSupplemental ? $transaction->transaction_id : $transaction->id;
        $query = DB::table('financial_movements')
            ->where('transaction_id', $parentId)
            ->where('date_operation', '<=', $asOf->toDateTimeString());

        if ($isSupplemental) {
            $query->where('comments', 'LIKE', '%versement compl%ID ' . $transaction->id . '%');
        } else {
            $query->where(function ($subQuery) {
                $subQuery->whereNull('comments')
                    ->orWhere('comments', 'NOT LIKE', '%versement compl%ID %');
            });
        }

        return $query->orderBy('date_operation')->orderBy('id')->get();
    }

    private function transactionStartDate(Model $transaction): ?Carbon
    {
        $value = $transaction->date_validation ?: $transaction->created_at;

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }
}
