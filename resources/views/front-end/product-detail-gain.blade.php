@extends('front-end/app/app-home', ['title' => $product['product_name'], 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden'])

@php
    $type = App\Models\ProductsCategory::where('id', $product['type_product'])->first();
    $user_ = App\Models\User::where('id', Auth::user()->id)->first();

    $dateValeur = \Carbon\Carbon::parse($product['date_souscription']);
    $dateEcheance = $dateValeur->copy()->addMonths($product['duree']);
    $moisEcoules = $dateValeur->diffInMonths(\Carbon\Carbon::now());
    $moisEcoules = min($moisEcoules, $product['duree']);
    $progression = $product['duree'] > 0 ? round(($moisEcoules / $product['duree']) * 100) : 0;

    if ($product['type_product'] == 2) {
        $gainTotal = $product['gain_month'];
        $valeurTotale = $product['montant_transaction'] + $gainTotal;
    } else {
        $gainTotal = $user_->gain;
        $valeurTotale = $user_->solde + $gainTotal;
    }
@endphp

@section('content')
    <style>
        .kori-detail * {
            box-sizing: border-box;
        }

        .kori-detail {
            font-family: 'DM Sans', sans-serif;
            padding: 2rem 2rem 3rem;
            color: #1a1a1a;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── Header ── */
        .kd-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .kd-back {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #9c8c6e;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .kd-back::before {
            content: "←";
            font-size: 13px;
        }

        .kd-title {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            color: #1a1a1a;
            line-height: 1;
            letter-spacing: -.01em;
            margin: 0 0 8px;
        }

        .kd-badge {
            display: inline-block;
            background: #5C1F10;
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 4px;
        }

        .kd-amount-block {
            text-align: right;
        }

        .kd-amount-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #9c8c6e;
            margin-bottom: 4px;
        }

        .kd-amount {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            color: #C49A22;
            letter-spacing: -.01em;
            white-space: nowrap;
        }

        .kd-amount-currency {
            font-size: 16px;
            font-family: 'DM Sans', sans-serif;
            font-weight: 400;
            opacity: .65;
            margin-right: 4px;
        }

        .kd-gain-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .kd-gain-pill.positive {
            background: #eef7ee;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .kd-gain-pill.positive::before {
            content: "▲";
            font-size: 9px;
        }

        .kd-gain-pill.negative {
            background: #fdecea;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .kd-gain-pill.negative::before {
            content: "▼";
            font-size: 9px;
        }

        /* ── Stats card ── */
        .kd-stats-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8e4dc;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .kd-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }

        .kd-stat {
            padding: 1.5rem 1.25rem;
            position: relative;
        }

        .kd-stat:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            bottom: 20%;
            width: 1px;
            background: #e8e4dc;
        }

        .kd-stat-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #9c8c6e;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .kd-stat-value {
            font-size: 16px;
            font-weight: 500;
            color: #1a1a1a;
            line-height: 1.2;
        }

        .kd-stat-value.accent {
            color: #5C1F10;
        }

        .kd-stat-sub {
            font-size: 12px;
            font-weight: 400;
            color: #9c8c6e;
            margin-top: 2px;
            display: block;
        }

        /* Progress bar */
        .kd-progress {
            margin-top: 8px;
        }

        .kd-progress-bar {
            height: 4px;
            background: #f0ebe2;
            border-radius: 2px;
            overflow: hidden;
        }

        .kd-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #C49A22, #e8b830);
            border-radius: 2px;
            transition: width .8s ease;
        }

        .kd-progress-label {
            font-size: 10px;
            color: #9c8c6e;
            margin-top: 4px;
        }

        /* Info strip */
        .kd-strip {
            background: #5C1F10;
            color: rgba(255, 255, 255, .7);
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 10px 1.25rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px 16px;
        }

        .kd-strip strong {
            color: #fff;
        }

        /* ── Chart card ── */
        .kd-chart-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8e4dc;
            padding: 1.5rem;
        }

        .kd-chart-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0ebe2;
        }

        .kd-chart-title {
            font-size: 15px;
            font-weight: 500;
            color: #1a1a1a;
        }

        .kd-chart-sub {
            font-size: 12px;
            color: #9c8c6e;
            margin-top: 2px;
        }

        .kd-chart-tag {
            font-size: 11px;
            background: #fdf3dc;
            color: #9c6e0a;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 500;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .kori-detail {
                padding: 1.25rem 1rem 2rem;
            }

            .kd-title {
                font-size: 28px;
            }

            .kd-amount {
                font-size: 26px;
            }

            .kd-header {
                flex-direction: column;
            }

            .kd-amount-block {
                text-align: left;
            }

            .kd-stat:not(:last-child)::after {
                display: none;
            }

            .kd-stat {
                border-bottom: 1px solid #f0ebe2;
            }
        }
    </style>

    <main class="main-content has-sidebar">
        <div class="kori-detail">

            {{-- ── Header ── --}}
            <div class="kd-header">
                <div>
                    <a href="{{ url()->previous() }}" class="kd-back">Mes placements</a>
                    <h1 class="kd-title">{{ $product['product_name'] }}</h1>
                    <span class="kd-badge">{{ $type->abreviation }}</span>
                </div>
                <div class="kd-amount-block">
                    <div class="kd-amount-label">Valorisation totale</div>
                    <div class="kd-amount">
                        <span class="kd-amount-currency">XAF</span>{{ number_format($valeurTotale, 0, ',', ' ') }}
                    </div>
                    @if ($gainTotal >= 0)
                        <div class="kd-gain-pill positive">
                            +{{ number_format($gainTotal, 0, ',', ' ') }} XAF de gain
                        </div>
                    @else
                        <div class="kd-gain-pill negative">
                            {{ number_format($gainTotal, 0, ',', ' ') }} XAF
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Stats card ── --}}
            <div class="kd-stats-card">
                <div class="kd-stats-grid">

                    @if ($product['type_product'] == 2)
                        {{-- PMG / Produit garanti --}}
                        <div class="kd-stat">
                            <div class="kd-stat-label">Type de produit</div>
                            <div class="kd-stat-value accent">{{ $type->abreviation }}</div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">Taux d'intérêt / an</div>
                            <div class="kd-stat-value">{{ $product['vl_achat'] }} %<span class="kd-stat-sub">Net</span>
                            </div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">Montant investi</div>
                            <div class="kd-stat-value">
                                {{ number_format($product['montant_transaction'], 0, ',', ' ') }}<span
                                    class="kd-stat-sub">XAF</span></div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">Date de valeur</div>
                            <div class="kd-stat-value">{{ $dateValeur->format('d/m/Y') }}</div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">Durée</div>
                            <div class="kd-stat-value">{{ number_format($product['duree'], 0, ',', ' ') }} <span
                                    class="kd-stat-sub">Mois</span></div>
                            <div class="kd-progress">
                                <div class="kd-progress-bar">
                                    <div class="kd-progress-fill" style="width: {{ $progression }}%"></div>
                                </div>
                                <div class="kd-progress-label">{{ $moisEcoules }} mois écoulés sur
                                    {{ $product['duree'] }}</div>
                            </div>
                        </div>
                    @else
                        {{-- FCP --}}
                        <div class="kd-stat">
                            <div class="kd-stat-label">Nombre de parts</div>
                            <div class="kd-stat-value accent">{{ round($product['nb_part'], 2) }}</div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">Type</div>
                            <div class="kd-stat-value">{{ $type->abreviation }}</div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">VL actuelle</div>
                            <div class="kd-stat-value">{{ number_format($product['vl_actuel'], 2, ',', ' ') }}<span
                                    class="kd-stat-sub">XAF</span></div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">Montant investi</div>
                            <div class="kd-stat-value">
                                {{ number_format($product['montant_transaction'], 0, ',', ' ') }}<span
                                    class="kd-stat-sub">XAF</span></div>
                        </div>
                        <div class="kd-stat">
                            <div class="kd-stat-label">VL à l'achat</div>
                            <div class="kd-stat-value">{{ number_format($product['vl_achat'], 2, ',', ' ') }}<span
                                    class="kd-stat-sub">XAF</span></div>
                        </div>
                    @endif

                </div>

                {{-- Strip bas de carte --}}
                @if ($product['type_product'] == 2)
                    <div class="kd-strip">
                        <span>Date d'échéance : <strong>{{ $dateEcheance->format('d/m/Y') }}</strong></span>
                        <span></span>
                    </div>
                @else
                    <div class="kd-strip">
                        <span>Plus-value : <strong>{{ number_format($gainTotal, 0, ',', ' ') }} XAF</strong></span>
                        <span>Parts valorisées au {{ now()->format('d/m/Y') }}</span>
                    </div>
                @endif
            </div>

            {{-- ── Graphique (FCP uniquement) ── --}}
            @if ($product['type_product'] == 1)
                <div class="kd-chart-card">
                    <div class="kd-chart-header">
                        <div>
                            <div class="kd-chart-title">Évolution des gains</div>
                            <div class="kd-chart-sub">4 dernières semaines</div>
                        </div>
                        <span class="kd-chart-tag">{{ $product['product_name'] }}</span>
                    </div>
                    <div class="income-chart-afb"></div>
                </div>
            @endif

        </div>
    </main>
@endsection

@section('script_front_end')
    @if ($product['type_product'] == 1)
        @php
            $tableau = array_map('intval', $product['recent_gains']);
            sort($tableau);
            $minimum = $tableau[0];
            $maximum = end($tableau);
        @endphp

        <script>
            const incomeChartAfb = document.querySelector(".income-chart-afb");
            if (incomeChartAfb) {
                const chart = new ApexCharts(incomeChartAfb, {
                    series: [{
                        name: "{{ $product['product_name'] }}",
                        type: "line",
                        data: [
                            @foreach (array_reverse($product['recent_gains']) as $value)
                                {{ intval($value) }},
                            @endforeach
                        ],
                    }],
                    chart: {
                        height: 300,
                        type: "line",
                        toolbar: {
                            show: false
                        },
                        fontFamily: "'DM Sans', sans-serif",
                    },
                    legend: {
                        show: false
                    },
                    colors: ["#C49A22"],
                    stroke: {
                        width: [3],
                        curve: "smooth",
                        lineCap: "round",
                    },
                    markers: {
                        size: 4,
                        colors: ["#fff"],
                        strokeColors: "#C49A22",
                        strokeWidth: 2,
                    },
                    fill: {
                        type: "gradient",
                        gradient: {
                            shade: "light",
                            type: "vertical",
                            shadeIntensity: 0.3,
                            gradientToColors: ["#f5e9c0"],
                            opacityFrom: 0.3,
                            opacityTo: 0,
                        },
                    },
                    xaxis: {
                        categories: [
                            @foreach ($product['recent_gains'] as $key => $value)
                                "Semaine {{ $key + 1 }}",
                            @endforeach
                        ],
                        axisTicks: {
                            show: false
                        },
                        axisBorder: {
                            show: false
                        },
                        labels: {
                            style: {
                                colors: "#9c8c6e",
                                fontSize: "12px"
                            }
                        },
                    },
                    yaxis: {
                        min: 0,
                        max: {{ $maximum }},
                        tickAmount: 5,
                        labels: {
                            offsetX: -10,
                            style: {
                                colors: "#9c8c6e",
                                fontSize: "12px"
                            },
                        },
                    },
                    grid: {
                        borderColor: "#f0ebe2",
                        padding: {
                            left: -10,
                            bottom: -10
                        },
                        xaxis: {
                            lines: {
                                show: true
                            }
                        },
                    },
                    tooltip: {
                        style: {
                            fontFamily: "'DM Sans', sans-serif"
                        },
                    },
                    responsive: [{
                            breakpoint: 768,
                            options: {
                                chart: {
                                    height: 300
                                }
                            }
                        },
                        {
                            breakpoint: 570,
                            options: {
                                chart: {
                                    height: 240
                                }
                            }
                        },
                    ],
                });
                chart.render();
            }
        </script>
    @endif
@endsection
