@extends('front-end/app/app-home', ['title' => $product->title, 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden'])

@php
    $type = App\ProductsCategory::where('id', $product->products_category_id)->first();
    $asset_value = App\AssetValue::where('product_id', $product->id)
        ->orderBy('created_at', 'desc')
        ->latest()
        ->first();
@endphp


@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <h3>{{ $product->title }}</h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4">
                <div class="inner-button">
                    <button class="btn ac-modal-btn buy ac-modal-btn">
                        SOUSCRIRE
                    </button>
                </div>
            </div>
            <div class="col-span-12 detail-produit-wrapper">
                <!-- Statistics -->
                @if ($product->products_category_id == 2)
                    <div class="flex align-center content-elt">
                        <div
                            class="max-xxl:box stat-part col-span-12 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                            <div>
                                <p class="mb-4 font-medium">NOMBRE DE PARTS</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4">1</h4>
                                </div>
                            </div>
                        </div>
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
                                <p class="mb-4 font-medium">TAUX D'INTÉRÊT PAR AN</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4"> {{ $product->vl }}% Net</h4>
                                </div>
                            </div>
                        </div>
                        {{-- <div
                            class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                            <div>
                                <p class="mb-4 font-medium">Valeur liquidative </p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4">XAF {{ number_format($product->vl, 0, ' ', ' ') }}</h4>
                                </div>
                            </div>
                        </div>
                         <div
                            class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                            <div>
                                <p class="mb-4 font-medium">DATE</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4">15 Nov. 2023</h4>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                @else
                    <div class="flex align-center content-elt">
                        <div
                            class="max-xxl:box stat-part col-span-12 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                            <div>
                                <p class="mb-4 font-medium">PARTS DISPONIBLES</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4">{{ number_format($product->nb_action, 0, ' ', ' ') }}</h4>
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
                                <p class="mb-4 font-medium">VALEUR LIQUIDATIVE</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4">XAF {{ number_format($asset_value->vl, 0, ' ', ' ') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div
                            class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                            <div>
                                <p class="mb-4 font-medium">RENTABILITÉ CIBLE</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4"> {{ number_format($product->seuil_rentabilite, 0, ' ', ' ') }}%
                                    </h4>
                                </div>
                            </div>
                        </div>
                        {{-- <div
                            class="max-xxl:box stat-part col-span-6 flex items-center justify-between gap-3 overflow-x-hidden sm:col-span-3 md:col-span-6 lg:col-span-3 xxl:col-span-2 xxl:px-4 xxl:ltr:first:pl-0 xxl:last:ltr:pr-0">
                            <div>
                                <p class="mb-4 font-medium">DATE</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="h4">15 Nov. 2023</h4>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                @endif
            </div>
            <!-- Graphe et statistiques -->
            @if ($product->products_category_id == 1)
                <div class="col-span-12">
                    <div class="grid grid-cols-12 gap-4 xxl:gap-6">
                        <div class="col-span-12 flex flex-col gap-12 md:col-span-12 lg:col-span-12 xxl:gap-12">
                            <!-- Income and expences -->
                            <div class="box overflow-x-hidden">
                                <div
                                    class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4 lg:mb-6 lg:pb-6">
                                    <h4 class="h4">Évolution de la valeur liquidative</h4>
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
            @if ($product->description != '')
                <div class="col-span-12">
                    <div class="grid grid-cols-12 gap-4 xxl:gap-6">
                        <div class="col-span-12 flex flex-col gap-12 md:col-span-12 lg:col-span-12 xxl:gap-12">
                            <!-- Income and expences -->
                            <div class="box overflow-x-hidden">
                                <div
                                    class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4 lg:mb-6 lg:pb-6">
                                    <h4 class="h4">Description du produit</h4>
                                    <div class="flex items-center gap-3">
                                    </div>
                                </div>
                                <div class="content-product-description">
                                    {!! $product->description !!}</div>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

        </div>
        </div>
        </div>
        </div>

        <!-- modal -->
        @if ($product->products_category_id == 2)
            <div class="ac-modal-overlay fixed inset-0 z-[99] modalhide overflow-y-auto bg-n900/80 duration-500">
                <div class="overflow-y-auto">
                    <div
                        class="modal box modal-inner absolute left-1/2 my-10 max-h-[90vh] w-[95%] max-w-[710px] duration-300 -translate-x-1/2 overflow-y-auto xl:p-8">
                        <!-- { "translate-y-0 scale-100 opacity-100 my-10": open } -->
                        <div class="relative">
                            <button class="ac-modal-close-btn absolute top-0 ltr:right-0 rtl:left-0">
                                <i class="las la-times"></i>
                            </button>
                            <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                                <h4 class="h4">Souscrire au produit: {{ $product->title }} - Taux d'interêt :
                                    {{ $product->vl }}%</h4>
                            </div>
                            <div class="alert alert-success"></div>
                            <form action="achat-ok.php" method="post">
                                <div class="mt-6 grid grid-cols-2 gap-4 xl:mt-8 xxxl:gap-6">
                                    <div class="col-span-2">
                                        <label for="name" class="mb-4 block font-medium md:text-lg">
                                            Montant (en XAF)
                                        </label>
                                        <input type="number"
                                            class="w-full rounded-3xl border border-n30 bg-secondary1/5 px-6 py-2.5 dark:border-n500 dark:bg-bg3 md:py-3"
                                            placeholder="Indiquez le montant" min="500000" value="0"
                                            id="montantInput" name="montantInput" required />
                                    </div>
                                    <div class="col-span-2">
                                        <label for="number" class="mb-4 block font-medium md:text-lg"
                                            id="valeur-liquidative">
                                            Gains potentiels (par an): 0
                                        </label>
                                    </div>
                                    <div class="col-span-2">
                                        <label for="number" class="mb-4 block font-medium md:text-lg" id="montantTotal">
                                            Montant total : XAF 0
                                        </label>
                                    </div>
                                    <div class="col-span-2 mt-4">
                                        <button class="btn flex w-full justify-center" id="submitButton" type="button">
                                            Souscrire maintenant
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="ac-modal-overlay fixed inset-0 z-[99] modalhide overflow-y-auto bg-n900/80 duration-500">
                <div class="overflow-y-auto">
                    <div
                        class="modal box modal-inner absolute left-1/2 my-10 max-h-[90vh] w-[95%] max-w-[710px] duration-300 -translate-x-1/2 overflow-y-auto xl:p-8">
                        <!-- { "translate-y-0 scale-100 opacity-100 my-10": open } -->
                        <div class="relative">
                            <button class="ac-modal-close-btn absolute top-0 ltr:right-0 rtl:left-0">
                                <i class="las la-times"></i>
                            </button>
                            <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                                <h4 class="h4">Acheter {{ $product->title }} - VL : XAF {{ $asset_value->vl }}</h4>
                            </div>
                            <div class="alert alert-success" id="response"></div>
                            <form action="achat-ok.php" method="post">
                                <div class="mt-6 grid grid-cols-2 gap-4 xl:mt-8 xxxl:gap-6">
                                    <div class="col-span-2">
                                        <label for="name" class="mb-4 block font-medium md:text-lg">
                                            Montant (en XAF)
                                        </label>
                                        <input type="number"
                                            class="w-full rounded-3xl border border-n30 bg-secondary1/5 px-6 py-2.5 dark:border-n500 dark:bg-bg3 md:py-3"
                                            placeholder="Indiquez le montant" min="0" id="montantInput"
                                            name="montantInput" required />
                                    </div>
                                    <div class="col-span-2">
                                        <label for="number" class="mb-4 block font-medium md:text-lg"
                                            id="valeur-liquidative">
                                            Nombre de parts : 0
                                        </label>
                                    </div>
                                    <div class="col-span-2">
                                        <label for="number" class="mb-4 block font-medium md:text-lg"
                                            id="frais-de-gestion">
                                            Frais de souscription : XAF 0
                                        </label>
                                    </div>
                                    <div class="col-span-2">
                                        <label for="number" class="mb-4 block font-medium md:text-lg"
                                            id="montantTotal">
                                            Montant total : XAF 0
                                        </label>
                                    </div>
                                    <div class="col-span-2 mt-4">
                                        <button class="btn flex w-full justify-center" id="submitButton" type="button">
                                            Souscrire maintenant
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </main>
@endsection

@section('script_front_end')
    <script>
        @if ($product->products_category_id == 1)
            if (document.querySelector(".ac-modal-btn")) {
                setupModal(".ac-modal-btn", ".ac-modal-overlay", ".ac-modal-close-btn");
            }

            var submitButton = $('#submitButton');
            submitButton.attr('disabled', true);


            $(document).ready(function() {
                // Écoutez les changements de l'input
                $('#montantInput').on('input', function() {
                    // Récupérez la nouvelle valeur de l'input
                    var nouveauMontant = parseFloat($(this).val());

                    var montantAchat = nouveauMontant / {{ $asset_value->vl }};
                    var frais = (nouveauMontant * {{ $product->free }}) / 100;
                    var montantTotal = frais + nouveauMontant;
                    var montant_normal = nouveauMontant;


                    if (nouveauMontant > 0) {
                        submitButton.attr('disabled', false);

                        $("#response").removeClass("show-response");
                        $('#response').text(
                            'Limite de parts atteinte. Bien vouloir  réessayer avec une quantité inférieure'
                        );

                        // Mettez à jour les valeurs des spans
                        $('#valeur-liquidative').text('Nombre de parts : ' + montantAchat
                            .toLocaleString(
                                'fr-FR'));
                        $('#frais-de-gestion').text('Frais de souscription : XAF ' + frais
                            .toLocaleString(
                                'fr-FR'));
                        $('#montantTotal').text('Montant à payer  : XAF ' + montantTotal.toLocaleString(
                            'fr-FR'));

                        submitButton.off('click').on('click', function(event) {
                            event.preventDefault();
                            // Afficher le loader
                            $('.loader').show();

                            $.ajax({
                                url: '{{ route('achat-action-fcp') }}',
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    montantAchat: montantAchat,
                                    fraisGestion: frais,
                                    montant_normal: montant_normal,
                                    montantTotal: montantTotal,
                                    product: {{ $product->id }}
                                },
                                success: function(response) {
                                    console.log(response.message);
                                    $("#response").text(response
                                        .message); // Affiche le message de succès
                                    // Redirigez vers une autre vue si nécessaire
                                    window.location.href =
                                        '{{ route('success-transaction') }}';
                                },
                                error: function(error) {
                                    console.error(
                                        'Erreur lors de l\'envoi des données :',
                                        error);
                                },
                                complete: function() {
                                    // Cacher le loader une fois la requête terminée
                                    $('.loader').hide();
                                }
                            });
                        });
                    } else {

                        $('#response').text(
                            'Vous devez indiquer une valeur positive.'
                        );
                    }
                });
            });
        @else
            if (document.querySelector(".ac-modal-btn")) {
                setupModal(".ac-modal-btn", ".ac-modal-overlay", ".ac-modal-close-btn");
            }

            var submitButton = $('#submitButton');
            submitButton.attr('disabled', true);


            $(document).ready(function() {
                // Écoutez les changements de l'input
                $('#montantInput').on('input', function() {
                    // Récupérez la nouvelle valeur de l'input
                    var nouveauMontant = parseFloat($(this).val());

                    var montantAchat = (nouveauMontant * {{ $product->vl / 100 }});
                    var montantTotal = nouveauMontant;


                    if (nouveauMontant > 0) {
                        submitButton.attr('disabled', false);


                        // Mettez à jour les valeurs des spans
                        $('#valeur-liquidative').text('Gains potentiels (par an) : ' + montantAchat
                            .toLocaleString(
                                'fr-FR'));
                        $('#montantTotal').text('Montant à payer  : XAF ' + montantTotal.toLocaleString(
                            'fr-FR'));

                        submitButton.off('click').on('click', function(event) {
                            event.preventDefault();
                            // Afficher le loader
                            $('.loader').show();

                            $.ajax({
                                url: "{{ route('achat-action-pmg') }}",
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    montantAchat: montantAchat,
                                    fraisGestion: 0,
                                    montantTotal: montantTotal,
                                    product: {{ $product->id }}
                                },
                                success: function(response) {
                                    console.log(response.message);
                                    // Redirigez vers une autre vue si nécessaire
                                    window.location.href =
                                        '{{ route('success-transaction') }}';
                                },
                                error: function(error) {
                                    console.error(
                                        'Erreur lors de l\'envoi des données :',
                                        error);
                                },
                                complete: function() {
                                    // Cacher le loader une fois la requête terminée
                                    $('.loader').hide();
                                }
                            });
                        });

                    } else {
                        submitButton.attr('disabled', true);
                    }
                });
            });
        @endif
    </script>

    @if ($product->products_category_id == 1)
        @php
            $tableau = [];
            foreach ($asset_value_all as $value) {
                $tableau[] = intval($value->vl);
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
                        name: "{{ $product->title }}",
                        type: "line",
                        data: [
                            @foreach ($asset_value_all2 as $value2)
                                {{ intval($value2->vl) }},
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
                            @foreach ($asset_value_all as $key => $value)
                                "Semaine  {{ $key + 1 }}",
                            @endforeach
                        ],
                        tickAmount: {{ $asset_value_all->count() }},
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
