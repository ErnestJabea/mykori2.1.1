@extends('front-end/app/app-home-asset', ['title' => $customer->name . ' | Clients ', 'body_class' => 'vertical
bg-secondary1/5 dark:bg-bg3 my-products-page other-page'])

@section('content')
<main class="main-content has-sidebar">
    <div class="grid grid-cols-12 gap-4 xxl:gap-6">
        <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
            <p><a href="{{ route('customer') }}" style="font-size:15px; color: #ebb009">
                    < Retour</a>
            </p>
            <h3>CLIENTS / {{ $customer->name }}</h3>
        </div>
        <div class="col-span-12 md:col-span-5 lg:col-span-4">
            <p style="text-align: right">{{ date('d-m-Y') }}</p>
            <div class="content-right">
                <button class="btn ac-modal-btn buy">
                    <a href="{{ route('transactions-client', ['customer' => $customer->id]) }}">TRANSACTIONS</a>
                </button>
                <button class="btn ac-modal-btn buy">
                    <a href="{{ route('products-customer', ['customer' => $customer->id]) }}">SOUSCRIRE</a>
                </button>
            </div>
        </div>
    </div>

    <div class="content-separator" style="height:30px"></div>

    <div class="grid grid-cols-12 gap-4 xxl:gap-6 client-page flex flex-wrap">
        <div class="box box-card col-span-12 md:col-span-3 bg-primary/5">
            <p class="text-xs opacity-70 uppercase font-bold text-primary">Portefeuille Total</p>
            <h4 class="h4">{{ number_format($portefeuille_total, 0, ' ', ' ') }} XAF</h4>
        </div>

        <div class="box box-card col-span-12 md:col-span-3">
            <p class="text-xs opacity-70 uppercase">Portefeuille PMG</p>
            <h4 class="h4">{{ number_format($portefeuille_pmg, 0, ' ', ' ') }} XAF</h4>
        </div>

        <div class="box box-card col-span-12 md:col-span-3">
            <p class="text-xs opacity-70 uppercase">Portefeuille FCP</p>
            <h4 class="h4">{{ number_format($portefeuille_fcp, 0, ' ', ' ') }} XAF</h4>
        </div>

        <div class="box box-card col-span-12 md:col-span-3 border-l-4 border-primary">
            <p class="text-xs opacity-70 uppercase text-primary font-bold">Intérêts Générés</p>
            <h4 class="h4 text-primary">+ {{ number_format($total_interets, 0, ' ', ' ') }} XAF</h4>
        </div>
    </div>

    <div class="content-separator" style="height:30px"></div>

    <div class="flex flex-wrap">
        <div class="content-bloc-list-produit ">
            <div class="box ">
                <h3 class="mb-4">MES PRODUITS PMG ACTIFS</h3>
                <div class="content-inner-wrapper flex flex-wrap">
                @foreach ($productsWithGains as $my_product)

                    @if ($my_product['type_product'] == 2)
                    <div class="item-product">
                        <div class="content-link-title">
                            <a href="#!" class="flex flex-space-between-center">
                                <span>{{ $my_product['product_name'] }}</span>
                            </a>
                        </div>
                        <div class="inner-header">
                            <div class="content-label-info">
                                <div class="label-">Investissement initial :</div>
                                <div class="response-">XAF {{ number_format($my_product['capital_investi'], 0, ' ', ' ')
                                    }}
                                </div>
                            </div>
                            <div class="content-label-info">
                                <div class="label-">Taux d'intérêt :</div>
                                <div class="response-">{{ $my_product['vl_achat'] }}%</div>
                            </div>
                            <div class="content-label-info">
                                <div class="label-">Durée :</div>
                                <div class="response-">{{ $my_product['days_months']['months'] }} mois</div>
                            </div>
                            <div class="content-label-info">
                                <div class="label-">Intérêt mensuel :</div>
                                <div class="response-">XAF {{ number_format(($my_product['capital_investi'] *
                                    ($my_product['vl_achat'] / 100)) / 12, 0, ' ', ' ') }}</div>
                            </div>
                            <div class="content-label-info">
                                <div class="label-">Gains cumulés :</div>
                                <div class="response-">XAF {{ number_format($my_product['interets_generes'], 0, ' ', '
                                    ') }}
                                </div>
                            </div>
                            <div class="content-label-info">
                                <div class="label-">Portofolio :</div>
                                <div class="response-">XAF {{ number_format($my_product['portfolio_valeur'], 0, ' ', '
                                    ') }}
                                </div>
                            </div>
                        </div>
                        <div class="content-label-info">
                            <div class="label-">Date de valeur:</div>
                            <div class="response-">{{ $my_product['souscription'] }}</div>
                        </div>
                        <div class="content-label-info">
                            <div class="label-">Date d'échéance:</div>
                            <div class="response-">{{ $my_product['date_echeance'] }}</div>
                        </div>
                    </div>
                    {{-- <div class="item-product mb-4 p-4 border rounded bg-white dark:bg-bg4">
                        <div class="inner-header">
                            <div class="flex justify-between mb-2">
                                <span class="label-">Capital Investi :</span>
                                <span class="response font-bold">XAF {{ number_format($my_product['capital_investi'], 0,
                                    '
                                    ', ' ') }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="label-">Taux Annuel :</span>
                                <span class="response">{{ $my_product['vl_achat'] }}%</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="label-">Intérêts Courus :</span>
                                <span class="response text-primary">+ XAF {{
                                    number_format($my_product['interets_generes'],
                                    0, ' ', ' ') }}</span>
                            </div>
                            <div class="flex justify-between font-bold pt-2 border-t border-dashed">
                                <span class="label-">VALEUR ACTUELLE :</span>
                                <span class="response">XAF {{ number_format($my_product['portfolio_valeur'], 0, ' ', '
                                    ')
                                    }}</span>
                            </div>
                        </div>
                    </div> --}}
                    @endif
                @endforeach
                </div>
            </div>
        </div>

        <div class="content-bloc-list-produit">
            <h3>MES PRODUITS FCP ACTIFS</h3>
            @foreach ($productsWithGains as $my_product)
            @if ($my_product['type_product'] == 1)
            <div class="item-product">
                <div class="content-link-title">
                    <a href="{{ route('product-detail-gain', ['slug' => $my_product['slug'] ?? 'fcp']) }}"
                        class="flex flex-space-between-center">
                        <span>{{ $my_product['product_name'] }}</span>
                        <span><i class="las la-arrow-right"></i></span>
                    </a>
                </div>
                <div class="inner-header">
                    <div class="content-label-info">
                        <div class="label-">Capital Investi :</div>
                        <div class="response-">XAF {{ number_format($my_product['capital_investi'], 0, ' ', ' ') }}
                        </div>
                    </div>
                    <div class="content-label-info">
                        <div class="label-">Nombre de parts :</div>
                        <div class="response-">{{ number_format($my_product['nb_part'], 4, '.', ' ') }}</div>
                    </div>
                    <div class="content-label-info">
                        <div class="label-">VL Actuelle :</div>
                        <div class="response-">XAF {{ number_format($my_product['vl_actuel'], 2, '.', ' ') }}</div>
                    </div>
                    <div class="content-label-info font-bold" style="border-top: 1px dashed #ccc; padding-top: 5px;">
                        <div class="label-">VALEUR PORTFOLIO :</div>
                        <div class="response-">XAF {{ number_format($my_product['portfolio_valeur'], 0, ' ', ' ') }}
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</main>
@endsection