@extends('front-end/app/app-home', ['Tableau de bord', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden'])

@php
    $type = App\ProductsCategory::get();
    $mestransactions = App\transaction::where('user_id', Auth::user()->id)
        ->where('status', 'Succès')
        ->orderBy('created_at', 'desc')
        ->get(['vl_buy', 'product_id', 'updated_at', 'amount']);

    $produits = App\AssetValue::select(
        'product_id',
        DB::raw('SUBSTRING_INDEX(GROUP_CONCAT(vl ORDER BY created_at DESC), ",", 4) as latest_prices'),
    )
        ->groupBy('product_id')
        ->get();

    $asset_value = App\AssetValue::orderBy('created_at', 'desc')->get();

    function convertirNotationAbregee($nombre)
    {
        if ($nombre >= 1000000000) {
            // Convertir en milliards (B)
            return round($nombre / 1000000000, 2) . 'B';
        } elseif ($nombre >= 1000000) {
            // Convertir en millions (M)
            return round($nombre / 1000000, 2) . 'M';
        } elseif ($nombre >= 1000) {
            // Convertir en milliers (K)
            return round($nombre / 1000, 2) . 'K';
        } else {
            // Pas besoin de notation abrégée
            return $nombre;
        }
    }
@endphp

@section('content')
    @php
        $user_ = App\Models\User::where('id', Auth::user()->id)->first();

        $percent = $user_->percent;
        $percent_gain = 0;
        //dd(portefeuilleTotal);

        $somme_solde = max(0, $gain_user);

    @endphp
    <main class="main-content has-sidebar">
        <div class="inner-elt-dashboard">
            <div class="content-left-wrappern content-left">
                <div class="content-left">
                    <div class="title-wrapper">
                        <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">XAF
                                {{ number_format($totalPortefeuilleFcp + $totalPortefeuillePmg, 0, ' ', ' ') }}</h4>
                            <div class="flex items-center gap-3">
                            </div>
                        </div>
                    </div>
                    <div class="content-card-elt">
                        <div class="card-portofolio">
                            <p class="mb-4 font-medium">PMG</p>
                            <h4 class="h4">XAF {{ number_format($totalPortefeuillePmg, 0, ' ', ' ') }}</h4>
                            {{-- <span class="flex items-center gap-1 text-sm text-primary">
                                    <i class="las la-arrow-up text-base"></i> {{ $user_->percent }}%
                                </span> --}}
                        </div>
                        <div class="card-portofolio">
                            <p class="mb-4 font-medium">FCP</p>
                            <h4 class="h4">XAF
                                {{ number_format($totalTransactionAmountFcp + $totalPortefeuilleFcp, 0, ' ', ' ') }}
                            </h4>
                            {{-- <span class="flex items-center gap-1 text-sm text-primary">
                                    <i class="las la-arrow-up text-base"></i> {{ $user_->percent }}%
                                </span> --}}
                        </div>
                        <div class="card-portofolio">
                            <p class="mb-4 font-medium">GAINS/PERTES </p>
                            <h4 class="h4">XAF
                                {{ number_format($user_->gain + ($gain_cumule_fcp + $gain_cumule_pmg), 0, ' ', ' ') }}
                            </h4>
                            {{-- <span class="flex items-center gap-1 text-sm text-primary">
                                    <i class="las la-arrow-up text-base"></i> {{ $percent_gain }}%
                                </span> --}}
                        </div>
                    </div>
                </div>
                <div class="content-btn-see-all">
                    <a class="group mt-6 inline-flex items-center gap-1 font-semibold text-primary"
                        href="{{ route('my-products') }}">
                        Voir les détails
                        <i class="las la-arrow-right duration-300 group-hover:pl-2"></i>
                    </a>
                </div>

            </div>
            <div class="content-right">
                <button class="btn ac-modal-btn buy" id="souscription">
                    <a href="{{ route('products') }}">SOUSCRIRE</a>
                </button>
            </div>
            </di>
        </div>
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">

        </div>
        <!-- Graphe et statistiques -->
        <div class="col-span-12">
            <div class="grid grid-cols-12 gap-4 xxl:gap-6">
                {{-- <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                    <!-- Income and expences -->
                    <div class="box overflow-x-hidden">
                        <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">Statistiques de gains/pertes</h4>
                            <div class="flex items-center gap-3">
                            </div>
                        </div>
                        <div class="income-chart"></div>
                    </div>
                </div> --}}
                <div class="col-span-12 md:col-span-5 lg:col-span-12">
                    <div class="box status-gains">
                        <h4 class="h4 bb-dashed mb-4 pb-4 lg:mb-6 lg:pb-6">Détail du portefeuille</h4>
                        <div class="weekly-product-transactions"></div>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-5"></div>
        <!-- Liste des produits -->
        <div class="col-span-12">
            <div class="box col-span-12 lg:col-span-6">
                <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                    <h4 class="h4">PRODUITS DISPONIBLES</h4>
                </div>
                <div class="overflow-x-auto">
                    <div class="mb-6 flex items-center justify-center gap-3 lg:mb-8 xxl:gap-4">
                        <button
                            class="prev-wallet h-8 w-8 shrink-0 rounded-full border border-primary bg-n0 text-primary duration-300 hover:bg-primary hover:text-n0 dark:bg-bg4 dark:hover:bg-primary xxl:h-10 xxl:w-10">
                            <i class="las la-angle-left text-lg rtl:rotate-180"></i>
                        </button>
                        <div class="swiper walletSwiper " dir="ltr">
                            <div class="swiper-wrapper">
                                @foreach ($products as $product)
                                    @php
                                        $vl = App\Product::where('id', $product->id)->first();
                                        $asset_value = App\AssetValue::where('product_id', $product->id)
                                            ->orderBy('created_at', 'desc')
                                            ->latest()
                                            ->first();
                                    @endphp
                                    <div class="swiper-slide">
                                        <div class="flex justify-center">
                                            <div class="content-product">
                                                <div class="product-img">
                                                    <img src="{{ $product->logo }}" class="rounded-xl"
                                                        alt="{{ $product->title }}" />
                                                </div>
                                                <div class="title-product">
                                                    <h3>{{ $product->title }}</h3>
                                                </div>
                                                <div class="product-price">
                                                    <span
                                                        class="price">{{ number_format($asset_value->vl, 0, ' ', ' ') }}</span>
                                                    <sup>XAF</sup>
                                                </div>
                                                <div class="content-btn-action">
                                                    <button class="btn ac-modal-btn buy">
                                                        <a
                                                            href="{{ route('product-detail', ['slug' => $product->slug]) }}">SOUSCRIRE</a>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div> <button
                            class="next-wallet h-8 w-8 shrink-0 rounded-full border border-primary bg-n0 text-primary duration-300 hover:bg-primary hover:text-n0 dark:bg-bg4 dark:hover:bg-primary xxl:h-10 xxl:w-10">
                            <i class="las la-angle-right text-lg rtl:rotate-180"></i>
                        </button>
                    </div>
                </div>
                <div class="content-btn-see-all">
                    <a class="group mt-6 inline-flex items-center gap-1 font-semibold text-primary"
                        href="{{ route('products') }}">
                        Voir tous les produits
                        <i class="las la-arrow-right duration-300 group-hover:pl-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-2"></div>
        <!-- Latest Transactions -->
        <div class="col-span-12">
            <div class="box col-span-12 lg:col-span-6">
                <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                    <h4 class="h4">Transactions récentes</h4>
                    <div class="flex items-center gap-4">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap select-all-table" id="transactionTable">
                        @if ($transactions->count() > 0)
                            <thead>
                                <tr class="bg-secondary1/5 dark:bg-bg3">
                                    <th class="min-w-[220px] cursor-pointer px-6 py-5 text-start">
                                        <div class="flex items-center gap-1">Libellé</div>
                                    </th>
                                    <th class="min-w-[120px] py-5 text-center">Référence</th>
                                    <th class="min-w-[120px] cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1">Moyens de paiement
                                        </div>
                                    </th>
                                    <th class="min-w-[120px] cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1">Montant (XAF)
                                        </div>
                                    </th>
                                    <th align="center" class="cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1" style="justify-content: center;">Status</div>
                                    </th>
                                    <th align="center" class="cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1" style="justify-content: center;">Actions</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    @php
                                        $product_ = App\Product::find($transaction->product_id)->first();
                                    @endphp
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="px-6 py-2">
                                            <div class="flex items-center gap-3">
                                                <div>
                                                    <p class="mb-1 font-medium">{{ $transaction->title }} </p>
                                                    <span class="text-xs">{{ $transaction->created_at }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td align="center" class="py-2">{{ $transaction->ref }}</td>
                                        <td align="center" class="py-2">{{ $transaction->payment_mode }}</td>
                                        <td align="center" class="py-2">
                                            {{ number_format($transaction->amount, 0, ' ', ' ') }}</td>
                                        <td align="center" class="py-2">
                                            @if ($transaction->status == 'Refusé')
                                                <span
                                                    class="block w-28 rounded-[30px] border border-n30 bg-secondary2/10 py-2 text-center text-xs text-secondary2 dark:border-n500 dark:bg-bg3 xxl:w-36">
                                                    {{ $transaction->status }}
                                                </span>
                                            @elseif ($transaction->status == 'En attente')
                                                <span
                                                    class="block w-28 rounded-[30px] border border-n30 bg-secondary3/10 py-2 text-center text-xs text-secondary3 dark:border-n500 dark:bg-bg3 xxl:w-36">
                                                    {{ $transaction->status }}
                                                </span>
                                            @elseif ($transaction->status == 'Succès')
                                                <span
                                                    class="block w-28 rounded-[30px] border border-n30 bg-primary/10 py-2 text-center text-xs text-primary dark:border-n500 dark:bg-bg3 xxl:w-36">
                                                    {{ $transaction->status }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <!-- Add your action elements here -->
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <a
                                                                href="{{ route('transaction-detail', ['reference' => $transaction->ref]) }}">
                                                                <span
                                                                    class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                    Voir le détail
                                                                </span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @else
                            <p style="text-align: center"> Pas de transaction pour le moment</p>
                        @endif
                    </table>
                    <div class="content-btn-see-all">
                        <a class="group mt-6 inline-flex items-center gap-1 font-semibold text-primary"
                            href="{{ route('my-history') }}">
                            Voir +
                            <i class="las la-arrow-right duration-300 group-hover:pl-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        </div>
        </div>
    </main>
@endsection

@section('script_front_end')
    <script>
        @php
            //dd($chartData);
        @endphp
        /*    const chartData = @json($chartData);
            console.log(chartData.chartData);
            const incomeChart = document.querySelector(".income-chart");

            if (incomeChart) {
                const chartOptions = {
                    series: chartData.chartData.map(data => ({
                        name: data.name,
                        type: "line",
                        data: data.data
                    })),
                    chart: {
                        height: 300,
                        type: "line",
                        toolbar: {
                            show: false,
                        },
                    },
                    legend: {
                        show: false,
                    },
                    colors: ["#F9F5E5", "#F4E9BD", "#EFDE96", "#E5C646"],
                    stroke: {
                        width: [3, 3],
                        curve: "smooth",
                        lineCap: "round",
                        dashArray: [0, 5],
                    },
                    xaxis: {
                        type: "category",
                        categories: chartData.weekLabels,
                        tickAmount: 12,
                        labels: {},
                        axisTicks: {
                            show: false,
                        },
                        axisBorder: {
                            show: false,
                        },
                    },
                    yaxis: {
                        min: 0,
                        tickAmount: 5,
                        labels: {
                            formatter: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(1) + 'K';
                                }
                                return value;
                            },
                            offsetX: -17,
                        },
                    },
                    fill: {
                        opacity: 1,
                    },
                    grid: {
                        padding: {
                            left: -10,
                            bottom: -10,
                        },
                        show: true,
                        xaxis: {
                            lines: {
                                show: true,
                            },
                        },
                    },
                    responsive: [{
                        breakpoint: 768,
                        options: {
                            chart: {
                                height: 300,
                            },
                        },
                    },
                        {
                            breakpoint: 570,
                            options: {
                                chart: {
                                    height: 240,
                                },
                            },
                        },
                    ],
                };
                const chart = new ApexCharts(incomeChart, chartOptions);
                chart.render();
            }

        */
        const weeklyProductTransactionsChart = document.querySelector(
            ".weekly-product-transactions"
        );

        var productGains = @json($result_gain);

        var series = productGains.map(function(item) {
            return item.total_gain;
        });
        var labels = productGains.map(function(item) {
            return item.product_name;
        });


        if (weeklyProductTransactionsChart) {
            document.addEventListener('DOMContentLoaded', function() {
                var options = {
                    chart: {
                        type: 'pie',
                        height: 500
                    },
                    series: series,
                    labels: labels,
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 500
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };


                var chart = new ApexCharts(weeklyProductTransactionsChart, options);
                chart.render();
            });
        }
    </script>
    <script>
        const driver = window.driver.js.driver;

        const driverObj = driver({
            showProgress: true,
            steps: [{
                    element: '#tableau-de-bord',
                    popover: {
                        title: 'Tableau de bord',
                        description: 'Pour accéder de manière générale à toutes les informations.'
                    }
                },
                {
                    element: '#produits-disponibles',
                    popover: {
                        title: 'Produits disponibles',
                        description: 'Consulter la liste des produits disponibles'
                    }
                },
                {
                    element: '#mes-produits',
                    popover: {
                        title: 'Mes produits',
                        description: 'Consulter vos produits souscrits'
                    }
                },
                {
                    element: '#historique',
                    popover: {
                        title: 'Historque',
                        description: 'Consulter l\'hsitorique de vos différentes transactions'
                    }
                },
                {
                    element: '#souscription',
                    popover: {
                        title: 'Souscription',
                        description: 'En cliquant sur ce bouton, vous lancerez la procédure de souscription à un produit'
                    }
                },
            ]
        });


        document.addEventListener("DOMContentLoaded", function() {
            // Vérifiez si le script a déjà été exécuté dans cette session
            if (!sessionStorage.getItem('driverExecuted')) {
                // Exécutez votre code pour `driver.js`
                driverObj.drive(); // Remplacez cette ligne par votre appel à driver.js

                // Enregistrez que `driver.js` a été exécuté pour cette session
                sessionStorage.setItem('driverExecuted', 'true');
            }
        });
    </script>
@endsection
