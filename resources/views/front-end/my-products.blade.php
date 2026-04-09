@extends('front-end/app/app-home', ['Mes produits', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <h3>MES SOUSCRIPTIONS</h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4">
                <p style="text-align: right">{{ date('d-m-Y') }}</p>
            </div>
            <div class="col-span-12 detail-produit-wrapper">
                <!-- Statistics -->
                <div class="content-my-products-wrapper flex">
                    <div class="content-bloc-list-produit">
                        <h3>MES SOUSCRIPTIONS PMG</h3>
                        @foreach ($productsWithGains as $my_product)
                            @if ($my_product['type_product'] == 2)
                                <div class="item-product">
                                    <div class="content-link-title">
                                        <a href="{{ route('product-detail-gain', ['slug' => $my_product['slug']]) }}"
                                            class="flex flex-space-between-center">
                                            <span>{{ $my_product['product_name'] }}</span>
                                            <span> <i class="las la-arrow-right duration-300 group-hover:pl-2"></i></span>
                                        </a>
                                    </div>
                                    <div class="inner-header">
                                        <div class="content-label-info">
                                            <div class="label-">Investissement initial :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['montant_transaction'], 0, ' ', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Taux d'intérêt :</div>
                                            <div class="response-">
                                                {{ $my_product['vl_actuel'] }}%</div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Durée :</div>
                                            <div class="response-">
                                                {{ $my_product['days_months']['months'] }} mois @if ($my_product['days_months']['days'] > 0)
                                                    {{ $my_product['days_months']['days'] }} jour(s)
                                                @endif
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Intérêt mensuel :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['gain_mensuel'], 0, ' ', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Gains cumulés :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['interets_generes'], 0, ' ', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Portefeuille :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['portfolio_valeur'], 0, ' ', ' ') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="content-label-info">
                                        <div class="label-">Date de valeur:</div>
                                        <div class="response-"> {{ $my_product['souscription'] }}
                                        </div>
                                    </div>
                                    <div class="content-label-info">
                                        <div class="label-">Date d'échéance:</div>
                                        <div class="response-"> {{ $my_product['date_echeance'] }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="content-bloc-list-produit">
                        <h3>MES SOUSCRIPTIONS FCP</h3>
                        @foreach ($productsWithGains as $my_product)
                            @if ($my_product['type_product'] == 1)
                                <div class="item-product">
                                    <div class="content-link-title">
                                        <a href="{{ route('product-detail-gain', ['slug' => $my_product['slug']]) }}"
                                            class="flex flex-space-between-center">
                                            <span>{{ $my_product['product_name'] }}</span>
                                            <span> <i class="las la-arrow-right duration-300 group-hover:pl-2"></i></span>
                                        </a>
                                    </div>
                                    <div class="inner-header">
                                        <div class="content-label-info">
                                            <div class="label-">Investissement initial (Brut) :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['montant_transaction'], 0, ' ', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Nombre de parts :</div>
                                            <div class="response-">
                                                {{ number_format($my_product['nb_part'], 6, ',', ' ') }}</div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">VL souscription :</div>
                                            <div class="response-">
                                                XAF {{ number_format($my_product['vl_achat'], 2, ',', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">VL actuelle :</div>
                                            <div class="response-">
                                                XAF
                                                {{ number_format((float) $my_product['vl_actuel'], 2, ',', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Gain cumulé :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['total_gains_fcp'], 0, ' ', ' ') }}</div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Gain de la semaine :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['gain_semaine_fcp'], 0, ' ', ' ') }}</div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="response-">XAF
                                                {{ number_format($my_product['portfolio_valeur'], 0, ' ', ' ') }}
                                            </div>
                                        </div>

                                        <div class="content-label-info">
                                            <div class="label-">Date de souscription:</div>
                                            <div class="response-"> {{ $my_product['souscription'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

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
