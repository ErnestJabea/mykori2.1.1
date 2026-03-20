@extends('front-end/app/app-home', ['title' => $product['product_name'], 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden'])

@php
$type = App\ProductsCategory::where('id', $product['type_product'])->first();
$user_ = App\Models\User::where('id', Auth::user()->id)->first();
@endphp


@section('content')
<main class="main-content has-sidebar">
    <div class="grid grid-cols-12 gap-4 xxl:gap-6 header-main">
        <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
            <h3>{{ $product['product_name'] }}</h3>
        </div>
        <div class="col-span-12 md:col-span-5 lg:col-span-4">
            <div class="inner-button inner-amount">
                @if ($product["type_product"] == 2)
                    XAF {{ number_format($product['montant_transaction'] + $product['gain_month'], 0, ' ', ' ') }}
                @else

                XAF {{ number_format($user_ -> solde + $user_ -> gain, 0, ' ', ' ') }}
                @endif
            </div>
        </div>
        <div class="col-span-12 detail-produit-wrapper">
            <!-- Statistics -->
            @if ($product["type_product"] == 2)
            <div class="flex align-center content-elt">
                <div
                    class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">TYPE DE PRODUIT</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">{{ $type->abreviation }}</h4>
                        </div>
                    </div>
                </div>
                <div
                    class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">TAUX D'INTÉRÊT/AN</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4"> {{ $product['vl_achat'] }}% Net</h4>
                        </div>
                    </div>
                </div>
                <div
                    class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">MONTANT INVESTI  </p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">XAF {{ number_format($product['montant_transaction'], 0, ' ', ' ') }}</h4>
                        </div>
                    </div>
                </div>
                <div
                    class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">DATE DE VALEUR</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">{{ $product ['date_souscription']}}</h4>
                        </div>
                    </div>
                </div>
                <div
                        class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">DURÉE</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4"> {{ number_format($product['duree'], 0, ' ', ' ') }} Mois
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="flex align-center content-elt">
                <div
                    class="max-xxl:box stat-part col-span-12 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">NOMBRE DE PARTS</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">{{ round($product['nb_part'],2) }}</h4>
                        </div>
                    </div>
                </div>
                <div
                    class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">TYPE</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">{{ $type->abreviation }}</h4>
                        </div>
                    </div>
                </div>
                <div
                    class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">VL ACTUELLE</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">XAF {{ $product['vl_actuel'] }}</h4>
                        </div>
                    </div>
                </div>
                <div
                        class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">MONTANT INVESTI  </p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">XAF {{ number_format($product['montant_transaction'], 0, ' ', ' ') }}</h4>
                        </div>
                    </div>
                </div>
                <div
                    class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                    <div>
                        <p class="mb-4 font-medium">VL À L'ACHAT</p>
                        <div class="flex items-center gap-2">
                            <h4 class="h4">XAF {{ $product['vl_achat'] }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <!-- Graphe et statistiques -->
        @if ($product['type_product'] == 1)
        <div class="col-span-12">
            <div class="grid grid-cols-12 gap-4 xxl:gap-6">
                <div class="col-span-12 flex flex-col gap-12 md:col-span-12 lg:col-span-12 xxl:gap-12">
                    <!-- Income and expences -->
                    <div class="box overflow-x-hidden">
                        <div
                            class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">Évolution des gains (4 dernières semaines)</h4>
                            <div class="flex items-center gap-3">
                            </div>
                        </div>
                        <div class="income-chart-afb"></div>
                    </div>
                </div>

            </div>
        </div>
        @endif
        <div class="content-separator" style="height:30px">

        </div>


    </div>
    </div>
    </div>
    </div>


</main>
@endsection

@section('script_front_end')

@if ($product['type_product'] == 1)
@php
$tableau = [];
foreach ($product['recent_gains'] as $value) {
$tableau[] = intval($value);
}


sort($tableau); // Trie le tableau dans l'ordre croissant
$minimum = $tableau[0];
$maximum = end($tableau);



@endphp



<script>
    const incomeChartAfb = document.querySelector(".income-chart-afb");

    if (incomeChartAfb) {
        const chartData = {
            series: [{
                name: "{{ $product['product_name'] }}",
                type: "line",
                data: [
                @foreach (array_reverse($product['recent_gains']) as $key => $value2)
        {{ intval($value2) }},
    @endforeach
    ],
    }, ],
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
        colors: ["#E5C646"],
            stroke: {
            width: [3, 3],
                curve: "smooth",
                lineCap: "round",
                dashArray: [0, 5],
        },
        xaxis: {
            type: "Semaine",
                categories: [
            @foreach ($product['recent_gains'] as $key => $value)
            "Semaine  {{ $key + 1 }}",
            @endforeach
        ],
            tickAmount: {{ count($product['recent_gains']) }},
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
                max: {{ $maximum }},
            tickAmount: 5,
                labels: {
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
        const chart = new ApexCharts(incomeChartAfb, chartData);
        chart.render();
    }
</script>
@endif
@endsection
