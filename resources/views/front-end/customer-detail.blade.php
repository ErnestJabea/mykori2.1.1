@extends('front-end/app/app-home-asset', [
    'title' => $customer->name . ' | Clients ',
    'body_class' => 'vertical
bg-secondary1/5 dark:bg-bg3 my-products-page other-page',
])

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
                    <button class="btn buy">
                        <a href="{{ route('transactions-client', ['customer' => $customer->id]) }}">TRANSACTIONS</a>
                    </button>
                    <button class="btn add-placement-btn buy" style="background-color: #ebb009; color:white">
                        <i class="las la-plus-circle"></i> AJOUTER UN PLACEMENT
                    </button>
                </div>
            </div>
        </div>

        <!-- NEW PLACEMENT MODAL -->
        <div
            class="ac-modal-overlay placement-modal fixed inset-0 z-[99] modalhide bg-black/60 duration-500 overflow-y-auto flex items-center justify-center p-4">
            <div
                class="modal box modal-inner relative w-full max-w-550px] bg-white dark:bg-bg3 rounded-3xl shadow-2xl p-6 md:p-8 duration-300">
                <button class="ac-modal-close-btn absolute top-4 right-4 text-3xl hover:text-red-500 duration-300">
                    <i class="las la-times text-2xl"></i>
                </button>
                <div class="bb-dashed mb-6 pb-4 border-b border-dashed border-gray-300">
                    <h4 class="h4 text-xl font-bold">Nouveau Placement</h4>
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
                                        <span class="text-xs font-semibold opacity-70">Frais de souscription (1%): </span>
                                        <span id="summary-fees-fcp" class="font-bold text-primary">0 XAF</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold opacity-70">Nombre de parts : </span>
                                        <span id="summary-parts" class="font-bold text-primary">0</span>
                                    </div>
                                </div>

                                <!-- PMG Results -->
                                <div id="summary-pmg-only" class="hidden">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold opacity-70">Frais de souscription (1%): </span>
                                        <span id="summary-fees-pmg" class="font-bold text-primary">0 XAF</span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold opacity-70">Gain Mensuel Net : </span>
                                        <span id="summary-monthly" class="font-bold text-secondary">0 XAF</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold opacity-70">Gain à l'échéance : </span>
                                        <span id="summary-annual" class="font-bold text-secondary">0 XAF</span>
                                    </div>
                                </div>
                            </div>


                            <div class="mt-4 full-width">
                                <button type="submit" id="btn-submit-placement"
                                    class="btn w-full justify-center bg-primary text-white py-4 rounded-xl hover:bg-primary/90 duration-300 font-bold tracking-wider shadow-lg">
                                    ENREGISTRER LE PLACEMENT
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
                                            <span>{{ $my_product['product_name'] }}</span>
                                        </a>
                                    </div>
                                    <div class="inner-header">
                                        <div class="content-label-info">
                                            <div class="label-">Investissement initial :</div>
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
                                                {{ number_format(
                                                    $my_product['interets_generes'],
                                                    0,
                                                    ' ',
                                                    '
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ',
                                                ) }}
                                            </div>
                                        </div>
                                        <div class="content-label-info">
                                            <div class="label-">Portofolio :</div>
                                            <div class="response-">XAF
                                                {{ number_format(
                                                    $my_product['portfolio_valeur'],
                                                    0,
                                                    ' ',
                                                    '
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ',
                                                ) }}
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
                                    <div class="response-">XAF
                                        {{ number_format($my_product['capital_investi'], 0, ' ', ' ') }}
                                    </div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">Nombre de parts :</div>
                                    <div class="response-">{{ number_format($my_product['nb_part'], 4, '.', ' ') }}</div>
                                </div>
                                <div class="content-label-info">
                                    <div class="label-">VL Actuelle :</div>
                                    <div class="response-">XAF {{ number_format($my_product['vl_actuel'], 2, '.', ' ') }}
                                    </div>
                                </div>
                                <div class="content-label-info font-bold"
                                    style="border-top: 1px dashed #ccc; padding-top: 5px;">
                                    <div class="label-">VALEUR PORTFOLIO :</div>
                                    <div class="response-">XAF
                                        {{ number_format($my_product['portfolio_valeur'], 0, ' ', ' ') }}
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

@section('script_front_end')
    <script>
        const productsList = @json($products);

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
                minDate: new Date(),
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
                    updateSummary();
                }
            });

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
                    updateSummary();
                }
            });

            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    const catId = this.value;
                    prodSelect.innerHTML = '<option value="">Sélectionner un produit</option>';

                    if (catId) {
                        prodSelect.disabled = false;
                        const filtered = productsList.filter(p => p.products_category_id == catId);
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
                            document.getElementById('type_souscription_container').classList.remove('hidden');
                        } else { // PMG
                            labelVlTaux.textContent = "Taux d'intérêt (%)";
                            vlTauxInput.readOnly = false;
                            vlTauxInput.classList.remove('bg-gray-100');
                            echeanceContainer.style.opacity = "1";
                            echeanceInput.disabled = false;
                            document.getElementById('type_souscription_container').classList.add('hidden');
                        }
                        updateSummary();
                    } else {
                        prodSelect.disabled = true;
                    }
                });
            }

            if (prodSelect) {
                prodSelect.addEventListener('change', function() {
                    const productId = this.value;
                    const product = productsList.find(p => p.id == productId);
                    if (product) {
                        vlTauxInput.value = product.recent_vl;
                        updateSummary();
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
                const amount = parseFloat(amountInput.value) || 0;
                const vl_taux = parseFloat(vlTauxInput.value) || 0;
                const typeId = typeSelect.value;
                const summaryDiv = document.getElementById('placement-summary');

                if (amount > 0 && typeId) {
                    summaryDiv.classList.remove('hidden');

                    const fees = amount * 0.01;

                    if (typeId == 1) { // FCP
                        document.getElementById('summary-fcp-only').classList.remove('hidden');
                        document.getElementById('summary-pmg-only').classList.add('hidden');

                        document.getElementById('summary-fees-fcp').textContent = fees.toLocaleString('fr-FR') +
                            ' XAF';

                        if (vl_taux > 0) {
                            const parts = (amount - fees) / vl_taux;
                            document.getElementById('summary-parts').textContent = parts.toFixed(4);
                        } else {
                            document.getElementById('summary-parts').textContent = "0";
                        }
                    } else if (typeId == 2) { // PMG
                        document.getElementById('summary-fcp-only').classList.add('hidden');
                        document.getElementById('summary-pmg-only').classList.remove('hidden');

                        document.getElementById('summary-fees-pmg').textContent = fees.toLocaleString('fr-FR') +
                            ' XAF';

                        const dateValStr = document.getElementById('datepicker_valeur').value;
                        const dateEcheanceStr = document.getElementById('datepicker_echeance').value;

                        const netAnnual = amount * (vl_taux / 100);
                        const netMonthly = netAnnual / 12;

                        document.getElementById('summary-monthly').textContent = netMonthly.toLocaleString(
                            'fr-FR', {
                                maximumFractionDigits: 0
                            }) + ' XAF';

                        if (dateValStr && dateEcheanceStr) {
                            const d1 = new Date(dateValStr);
                            const d2 = new Date(dateEcheanceStr);
                            const diffTime = d2 - d1;
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                            if (diffDays > 0) {
                                const gainEcheance = (amount * (vl_taux / 100) * diffDays) / 360;
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

                    const amount = parseFloat(document.getElementById('amount_input').value);
                    const vl_taux = parseFloat(vlTauxInput.value);
                    const fees = amount * 0.01;

                    const data = {
                        _token: '{{ csrf_token() }}',
                        product: prodSelect.value,
                        customer: {{ $customer->id }},
                        montantTotal: amount,
                        montant_normal: amount,
                        fraisGestion: fees,
                        date_valeur: formData.get('date_valeur'),
                        date_echeance: formData.get('date_echeance'),
                        taux_insere: vl_taux,
                        type_souscription: formData.get('type_souscription')
                    };

                    if (typeId == 1) {
                        data.montantAchat = (amount - fees) / vl_taux; // nb_part
                    } else {
                        data.montantAchat = (amount * (vl_taux / 100)); // interest amount estimate
                    }

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: data,
                        success: function(response) {
                            responseDiv.textContent =
                                response.message || "Placement ajouté avec succès ! Redirection...";
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
                                errorMsg = Object.values(xhr.responseJSON.errors).flat().join("<br>");
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
                            btn.textContent = "ENREGISTRER LE PLACEMENT";
                        }
                    });
                });
            }
        });
    </script>
@endsection
