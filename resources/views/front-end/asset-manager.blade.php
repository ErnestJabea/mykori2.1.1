@extends('front-end/app/app-home-asset', ['Dashboard ', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@section('content')
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
                            <p class="font-medium">{{ date('d-m-Y') }}</p>
                            <span class="text-xs opacity-50">Dernière mise à jour</span>
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

            <!-- Stats Cards Overview -->
            <!-- Total Invested Card -->
            <div class="col-span-12 lg:col-span-3">
                <div
                    class="box bg-primary p-6 rounded-2xl text-white relative overflow-hidden h-full min-h-[160px] flex flex-col justify-center shadow-lg transform hover:scale-[1.02] duration-300">
                    <div class="relative z-10">
                        <p class="text-sm opacity-80 mb-2 font-medium text-white/90 uppercase tracking-wider">Total Investi
                            (Capital)</p>
                        <h2 class="h2 mb-0 text-white font-bold">{{ number_format($globalTotalInvested, 0, ' ', ' ') }}
                            <span class="text-xs opacity-70">XAF</span>
                        </h2>
                        <div class="mt-4 pt-3 border-t border-white/20 flex items-center gap-2">
                            <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full">Global</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Interests Card -->
            <div class="col-span-12 lg:col-span-3">
                <div class="box bg-green-600 p-6 rounded-2xl text-white relative overflow-hidden h-full min-h-[160px] flex flex-col justify-center shadow-lg transform hover:scale-[1.02] duration-300"
                    style="background-color: #10b981;">
                    <div class="relative z-10">
                        <p class="text-sm opacity-80 mb-2 font-medium text-white/90 uppercase tracking-wider">Intérêts
                            Générés</p>
                        <h2 class="h2 mb-0 text-white font-bold">{{ number_format($globalTotalInterests, 0, ' ', ' ') }}
                            <span class="text-xs opacity-70">XAF</span>
                        </h2>
                        <div class="mt-4 pt-3 border-t border-white/20">
                            <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-medium">Clients actifs</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6">
                <div
                    class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 flex flex-col justify-between h-full min-h-[160px]">
                    <div>
                        <p class="text-sm opacity-70 mb-1 font-medium">Portefeuille Clients</p>
                        <div class="flex items-end gap-4">
                            <h2 class="h2 text-secondary1 mb-0">{{ $activeClientsCount }}</h2>
                            <p class="text-sm mb-1 opacity-70">Clients Actifs</p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-n30 pt-4">
                        <span class="text-xs px-2 py-1 bg-secondary1/10 text-secondary1 rounded-full font-medium">Total:
                            {{ $customers->count() }} clients enregistrés</span>
                        <i class="las la-users text-4xl text-secondary1/20"></i>
                    </div>
                </div>
            </div>

            <!-- Graph Section -->
            <div class="col-span-12">
                <div class="grid grid-cols-12 gap-4 xxl:gap-6">
                    <!-- VL Evolution Chart -->
                    <div class="col-span-12 lg:col-span-8">
                        <div class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm h-full">
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
                        <div class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm h-full">
                            <div class="mb-6 bb-dashed pb-4">
                                <h4 class="h4 flex items-center gap-2">
                                    <i class="las la-chart-pie text-secondary1"></i> Total actifs & intérêts générés
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
            const chartElement = document.querySelector("#fcp-vls-chart");
            if (chartElement) {
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
                                return val.toLocaleString() + " XAF";
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
            }

            const distributionElement = document.querySelector("#portfolio-distribution-chart");
            if (distributionElement) {
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
                                return val.toLocaleString() + " XAF";
                            }
                        }
                    }
                };

                const distChart = new ApexCharts(distributionElement, distributionOptions);
                distChart.render();
            }
        });
    </script>
@endsection
