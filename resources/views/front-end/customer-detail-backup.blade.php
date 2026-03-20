@extends('front-end/app/app-home-asset', ['title' => $customer->name . ' | Clients ', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page other-page'])


@section('content')
    <main class="main-content has-sidebar ">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <p><a href="{{ route('customer') }}" style="font-size:15px; color: #ebb009">
                        < Retour</a>
                </p>
                <h3>CLIENTS/ {{ $customer->name }} </h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4">
                <p style="text-align: right">{{ date('d-m-Y') }}</p>
                <div class="content-right">
                    <button class="btn ac-modal-btn buy">
                        <a href="{{ route('products-customer', ['customer' => $customer->id]) }}">SOUSCRIRE</a>
                    </button>
                </div>
            </div>

        </div>
        <div class="content-separator" style="height:30px">

        </div>
        <!-- Transaction account -->
        <div class="grid grid-cols-12 gap-4 xxl:gap-6 client-page">
            <!-- Statistics -->
            <div class="box col-span-3 bg-n0 dark:bg-bg4 min-[650px]:col-span-6 xxxl:col-span-3">
                <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                    <span class="font-medium">Portefeuille total</span>

                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="h4 mb-4">
                            XAF
                            {{ number_format(Session::get('pf_pmg') + Session::get('pf_fcp'), 0, ' ', ' ') }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="box col-span-3 bg-n0 dark:bg-bg4 min-[650px]:col-span-6 xxxl:col-span-3">
                <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                    <span class="font-medium">Portefeuille PMG</span>

                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="h4 mb-4"> XAF
                            @if (Session::has('pf_pmg'))
                                {{ number_format(Session::get('pf_pmg'), 0, ' ', ' ') }}
                            @else
                                {{ number_format($customer->gain_pmg, 0, ' ', ' ') }}
                            @endif

                        </h4>
                    </div>
                </div>
            </div>
            <div class="box col-span-3 bg-n0 dark:bg-bg4 min-[650px]:col-span-6 xxxl:col-span-3">
                <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                    <span class="font-medium">Portefeuille FCP</span>

                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="h4 mb-4">XAF

                            @if (Session::has('pf_fcp'))
                                {{ number_format(Session::get('pf_fcp'), 0, ' ', ' ') }}
                            @else
                                {{ number_format($customer->gain + $customer->solde, 0, ' ', ' ') }}
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
            <div class="box col-span-3 bg-n0 dark:bg-bg4 min-[650px]:col-span-6 xxxl:col-span-3">
                <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                    <span class="font-medium">Nombre de produits</span>

                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="h4 mb-4">{{ $product_nb }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-separator" style="height:30px">

        </div>

        <div class="content-my-products-wrapper flex">
            <div class="content-bloc-list-produit">
                <h3>MES PRODUITS PMG</h3>
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
                                        {{ number_format($my_product['gain_month'] + $my_product['soulte'] - $my_product['montant_transaction'], 0, ' ', ' ') }}
                                    </div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">Portofolio :</div>
                                    <div class="response-">XAF
                                        {{ number_format($my_product['gain_month'] + $my_product['soulte'], 0, ' ', ' ') }}
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
                <h3>MES PRODUITS FCP</h3>
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
                                    <div class="label-">VL achat:</div>
                                    <div class="response-">
                                        XAF {{ (float) $my_product['vl_achat'] }}
                                    </div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">VL actuelle :</div>
                                    <div class="response-">
                                        XAF {{ (float) $my_product['vl_actuel'] }}
                                    </div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">Gain cumulé :</div>
                                    <div class="response-">XAF
                                        {{ number_format(floor($my_product['total_gains_fcp']), 0, ' ', ' ') }}</div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">Gain de la semaine :</div>
                                    <div class="response-">XAF
                                        {{ number_format(floor($my_product['gain_semaine_fcp']), 0, ' ', ' ') }}</div>
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

        {{-- <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="box col-span-12 bg-n0 dark:bg-bg4 min-[650px]:col-span-6 xxxl:col-span-3">
                <div class="flex items-center justify-between">
                    <div class="content-profil">
                        @php
                            $initial = explode(' ', $customer->name);
                            $min = '';

                        @endphp
                        @foreach ($initial as $val)
                            {{ str_replace(' ', '', mb_substr($val, 0, 1)) }}
                        @endforeach

                    </div>
                </div>
            </div>
            <div class="box col-span-12 bg-n0 dark:bg-bg4 min-[650px]:col-span-6 xxxl:col-span-3">
                <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                    <span class="font-medium">Total Spending</span>
                    <div class="relative">
                        <i class="las la-ellipsis-h horiz-option-btn cursor-pointer"></i>
                        <ul
                            class="horiz-option hide absolute top-full z-[3] min-w-[122px] rounded-md bg-n0 p-3 shadow-[0px_6px_30px_0px_rgba(0,0,0,0.08)] duration-300 dark:bg-bg3 ltr:right-0 ltr:origin-top-right rtl:left-0 rtl:origin-top-left">
                            <li>
                                <span
                                    class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                    Edit
                                </span>
                            </li>
                            <li>
                                <span
                                    class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                    Print
                                </span>
                            </li>
                            <li>
                                <span
                                    class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                    Share
                                </span>
                            </li>
                        </ul>
                    </div>

                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="h4 mb-4">$2540 USD</h4>
                        <span class="flex items-center gap-1 whitespace-nowrap text-primary">
                            <i class="las la-arrow-up text-lg"></i> 35.7 AVG
                        </span>
                    </div>
                    <div
                        class="-my-3 shrink-0 ltr:translate-x-3 xl:ltr:translate-x-7 xxxl:ltr:translate-x-2 4xl:ltr:translate-x-9 rtl:-translate-x-3 xl:rtl:-translate-x-7 xxxl:rtl:-translate-x-2 4xl:rtl:-translate-x-9">
                        <div class="progress-chart"></div>
                    </div>
                </div>
            </div>
        </div> --}}
    </main>
@endsection
