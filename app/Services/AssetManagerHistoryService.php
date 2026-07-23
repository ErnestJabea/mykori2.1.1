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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssetManagerHistoryService
{
    private const FCP_SUBSCRIPTION_TYPES = ['souscription', 'souscription_initiale', 'versement_libre'];
    private const FCP_REDEMPTION_TYPES = ['rachat', 'rachat_partiel', 'rachat_total'];
    private const PMG_CAPITAL_OUTFLOW_TYPES = ['rachat_partiel', 'rachat_total', 'paiement_capital'];

    public function __construct(private PmgValuationService $pmgValuationService)
    {
    }

    public function build(Carbon $asOf): array
    {
        $fingerprint = $this->dataFingerprint();
        $cacheKey = 'asset-manager-history:v4:' . $asOf->toDateString() . ':' . $fingerprint;

        return Cache::remember(
            $cacheKey,
            now()->addHours(6),
            fn () => $this->generate($asOf->copy()->endOfDay())
        );
    }

    private function generate(Carbon $asOf): array
    {
        Carbon::setLocale('fr');
        $customerIds = User::where('role_id', 2)->pluck('id');
        $mainPmg = Transaction::query()
            ->whereIn('user_id', $customerIds)
            ->where('status', 'Succès')
            ->whereHas('product', fn ($query) => $query->where('products_category_id', 2))
            ->get();
        $supplementalPmg = TransactionSupplementaire::query()
            ->whereIn('user_id', $customerIds)
            ->where('status', 'Succès')
            ->whereHas('product', fn ($query) => $query->where('products_category_id', 2))
            ->get();
        $pmgPositions = $mainPmg
            ->map(fn ($transaction) => ['transaction' => $transaction, 'supplemental' => false])
            ->concat($supplementalPmg->map(
                fn ($transaction) => ['transaction' => $transaction, 'supplemental' => true]
            ));

        $parentIds = $pmgPositions
            ->map(fn ($position) => $position['supplemental']
                ? $position['transaction']->transaction_id
                : $position['transaction']->id)
            ->filter()
            ->unique()
            ->values();
        $pmgMovements = DB::table('financial_movements')
            ->whereIn('transaction_id', $parentIds)
            ->where('date_operation', '<=', $asOf->toDateTimeString())
            ->orderBy('date_operation')
            ->orderBy('id')
            ->get()
            ->groupBy('transaction_id');
        $pmgPositions = $pmgPositions->map(function ($position) use ($pmgMovements) {
            $transaction = $position['transaction'];
            $parentId = $position['supplemental'] ? $transaction->transaction_id : $transaction->id;
            $position['movements'] = $pmgMovements->get($parentId, collect());
            $position['start_date'] = $this->transactionStartDate($transaction);
            $position['expiry_date'] = $transaction->date_echeance
                ? Carbon::parse($transaction->date_echeance)->endOfDay()
                : null;
            $position['original_principal'] = $this->pmgValuationService->originalPrincipal(
                $transaction,
                $position['movements'],
                $position['supplemental']
            );

            return $position;
        })->filter(fn ($position) => $position['start_date'] && $position['start_date']->lte($asOf));

        $fcpMovements = DB::table('fcp_movements')
            ->whereIn('user_id', $customerIds)
            ->where('date_operation', '<=', $asOf->toDateTimeString())
            ->orderBy('date_operation')
            ->orderBy('id')
            ->get();
        $fcpGroups = $fcpMovements->groupBy(
            fn ($movement) => $movement->user_id . ':' . $movement->product_id
        );
        $fcpProductIds = $fcpMovements->pluck('product_id')->filter()->unique()->values();
        $assetValues = AssetValue::query()
            ->whereIn('product_id', $fcpProductIds)
            ->where('date_vl', '<=', $asOf->toDateString())
            ->orderBy('date_vl')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $earliestDate = $this->earliestDate($pmgPositions, $fcpMovements, $asOf);
        $references = $this->monthlyReferences($earliestDate, $asOf);
        $flows = $this->monthlyFlows($references, $pmgPositions, $pmgMovements, $fcpMovements);
        $labels = [];
        $fcpAum = [];
        $pmgAum = [];
        $totalAum = [];
        $fcpClients = [];
        $pmgClients = [];
        $uniqueClients = [];

        foreach ($references as $monthKey => $referenceDate) {
            $labels[] = ucfirst($referenceDate->translatedFormat('M y'));
            $fcpMonthAum = BigDecimal::zero();
            $pmgMonthAum = BigDecimal::zero();
            $activeFcpClientIds = [];
            $activePmgClientIds = [];

            foreach ($fcpGroups as $group) {
                $parts = $group
                    ->filter(fn ($movement) => Carbon::parse($movement->date_operation)->lte($referenceDate))
                    ->reduce(
                        fn (BigDecimal $total, $movement) => $total->plus(FinancialDecimal::of($movement->nb_parts_change)),
                        BigDecimal::zero()
                    );
                if ($parts->isLessThanOrEqualTo('0.0000000001')) {
                    continue;
                }

                $lastMovement = $group->last();
                $latestVl = $assetValues->get($lastMovement->product_id, collect())
                    ->filter(fn ($value) => Carbon::parse($value->date_vl)->lte($referenceDate))
                    ->last();
                if ($latestVl) {
                    $fcpMonthAum = $fcpMonthAum->plus(
                        $parts->multipliedBy(FinancialDecimal::of($latestVl->vl))
                    );
                }
                $activeFcpClientIds[(int) $lastMovement->user_id] = true;
            }

            foreach ($pmgPositions as $position) {
                $transaction = $position['transaction'];
                if ($position['start_date']->gt($referenceDate)
                    || !$position['expiry_date']
                    || $position['expiry_date']->lt($referenceDate->copy()->startOfDay())) {
                    continue;
                }

                $valuation = $this->pmgValuationService->calculate(
                    $transaction,
                    $referenceDate,
                    $position['movements']
                );
                if ($valuation <= 0) {
                    continue;
                }

                $pmgMonthAum = $pmgMonthAum->plus(FinancialDecimal::of($valuation));
                $activePmgClientIds[(int) $transaction->user_id] = true;
            }

            $fcpValue = $fcpMonthAum->toScale(2, RoundingMode::HALF_UP)->toFloat();
            $pmgValue = $pmgMonthAum->toScale(2, RoundingMode::HALF_UP)->toFloat();
            $fcpAum[] = $fcpValue;
            $pmgAum[] = $pmgValue;
            $totalAum[] = round($fcpValue + $pmgValue, 2);
            $fcpClients[] = count($activeFcpClientIds);
            $pmgClients[] = count($activePmgClientIds);
            $uniqueClients[] = count($activeFcpClientIds + $activePmgClientIds);
        }

        $lastIndex = max(0, count($totalAum) - 1);
        $comparisonIndex = max(0, $lastIndex - 12);
        $aumChange12Months = ($totalAum[$comparisonIndex] ?? 0) > 0
            ? (($totalAum[$lastIndex] - $totalAum[$comparisonIndex]) / $totalAum[$comparisonIndex]) * 100
            : null;

        return [
            'labels' => $labels,
            'months' => $references->keys()->values()->all(),
            'aum' => [
                'fcp' => $fcpAum,
                'pmg' => $pmgAum,
                'total' => $totalAum,
            ],
            'flows' => $flows,
            'clients' => [
                'fcp' => $fcpClients,
                'pmg' => $pmgClients,
                'unique' => $uniqueClients,
            ],
            'summary' => [
                'start_date' => $earliestDate->toDateString(),
                'months_count' => count($labels),
                'current_aum' => $totalAum[$lastIndex] ?? 0,
                'aum_change_12_months' => $aumChange12Months === null ? null : round($aumChange12Months, 2),
                'gross_subscriptions' => round(array_sum($flows['fcp_subscriptions']) + array_sum($flows['pmg_subscriptions']), 2),
                'capital_outflows' => round(abs(array_sum($flows['capital_outflows'])), 2),
                'net_collection' => round(array_sum($flows['net']), 2),
            ],
        ];
    }

    private function monthlyFlows(
        Collection $references,
        Collection $pmgPositions,
        Collection $pmgMovements,
        Collection $fcpMovements
    ): array {
        $keys = $references->keys();
        $fcpSubscriptions = array_fill_keys($keys->all(), 0.0);
        $pmgSubscriptions = array_fill_keys($keys->all(), 0.0);
        $fcpSubscriptionCounts = array_fill_keys($keys->all(), 0);
        $pmgSubscriptionCounts = array_fill_keys($keys->all(), 0);
        $capitalOutflows = array_fill_keys($keys->all(), 0.0);

        foreach ($pmgPositions as $position) {
            $monthKey = $position['start_date']->format('Y-m');
            if (array_key_exists($monthKey, $pmgSubscriptions)) {
                $pmgSubscriptions[$monthKey] += (float) $position['original_principal'];
                $pmgSubscriptionCounts[$monthKey]++;
            }
        }

        foreach ($fcpMovements as $movement) {
            $monthKey = Carbon::parse($movement->date_operation)->format('Y-m');
            if (!array_key_exists($monthKey, $fcpSubscriptions)) {
                continue;
            }

            if (in_array($movement->type, self::FCP_SUBSCRIPTION_TYPES, true)) {
                $fcpSubscriptions[$monthKey] += (float) $movement->amount_xaf + (float) ($movement->fees ?? 0);
                $fcpSubscriptionCounts[$monthKey]++;
            } elseif (in_array($movement->type, self::FCP_REDEMPTION_TYPES, true)) {
                $capitalOutflows[$monthKey] += abs((float) $movement->amount_xaf);
            }
        }

        foreach ($pmgMovements->flatten(1)->unique('id') as $movement) {
            if (!in_array($movement->type, self::PMG_CAPITAL_OUTFLOW_TYPES, true)) {
                continue;
            }
            $monthKey = Carbon::parse($movement->date_operation)->format('Y-m');
            if (array_key_exists($monthKey, $capitalOutflows)) {
                $capitalOutflows[$monthKey] += abs((float) $movement->amount);
            }
        }

        $net = [];
        foreach ($keys as $monthKey) {
            $net[$monthKey] = round(
                $fcpSubscriptions[$monthKey] + $pmgSubscriptions[$monthKey] - $capitalOutflows[$monthKey],
                2
            );
            $fcpSubscriptions[$monthKey] = round($fcpSubscriptions[$monthKey], 2);
            $pmgSubscriptions[$monthKey] = round($pmgSubscriptions[$monthKey], 2);
            $capitalOutflows[$monthKey] = round($capitalOutflows[$monthKey], 2);
        }

        return [
            'fcp_subscriptions' => array_values($fcpSubscriptions),
            'pmg_subscriptions' => array_values($pmgSubscriptions),
            'fcp_subscription_counts' => array_values($fcpSubscriptionCounts),
            'pmg_subscription_counts' => array_values($pmgSubscriptionCounts),
            'capital_outflows' => array_map(fn ($value) => -$value, array_values($capitalOutflows)),
            'net' => array_values($net),
        ];
    }

    private function earliestDate(Collection $pmgPositions, Collection $fcpMovements, Carbon $asOf): Carbon
    {
        $dates = $pmgPositions->pluck('start_date')
            ->filter()
            ->map(fn ($date) => $date->copy());
        if ($fcpMovements->isNotEmpty()) {
            $dates->push(Carbon::parse($fcpMovements->min('date_operation')));
        }

        return ($dates->sort()->first() ?: $asOf->copy())->startOfMonth();
    }

    private function monthlyReferences(Carbon $start, Carbon $asOf): Collection
    {
        $references = collect();
        $cursor = $start->copy()->startOfMonth();
        $currentMonth = $asOf->copy()->startOfMonth();

        while ($cursor->lte($currentMonth)) {
            $references->put(
                $cursor->format('Y-m'),
                $cursor->isSameMonth($asOf) ? $asOf->copy() : $cursor->copy()->endOfMonth()
            );
            $cursor->addMonth();
        }

        return $references;
    }

    private function transactionStartDate($transaction): ?Carbon
    {
        $value = $transaction->date_validation ?: $transaction->created_at;

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    private function dataFingerprint(): string
    {
        $tables = ['users', 'transactions', 'transaction_supplementaires', 'financial_movements', 'fcp_movements', 'asset_values'];
        $parts = [];

        foreach ($tables as $table) {
            $parts[] = $table . ':' . DB::table($table)->count() . ':' . (DB::table($table)->max('updated_at') ?? '');
        }

        return sha1(implode('|', $parts));
    }
}
