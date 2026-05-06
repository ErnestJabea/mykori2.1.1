@extends('front-end/app/app-home-asset', ['Produits disponibles', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden products-page'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6 pb-4">
            <!-- Header with Customer Name and Add Placement Button -->
            <div class="col-span-12 flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30 mb-4">
                <div>
                    <h3 class="h3">SOUSCRIPTIONS : {{ strtoupper($customer->name) }}</h3>
                    <p class="text-sm opacity-70">Sélectionnez un produit ou ajoutez une nouvelle souscription manuellement</p>
                </div>
                <button class="btn add-placement-btn bg-primary text-white rounded-lg px-6 py-3 hover:bg-primary/90 duration-300 flex items-center gap-2 shadow-md">
                    <i class="las la-plus-circle text-xl"></i> NOUVELLE SOUSCRIPTION
                </button>
            </div>

            <!-- Liste des produits -->
            @foreach ($products_categories as $category)
                <div class="col-span-12">
                    <div class="box col-span-12 lg:col-span-6">
                        <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">{{ $category->abreviation }} : {{ $category->title }}</h4>
                        </div>
                        @php
                            $produits = $products->where('products_category_id', $category->id);
                        @endphp
                        <div class="overflow-x-auto">
                            <div class="mb-6 flex items-center justify-center gap-3 lg:mb-8 xxl:gap-4">
                                <button
                                    class="prev-wallet h-8 w-8 shrink-0 rounded-full border border-primary bg-n0 text-primary duration-300 hover:bg-primary hover:text-n0 dark:bg-bg4 dark:hover:bg-primary xxl:h-10 xxl:w-10">
                                    <i class="las la-angle-left text-lg rtl:rotate-180"></i>
                                </button>
                                <div class="swiper walletSwiper" dir="ltr">
                                    <div class="swiper-wrapper">
                                        @foreach ($produits as $product)
                                            <div class="swiper-slide">
                                                <div class="flex justify-center">
                                                    <div class="content-product">
                                                        <div class="product-img">
                                                            <img src="{{ $product->logo }}" class="rounded-xl w-full h-32 object-cover"
                                                                alt="{{ $product->title }}" />
                                                        </div>
                                                        <div class="title-product mt-3">
                                                            <h3 class="text-lg font-bold">{{ $product->title }}</h3>
                                                        </div>
                                                        <div class="product-price my-2">
                                                            @if ($product->products_category_id == 2)
                                                                <span class="price text-secondary1 font-bold">{{ $product->vl }}%</span>
                                                            @else
                                                                <span class="price text-primary font-bold">{{ number_format($product->recent_vl, 2, ',', ' ') }}</span>
                                                                <sup class="text-[10px]">Fcfa</sup>
                                                            @endif
                                                        </div>
                                                        <div class="content-btn-action mt-4">
                                                            <a href="{{ route('product-customer-detail', ['slug' => $product->slug, 'customer' => $customer->id]) }}" 
                                                               class="btn-sm border border-primary text-primary rounded-full px-4 py-1 hover:bg-primary hover:text-white duration-300 block text-center">
                                                                VOIR DÉTAILS
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <button
                                    class="next-wallet h-8 w-8 shrink-0 rounded-full border border-primary bg-n0 text-primary duration-300 hover:bg-primary hover:text-n0 dark:bg-bg4 dark:hover:bg-primary xxl:h-10 xxl:w-10">
                                    <i class="las la-angle-right text-lg rtl:rotate-180"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- NEW PLACEMENT MODAL -->
        <div class="ac-modal-overlay fixed inset-0 z-[99] modalhide bg-black/60 duration-500 overflow-y-auto flex items-center justify-center p-4">
            <div class="modal box modal-inner relative w-full max-w-[450px] bg-white dark:bg-bg3 rounded-3xl shadow-2xl p-6 md:p-8 duration-300">
                <button class="ac-modal-close-btn absolute top-4 right-4 text-3xl hover:text-red-500 duration-300 font-bold">
                    <i class="las la-times text-2xl"></i>
                </button>
                <div class="bb-dashed mb-6 pb-4 border-b border-dashed border-gray-300">
                    <h4 class="h4 text-xl font-bold">Nouvelle Souscription</h4>
                    <p class="text-xs opacity-70 mt-1">Client : <span class="text-primary font-bold">{{ $customer->name }}</span></p>
                </div>
                
                <div id="modal-response" class="alert hidden mb-4 p-4 rounded-xl"></div>

                <form id="new-placement-form">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold opacity-80">Type de produit</label>
                            <select id="type_produit" name="type_produit" class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary font-bold">
                                <option value="">Sélectionner...</option>
                                @foreach($products_categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->abreviation }}</option>
                                @endforeach
                            </select>
                        </div>

                        </div>
                        
                        <!-- Mode de gestion des intérêts (PMG only) -->
                        <div class="hidden" id="interest_management_container">
                            <label class="mb-2 block text-sm font-semibold opacity-80 italic text-primary">Gestion des Intérêts</label>
                            <select id="interest_management" name="interest_management" class="w-full rounded-xl border border-primary/30 bg-primary/5 px-4 py-3 outline-none focus:border-primary font-bold">
                                <option value="">Choisir un mode de gestion...</option>
                                <option value="A la date d'échéance de placement">À la date d'échéance de placement</option>
                                <option value="Annuellement a la date anniversaire">Annuellement à la date anniversaire</option>
                                <option value="Capitalisation jusqu'a echeance du mandat de placement">Capitalisation jusqu'à échéance du mandat de placement</option>
                                <option value="Interets precomptes">Intérêts précomptés</option>
                                <option value="Chaque mois (mois anniversaire pour les cas exceptionnels)">Chaque mois (mois anniversaire pour les cas exceptionnels)</option>
                            </select>
                        </div>

                        <div>
                            <label id="label_vl_taux" class="mb-2 block text-sm font-semibold opacity-80">Valeur / Taux</label>
                            <input type="text" id="vl_taux_input" name="vl_taux" class="w-full rounded-xl border border-n30 bg-gray-100 dark:bg-bg4 px-4 py-3 outline-none" readonly>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold opacity-80">Montant investi (XAF)</label>
                            <input type="number" name="amount" id="amount_input" class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary font-bold text-primary" placeholder="Ex: 5 000 000" required>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold opacity-80">Date de valeur</label>
                            <input type="date" name="date_valeur" id="date_valeur" value="{{ date('Y-m-d') }}" class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary" required>
                        </div>

                        <div id="date_echeance_container">
                            <label class="mb-2 block text-sm font-semibold opacity-80">Date d'échéance</label>
                            <input type="date" name="date_echeance" id="date_echeance_input" class="w-full rounded-xl border border-n30 bg-secondary1/5 px-4 py-3 outline-none focus:border-primary">
                        </div>

                        <div class="mt-4">
                            <button type="submit" id="btn-submit-placement" class="btn w-full justify-center bg-primary text-white py-4 rounded-xl hover:bg-primary/90 duration-300 font-bold tracking-wider shadow-lg">
                                ENREGISTRER LA SOUSCRIPTION
                            </button>
                        </div>
                    </div>
                </form>
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
        const echeanceInput = document.getElementById('date_echeance_input');

        typeSelect.addEventListener('change', function() {
            const catId = this.value;
            prodSelect.innerHTML = '<option value="">Sélectionner un produit</option>';
            
            // Reset date limits by default
            const dateInput = document.getElementById('date_valeur');
            dateInput.removeAttribute('min');
            dateInput.removeAttribute('max');

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

                // RESTRICTION DATE (Commune FCP & PMG) : Aujourd'hui (max) et -7 jours (min)
                const today = new Date().toISOString().split('T')[0];
                const minDate = new Date();
                minDate.setDate(new Date().getDate() - 7);
                const minDateStr = minDate.toISOString().split('T')[0];

                dateInput.setAttribute('min', minDateStr);
                dateInput.setAttribute('max', today);
                
                // Si la valeur actuelle est hors limites, on remet à aujourd'hui
                if (dateInput.value < minDateStr || dateInput.value > today) {
                    dateInput.value = today;
                }

                if (catId == 1) { // FCP
                   labelVlTaux.textContent = "Valeur Liquidative (Dernière)";
                   vlTauxInput.readOnly = true;
                   vlTauxInput.classList.add('bg-gray-100');
                   echeanceContainer.style.opacity = "0.5";
                   echeanceInput.disabled = true;
                   document.getElementById('interest_management_container').classList.add('hidden');

                } else { // PMG
                   labelVlTaux.textContent = "Taux d'intérêt (%)";
                   vlTauxInput.readOnly = false;
                   vlTauxInput.classList.remove('bg-gray-100');
                   echeanceContainer.style.opacity = "1";
                   echeanceInput.disabled = false;
                   document.getElementById('interest_management_container').classList.remove('hidden');

                   // SYNC: d'échéance min = date de valeur + 1 jour
                   const dVal = new Date(dateInput.value);
                   const nextDay = new Date(dVal);
                   nextDay.setDate(nextDay.getDate() + 1);
                   echeanceInput.setAttribute('min', nextDay.toISOString().split('T')[0]);
                }
            } else {
                prodSelect.disabled = true;
            }
        });

        function calculateExpiry() {
            const dateInput = document.getElementById('date_valeur');
            const dateValStr = dateInput.value;
            const productId = prodSelect.value;
            const product = productsList.find(p => p.id == productId);

            if (dateValStr && product && product.duree) {
                const dateVal = new Date(dateValStr);
                const duree = parseInt(product.duree);
                dateVal.setMonth(dateVal.getMonth() + duree);
                const expiryStr = dateVal.getFullYear() + '-' + String(dateVal.getMonth() + 1).padStart(2, '0') + '-' + String(dateVal.getDate()).padStart(2, '0');
                echeanceInput.value = expiryStr;
            }
        }

        document.getElementById('date_valeur').addEventListener('change', function() {
            if (typeSelect.value == 2) { // PMG
                calculateExpiry();
                
                // SYNC min date for expiry
                const nextDay = new Date(this.value);
                nextDay.setDate(nextDay.getDate() + 1);
                echeanceInput.setAttribute('min', nextDay.toISOString().split('T')[0]);
                
                if (echeanceInput.value && echeanceInput.value <= this.value) {
                    echeanceInput.value = nextDay.toISOString().split('T')[0];
                }
            }
        });

        prodSelect.addEventListener('change', function() {
            const productId = this.value;
            const product = productsList.find(p => p.id == productId);
            if (product) {
                vlTauxInput.value = product.recent_vl;
                if (typeSelect.value == 2) {
                    calculateExpiry();
                }
            }
        });

        const form = document.getElementById('new-placement-form');
        if(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const btn = document.getElementById('btn-submit-placement');
                const responseDiv = document.getElementById('modal-response');

                btn.disabled = true;
                btn.textContent = "Traitement...";

                const typeId = typeSelect.value;
                const url = typeId == 1 ? '{{ route("achat-action-customer-fcp") }}' : '{{ route("achat-action-customer-pmg") }}';
                
                const amount = document.getElementById('amount_input').value;
                const vl_taux = parseFloat(vlTauxInput.value);
                const pId = prodSelect.value;
                const product = productsList.find(p => p.id == pId);
                const feeRate = product ? (parseFloat(product.free) || 0) : 0;
                
                let fees = (amount * feeRate) / 100;
                let net_invested = amount - fees;

                if (typeId == 2) {
                    fees = 0;
                    net_invested = amount;
                }

                const data = {
                    _token: '{{ csrf_token() }}',
                    product: pId,
                    customer: {{ $customer->id }},
                    montantTotal: amount,
                    montant_normal: net_invested,
                    fraisGestion: fees,
                    date_valeur: formData.get('date_valeur'),
                    date_echeance: formData.get('date_echeance'),
                    interest_management: document.getElementById('interest_management').value,
                    taux_insere: vl_taux
                };

                if(typeId == 1) {
                    data.montantAchat = net_invested / vl_taux;
                } else {
                    data.montantAchat = net_invested; // Capital pour PMG
                }

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        responseDiv.textContent = "Souscription ajoutée avec succès !";
                        responseDiv.className = "alert alert-success show mb-4 p-4 rounded-xl bg-success/10 text-success border border-success/20";
                        responseDiv.classList.remove('hidden');
                        setTimeout(() => {
                            window.location.href = "{{ route('success-transaction-customer') }}";
                        }, 1500);
                    },
                    error: function(err) {
                        responseDiv.textContent = "Erreur lors de l'enregistrement.";
                        responseDiv.className = "alert alert-danger show mb-4 p-4 rounded-xl bg-red-100 text-red-600 border border-red-200";
                        responseDiv.classList.remove('hidden');
                        btn.disabled = false;
                        btn.textContent = "ENREGISTRER LA SOUSCRIPTION";
                    }
                });
            });
        }
    });
</script>
@endsection
