@extends('front-end/app/app-home-asset', ['Dashboard ', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@section('content')
    @php
        $metrics = $dashboardMetrics;
        $history = $dashboardHistory;
        $performanceIsNegative = (float) $metrics['active_performance'] < 0;
    @endphp
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                <div
                    class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div>
                        <h3 class="h3">TABLEAU DE BORD</h3>
                        <p class="text-sm opacity-70">Bienvenue sur votre espace Asset Manager</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden md:block text-right mr-4 border-r border-n30 pr-4">
                            <p class="font-medium">{{ $metrics['reference_date']->format('d/m/Y') }}</p>
                            <span class="text-xs opacity-50">Date de valorisation</span>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('releve-client') }}"
                                class="btn bg-primary text-white rounded-lg px-4 py-2 hover:bg-primary/90 duration-300 flex items-center gap-2 text-sm shadow-sm">
                                <i class="las la-file-alt"></i> Générer Relevés
                            </a>
                            <a href="{{ route('customer') }}"
                                class="btn bg-secondary1 text-white rounded-lg px-4 py-2 hover:bg-secondary1/90 duration-300 flex items-center gap-2 text-sm shadow-sm">
                                <i class="las la-user-cog"></i> Gérer Clients
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <div class="box h-full min-h-[152px] rounded-lg bg-primary p-5 text-white shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-white/80">Investissement actif</p>
                            <p class="mt-3 text-xl font-bold text-white">
                                {{ number_format((float) $metrics['active_investment'], 2, ',', ' ') }} XAF
                            </p>
                        </div>
                        <i class="las la-wallet text-2xl text-white/70" aria-hidden="true"></i>
                    </div>
                    <p class="mt-5 text-xs text-white/80">
                        FCP {{ number_format((float) $metrics['fcp_active_investment'], 2, ',', ' ') }}
                        · PMG {{ number_format((float) $metrics['pmg_active_investment'], 2, ',', ' ') }}
                    </p>
                </div>
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <div class="box h-full min-h-[152px] rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase opacity-70">Investissement historique</p>
                            <p class="mt-3 text-xl font-bold text-n900 dark:text-white">
                                {{ number_format((float) $metrics['historical_investment'], 2, ',', ' ') }} XAF
                            </p>
                        </div>
                        <i class="las la-history text-2xl text-primary" aria-hidden="true"></i>
                    </div>
                    <p class="mt-5 text-xs opacity-70">
                        Sorti, racheté ou échu : {{ number_format((float) $metrics['inactive_investment'], 2, ',', ' ') }}
                        XAF
                    </p>
                </div>
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <div class="box h-full min-h-[152px] rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase opacity-70">Valorisation active</p>
                            <p class="mt-3 text-xl font-bold text-n900 dark:text-white">
                                {{ number_format((float) $metrics['active_valuation'], 2, ',', ' ') }} XAF
                            </p>
                        </div>
                        <i class="las la-chart-line text-2xl text-green-600" aria-hidden="true"></i>
                    </div>
                    <p class="mt-5 text-xs opacity-70">
                        FCP {{ number_format((float) $metrics['fcp_aum'], 2, ',', ' ') }}
                        · PMG {{ number_format((float) $metrics['pmg_aum'], 2, ',', ' ') }}
                    </p>
                </div>
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <div class="box h-full min-h-[152px] rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase opacity-70">Performance active</p>
                            <p
                                class="mt-3 text-xl font-bold {{ $performanceIsNegative ? 'text-red-500' : 'text-green-600' }}">
                                {{ $performanceIsNegative ? '' : '+' }}{{ number_format((float) $metrics['active_performance'], 2, ',', ' ') }}
                                XAF
                            </p>
                        </div>
                        <i class="las {{ $performanceIsNegative ? 'la-arrow-down' : 'la-arrow-up' }} text-2xl {{ $performanceIsNegative ? 'text-red-500' : 'text-green-600' }}"
                            aria-hidden="true"></i>
                    </div>
                    <p class="mt-5 text-xs opacity-70">Valorisation moins capital actif au
                        {{ $metrics['reference_date']->format('d/m/Y') }}</p>
                </div>
            </div>

            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <a href="{{ route('customer', ['category' => '1', 'status' => 'active']) }}" class="block h-full">
                    <div
                        class="box h-full min-h-[132px] rounded-lg border border-n30 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:bg-bg3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase text-secondary1">Clients FCP actifs</p>
                                <p class="mt-3 text-3xl font-bold text-secondary1">
                                    {{ $metrics['active_fcp_clients_count'] }}</p>
                            </div>
                            <i class="las la-chart-pie text-2xl text-secondary1" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-xs opacity-60">Ouvrir la liste FCP</p>
                    </div>
                </a>
            </div>

            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <a href="{{ route('customer', ['category' => '2', 'status' => 'active']) }}" class="block h-full">
                    <div
                        class="box h-full min-h-[132px] rounded-lg border border-n30 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:bg-bg3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase text-blue-600">Clients PMG actifs</p>
                                <p class="mt-3 text-3xl font-bold text-blue-600">{{ $metrics['active_pmg_clients_count'] }}
                                </p>
                            </div>
                            <i class="las la-user-check text-2xl text-blue-600" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-xs opacity-60">{{ $metrics['active_clients_count'] }} client(s) actif(s)
                            unique(s)</p>
                    </div>
                </a>
            </div>

            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <a href="{{ route('customer', ['filter' => 'expiring_pmg']) }}" class="block h-full">
                    <div
                        class="box h-full min-h-[132px] rounded-lg border border-n30 border-l-4 border-l-red-500 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:bg-bg3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase text-red-500">Échéances PMG</p>
                                <p class="mt-3 text-3xl font-bold text-red-500">{{ $metrics['expiring_pmg_count'] }}</p>
                            </div>
                            <i class="las la-hourglass-end text-2xl text-red-500" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-xs opacity-60">Échéances du mois</p>
                    </div>
                </a>
            </div>

            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <a href="{{ route('customer', ['filter' => 'anniversaries']) }}" class="block h-full">
                    <div
                        class="box h-full min-h-[132px] rounded-lg border border-n30 border-l-4 border-l-primary bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:bg-bg3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase text-primary">Anniversaires PMG</p>
                                <p class="mt-3 text-3xl font-bold text-primary">{{ $metrics['anniversary_pmg_count'] }}</p>
                            </div>
                            <i class="las la-birthday-cake text-2xl text-primary" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-xs opacity-60">Anniversaires du mois</p>
                    </div>
                </a>
            </div>

            <div class="col-span-12">
                <div
                    class="flex flex-col gap-4 border-y border-n30 bg-white px-5 py-4 dark:bg-bg3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <i class="las la-database mt-0.5 text-xl text-primary" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold">Traçabilité des indicateurs</p>
                            <p class="mt-1 text-xs opacity-70">
                                {{ $metrics['historical_placements_count'] }} souscription(s),
                                {{ $metrics['active_positions_count'] }} position(s) active(s),
                                {{ $metrics['fallback_records_count'] }} ancienne(s) ligne(s) reconstituée(s).
                                @if ($metrics['missing_fcp_vl_count'] || $metrics['missing_pmg_expiry_count'])
                                    Anomalies : {{ $metrics['missing_fcp_vl_count'] }} VL FCP manquante(s),
                                    {{ $metrics['missing_pmg_expiry_count'] }} échéance(s) PMG manquante(s).
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 lg:max-w-[48%]">
                        <i class="las la-bell mt-0.5 text-xl text-blue-600" aria-hidden="true"></i>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">Alertes produits PMG</p>
                            <p class="mt-1 break-words text-xs opacity-70">
                                {{ count($pmgAlertConfiguration['emails']) ? implode(', ', $pmgAlertConfiguration['emails']) : 'Aucun destinataire valide' }}
                            </p>
                            <p class="mt-1 text-xs text-blue-600">Configuration : {{ $pmgAlertConfiguration['source'] }}
                            </p>
                        </div>
                        @if ($pmgAlertConfiguration['managed_in_voyager'])
                            <a href="{{ $pmgAlertConfiguration['settings_url'] }}"
                                class="btn shrink-0 rounded-lg border border-n30 p-2"
                                title="Configurer les destinataires PMG" aria-label="Configurer les destinataires PMG">
                                <i class="las la-cog text-lg" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div
                class="col-span-12 mt-2 flex flex-col gap-4 border-b border-n30 pb-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h4 class="h4">Pilotage historique</h4>
                    <p class="mt-1 text-sm opacity-60" id="history-date-subtitle">
                        Du {{ \Carbon\Carbon::parse($history['summary']['start_date'])->format('d/m/Y') }}
                        au {{ $metrics['reference_date']->format('d/m/Y') }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex w-fit rounded-lg border border-n30 bg-white p-1 dark:bg-bg3" role="group"
                        aria-label="Période des graphiques historiques">
                        <button type="button" data-history-range="12" aria-pressed="false"
                            class="history-range-button rounded-md px-3 py-2 text-xs font-semibold transition-colors">
                            12 mois
                        </button>
                        <button type="button" data-history-range="all" aria-pressed="true"
                            class="history-range-button rounded-md bg-secondary1 px-3 py-2 text-xs font-semibold text-white transition-colors">
                            Depuis le début
                        </button>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 rounded-lg border border-n30 bg-white p-1.5 text-xs dark:bg-bg3 shadow-sm">
                        <div class="flex items-center gap-1.5 px-1">
                            <span class="font-semibold text-n500 dark:text-n200">Du</span>
                            <input type="month" id="history-start-month" title="Mois de début"
                                aria-label="Mois de début"
                                class="rounded border border-n30 bg-n10 px-2 py-1 text-xs font-medium text-n700 dark:bg-bg2 dark:text-n100 focus:outline-none focus:ring-1 focus:ring-primary" />
                        </div>
                        <span class="text-n300 dark:text-n400 font-bold">-</span>
                        <div class="flex items-center gap-1.5 px-1">
                            <span class="font-semibold text-n500 dark:text-n200">au</span>
                            <input type="month" id="history-end-month" title="Mois de fin" aria-label="Mois de fin"
                                class="rounded border border-n30 bg-n10 px-2 py-1 text-xs font-medium text-n700 dark:bg-bg2 dark:text-n100 focus:outline-none focus:ring-1 focus:ring-primary" />
                        </div>
                        <button type="button" id="apply-custom-month-filter"
                            class="rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-white transition-all hover:bg-primary/90 shadow-sm flex items-center gap-1">
                            <i class="las la-filter"></i> Filtrer
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-8">
                <div class="box h-full rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-n30 pb-4">
                        <div>
                            <h4 class="h4 flex items-center gap-2">
                                <i class="las la-layer-group text-primary" aria-hidden="true"></i>
                                Encours sous gestion
                            </h4>
                            <p class="mt-1 text-xs opacity-60">Valorisation mensuelle FCP et PMG</p>
                        </div>
                        <div class="text-right">
                            <p id="history-aum-val" class="text-lg font-bold">
                                {{ number_format((float) $history['summary']['current_aum'], 2, ',', ' ') }} XAF</p>
                            <p id="history-aum-change"
                                class="mt-1 text-xs {{ ($history['summary']['aum_change_12_months'] ?? 0) < 0 ? 'text-red-500' : 'text-green-600' }}">
                                @if (($history['summary']['aum_change_12_months'] ?? null) !== null)
                                    {{ $history['summary']['aum_change_12_months'] < 0 ? '' : '+' }}{{ number_format((float) $history['summary']['aum_change_12_months'], 2, ',', ' ') }}
                                    % sur la période
                                @endif
                            </p>
                        </div>
                    </div>
                    <div id="aum-history-chart" class="h-[360px] w-full"></div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-4">
                <div class="box h-full rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="mb-5 border-b border-n30 pb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="h4 flex items-center gap-2">
                                    <i class="las la-users text-blue-600" aria-hidden="true"></i>
                                    Clients actifs
                                </h4>
                                <p class="mt-1 text-xs opacity-60">Évolution par classe actifs</p>
                            </div>
                            <p id="history-active-clients-count" class="text-2xl font-bold text-blue-600">
                                {{ $history['clients']['unique'][count($history['clients']['unique']) - 1] ?? $metrics['active_clients_count'] }}
                            </p>
                        </div>
                    </div>
                    <div id="active-clients-history-chart" class="h-[360px] w-full"></div>
                </div>
            </div>

            <div class="col-span-12">
                <div class="box rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-4 border-b border-n30 pb-4">
                        <div>
                            <h4 class="h4 flex items-center gap-2">
                                <i class="las la-exchange-alt text-green-600" aria-hidden="true"></i>
                                Collecte et sorties de capital
                            </h4>
                            <p class="mt-1 text-xs opacity-60">Flux mensuels enregistrés</p>
                        </div>
                        <div class="flex flex-wrap gap-5 text-right text-xs">
                            <div>
                                <p class="opacity-60">Souscriptions cumulées</p>
                                <p id="history-gross-subscriptions" class="mt-1 font-bold text-green-600">
                                    {{ number_format((float) $history['summary']['gross_subscriptions'], 2, ',', ' ') }}
                                    XAF</p>
                            </div>
                            <div>
                                <p class="opacity-60">Sorties de capital</p>
                                <p id="history-capital-outflows" class="mt-1 font-bold text-red-500">
                                    {{ number_format((float) $history['summary']['capital_outflows'], 2, ',', ' ') }} XAF
                                </p>
                            </div>
                            <div>
                                <p class="opacity-60">Collecte nette</p>
                                <p id="history-net-collection" class="mt-1 font-bold text-secondary1">
                                    {{ number_format((float) $history['summary']['net_collection'], 2, ',', ' ') }} XAF</p>
                            </div>
                        </div>
                    </div>
                    <div id="cash-flows-history-chart" class="h-[370px] w-full"></div>
                </div>
            </div>

            <!-- Graphiques évolution du nombre de souscriptions (PMG & FCP) -->
            <div class="col-span-12 lg:col-span-6">
                <div class="box h-full rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="mb-5 flex items-start justify-between gap-3 border-b border-n30 pb-4">
                        <div>
                            <h4 class="h4 flex items-center gap-2">
                                <i class="las la-chart-bar text-primary" aria-hidden="true"></i>
                                Souscriptions PMG
                            </h4>
                            <p class="mt-1 text-xs opacity-60">Évolution du nombre de souscriptions PMG par mois</p>
                        </div>
                        <div class="text-right">
                            <p id="history-pmg-sub-count-total" class="text-xl font-bold text-primary">
                                {{ array_sum($history['flows']['pmg_subscription_counts'] ?? []) }}
                            </p>
                            <p class="text-xs opacity-50">souscriptions</p>
                        </div>
                    </div>
                    <div id="pmg-subscriptions-chart" class="h-[320px] w-full"></div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6">
                <div class="box h-full rounded-lg border border-n30 bg-white p-5 shadow-sm dark:bg-bg3">
                    <div class="mb-5 flex items-start justify-between gap-3 border-b border-n30 pb-4">
                        <div>
                            <h4 class="h4 flex items-center gap-2">
                                <i class="las la-chart-bar text-amber-500" aria-hidden="true"></i>
                                Souscriptions FCP
                            </h4>
                            <p class="mt-1 text-xs opacity-60">Évolution du nombre de souscriptions FCP par mois</p>
                        </div>
                        <div class="text-right">
                            <p id="history-fcp-sub-count-total" class="text-xl font-bold text-amber-500">
                                {{ array_sum($history['flows']['fcp_subscription_counts'] ?? []) }}
                            </p>
                            <p class="text-xs opacity-50">souscriptions</p>
                        </div>
                    </div>
                    <div id="fcp-subscriptions-chart" class="h-[320px] w-full"></div>
                </div>
            </div>

            <!-- Graph Section -->
            <div class="col-span-12">
                <div class="grid grid-cols-12 gap-4 xxl:gap-6">
                    <!-- VL Evolution Chart -->
                    <div class="col-span-12 lg:col-span-8">
                        <div class="box h-full rounded-lg border border-n30 bg-white p-6 shadow-sm dark:bg-bg3">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bb-dashed pb-4">
                                <h4 class="h4 flex items-center gap-2">
                                    <i class="las la-chart-line text-primary highlight-text"></i> Évolution des Valeurs
                                    Liquidatives (FCP)
                                </h4>
                            </div>
                            <div id="fcp-vls-chart" class="w-full h-80"></div>
                        </div>
                    </div>

                    <!-- Portfolio Distribution Chart -->
                    <div class="col-span-12 lg:col-span-4">
                        <div class="box h-full rounded-lg border border-n30 bg-white p-6 shadow-sm dark:bg-bg3">
                            <div class="mb-6 bb-dashed pb-4">
                                <h4 class="h4 flex items-center gap-2">
                                    <i class="las la-chart-pie text-secondary1"></i> Répartition de la valorisation active
                                </h4>
                            </div>
                            <div id="portfolio-distribution-chart" class="w-full h-80 flex items-center justify-center">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Graphe et statistiques -->
        <div class="content-separator" style="height:30px">

        </div>

        </div>
        </div>
        </div>
        </div>
    </main>
@endsection

@section('script_front_end')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const historyData = @json($history);
            const compactNumber = new Intl.NumberFormat('fr-FR', {
                notation: 'compact',
                maximumFractionDigits: 1
            });
            const moneyNumber = new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
            const formatCompactXaf = value => `${compactNumber.format(Number(value || 0))} XAF`;
            const formatXaf = value => `${moneyNumber.format(Number(value || 0))} XAF`;
            const monthsList = historyData.months || [];
            let historyRange = 'all';
            let customStartIndex = 0;
            let customEndIndex = Math.max(0, (historyData.labels || []).length - 1);

            const getHistoryRangeIndices = () => {
                const total = (historyData.labels || []).length;
                if (!total) return {
                    start: 0,
                    end: 0
                };
                if (historyRange === '12') {
                    return {
                        start: Math.max(0, total - 12),
                        end: total - 1
                    };
                }
                if (historyRange === 'custom') {
                    return {
                        start: customStartIndex,
                        end: customEndIndex
                    };
                }
                return {
                    start: 0,
                    end: total - 1
                };
            };

            const sliceHistory = values => {
                if (!values) return [];
                const {
                    start,
                    end
                } = getHistoryRangeIndices();
                return values.slice(start, end + 1);
            };

            const historyCategories = () => sliceHistory(historyData.labels);

            const startMonthInput = document.querySelector('#history-start-month');
            const endMonthInput = document.querySelector('#history-end-month');
            const applyCustomFilterBtn = document.querySelector('#apply-custom-month-filter');

            if (monthsList.length > 0) {
                if (startMonthInput) {
                    startMonthInput.min = monthsList[0];
                    startMonthInput.max = monthsList[monthsList.length - 1];
                    startMonthInput.value = monthsList[0];
                }
                if (endMonthInput) {
                    endMonthInput.min = monthsList[0];
                    endMonthInput.max = monthsList[monthsList.length - 1];
                    endMonthInput.value = monthsList[monthsList.length - 1];
                }
            }

            const syncMonthInputsFromRange = () => {
                if (!monthsList.length) return;
                const {
                    start,
                    end
                } = getHistoryRangeIndices();
                if (startMonthInput && monthsList[start]) {
                    startMonthInput.value = monthsList[start];
                }
                if (endMonthInput && monthsList[end]) {
                    endMonthInput.value = monthsList[end];
                }
            };
            const commonHistoryChart = {
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                animations: {
                    enabled: false
                },
                fontFamily: 'inherit'
            };
            const commonXAxis = () => ({
                categories: historyCategories(),
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    rotate: 0,
                    hideOverlappingLabels: true,
                    trim: true,
                    style: {
                        fontSize: '11px'
                    }
                }
            });

            const aumElement = document.querySelector('#aum-history-chart');
            const aumSeries = () => [{
                    name: 'FCP',
                    data: sliceHistory(historyData.aum.fcp)
                },
                {
                    name: 'PMG',
                    data: sliceHistory(historyData.aum.pmg)
                }
            ];
            let aumHistoryChart = null;
            if (aumElement && historyData.labels.length) {
                aumHistoryChart = new ApexCharts(aumElement, {
                    series: aumSeries(),
                    chart: {
                        ...commonHistoryChart,
                        type: 'area',
                        stacked: true,
                        height: 360
                    },
                    colors: ['#D9A400', '#0F766E'],
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    fill: {
                        type: 'solid',
                        opacity: 0.24
                    },
                    dataLabels: {
                        enabled: false
                    },
                    markers: {
                        size: 0,
                        hover: {
                            size: 4
                        }
                    },
                    xaxis: commonXAxis(),
                    yaxis: {
                        labels: {
                            formatter: formatCompactXaf
                        }
                    },
                    grid: {
                        borderColor: '#e7e7e7',
                        strokeDashArray: 4
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left'
                    },
                    tooltip: {
                        shared: true,
                        y: {
                            formatter: formatXaf
                        }
                    }
                });
                aumHistoryChart.render();
            }

            const clientsElement = document.querySelector('#active-clients-history-chart');
            const clientsSeries = () => [{
                    name: 'FCP actifs',
                    data: sliceHistory(historyData.clients.fcp)
                },
                {
                    name: 'PMG actifs',
                    data: sliceHistory(historyData.clients.pmg)
                },
                {
                    name: 'Total ',
                    data: sliceHistory(historyData.clients.unique)
                }
            ];
            let activeClientsHistoryChart = null;
            if (clientsElement && historyData.labels.length) {
                activeClientsHistoryChart = new ApexCharts(clientsElement, {
                    series: clientsSeries(),
                    chart: {
                        ...commonHistoryChart,
                        type: 'line',
                        height: 360
                    },
                    colors: ['#D9A400', '#2563EB', '#111827'],
                    stroke: {
                        curve: 'smooth',
                        width: [2, 2, 3],
                        dashArray: [4, 4, 0]
                    },
                    dataLabels: {
                        enabled: false
                    },
                    markers: {
                        size: [0, 0, 2],
                        hover: {
                            size: 5
                        }
                    },
                    xaxis: commonXAxis(),
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            formatter: value => Math.round(value).toString()
                        }
                    },
                    grid: {
                        borderColor: '#e7e7e7',
                        strokeDashArray: 4
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left'
                    },
                    tooltip: {
                        shared: true,
                        y: {
                            formatter: value => `${Math.round(value)} client(s)`
                        }
                    }
                });
                activeClientsHistoryChart.render();
            }

            const flowsElement = document.querySelector('#cash-flows-history-chart');
            const flowsSeries = () => [{
                    name: 'Souscriptions FCP',
                    type: 'column',
                    data: sliceHistory(historyData.flows.fcp_subscriptions)
                },
                {
                    name: 'Souscriptions PMG',
                    type: 'column',
                    data: sliceHistory(historyData.flows.pmg_subscriptions)
                },
                {
                    name: 'Sorties de capital',
                    type: 'column',
                    data: sliceHistory(historyData.flows.capital_outflows)
                },
                {
                    name: 'Collecte nette',
                    type: 'line',
                    data: sliceHistory(historyData.flows.net)
                }
            ];
            let cashFlowsHistoryChart = null;
            if (flowsElement && historyData.labels.length) {
                cashFlowsHistoryChart = new ApexCharts(flowsElement, {
                    series: flowsSeries(),
                    chart: {
                        ...commonHistoryChart,
                        type: 'line',
                        height: 370
                    },
                    colors: ['#D9A400', '#0F766E', '#DC2626', '#2563EB'],
                    stroke: {
                        curve: 'smooth',
                        width: [0, 0, 0, 3]
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '58%',
                            borderRadius: 2
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    markers: {
                        size: [0, 0, 0, 2],
                        hover: {
                            size: 5
                        }
                    },
                    xaxis: commonXAxis(),
                    yaxis: {
                        labels: {
                            formatter: formatCompactXaf
                        }
                    },
                    grid: {
                        borderColor: '#e7e7e7',
                        strokeDashArray: 4
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left'
                    },
                    tooltip: {
                        shared: true,
                        y: {
                            formatter: formatXaf
                        }
                    }
                });
                cashFlowsHistoryChart.render();
            }

            // Helpers to guarantee matching data length with historyData.labels
            const getPmgSubscriptionCounts = () => {
                if (historyData.flows && Array.isArray(historyData.flows.pmg_subscription_counts) && historyData.flows.pmg_subscription_counts.length) {
                    return historyData.flows.pmg_subscription_counts;
                }
                if (historyData.flows && Array.isArray(historyData.flows.pmg_subscriptions)) {
                    return historyData.flows.pmg_subscriptions.map(v => Number(v) > 0 ? 1 : 0);
                }
                return (historyData.labels || []).map(() => 0);
            };

            const getFcpSubscriptionCounts = () => {
                if (historyData.flows && Array.isArray(historyData.flows.fcp_subscription_counts) && historyData.flows.fcp_subscription_counts.length) {
                    return historyData.flows.fcp_subscription_counts;
                }
                if (historyData.flows && Array.isArray(historyData.flows.fcp_subscriptions)) {
                    return historyData.flows.fcp_subscriptions.map(v => Number(v) > 0 ? 1 : 0);
                }
                return (historyData.labels || []).map(() => 0);
            };

            // PMG Subscriptions Chart (Counts)
            const pmgSubSeries = () => [{
                name: 'Souscriptions PMG',
                type: 'column',
                data: sliceHistory(getPmgSubscriptionCounts())
            }];
            const pmgSubElement = document.querySelector('#pmg-subscriptions-chart');
            let pmgSubscriptionsChart = null;
            if (pmgSubElement && (historyData.labels || []).length) {
                try {
                    pmgSubscriptionsChart = new ApexCharts(pmgSubElement, {
                        series: pmgSubSeries(),
                        chart: {
                            ...commonHistoryChart,
                            type: 'line',
                            height: 320
                        },
                        colors: ['#0F766E'],
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '50%',
                                borderRadius: 4
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: commonXAxis(),
                        yaxis: {
                            min: 0,
                            forceNiceScale: true,
                            labels: {
                                formatter: val => Math.round(val || 0).toString()
                            }
                        },
                        grid: {
                            borderColor: '#e7e7e7',
                            strokeDashArray: 4
                        },
                        tooltip: {
                            shared: true,
                            y: {
                                formatter: (val, opts) => {
                                    const v = (val !== undefined && val !== null) ? Math.round(Number(val)) : 0;
                                    const idx = (opts && typeof opts.dataPointIndex === 'number') ? opts.dataPointIndex : -1;
                                    if (idx >= 0 && historyData.flows) {
                                        const { start } = getHistoryRangeIndices();
                                        const realIdx = (start || 0) + idx;
                                        const amount = (historyData.flows.pmg_subscriptions || [])[realIdx] || 0;
                                        if (amount > 0) {
                                            return `${v} souscription(s) (${formatXaf(amount)})`;
                                        }
                                    }
                                    return `${v} souscription(s)`;
                                }
                            }
                        }
                    });
                    pmgSubscriptionsChart.render();
                } catch (e) {
                    console.error('Error creating PMG subscriptions chart:', e);
                }
            }

            // FCP Subscriptions Chart (Counts)
            const fcpSubSeries = () => [{
                name: 'Souscriptions FCP',
                type: 'column',
                data: sliceHistory(getFcpSubscriptionCounts())
            }];
            const fcpSubElement = document.querySelector('#fcp-subscriptions-chart');
            let fcpSubscriptionsChart = null;
            if (fcpSubElement && (historyData.labels || []).length) {
                try {
                    fcpSubscriptionsChart = new ApexCharts(fcpSubElement, {
                        series: fcpSubSeries(),
                        chart: {
                            ...commonHistoryChart,
                            type: 'line',
                            height: 320
                        },
                        colors: ['#D9A400'],
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '50%',
                                borderRadius: 4
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: commonXAxis(),
                        yaxis: {
                            min: 0,
                            forceNiceScale: true,
                            labels: {
                                formatter: val => Math.round(val || 0).toString()
                            }
                        },
                        grid: {
                            borderColor: '#e7e7e7',
                            strokeDashArray: 4
                        },
                        tooltip: {
                            shared: true,
                            y: {
                                formatter: (val, opts) => {
                                    const v = (val !== undefined && val !== null) ? Math.round(Number(val)) : 0;
                                    const idx = (opts && typeof opts.dataPointIndex === 'number') ? opts.dataPointIndex : -1;
                                    if (idx >= 0 && historyData.flows) {
                                        const { start } = getHistoryRangeIndices();
                                        const realIdx = (start || 0) + idx;
                                        const amount = (historyData.flows.fcp_subscriptions || [])[realIdx] || 0;
                                        if (amount > 0) {
                                            return `${v} souscription(s) (${formatXaf(amount)})`;
                                        }
                                    }
                                    return `${v} souscription(s)`;
                                }
                            }
                        }
                    });
                    fcpSubscriptionsChart.render();
                } catch (e) {
                    console.error('Error creating FCP subscriptions chart:', e);
                }
            }

            const updateChartData = () => {
                const categories = historyCategories();
                const {
                    start,
                    end
                } = getHistoryRangeIndices();
                const subtitleElement = document.querySelector('#history-date-subtitle');
                if (subtitleElement && (historyData.labels || []).length) {
                    const startLabel = historyData.labels[start] || categories[0];
                    const endLabel = historyData.labels[end] || categories[categories.length - 1];
                    if (historyRange === '12') {
                        subtitleElement.textContent = `12 derniers mois (du ${startLabel} au ${endLabel})`;
                    } else if (historyRange === 'custom') {
                        subtitleElement.textContent = `Période personnalisée (du ${startLabel} au ${endLabel})`;
                    } else {
                        subtitleElement.textContent =
                            `Du {{ \Carbon\Carbon::parse($history['summary']['start_date'])->format('d/m/Y') }} au {{ $metrics['reference_date']->format('d/m/Y') }}`;
                    }
                }

                // Update Header Card Summaries dynamically
                const aumValEl = document.querySelector('#history-aum-val');
                const aumChangeEl = document.querySelector('#history-aum-change');
                const clientsCountEl = document.querySelector('#history-active-clients-count');
                const grossSubEl = document.querySelector('#history-gross-subscriptions');
                const outflowsEl = document.querySelector('#history-capital-outflows');
                const netCollEl = document.querySelector('#history-net-collection');
                const pmgSubTotalEl = document.querySelector('#history-pmg-sub-count-total');
                const fcpSubTotalEl = document.querySelector('#history-fcp-sub-count-total');

                if (historyData.aum && historyData.aum.total && historyData.aum.total.length) {
                    const endAum = historyData.aum.total[end] || 0;
                    const startAum = historyData.aum.total[start] || 0;
                    if (aumValEl) {
                        aumValEl.textContent = formatXaf(endAum);
                    }
                    if (aumChangeEl) {
                        if (startAum > 0) {
                            const pct = ((endAum - startAum) / startAum) * 100;
                            const periodText = historyRange === '12' ? 'sur 12 mois' : (historyRange ===
                                'custom' ? 'sur la période' : 'depuis le début');
                            aumChangeEl.textContent =
                                `${pct >= 0 ? '+' : ''}${moneyNumber.format(pct)} % ${periodText}`;
                            aumChangeEl.className =
                                `mt-1 text-xs ${pct < 0 ? 'text-red-500' : 'text-green-600'}`;
                        } else {
                            aumChangeEl.textContent = '';
                        }
                    }
                }

                if (historyData.clients && historyData.clients.unique && historyData.clients.unique.length) {
                    if (clientsCountEl) {
                        clientsCountEl.textContent = historyData.clients.unique[end] || 0;
                    }
                }

                if (historyData.flows) {
                    const sumArray = arr => (arr || []).slice(start, end + 1).reduce((acc, v) => acc + Number(
                        v || 0), 0);
                    const grossSubs = sumArray(historyData.flows.fcp_subscriptions) + sumArray(historyData.flows
                        .pmg_subscriptions);
                    const capitalOutflows = Math.abs(sumArray(historyData.flows.capital_outflows));
                    const netCollection = sumArray(historyData.flows.net);

                    if (grossSubEl) grossSubEl.textContent = formatXaf(grossSubs);
                    if (outflowsEl) outflowsEl.textContent = formatXaf(capitalOutflows);
                    if (netCollEl) netCollEl.textContent = formatXaf(netCollection);

                    if (pmgSubTotalEl) pmgSubTotalEl.textContent = sumArray(getPmgSubscriptionCounts());
                    if (fcpSubTotalEl) fcpSubTotalEl.textContent = sumArray(getFcpSubscriptionCounts());
                }

                if (aumHistoryChart) {
                    aumHistoryChart.updateOptions({
                        xaxis: commonXAxis(),
                        series: aumSeries()
                    }, true, true);
                }
                if (activeClientsHistoryChart) {
                    activeClientsHistoryChart.updateOptions({
                        xaxis: commonXAxis(),
                        series: clientsSeries()
                    }, true, true);
                }
                if (cashFlowsHistoryChart) {
                    cashFlowsHistoryChart.updateOptions({
                        xaxis: commonXAxis(),
                        series: flowsSeries()
                    }, true, true);
                }
                if (pmgSubscriptionsChart) {
                    pmgSubscriptionsChart.updateOptions({
                        xaxis: commonXAxis(),
                        series: pmgSubSeries()
                    }, true, true);
                }
                if (fcpSubscriptionsChart) {
                    fcpSubscriptionsChart.updateOptions({
                        xaxis: commonXAxis(),
                        series: fcpSubSeries()
                    }, true, true);
                }
            };

            document.querySelectorAll('[data-history-range]').forEach(button => {
                button.addEventListener('click', function() {
                    historyRange = this.dataset.historyRange;
                    document.querySelectorAll('[data-history-range]').forEach(rangeButton => {
                        const isActive = rangeButton.dataset.historyRange === historyRange;
                        rangeButton.classList.toggle('bg-secondary1', isActive);
                        rangeButton.classList.toggle('text-white', isActive);
                        rangeButton.setAttribute('aria-pressed', isActive ? 'true' :
                            'false');
                    });

                    syncMonthInputsFromRange();
                    updateChartData();
                });
            });

            const applyCustomFilter = () => {
                if (!startMonthInput || !endMonthInput || !monthsList.length) return;
                let sVal = startMonthInput.value;
                let eVal = endMonthInput.value;

                let sIdx = monthsList.indexOf(sVal);
                let eIdx = monthsList.indexOf(eVal);

                if (sIdx === -1) sIdx = 0;
                if (eIdx === -1) eIdx = monthsList.length - 1;

                if (sIdx > eIdx) {
                    const temp = sIdx;
                    sIdx = eIdx;
                    eIdx = temp;
                    startMonthInput.value = monthsList[sIdx];
                    endMonthInput.value = monthsList[eIdx];
                }

                customStartIndex = sIdx;
                customEndIndex = eIdx;
                historyRange = 'custom';

                document.querySelectorAll('[data-history-range]').forEach(rangeButton => {
                    rangeButton.classList.remove('bg-secondary1', 'text-white');
                    rangeButton.setAttribute('aria-pressed', 'false');
                });

                updateChartData();
            };

            if (applyCustomFilterBtn) {
                applyCustomFilterBtn.addEventListener('click', applyCustomFilter);
            }
            if (startMonthInput) {
                startMonthInput.addEventListener('change', applyCustomFilter);
            }
            if (endMonthInput) {
                endMonthInput.addEventListener('change', applyCustomFilter);
            }

            const chartElement = document.querySelector("#fcp-vls-chart");
            if (chartElement) {
                try {
                    const options = {
                        series: [
                            @foreach ($fcpProducts as $product)
                                {
                                    name: "{{ $product->title }}",
                                    data: [
                                        @foreach ($product->vl_history as $vl)
                                            {{ $vl->vl }},
                                        @endforeach
                                    ]
                                },
                            @endforeach
                        ],
                        chart: {
                            height: 320,
                            type: 'line',
                            toolbar: {
                                show: false
                            },
                            zoom: {
                                enabled: false
                            },
                            dropShadow: {
                                enabled: true,
                                top: 3,
                                left: 2,
                                blur: 4,
                                opacity: 0.1,
                            }
                        },
                        colors: ['#E5C646', '#10b981', '#3b82f6'],
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            width: 3,
                            curve: 'smooth'
                        },
                        markers: {
                            size: 4,
                            strokeWidth: 0,
                            hover: {
                                size: 6
                            }
                        },
                        xaxis: {
                            categories: [
                                @if (isset($fcpProducts[0]) && $fcpProducts[0]->vl_history->count() > 0)
                                    @foreach ($fcpProducts[0]->vl_history as $vl)
                                        "{{ \Carbon\Carbon::parse($vl->date_vl)->format('d/m') }}",
                                    @endforeach
                                @endif
                            ],
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: function(val) {
                                    return (val || 0).toLocaleString() + " XAF";
                                }
                            }
                        },
                        grid: {
                            borderColor: '#e7e7e7',
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right',
                        }
                    };

                    const chart = new ApexCharts(chartElement, options);
                    chart.render();
                } catch (e) {
                    console.error('Error rendering fcp-vls-chart:', e);
                }
            }

            const distributionElement = document.querySelector("#portfolio-distribution-chart");
            if (distributionElement) {
                try {
                    const distributionOptions = {
                        series: [{{ intval($totalFcpAum) }}, {{ intval($totalPmgAum) }}],
                        chart: {
                            type: 'donut',
                            height: 320
                        },
                        labels: ['FCP', 'PMG'],
                        colors: ['#E5C646', '#10b981'],
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: '',
                                            formatter: function(w) {
                                                return "{{ number_format($globalAum, 0, ' ', ' ') }} XAF";
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return (val || 0).toLocaleString() + " XAF";
                                }
                            }
                        }
                    };

                    const distChart = new ApexCharts(distributionElement, distributionOptions);
                    distChart.render();
                } catch (e) {
                    console.error('Error rendering portfolio-distribution-chart:', e);
                }
            }
        });
    </script>
@endsection
