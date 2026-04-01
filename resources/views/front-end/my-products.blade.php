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



                    <div class="content-bloc-list-pr                        <div class="grid grid-cols-1 gap-4">
                        @foreach ($productsWithGains as $my_product)
                            @if ($my_product['type_product'] == 1)
                                <div class="bg-white rounded-2xl border border-n30 overflow-hidden shadow-sm hover:shadow-md transition-all">
                                    <div class="bg-gray-50/50 p-4 border-b border-n30 flex justify-between items-center">
                                        <h4 class="text-xs font-bold text-n900 uppercase italic">{{ $my_product['product_name'] }}</h4>
                                        <a href="{{ route('product-detail-gain', ['slug' => $my_product['slug']]) }}" class="text-primary group flex items-center gap-1 text-[10px] font-bold uppercase transition-all">
                                            Détails <i class="las la-arrow-right group-hover:translate-x-1 duration-200"></i>
                                        </a>
                                    </div>
                                    <div class="p-4">
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-[10px] text-n500 uppercase font-medium">Investissement initial</span>
                                                <span class="text-xs font-bold text-n900">XAF {{ number_format($my_product['montant_transaction'], 0, ' ', ' ') }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-[10px] text-n500 uppercase font-medium">Nombre de parts</span>
                                                <span class="text-sm font-bold text-primary italic">{{ round($my_product['nb_part'], 2) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-[10px] text-n500 uppercase font-medium">VL souscription</span>
                                                <span class="text-xs font-bold text-n800 italic">XAF {{ number_format(floor($my_product['vl_achat']), 0, ' ', ' ') }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-[10px] text-n500 uppercase font-medium">VL actuelle</span>
                                                <span class="text-xs font-bold text-secondary italic">XAF {{ number_format(floor((float) $my_product['vl_actuel']), 0, ' ', ' ') }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-[10px] text-n500 uppercase font-medium">Gain cumulé</span>
                                                <span class="text-xs font-bold text-success">{{ number_format($my_product['total_gains_fcp'], 0, ' ', ' ') }} XAF</span>
                                            </div>
                                            
                                            <div class="pt-2 border-t border-dashed border-n30 flex justify-between items-center">
                                                <span class="text-[10px] text-n900 font-bold uppercase">Portefeuille</span>
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
                                            <div class="label-">Investissement initial :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['montant_transaction'], 0, ' ', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Nombre de part :</div>
                                            <div class="response-">
                                                {{ round($my_product['nb_part'], 2) }}</div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">VL souscription :</div>
                                            <div class="response-">
                                                XAF {{ number_format(floor($my_product['vl_achat']), 0, ' ', ' ') }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">VL actuelle :</div>
                                            <div class="response-">
                                                XAF
                                                {{ number_format(floor((float) $my_product['vl_actuel']), 0, ' ', ' ') }}
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
                                            <div class="label-"> Portofolio :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['montant_transaction'] + floor($my_product['total_gains_fcp']), 0, ' ', ' ') }}
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
