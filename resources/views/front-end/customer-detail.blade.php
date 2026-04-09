@extends('front-end/app/app-home-asset', [
    'title' => $customer->name . ' | Clients ',
    'body_class' => 'vertical
bg-secondary1/5 dark:bg-bg3 my-products-page other-page',
])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6 items-center">
            <div class="col-span-12 md:col-span-4 lg:col-span-5">
                <p><a href="{{ route('customer') }}" style="font-size:15px; color: #ebb009">
                        < Retour</a>
                </p>
                <h3>CLIENTS / {{ $customer->name }}</h3>
            </div>

            <div class="col-span-12 md:col-span-8 lg:col-span-7">
                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('customer-history.pdf', ['customer_id' => $customer->id]) }}" class="btn buy shadow-sm"
                        style="background-color: #00466a; color:white; border-radius: 12px; padding: 12px 20px;">
                        <i class="las la-file-invoice-doll"></i> HISTORIQUE DE TRANSACTIONS
                    </a>
                    <a href="{{ route('transactions-client', ['customer' => $customer->id]) }}" class="btn buy shadow-sm"
                        style="background-color: #531d09; color:white; border-radius: 12px; padding: 12px 20px;">
                        <i class="las la-exchange-alt"></i> TRANSACTIONS
                    </a>
                    <button class="btn add-placement-btn buy shadow-sm"
                        style="background-color: #ebb009; color:white; border-radius: 12px; padding: 12px 20px;">
                        <i class="las la-plus-circle"></i> AJOUTER UN PLACEMENT
                    </button>
                </div>
            </div>
        </div>

        <!-- NEW PLACEMENT MODAL -->
        <div
            class="ac-modal-overlay placement-modal fixed inset-0 z-[99] modalhide bg-black/60 duration-500 overflow-y-auto flex items-center justify-center p-4">
            <div
                class="modal box modal-inner relative w-full max-w-[750px] bg-white dark:bg-bg3 rounded-3xl shadow-2xl p-6 md:p-10 duration-300">
                <button class="ac-modal-close-btn absolute top-4 right-4 text-3xl hover:text-red-500 duration-300">
                    <i class="las la-times text-2xl"></i>
                </button>
                <div class="bb-dashed mb-6 pb-4 border-b border-dashed border-gray-300">
                    <h4 class="h4 text-xl font-bold">Nouvelle Souscription</h4>
                    <p class="text-xs opacity-70 mt-1">Client : <span
                            class="text-primary font-bold">{{ $customer->name }}</span></p>
                </div>

                <div id="modal-response" class="alert hidden mb-4 p-4 rounded-xl"></div>

                <form id="new-placement-form-detail">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <div class="flex flex-wrap">
                        <div class="form-control-wrapper">
                            <!-- Type Selection -->
                            <div class="col-span-1 form-control">
                                <label class="mb-2 block text-sm font-semibold opacity-80">Type de produit</label>
                                <select id="type_produit" name="type_produit"
                                    class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary">
                                    <option value="">Sélectionner...</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->abreviation }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Product Selection -->
                            <div class="col-span-1 form-control">
                                <label class="mb-2 block text-sm font-semibold opacity-80">Produit</label>
                                <select id="select_produit" name="product_id"
                                    class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary"
                                    disabled>
                                    <option value="">Choisir un type</option>
                                </select>
                            </div>

                            <!-- Type de souscription (FCP only) -->
                            <div class="col-span-1 form-control hidden" id="type_souscription_container">
                                <label class="mb-2 block text-sm font-semibold opacity-80">Type de souscription</label>
                                <select id="type_souscription" name="type_souscription"
                                    class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary">
                                    <option value="ponctuelle">Ponctuelle (Simple)</option>
                                    <option value="mensuelle">Mensuelle (Récurrente)</option>
                                </select>
                            </div>

                        </div>

                        <div class="form-control-wrapper">

                            <!-- Dynamic Field: VL or Taux -->
                            <div class="col-span-1 form-control" id="vl_taux_container">
                                <label id="label_vl_taux" class="mb-2 block text-sm font-semibold opacity-80">Valeur /
                                    Taux</label>
                                <input type="text" id="vl_taux_input" name="vl_taux"
                                    class="w-full rounded-xl border border-n30 bg-gray-100 dark:bg-bg4 px-4 py-3 outline-none transition-all"
                                    readonly>
                            </div>

                            <!-- Amount -->
                            <div class="col-span-1 form-control">
                                <label class="mb-2 block text-sm font-semibold opacity-80">Montant investi (XAF)</label>
                                <input type="number" name="amount" id="amount_input"
                                    class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary font-bold text-primary"
                                    placeholder="Ex: 5 000 000" required>
                            </div>

                        </div>

                        <div class="form-control-wrapper">
                            <!-- Date Valeur -->
                            <div
                                class="col-span-1 form-control border-t border-dashed mt-2 pt-2 md:border-t-0 md:mt-0 md:pt-0">
                                <label class="mb-2 block text-sm font-semibold opacity-80">Date de valeur</label>
                                <input type="text" name="date_valeur" id="datepicker_valeur" value="{{ date('Y-m-d') }}"
                                    readonly
                                    class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary"
                                    required>
                            </div>

                            <!-- Date Echeance -->
                            <div class="col-span-1 form-control border-t border-dashed mt-2 pt-2 md:border-t-0 md:mt-0 md:pt-0"
                                id="date_echeance_container">
                                <label class="mb-2 block text-sm font-semibold opacity-80">Date d'échéance</label>
                                <input type="text" name="date_echeance" id="datepicker_echeance" readonly
                                    class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary">
                            </div>
                        </div>

                        <div class="form-control-wrapper justify-between align-center">
                            <!-- Summary Area -->
                            <div id="placement-summary"
                                class="hidden mt-4 mb-4 p-4 rounded-2xl bg-gray-50 border border-dashed border-gray-300">

                                <!-- FCP Results -->
                                <div id="summary-fcp-only" class="hidden">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold opacity-70">VL à la date sélectionnée : </span>
                                        <span id="summary-vl-fcp" class="font-bold text-primary">0 XAF</span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold opacity-70">Nombre de parts : </span>
                                        <span id="summary-parts" class="font-bold text-primary">0</span>
                                    </div>
                                    <div class="flex justify-between items-center mt-3 pt-2 border-t border-dashed">
                                        <span id="label-fees-fcp" class="text-xs opacity-60">Frais de souscription :
                                        </span>
                                        <span id="summary-fees-fcp" class="text-xs font-bold opacity-60">0 XAF</span>
                                    </div>
                                </div>

                                <!-- PMG Results -->
                                <div id="summary-pmg-only" class="hidden">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold opacity-70">Taux appliqué : </span>
                                        <span id="summary-vl-pmg" class="font-bold text-primary">0 %</span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold opacity-70">Gain Mensuel Net : </span>
                                        <span id="summary-monthly" class="font-bold text-secondary">0 XAF</span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold opacity-70">Gain à l'échéance : </span>
                                        <span id="summary-annual" class="font-bold text-secondary">0 XAF</span>
                                    </div>
                                    <div class="flex justify-between items-center mt-3 pt-2 border-t border-dashed">
                                        <span id="label-fees-pmg" class="text-xs opacity-60">Frais de souscription :
                                        </span>
                                        <span id="summary-fees-pmg" class="text-xs font-bold opacity-60">0 XAF</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 full-width">
                                <button type="submit" id="btn-submit-placement"
                                    class="btn w-full justify-center bg-primary text-white py-4 rounded-xl hover:bg-primary/90 duration-300 font-bold tracking-wider shadow-lg">
                                    ENREGISTRER LA SOUSCRIPTION
                                </button>
                            </div>

                        </div>

                    </div>
                </form>
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
                                            <span>Souscription : {{ $my_product['product_name'] }}</span>
                                        </a>
                                    </div>
                                    <div class="inner-header">
                                        <div class="content-label-info">
                                            <div class="label-">Investissement initial (Brut) :</div>
                                            <div class="response-">XAF
                                                {{ number_format($my_product['capital_investi'], 0, ' ', ' ') }}
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
                                        <div class="response-">
                                            {{ \Carbon\Carbon::parse($my_product['souscription'])->format('d/m/Y') }}</div>
                                    </div>
                                    <div class="content-label-info">
                                        <div class="label-">Date d'échéance:</div>
                                        <div class="response-">
                                            {{ \Carbon\Carbon::parse($my_product['date_echeance'])->format('d/m/Y') }}
                                        </div>
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
                <div class="box">
                    <h3>MES PRODUITS FCP ACTIFS</h3>
                    @foreach ($productsWithGains as $my_product)
                        @if ($my_product['type_product'] == 1)
                            <div class="item-product">
                                <div class="content-link-title">
                                    <a href="{{ route('product-detail-gain', ['slug' => $my_product['slug'] ?? 'fcp']) }}"
                                        class="flex flex-space-between-center">
                                        <span>Souscription : {{ $my_product['product_name'] }}</span>
                                        <span><i class="las la-arrow-right"></i></span>
                                    </a>
                                </div>
                                <div class="inner-header">
                                    <div class="content-label-info">
                                        <div class="label-">Date de souscription :</div>
                                        <div class="response-">
                                            {{ \Carbon\Carbon::parse($my_product['souscription'])->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    <div class="content-label-info">
                                        <div class="label-">Capital Total Investi (Brut) :</div>
                                        <div class="response-">XAF
                                            {{ number_format($my_product['capital_investi'], 0, ' ', ' ') }}
                                        </div>
                                    </div>
                                    <div class="content-label-info">
                                        <div class="label-">VL à la souscription :</div>
                                        <div class="response-">XAF
                                            {{ number_format($my_product['vl_achat'], 2, ',', ' ') }}
                                        </div>
                                    </div>
                                    <div class="content-label-info">
                                        <div class="label-">Nombre de parts :</div>
                                        <div class="response- text-primary font-bold">
                                            {{ number_format($my_product['nb_part'], 6, ',', ' ') }}
                                        </div>
                                    </div>
                                    <div class="content-label-info">
                                        <div class="label-">VL Actuelle :</div>
                                        <div class="response-">XAF
                                            {{ number_format($my_product['vl_actuel'], 2, ',', ' ') }}
                                            <span class="text-[10px] opacity-70 italic text-n600"> (du
                                                {{ \Carbon\Carbon::parse($my_product['date_vl_actuel'] ?? now())->format('d/m/Y') }})</span>
                                        </div>
                                    </div>
                                    <div class="content-label-info font-bold"
                                        style="border-top: 1px dashed #ccc; padding-top: 5px;">
                                        <div class="label-">VALEUR PORTFOLIO :</div>
                                        <div class="response-">XAF
                                            {{ number_format($my_product['portfolio_valeur'], 0, ' ', ' ') }}
                                        </div>
                                    </div>
                                    <div class="content-label-info font-bold text-green-600">
                                        <div class="label-">PLUS-VALUE :</div>
                                        <div class="response-"> XAF
                                            {{ number_format($my_product['total_gains_fcp'] ?? 0, 0, ' ', ' ') }}
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-end">
                                        <button type="button"
                                            onclick="showFcpEvolution({{ $my_product['product_id'] }}, '{{ $my_product['product_name'] }}')"
                                            class="text-[10px] font-bold text-secondary uppercase italic border border-secondary/30 px-3 py-1 rounded-lg hover:bg-secondary/10 transition-all">
                                            <i class="las la-chart-line"></i> Voir Évolution
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        </div>
        </div>

        <div class="content-separator" style="height:30px"></div>

        <!-- HISTORIQUE DES TRANSACTIONS & OPÉRATIONS -->
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12">
                <div class="box p-6 bg-n0 dark:bg-bg4 rounded-2xl shadow-sm border border-n30">
                    <h3 class="uppercase text-lg font-extrabold flex items-center gap-3 mb-6">
                        <i class="las la-list text-primary"></i> Historique des Transactions
                    </h3>
                    <div class="overflow-x-auto overflow-hidden rounded-2xl border border-n30">
                        <table class="w-full text-left">
                            <thead
                                class="bg-n10 text-[10px] uppercase font-bold text-n500 tracking-widest border-b border-n30">
                                <tr>
                                    <th class="px-5 py-4">Réf</th>
                                    <th class="px-5 py-4">Produit</th>
                                    <th class="px-5 py-4">Montant placement</th>
                                    <th class="px-5 py-4">VL / Taux</th>
                                    <th class="px-5 py-4">Date Valeur</th>
                                    <th class="px-5 py-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30">
                                @foreach ($allTransactionsHistory as $th)
                                    <tr class="hover:bg-n10/50 transition-all italic">
                                        <td class="px-5 py-4">
                                            <span class="text-xs font-bold text-n800">{{ $th->ref }}</span>
                                            @if ($th->is_supp)
                                                <br><span
                                                    class="bg-primary/10 text-primary text-[8px] px-2 py-0.5 rounded-full ml-1 font-bold">Ajout</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-sm font-bold text-n900">
                                            {{ optional($th->product)->title }}</td>
                                        <td class="px-5 py-4 text-sm font-bold text-n700">
                                            {{ number_format($th->amount, 0, ' ', ' ') }} XAF</td>
                                        <td class="px-5 py-4 text-xs">{{ $th->vl_buy }}
                                            {{ optional($th->product)->products_category_id == 1 ? 'XAF' : '%' }}</td>
                                        <td class="px-5 py-4 text-xs">
                                            {{ \Carbon\Carbon::parse($th->date_validation ?? $th->created_at)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <button type="button"
                                                onclick="openEditModal('{{ $th->id }}', '{{ $th->is_supp ? 'true' : 'false' }}', '{{ $th->ref }}', '{{ $th->amount }}', '{{ $th->vl_buy }}', '{{ \Carbon\Carbon::parse($th->date_validation ?? $th->created_at)->toDateString() }}', '{{ $th->date_echeance }}', '{{ $th->product_id }}', '{{ optional($th->product)->products_category_id }}')"
                                                class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 hover:bg-orange-200 transition-all flex items-center justify-center mx-auto">
                                                <i class="las la-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-span-12 mt-6">
                <div class="box p-6 bg-n0 dark:bg-bg4 rounded-2xl shadow-sm border border-n30">
                    <h3 class="mb-6 uppercase text-lg font-extrabold flex items-center gap-3">
                        <i class="las la-exchange-alt text-secondary"></i> Historique des Opérations Financières
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 font-Inter">
                        <div class="rounded-2xl border border-n30 overflow-hidden">
                            <div
                                class="bg-secondary/5 px-5 py-3 border-b border-n30 font-bold text-[10px] tracking-widest uppercase">
                                Mouvements PMG (CASH)</div>
                            <div class="max-h-[300px] overflow-y-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-n30">
                                        @foreach ($financialMovements as $fm)
                                            <tr class="text-[11px] hover:bg-n10 transition-all">
                                                <td class="px-4 py-3 font-bold">
                                                    {{ \Carbon\Carbon::parse($fm->date_operation)->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 opacity-60 uppercase">
                                                    {{ str_replace('_', ' ', $fm->type) }}</td>
                                                <td
                                                    class="px-4 py-3 text-right font-bold {{ $fm->amount < 0 ? 'text-red-500' : 'text-green-500' }}">
                                                    {{ number_format($fm->amount, 0, '.', ' ') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-n30 overflow-hidden">
                            <div
                                class="bg-primary/5 px-5 py-3 border-b border-n30 font-bold text-[10px] tracking-widest uppercase">
                                Mouvements FCP (PARTS)</div>
                            <div class="max-h-[300px] overflow-y-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-n30">
                                        @foreach ($fcpMovements as $fcm)
                                            <tr class="text-[11px] hover:bg-n10 transition-all">
                                                <td class="px-4 py-3 font-bold">
                                                    {{ \Carbon\Carbon::parse($fcm->date_operation)->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 opacity-60 uppercase">
                                                    {{ str_replace('_', ' ', $fcm->type) }}</td>
                                                <td
                                                    class="px-4 py-3 text-right font-bold {{ $fcm->nb_parts_change < 0 ? 'text-red-500' : 'text-primary' }}">
                                                    {{ ($fcm->nb_parts_change >= 0 ? '+' : '') . number_format($fcm->nb_parts_change, 2, ',', ' ') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-separator" style="height:30px"></div>

        <div class="grid grid-cols-12 gap-4 xxl:gap-6 mt-6">
            <div class="col-span-12">
                <div class="box rounded-2xl bg-n0 p-6 shadow-sm dark:bg-bg4">
                    <h3 class="mb-6 uppercase">RELEVÉS MENSUELS DU CLIENT</h3>
                    <div id="months-table-container">
                        @include('front-end.partials.customer-months-table')
                    </div>
                </div>

                <script>
                    $(document).on('click', '.ajax-pagination-detail .pagination a', function(e) {
                        e.preventDefault();
                        let url = $(this).attr('href');
                        let container = $('#months-table-container');

                        container.css('opacity', '0.5');
                        $.ajax({
                            url: url,
                            success: function(data) {
                                container.html(data);
                                container.css('opacity', '1');
                                // No pushState for detail sub-elements to avoid confusing URL
                            }
                        });
                    });
                </script>
            </div>
        </div>
        </div>



    </main>

    <!-- TRANSACTIONS MODALS -->
    <div id="modal-edit-transaction"
        class="ac-modal-overlay modalhide fixed inset-0 z-[100] bg-n900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="modal-inner bg-white dark:bg-bg4 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-n30 flex justify-between items-center bg-primary/10">
                <h3 class="text-xl font-bold text-n900 dark:text-n0">Modifier la Transaction</h3>
                <button type="button" onclick="closeEditModal()"
                    class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-white transition-all text-n500">
                    <i class="las la-times text-xl"></i>
                </button>
            </div>
            <div class="p-8">
                <form id="edit-transaction-form" class="space-y-5">
                    @csrf
                    <input type="hidden" name="trans_id" id="edit-trans-id">
                    <input type="hidden" name="is_supp" id="edit-is-supp">
                    <input type="hidden" id="edit-prod-id">
                    <input type="hidden" id="edit-cat-id">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic">Montant
                                de la Transaction</label>
                            <input type="number" step="0.01" name="amount" id="edit-amount"
                                class="w-full h-[50px] p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50">
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic"
                                id="edit-label-vl">VL / Taux d'intérêt</label>
                            <input type="number" step="0.000001" name="vl_buy" id="edit-vl"
                                class="w-full h-[50px] p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic">Date
                                de Valeur</label>
                            <input type="text" name="date_validation" id="edit-date-val"
                                class="w-full h-[50px] p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50"
                                readonly>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic">Date
                                d'Échéance</label>
                            <input type="text" name="date_echeance" id="edit-date-ech"
                                class="w-full h-[50px] p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50"
                                readonly>
                        </div>
                    </div>

                    <div id="edit-response-msg" class="hidden"></div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="closeEditModal()"
                            class="flex-1 h-[50px] rounded-xl border border-n30 text-n500 font-bold uppercase tracking-wider hover:bg-n10 transition-all">
                            Annuler
                        </button>
                        <button type="submit" id="btn-save-edit"
                            class="flex-1 h-[50px] rounded-xl bg-primary text-white font-bold uppercase tracking-wider hover:bg-primary/90 transition-all shadow-lg shadow-primary/20">
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODALE ÉVOLUTION FCP -->
    <div id="modal-evolution-fcp"
        class="ac-modal-overlay modalhide fixed inset-0 z-[100] bg-n900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div
            class="modal-inner bg-white dark:bg-bg4 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-modal-in">
            <div class="p-6 border-b border-n30 flex justify-between items-center bg-n10/50">
                <h3 class="text-xl font-bold text-n900 flex items-center gap-3 italic uppercase">
                    <i class="las la-history text-secondary text-2xl"></i>
                    Évolution : <span id="evo-product-name" class="text-primary italic">Produit</span>
                </h3>
                <button type="button" onclick="closeEvoModal()"
                    class="w-10 h-10 rounded-full hover:bg-n30 flex items-center justify-center text-n500 transition-all">
                    <i class="las la-times text-2xl"></i>
                </button>
            </div>
            <div class="p-8">
                <div id="evo-loader" class="py-10 text-center">
                    <i class="las la-spinner la-spin text-4xl text-primary"></i>
                    <p class="mt-4 text-n500 italic">Chargement des données...</p>
                </div>
                <div id="evo-content" class="hidden">
                    <div class="max-h-[400px] overflow-y-auto rounded-xl border border-n30">
                        <table class="w-full text-left">
                            <thead class="bg-n10 sticky top-0 border-b border-n30">
                                <tr class="text-[10px] font-bold uppercase text-n500 italic">
                                    <th class="px-5 py-4">Date</th>
                                    <th class="px-5 py-4">VL</th>
                                    <th class="px-5 py-4">Parts</th>
                                    <th class="px-5 py-4">Valorisation</th>
                                    <th class="px-5 py-4 text-right">Plus-value</th>
                                </tr>
                            </thead>
                            <tbody id="evo-table-body" class="divide-y divide-n30"></tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-[10px] text-n400 flex items-center gap-2 italic">
                        <i class="las la-info-circle text-primary"></i>
                        Cette table présente la valorisation et la plus-value cumulée à chaque date de VL.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script_front_end')
    <script>
        const productsList = @json($products);
        const ownedPmgProductIds = @json($ownedPmgProductIds);

        function openEditModal(id, isSupp, ref, amount, vl, dateVal, dateEch, prodId, catId) {
            console.log("Opening edit modal for:", ref);
            const modal = $('#modal-edit-transaction');
            if (!modal.length) {
                console.error("Modal #modal-edit-transaction not found in DOM");
                return;
            }

            $('#edit-trans-id').val(id);
            $('#edit-is-supp').val(isSupp);
            $('#edit-prod-id').val(prodId);
            $('#edit-cat-id').val(catId);
            $('#edit-trans-ref').text(ref);
            $('#edit-amount').val(amount);
            $('#edit-vl').val(vl);
            $('#edit-date-val').val(dateVal);
            $('#edit-date-ech').val(dateEch);

            // Hide/Show expiry for PMG/FCP
            if (catId == 1) {
                $('#edit-date-ech').parent().hide();
                $('#edit-vl').prop('readonly', true).addClass('bg-gray-100');
            } else {
                $('#edit-date-ech').parent().show();
                $('#edit-vl').prop('readonly', false).removeClass('bg-gray-100');
            }

            modal.removeClass('modalhide').addClass('modalshow').css('display', 'flex');
        }

        function closeEditModal() {
            $('#modal-edit-transaction').addClass('modalhide').removeClass('modalshow').hide();
        }

        function showFcpEvolution(productId, productName) {
            const modal = document.getElementById('modal-evolution-fcp');
            document.getElementById('evo-product-name').textContent = productName;
            modal.classList.remove('modalhide');
            modal.classList.add('modalshow');
            $.ajax({
                url: `/api/fcp-evolution/${productId}/{{ $customer->id }}`,
                success: function(r) {
                    document.getElementById('evo-loader').classList.add('hidden');
                    document.getElementById('evo-content').classList.remove('hidden');
                    let html = '';
                    r.history.reverse().forEach(row => {
                        const plusValueClass = row.plus_value >= 0 ? 'text-green-600' : 'text-red-500';
                        const prefix = row.plus_value >= 0 ? '+' : '';
                        html += `<tr class="text-[11px] hover:bg-n10 transition-all italic">
                            <td class="px-5 py-4 font-bold border-b border-n30">${row.date}</td>
                            <td class="px-5 py-4 border-b border-n30">${row.vl.toLocaleString('fr-FR')} XAF</td>
                            <td class="px-5 py-4 font-bold text-primary border-b border-n30">${Number(row.parts).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 6})}</td>
                            <td class="px-5 py-4 font-bold text-n900 border-b border-n30">${row.valuation.toLocaleString('fr-FR')} XAF</td>
                            <td class="px-5 py-4 text-right font-bold ${plusValueClass} border-b border-n30">${prefix}${row.plus_value.toLocaleString('fr-FR')} XAF</td>
                        </tr>`;
                    });
                    document.getElementById('evo-table-body').innerHTML = html ||
                        '<tr><td colspan="5" class="py-10 text-center text-n400 italic font-bold">Aucune donnée historique trouvée pour ce produit.</td></tr>';
                }
            });
        }

        function closeEvoModal() {
            document.getElementById('modal-evolution-fcp').classList.add('modalhide');
        }

        function fetchVlForEdit(productId, date) {
            const catId = $('#edit-cat-id').val();
            $('#edit-vl').addClass('opacity-50').prop('readonly', true);
            $.ajax({
                url: `/api/product-vl/${productId}/${date}`,
                success: function(r) {
                    $('#edit-vl').val(r.vl).removeClass('opacity-50');
                    if (catId == 1) $('#edit-vl').prop('readonly', true);
                    else $('#edit-vl').prop('readonly', false);
                },
                error: function() {
                    $('#edit-vl').removeClass('opacity-50');
                    if (catId == 1) $('#edit-vl').prop('readonly', true);
                    else $('#edit-vl').prop('readonly', false);
                }
            });
        }

        function fetchVlAtDate(productId, date) {
            $('#vl_taux_input').val("Chargement...");
            $.ajax({
                url: `/api/product-vl/${productId}/${date}`,
                success: function(r) {
                    $('#vl_taux_input').val(r.vl);
                    if (typeof updateSummary === 'function') updateSummary();
                },
                error: function() {
                    $('#vl_taux_input').val("");
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Edit Form Submit
            document.getElementById('edit-transaction-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('btn-save-edit');
                const resp = document.getElementById('edit-response-msg');
                btn.disabled = true;
                btn.textContent = "Mise à jour...";

                $.ajax({
                    url: "{{ route('customer.transaction.edit') }}",
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(data) {
                        resp.textContent = data.message;
                        resp.className =
                            "block bg-green-100 text-green-700 p-3 rounded-xl text-xs font-bold uppercase italic mt-4";
                        resp.classList.remove('hidden');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function() {
                        resp.textContent = "Erreur lors de la modification.";
                        resp.className =
                            "block bg-red-100 text-red-700 p-3 rounded-xl text-xs font-bold uppercase italic mt-4";
                        resp.classList.remove('hidden');
                        btn.disabled = false;
                        btn.textContent = "Sauvegarder";
                    }
                });
            });

            const addBtn = document.querySelector(".add-placement-btn");
            const modalOverlay = document.querySelector(".ac-modal-overlay");
            const closeBtn = document.querySelector(".ac-modal-close-btn");

            if (addBtn && modalOverlay) {
                addBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modalOverlay.classList.remove("modalhide");
                    modalOverlay.classList.add("modalshow");
                });
            }

            if (closeBtn && modalOverlay) {
                closeBtn.addEventListener('click', function() {
                    modalOverlay.classList.add("modalhide");
                    modalOverlay.classList.remove("modalshow");
                });
            }

            const typeSelect = document.getElementById('type_produit');
            const prodSelect = document.getElementById('select_produit');
            const vlTauxInput = document.getElementById('vl_taux_input');
            const labelVlTaux = document.getElementById('label_vl_taux');
            const echeanceContainer = document.getElementById('date_echeance_container');
            const echeanceInput = document.getElementById('datepicker_echeance');

            // Datepickers for edit modal
            if (typeof datepicker === 'function') {
                datepicker('#edit-date-val', {
                    formatter: (i, d) => {
                        i.value = d.toISOString().split('T')[0];
                    },
                    onSelect: (instance, date) => {
                        const y = date.getFullYear();
                        const m = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        const val = `${y}-${m}-${day}`;

                        instance.el.value = val;
                        const catId = $('#edit-cat-id').val();
                        const prodId = $('#edit-prod-id').val();
                        if (catId == 1 && prodId) {
                            fetchVlForEdit(prodId, val);
                        }
                    }
                });
                datepicker('#edit-date-ech', {
                    formatter: (i, d) => {
                        i.value = d.toISOString().split('T')[0];
                    }
                });
            }

            const pickerValeur = datepicker('#datepicker_valeur', {
                formatter: (input, date, instance) => {
                    const value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2,
                        '0') + '-' + String(date.getDate()).padStart(2, '0');
                    input.value = value;
                },
                startDay: 1,
                customDays: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                customMonths: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août',
                    'Septembre', 'Octobre', 'Novembre', 'Décembre'
                ],
                overlayButton: "Valider",
                overlayPlaceholder: "Année (4 chiffres)",
                onSelect: (instance, date) => {
                    const value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2,
                        '0') + '-' + String(date.getDate()).padStart(2, '0');
                    instance.el.value = value;

                    const prodId = prodSelect.value;
                    if (prodId && typeSelect.value == 1) { // 1 = FCP
                        fetchVlAtDate(prodId, value);
                    } else if (prodId && typeSelect.value == 2) {
                        // SUGGEST expiry for PMG only if empty
                        if (!echeanceInput.value) calculateExpiry();
                    }
                    updateSummary();
                }
            });

            const pickerEcheance = datepicker('#datepicker_echeance', {
                formatter: (input, date, instance) => {
                    input.value = date.toISOString().split('T')[0];
                },
                onSelect: (instance, date) => {
                    instance.el.value = date.toISOString().split('T')[0];
                    updateSummary();
                }
            });

            function calculateExpiry() {
                const dateValStr = document.getElementById('datepicker_valeur').value;
                const productId = prodSelect.value;
                const product = productsList.find(p => p.id == productId);

                if (dateValStr && product && product.duree) {
                    const dateVal = new Date(dateValStr);
                    const duree = parseInt(product.duree);
                    dateVal.setMonth(dateVal.getMonth() + duree);
                    const expiryStr = dateVal.toISOString().split('T')[0];
                    document.getElementById('datepicker_echeance').value = expiryStr;
                    pickerEcheance.setDate(dateVal);
                }
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    const catId = this.value;
                    prodSelect.innerHTML = '<option value="">Choisir un produit...</option>';

                    if (catId) {
                        prodSelect.disabled = false;
                        const filtered = productsList.filter(p => {
                            if (p.products_category_id == catId) {
                                if (catId == 2 && ownedPmgProductIds.includes(p.id)) return false;
                                return true;
                            }
                            return false;
                        });

                        filtered.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.textContent = p.title;
                            prodSelect.appendChild(opt);
                        });

                        if (catId == 1) { // FCP
                            labelVlTaux.textContent = "Valeur Liquidative (Dernière)";
                            vlTauxInput.readOnly = true;
                            vlTauxInput.classList.add('bg-gray-100');
                            echeanceContainer.style.opacity = "0.5";
                            echeanceInput.disabled = true;
                            document.getElementById('type_souscription_container').classList.remove(
                                'hidden');
                        } else { // PMG
                            labelVlTaux.textContent = "Taux d'intérêt (%)";
                            vlTauxInput.readOnly = false;
                            vlTauxInput.classList.remove('bg-gray-100');
                            echeanceContainer.style.opacity = "1";
                            echeanceInput.disabled = false;
                            document.getElementById('type_souscription_container').classList.add('hidden');
                        }
                    } else {
                        prodSelect.disabled = true;
                    }
                });
            }

            if (prodSelect) {
                prodSelect.addEventListener('change', function() {
                    const productId = this.value;
                    if (productId && typeSelect.value == 2) {
                        calculateExpiry();
                    }
                    updateSummary();
                });
            }

            const amountInput = document.getElementById('amount_input');
            if (amountInput) amountInput.addEventListener('input', updateSummary);
            if (vlTauxInput) vlTauxInput.addEventListener('input', updateSummary);

            function updateSummary() {
                const total_input = parseFloat(amountInput.value) || 0;
                const vl_raw = vlTauxInput.value;
                const productId = prodSelect.value;
                const product = productsList.find(p => p.id == productId);
                const feeRate = product ? (parseFloat(product.free) || 0) : 0;

                if (vl_raw === "Chargement...") return;

                const vl_taux = parseFloat(vl_raw) || 0;
                const typeId = typeSelect.value;
                const summaryDiv = document.getElementById('placement-summary');

                if (total_input > 0 && typeId) {
                    summaryDiv.classList.remove('hidden');
                    let fees = (total_input * feeRate) / 100;
                    let net_invested = total_input - fees;
                    if (typeId == 2) {
                        fees = 0;
                        net_invested = total_input;
                    }

                    if (typeId == 1) { // FCP
                        document.getElementById('summary-fcp-only').classList.remove('hidden');
                        document.getElementById('summary-pmg-only').classList.add('hidden');
                        document.getElementById('summary-vl-fcp').textContent = vl_taux.toLocaleString('fr-FR') +
                            ' XAF';
                        document.getElementById('summary-fees-fcp').textContent = fees.toLocaleString('fr-FR') +
                            ' XAF';
                        if (vl_taux > 0) document.getElementById('summary-parts').textContent = (net_invested /
                            vl_taux).toFixed(4);
                    } else if (typeId == 2) { // PMG
                        document.getElementById('summary-fcp-only').classList.add('hidden');
                        document.getElementById('summary-pmg-only').classList.remove('hidden');
                        document.getElementById('summary-vl-pmg').textContent = vl_taux + ' %';
                        document.getElementById('summary-fees-pmg').textContent = fees.toLocaleString('fr-FR') +
                            ' XAF';

                        const dateValStr = document.getElementById('datepicker_valeur').value;
                        const dateEcheanceStr = document.getElementById('datepicker_echeance').value;
                        if (dateValStr && dateEcheanceStr) {
                            const diffDays = Math.ceil((new Date(dateEcheanceStr) - new Date(dateValStr)) / (1000 *
                                60 * 60 * 24));
                            if (diffDays > 0) {
                                document.getElementById('summary-monthly').textContent = (net_invested * (vl_taux /
                                    100) / 12).toLocaleString('fr-FR') + ' XAF';
                                document.getElementById('summary-annual').textContent = ((net_invested * (vl_taux /
                                    100) * diffDays) / 360).toLocaleString('fr-FR') + ' XAF';
                            }
                        }
                    }
                } else {
                    summaryDiv.classList.add('hidden');
                }
            }

            const form = document.getElementById('new-placement-form-detail');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btn-submit-placement');
                    const resp = document.getElementById('modal-response');
                    btn.disabled = true;
                    btn.textContent = "Traitement...";

                    const typeId = typeSelect.value;
                    const url = typeId == 1 ? '{{ route('achat-action-customer-fcp') }}' :
                        '{{ route('achat-action-customer-pmg') }}';
                    const vl_taux = parseFloat(vlTauxInput.value);
                    const total_paid = parseFloat(amountInput.value);
                    const product = productsList.find(p => p.id == prodSelect.value);
                    const feeRate = product ? (parseFloat(product.free) || 0) : 0;
                    let fees = (total_paid * feeRate) / 100;
                    let net = total_paid - fees;
                    if (typeId == 2) {
                        fees = 0;
                        net = total_paid;
                    }

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            product: prodSelect.value,
                            customer: {{ $customer->id }},
                            montantTotal: total_paid,
                            montant_normal: net,
                            fraisGestion: fees,
                            date_valeur: document.getElementById('datepicker_valeur').value,
                            date_echeance: document.getElementById('datepicker_echeance').value,
                            taux_insere: vl_taux,
                            type_souscription: document.getElementById('type_souscription') ?
                                document.getElementById('type_souscription').value : null,
                            montantAchat: typeId == 1 ? net / vl_taux : net
                        },
                        success: function(r) {
                            window.location.reload();
                        },
                        error: function(xhr) {
                            resp.innerHTML = xhr.responseJSON.message ||
                                "Erreur lors de l'enregistrement.";
                            resp.className =
                                "alert alert-danger show mb-4 p-4 rounded-xl bg-red-50 text-red-600 border border-red-100";
                            resp.classList.remove('hidden');
                            btn.disabled = false;
                            btn.textContent = "ENREGISTRER LA SOUSCRIPTION";
                        }
                    });
                });
            }
        });
    </script>
@endsection
