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
                                        <span id="label-fees-fcp" class="text-xs opacity-60">Frais de souscription : </span>
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
                                        <span id="label-fees-pmg" class="text-xs opacity-60">Frais de souscription : </span>
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
                                        {{ number_format($my_product['vl_achat'], 2, '.', ' ') }}
                                    </div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">Nombre de parts :</div>
                                    <div class="response- text-primary font-bold">
                                        {{ number_format($my_product['nb_part'], 2, '.', ' ') }}
                                    </div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">VL Actuelle :</div>
                                    <div class="response-">XAF 
                                        {{ number_format($my_product['vl_actuel'], 2, '.', ' ') }}
                                        <span class="text-[10px] opacity-70 italic text-n600"> (du {{ \Carbon\Carbon::parse($my_product['date_vl_actuel'] ?? now())->format('d/m/Y') }})</span>
                                    </div>
                                </div>
                                <div class="content-label-info font-bold"
                                    style="border-top: 1px dashed #ccc; padding-top: 5px;">
                                    <div class="label-">VALEUR PORTFOLIO :</div>
                                    <div class="response-">XAF
                                        {{ number_format($my_product['portfolio_valeur'], 0, ' ', ' ') }}
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

        <div class="grid grid-cols-12 gap-4 xxl:gap-6 mt-6">
            <div class="col-span-12">
                <div class="box rounded-2xl bg-n0 p-6 shadow-sm dark:bg-bg4">
                    <h3 class="mb-6 uppercase">RELEVÉS MENSUELS DU CLIENT</h3>
                    @if (isset($availableMonths) && count($availableMonths) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full whitespace-nowrap">
                                <thead>
                                    <tr class="bg-secondary1/5 dark:bg-bg3">
                                        <th class="px-6 py-4 text-start font-semibold">Période</th>
                                        <th class="px-6 py-4 text-center font-semibold">Types</th>
                                        <th class="px-6 py-4 text-center font-semibold">Téléchargement (PDF)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($availableMonths as $m)
                                        <tr class="border-b border-n30 last:border-0 hover:bg-secondary1/5 duration-200">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                        <i class="las la-calendar text-xl"></i>
                                                    </div>
                                                    <span class="font-bold">{{ $m['label'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex justify-center gap-2">
                                                    @if ($m['has_pmg'])
                                                        <span
                                                            class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary border border-primary/20">PMG</span>
                                                    @endif
                                                    @if ($m['has_fcp'])
                                                        <span
                                                            class="rounded-full bg-secondary/10 px-3 py-1 text-xs font-bold text-secondary border border-secondary/20">FCP</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex justify-center gap-3">
                                                    @if ($m['has_pmg'])
                                                        <a href="{{ route('customer-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'pmg', 'customer_id' => $customer->id]) }}"
                                                            class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white hover:bg-primary/90 transition-all">
                                                            <i class="las la-download text-base"></i> PMG
                                                        </a>
                                                    @endif
                                                    @if ($m['has_fcp'])
                                                        <a href="{{ route('customer-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'fcp', 'customer_id' => $customer->id]) }}"
                                                            class="flex items-center gap-2 rounded-lg bg-secondary px-4 py-2 text-xs font-bold text-white hover:bg-secondary/90 transition-all"
                                                            style="background-color: #ebb009">
                                                            <i class="las la-download text-base"></i> FCP
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 px-6 pb-6">
                            {{ $availableMonths->links('partials.pagination') }}
                        </div>
                    @else
                        <div class="py-10 text-center opacity-60">
                            <i class="las la-folder-open text-5xl mb-2"></i>
                            <p>Aucun relevé mensuel disponible pour ce client.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        </div>

        <!-- MODALE ÉVOLUTION FCP -->
        <div id="modal-evolution-fcp" class="ac-modal-overlay modalhide fixed inset-0 z-[100] bg-n900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="modal-inner bg-white dark:bg-bg4 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-modal-in">
                <div class="p-6 border-b border-n30 flex justify-between items-center bg-n10/50">
                    <h3 class="text-lg font-bold text-n900 flex items-center gap-3 italic uppercase">
                        <i class="las la-history text-secondary text-2xl"></i>
                        Évolution : <span id="evo-product-name" class="text-primary italic">Produit</span>
                    </h3>
                    <button type="button" onclick="closeEvoModal()" class="w-10 h-10 rounded-full hover:bg-n30 flex items-center justify-center text-n500 transition-all">
                        <i class="las la-times text-2xl"></i>
                    </button>
                </div>
                <div class="p-8 max-h-[70vh] overflow-y-auto">
                    <div id="evo-loader" class="py-20 text-center">
                        <div class="inline-block w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
                        <p class="mt-4 text-n500 font-bold italic uppercase text-xs tracking-wider">Récupération de l'historique...</p>
                    </div>
                    <div id="evo-content" class="hidden">
                        <div class="overflow-hidden rounded-2xl border border-n30">
                            <table class="w-full text-left">
                                <thead class="bg-n10 text-[10px] uppercase font-bold text-n500 tracking-widest border-b border-n30">
                                    <tr>
                                        <th class="px-5 py-4">Date de Valeur</th>
                                        <th class="px-5 py-4">VL Publiée</th>
                                        <th class="px-5 py-4">Parts Détenues</th>
                                        <th class="px-5 py-4 text-right">Valorisation</th>
                                    </tr>
                                </thead>
                                <tbody id="evo-table-body" class="divide-y divide-n30">
                                    <!-- AJAX rows -->
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6 p-4 bg-primary/5 rounded-2xl border border-primary/10">
                            <p class="text-[10px] text-n500 italic leading-relaxed">
                                <i class="las la-info-circle text-primary"></i> 
                                Cette table présente la valorisation calculée au fur et à mesure de l'évolution de la VL pour l'ensemble des transactions cumulées à chaque date.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('script_front_end')
    <script>
        const productsList = @json($products);
        const ownedPmgProductIds = @json($ownedPmgProductIds);

        document.addEventListener('DOMContentLoaded', function() {
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

            if (modalOverlay) {
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) {
                        modalOverlay.classList.add("modalhide");
                        modalOverlay.classList.remove("modalshow");
                    }
                });
            }

            const typeSelect = document.getElementById('type_produit');
            const prodSelect = document.getElementById('select_produit');
            const vlTauxInput = document.getElementById('vl_taux_input');
            const labelVlTaux = document.getElementById('label_vl_taux');
            const echeanceContainer = document.getElementById('date_echeance_container');
            const echeanceInput = document.getElementById('datepicker_echeance');

            // Initialisation des datepickers personnalisés
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

                    // Si le produit est sélectionné on va chercher la VL à cette date
                    const prodId = prodSelect.value;
                    if (prodId && typeSelect.value == 1) { // 1 = FCP
                        fetchVlAtDate(prodId, value);
                    } else {
                        updateSummary();
                    }
                }
            });

            function fetchVlAtDate(productId, date) {
                vlTauxInput.value = "Chargement...";
                fetch(`/api/product-vl/${productId}/${date}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            vlTauxInput.value = data.vl;
                            updateSummary();
                        }
                    })
                    .catch(err => {
                        console.error("Erreur lors de la récupération de la VL:", err);
                        vlTauxInput.value = "0";
                    });
            }

            const pickerEcheance = datepicker('#datepicker_echeance', {
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
                    
                    // Auto-calculate expiry for PMG
                    if (typeSelect.value == 2) {
                        calculateExpiry();
                    }
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
                    
                    // On ajoute la durée en mois
                    dateVal.setMonth(dateVal.getMonth() + duree);
                    
                    const expiryStr = dateVal.getFullYear() + '-' + String(dateVal.getMonth() + 1).padStart(2, '0') + '-' + String(dateVal.getDate()).padStart(2, '0');
                    document.getElementById('datepicker_echeance').value = expiryStr;
                    
                    // On met à jour l'instance du datepicker
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
                                // Filter out PMG products already owned
                                if (catId == 2 && ownedPmgProductIds.includes(p.id)) {
                                    return false;
                                }
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
                            if (!vlTauxInput.classList.contains('bg-gray-100')) {
                                vlTauxInput.classList.add('bg-gray-100');
                            }
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

                        // Si un produit est déjà sélectionné (normalement non car re-rempli mais par précaution)
                        if (prodSelect.value) {
                            if (catId == 1) {
                                fetchVlAtDate(prodSelect.value, document.getElementById('datepicker_valeur')
                                    .value);
                            } else {
                                updateSummary();
                            }
                        }
                    } else {
                        prodSelect.disabled = true;
                    }
                });
            }

            if (prodSelect) {
                prodSelect.addEventListener('change', function() {
                    const productId = this.value;
                    const catId = typeSelect.value;
                    const dateVal = document.getElementById('datepicker_valeur').value;

                    if (productId) {
                        if (catId == 1) { // FCP
                            fetchVlAtDate(productId, dateVal);
                        } else {
                            const product = productsList.find(p => p.id == productId);
                             if (product) {
                                 vlTauxInput.value = product.recent_vl;
                                 if (catId == 2) {
                                     calculateExpiry();
                                 }
                                 updateSummary();
                             }
                        }
                    }
                });
            }

            const amountInput = document.getElementById('amount_input');
            if (amountInput) {
                amountInput.addEventListener('input', updateSummary);
            }

            if (vlTauxInput) {
                vlTauxInput.addEventListener('input', updateSummary);
            }

            function updateSummary() {
                const total_input = parseFloat(amountInput.value) || 0;
                const vl_raw = vlTauxInput.value;
                const productId = prodSelect.value;
                const product = productsList.find(p => p.id == productId);
                const feeRate = product ? (parseFloat(product.free) || 0) : 0;

                if (vl_raw === "Chargement...") {
                    const summaryDiv = document.getElementById('placement-summary');
                    if (total_input > 0) {
                        summaryDiv.classList.remove('hidden');
                        document.getElementById('summary-vl-fcp').textContent = "Chargement...";
                        document.getElementById('summary-vl-pmg').textContent = "Chargement...";
                    }
                    return;
                }

                const vl_taux = parseFloat(vl_raw) || 0;
                const typeId = typeSelect.value;
                const summaryDiv = document.getElementById('placement-summary');

                if (total_input > 0 && typeId) {
                    summaryDiv.classList.remove('hidden');

                    let fees = (total_input * feeRate) / 100;
                    let net_invested = total_input - fees;

                    // PMG (Type 2) has no fees and works with the full amount
                    if (typeId == 2) {
                        fees = 0;
                        net_invested = total_input;
                    }

                    if (typeId == 1) { // FCP
                        document.getElementById('summary-fcp-only').classList.remove('hidden');
                        document.getElementById('summary-pmg-only').classList.add('hidden');

                        document.getElementById('summary-vl-fcp').textContent = vl_taux.toLocaleString('fr-FR', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 2
                        }) + ' XAF';
                        document.getElementById('label-fees-fcp').textContent = 'Frais de souscription (' + feeRate + '%):';
                        document.getElementById('summary-fees-fcp').textContent = fees.toLocaleString('fr-FR') +
                            ' XAF';

                        if (vl_taux > 0) {
                            const parts = net_invested / vl_taux; // Parts basées sur le montant net (Total - Frais)
                            document.getElementById('summary-parts').textContent = parts.toFixed(4);
                        } else {
                            document.getElementById('summary-parts').textContent = "0";
                        }
                    } else if (typeId == 2) { // PMG
                        document.getElementById('summary-fcp-only').classList.add('hidden');
                        document.getElementById('summary-pmg-only').classList.remove('hidden');

                        document.getElementById('summary-vl-pmg').textContent = vl_taux + ' %';
                        document.getElementById('label-fees-pmg').textContent = 'Frais de souscription (' + feeRate + '%):';
                        document.getElementById('summary-fees-pmg').textContent = fees.toLocaleString('fr-FR') +
                            ' XAF';

                        const dateValStr = document.getElementById('datepicker_valeur').value;
                        const dateEcheanceStr = document.getElementById('datepicker_echeance').value;

                        const annualGain = net_invested * (vl_taux / 100); // Utilise le montant NET investi
                        const monthlyGain = annualGain / 12;

                        document.getElementById('summary-monthly').textContent = monthlyGain.toLocaleString(
                            'fr-FR', {
                                maximumFractionDigits: 0
                            }) + ' XAF';

                        if (dateValStr && dateEcheanceStr) {
                            const d1 = new Date(dateValStr);
                            const d2 = new Date(dateEcheanceStr);
                            const diffTime = d2 - d1;
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                            if (diffDays > 0) {
                                const gainEcheance = (net_invested * (vl_taux / 100) * diffDays) / 360; // Utilise le montant NET investi
                                document.getElementById('summary-annual').textContent = gainEcheance.toLocaleString(
                                    'fr-FR', {
                                        maximumFractionDigits: 0
                                    }) + ' XAF (' + diffDays + ' jours)';
                            } else {
                                document.getElementById('summary-annual').textContent = "0 XAF";
                            }
                        } else {
                            document.getElementById('summary-annual').textContent = "---";
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
                    const formData = new FormData(this);
                    const btn = document.getElementById('btn-submit-placement');
                    const responseDiv = document.getElementById('modal-response');

                    btn.disabled = true;
                    btn.textContent = "Traitement...";

                    const typeId = typeSelect.value;
                    const url = typeId == 1 ? '{{ route('achat-action-customer-fcp') }}' :
                        '{{ route('achat-action-customer-pmg') }}';

                    const total_paid = parseFloat(document.getElementById('amount_input').value);
                    const productId = prodSelect.value;
                    const product = productsList.find(p => p.id == productId);
                    const feeRate = product ? (parseFloat(product.free) || 0) : 0;
                    
                    const vl_taux = parseFloat(vlTauxInput.value);
                    
                    let fees = (total_paid * feeRate) / 100;
                    let net_invested = total_paid - fees;
                    
                    if (typeId == 2) {
                        fees = 0;
                        net_invested = total_paid;
                    }

                    const data = {
                        _token: '{{ csrf_token() }}',
                        product: productId,
                        customer: {{ $customer->id }},
                        montantTotal: total_paid, 
                        montant_normal: net_invested,
                        fraisGestion: fees,
                        date_valeur: formData.get('date_valeur'),
                        date_echeance: formData.get('date_echeance'),
                        taux_insere: vl_taux,
                        type_souscription: formData.get('type_souscription')
                    };

                    if (typeId == 1) {
                        data.montantAchat = net_invested / vl_taux; // Parts basées sur le montant NET
                    } else {
                        data.montantAchat = net_invested; // Pour PMG, nb_part = Capital investi
                    }

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: data,
                        success: function(response) {
                            responseDiv.textContent =
                                response.message ||
                                "Placement ajouté avec succès ! Redirection...";
                            responseDiv.className =
                                "alert alert-success show mb-4 p-4 rounded-xl bg-success/10 text-success border border-success/20";
                            responseDiv.classList.remove('hidden');

                            // Faire défiler vers le haut de la modale pour voir le message
                            document.querySelector('.modal-inner').scrollTop = 0;

                            setTimeout(() => {
                                window.location.href =
                                    "{{ route('success-transaction-customer') }}";
                            }, 1500);
                        },
                        error: function(xhr) {
                            let errorMsg = "Erreur lors de l'enregistrement.";
                            if (xhr.status === 422 && xhr.responseJSON.errors) {
                                errorMsg = Object.values(xhr.responseJSON.errors).flat().join(
                                    "<br>");
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }

                            responseDiv.innerHTML = errorMsg;
                            responseDiv.className =
                                "alert alert-danger show mb-4 p-4 rounded-xl bg-red-100 text-red-600 border border-red-200";
                            responseDiv.classList.remove('hidden');

                            // Faire défiler vers le haut de la modale
                            document.querySelector('.modal-inner').scrollTop = 0;

                            btn.disabled = false;
                            btn.textContent = "ENREGISTRER LA SOUSCRIPTION";
                        }
                    });
                });
            }
        });

        function showFcpEvolution(productId, productName) {
            const modal = document.getElementById('modal-evolution-fcp');
            const title = document.getElementById('evo-product-name');
            const loader = document.getElementById('evo-loader');
            const content = document.getElementById('evo-content');
            const tbody = document.getElementById('evo-table-body');

            title.textContent = productName;
            modal.classList.remove('modalhide');
            modal.classList.add('modalshow');
            loader.classList.remove('hidden');
            content.classList.add('hidden');
            tbody.innerHTML = '';

            $.ajax({
                url: `/api/fcp-evolution/${productId}/{{ $customer->id }}`,
                method: 'GET',
                success: function(response) {
                    loader.classList.add('hidden');
                    content.classList.remove('hidden');

                    if (response.history && response.history.length > 0) {
                        response.history.reverse().forEach(row => {
                            const tr = document.createElement('tr');
                            tr.className = "hover:bg-n10 transition-all italic";
                            tr.innerHTML = `
                                <td class="px-5 py-4 text-sm font-bold text-n800">${row.date}</td>
                                <td class="px-5 py-4 text-xs text-n600">XAF ${row.vl.toLocaleString('fr-FR')}</td>
                                <td class="px-5 py-4 text-xs font-bold text-primary">${row.parts.toFixed(2)}</td>
                                <td class="px-5 py-4 text-right text-sm font-extrabold text-n900">${row.valuation.toLocaleString('fr-FR')} XAF</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="py-10 text-center text-n400 italic">Aucune donnée historique.</td></tr>';
                    }
                },
                error: function() {
                    loader.classList.add('hidden');
                    tbody.innerHTML = '<tr><td colspan="4" class="py-10 text-center text-danger font-bold uppercase italic">Erreur lors du chargement.</td></tr>';
                }
            });
        }

        function closeEvoModal() {
            const modal = document.getElementById('modal-evolution-fcp');
            modal.classList.remove('modalshow');
            modal.classList.add('modalhide');
        }
    </script>
@endsection
