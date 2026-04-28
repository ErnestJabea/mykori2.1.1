@extends('front-end/app/app-home-asset', [
    'title' => $customer->name . ' | Clients ',
    'body_class' => 'vertical
bg-secondary1/5 dark:bg-bg3 my-products-page other-page',
])


@section('inner-head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magnific-popup@1.2.0/dist/magnific-popup.css">
    <link rel="stylesheet" href="https://unpkg.com/js-datepicker/dist/datepicker.min.css">
@endsection

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <p><a href="{{ route('customer-detail', ['customer' => $customer->id]) }}"
                        style="font-size:15px; color: #ebb009">
                        < Retour</a>
                </p>
                <h3>CLIENTS / {{ $customer->name }} </h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4">
                <p style="text-align: right">{{ date('d-m-Y') }}</p>

            </div>
        </div>

        <div class="content-separator" style="height:30px"></div>
        {{-- Dans vendor/voyager/customers/manage.blade.php --}}
        <div class="panel panel-bordered">
            <div class="panel-heading">
                <h3 class="panel-title">Opération sur les mandats</h3>
            </div>
            <div class="panel-body  ">
                <div class="flex flex-wrap">
                    <div class="content-card">
                        <a href="#popup-interet-precomptes" class="open-popup-link">
                            <div class="content-card-body">
                                <div class="content-card-icon">
                                    <i class="fa-solid fa-coins"></i>
                                </div>
                                <div class="content-card-info">
                                    <h4>Gérer les intérêts</h4>
                                    <p>Enregistrer un versement d'intérêts pour un contrat spécifique.</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="content-card">
                        <a href="#popup-rachat-partiel" class="open-popup-link">
                            <div class="content-card-body">
                                <div class="content-card-icon">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                                <div class="content-card-info">
                                    <h4>Gérer les rachats</h4>
                                    <p>Enregistrer un rachat partiel pour un contrat spécifique.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    {{--       <div class="content-card">
                        <a href="#popup-remboursement" class="open-popup-link">
                            <div class="content-card-body">
                                <div class="content-card-icon">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                                <div class="content-card-info">
                                    <h4>Gérer les remboursements</h4>
                                    <p>Enregistrer un remboursement pour un contrat spécifique.</p>
                                </div>
                            </div>
                        </a>
                    </div> --}}
                    <div class="content-card">
                        <a href="#popup-rachat-fcp" class="open-popup-link">
                            <div class="content-card-body">
                                <div class="content-card-icon">
                                    <i class="fa-solid fa-file-invoice-dollar text-secondary"></i>
                                </div>
                                <div class="content-card-info">
                                    <h4>Gérer les rachats FCP</h4>
                                    <p>Effectuer un rachat partiel ou total sur un produit FCP.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- NEW SECTION: HISTORIQUE DES OPERATIONS (THEME KORI) -->
                <div class="flex flex-wrap w-full mt-8 mb-8">
                    <div class="content-bloc-list-produit w-full" style="flex: 1 1 100%; max-width: 100%;">
                        <div class="box">
                            <h3 class="mb-4">HISTORIQUE SYNTHÉTIQUE DES OPÉRATIONS (FCP & PMG)</h3>
                            
                            <div class="kori-table-wrapper mt-4">
                                <table class="kori-fcp-table text-left" style="width: 100%;">
                                    <thead>
                                        <tr>
                                    <th>DATE</th>
                                    <th class="text-center">CATÉGORIE</th>
                                    <th>PRODUIT</th>
                                    <th>RÉF. OPÉRATION</th>
                                    <th>TYPE</th>
                                    <th class="text-right">MONTANT BRUT</th>
                                    <th class="text-right">PARTS (FCP)</th>
                                    <th>COMMENTAIRE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allOperations as $op)
                                <tr>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-n900">{{ \Carbon\Carbon::parse($op->date_op)->format('d/m/Y') }}</span>
                                            <span class="text-xs opacity-60">{{ \Carbon\Carbon::parse($op->date_op)->format('H:i') }}</span>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        @if($op->category == 'PMG')
                                            <span class="badge bg-secondary/10 text-secondary px-2 py-1 rounded text-[10px] font-bold border border-secondary/20 shadow-sm">
                                                PMG
                                            </span>
                                        @else
                                            <span class="badge bg-primary/10 text-primary px-2 py-1 rounded text-[10px] font-bold border border-primary/20 shadow-sm">
                                                FCP
                                            </span>
                                        @endif
                                    </td>
                                    
                                    <td>
                                        <span class="font-bold text-n900">{{ $op->product_title }}</span>
                                    </td>
                                    
                                    <td>
                                        <span class="font-mono text-xs opacity-70">{{ $op->reference ?? '-' }}</span>
                                    </td>
                                    
                                    <td>
                                        @if($op->type == 'precompte_interets')
                                            <span class="badge bg-blue-100 text-blue-700 px-2 py-1 rounded">Précompte Int.</span>
                                        @elseif($op->type == 'paiement_interets')
                                            <span class="badge bg-green-100 text-green-700 px-2 py-1 rounded">Paiement Int.</span>
                                        @elseif($op->type == 'rachat_partiel' || $op->type == 'rachat')
                                            <span class="badge bg-orange-100 text-orange-700 px-2 py-1 rounded">Rachat</span>
                                        @elseif($op->type == 'souscription')
                                            <span class="badge bg-green-100 text-green-700 px-2 py-1 rounded">Souscription</span>
                                        @else
                                            <span class="badge bg-gray-100 text-gray-700 px-2 py-1 rounded">{{ ucfirst(str_replace('_', ' ', $op->type)) }}</span>
                                        @endif
                                    </td>
                                    
                                    <td class="text-right">
                                        <span class="font-bold text-n900">
                                            {{ number_format($op->amount, 0, ',', ' ') }} XAF
                                        </span>
                                    </td>
                                    
                                    <td class="text-right">
                                        @if($op->parts_change !== null)
                                            <span class="inline-flex px-2 py-1 rounded {{ $op->parts_change < 0 ? 'bg-red-50 text-red-600' : 'bg-green-100 text-green-700' }} text-xs font-bold font-mono">
                                                {{ $op->parts_change > 0 ? '+' : '' }}{{ number_format($op->parts_change, 4) }}
                                            </span>
                                        @else
                                            <span class="opacity-30 font-bold">-</span>
                                        @endif
                                    </td>
                                    
                                    <td>
                                        <p class="text-xs opacity-70 italic max-w-[200px] truncate m-0" title="{{ $op->comment }}">{{ $op->comment ?? '-' }}</p>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 opacity-50">
                                        Aucun historique d'opération disponible pour ce client.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
                </div>

                <div id="popup-interet-precomptes" class="mfp-hide white-popup-block">
                    <h3>Gestion des Intérêts </h3>
                    <hr>
                    <form action="{{ route('transactions.precompte') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Contrat concerné (PMG)</label>
                            <select name="type" id="type-select" class="form-control">
                                <option value="">Sélectionner un type</option>
                                <option value="precompte_interets">Intérêts précomptés</option>
                                <option value="paiement_interets">Paiements Interets</option>
                            </select>
                            <select name="transaction_id" class="form-control" required>
                                @foreach ($transactionsUsers as $trans)
                                    @php
                                        $product = App\Models\Product::where('id', $trans->product_id)->first();
                                    @endphp
                                    <option value="{{ $trans->id }}">
                                        {{ $product->title }} - (Initial: {{ number_format($trans->amount, 0, ',', ' ') }}
                                        XAF)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Montant des intérêts à verser (XAF)</label>
                            <input type="number" name="interest_amount" class="form-control" placeholder="0" required>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary">Valider le versement</button>
                        </div>
                    </form>
                </div>

                <div id="popup-rachat-partiel" class="mfp-hide white-popup-block">
                    <h3>Effectuer un Rachat Partiel</h3>
                    <hr>
                    <form action="{{ route('transactions.rachat-partiel') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Sélectionner le mandat (PMG)</label>
                            <select name="transaction_id" class="form-control" required>
                                @foreach ($transactionsUsers as $trans)
                                    @php
                                        $product = App\Models\Product::where('id', $trans->product_id)->first();
                                    @endphp
                                    <option value="{{ $trans->id }}">
                                        {{ $product->title }} - (Initial: {{ number_format($trans->amount, 0, ',', ' ') }}
                                        XAF)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Montant du rachat (XAF)</label>
                            <input type="number" name="amount_brut" class="form-control"
                                placeholder="Ex: Montant du rachat" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Frais de gestion (XAF)</label>
                            <input type="number" name="amount_frais" class="form-control" placeholder="Frais de gestion "
                                required>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-warning">Confirmer le Rachat</button>
                        </div>
                    </form>
                </div>

                <div id="popup-remboursement" class="mfp-hide white-popup-block">
                    <h3>Remboursement des Intérêts</h3>
                    <hr>
                    <form action="{{ route('transactions.remboursement-interets') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Sélectionner le mandat (PMG)</label>
                            <select name="transaction_id" class="form-control" required id="remboursement-trans-id">
                                <option value="">Choisir un contrat</option>
                                @foreach ($transactionsUsers as $trans)
                                    @php
                                        $product = App\Models\Product::find($trans->product_id);
                                    @endphp
                                    <option value="{{ $trans->id }}">
                                        {{ $product->title }} - (Initial: {{ number_format($trans->amount, 0, ',', ' ') }}
                                        XAF)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Montant du remboursement (XAF)</label>
                            <input type="number" name="amount" class="form-control" placeholder="Montant à rembourser"
                                required>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-success">Valider le Remboursement</button>
                        </div>
                    </form>
                </div>

                <div id="popup-rachat-fcp" class="mfp-hide white-popup-block">
                    <h3>Effectuer un Rachat FCP</h3>
                    <hr>
                    <form action="{{ route('transactions.rachat-fcp') }}" method="POST" id="form-rachat-fcp">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ $customerId }}">
                        <div class="form-group mb-3">
                            <label>Sélectionner le produit FCP</label>
                            <select name="product_id" id="rachat-fcp-product" class="form-control" required>
                                <option value="">Choisir un produit...</option>
                                @foreach ($ownedFcpProducts as $product)
                                    <option value="{{ $product->id }}">{{ $product->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Date d'effet du rachat</label>
                            <input type="text" name="date_operation" id="rachat-fcp-date"
                                value="{{ date('Y-m-d') }}" class="form-control" readonly required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Montant Brut du rachat (XAF)</label>
                            <input type="number" name="amount_brut" id="rachat-fcp-amount" class="form-control"
                                placeholder="Montant souhaité" required>
                            <p class="text-[10px] mt-1 italic text-n500" id="rachat-fcp-available-label"></p>
                        </div>
                        <div class="form-group mb-3">
                            <label>Frais de rachat (XAF)</label>
                            <input type="number" name="amount_frais" id="rachat-fcp-frais" class="form-control"
                                value="0">
                        </div>

                        <!-- APERCU DU CALCUL -->
                        <div id="rachat-fcp-preview"
                            class="hidden mt-4 p-4 rounded-2xl bg-primary/5 border border-dashed border-primary/20">
                            <h5 class="text-xs font-bold uppercase mb-3 text-primary"><i class="las la-calculator"></i>
                                Aperçu du calcul</h5>
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between text-xs">
                                    <span>VL appliquée :</span>
                                    <span id="preview-fcp-vl" class="font-bold">0 XAF</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span>Parts à liquider :</span>
                                    <span id="preview-fcp-parts" class="font-bold">0.0000</span>
                                </div>
                                <div class="flex justify-between text-xs pt-2 border-t border-dashed border-primary/10">
                                    <span class="font-bold">NET À VERSER :</span>
                                    <span id="preview-fcp-net" class="font-bold text-secondary text-sm">0 XAF</span>
                                </div>
                                <div class="flex justify-between text-xs pt-2">
                                    <span class="opacity-70">VALORISATION RESTANTE :</span>
                                    <span id="preview-fcp-restant" class="font-bold opacity-70">0 XAF</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right mt-6">
                            <button type="submit" id="btn-confirm-rachat-fcp"
                                class="btn btn-primary bg-primary text-white px-8 py-3 rounded-xl font-bold uppercase tracking-wider">
                                Confirmer le Rachat FCP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </main>
@endsection


@section('script_front_end')
    <script src="https://cdn.jsdelivr.net/npm/magnific-popup@1.2.0/dist/jquery.magnific-popup.min.js"></script>
    <script src="https://unpkg.com/js-datepicker"></script>
    <script>
        $(document).ready(function() {
            $('.open-popup-link').magnificPopup({
                type: 'inline',
                preloader: false,
                focus: '#name',

                // When elemened is focused, some mobile browsers in some cases zoom in
                // It looks not nice, so we disable it:
                callbacks: {
                    beforeOpen: function() {
                        if ($(window).width() < 700) {
                            this.st.focus = false;
                        } else {
                            this.st.focus = '#name';
                        }
                    }
                }
            });

            $('#popup-interet-precomptes form').on('submit', function(e) {
                e.preventDefault();

                let $form = $(this);
                let $btn = $form.find('button[type="submit"]');

                $btn.prop('disabled', true).text('Enregistrement...');

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        $.magnificPopup.close(); // Fermer la modale

                        // Notification (Simple alert ou SweetAlert2)
                        alert(response.message);

                        // Actualiser les données du tableau si nécessaire
                        location.reload();
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Valider le versement');
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Erreur réseau';
                        alert('Erreur : ' + msg);
                    }
                });
            });

            $('#popup-rachat-partiel form').on('submit', function(e) {
                e.preventDefault();

                let $form = $(this);
                let $btn = $form.find('button[type="submit"]');

                $btn.prop('disabled', true).text('Enregistrement...');

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        $.magnificPopup.close(); // Fermer la modale

                        // Notification (Simple alert ou SweetAlert2)
                        alert(response.message);

                        // Actualiser les données du tableau si nécessaire
                        location.reload();
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Confirmer le Rachat');
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Erreur réseau';
                        alert('Erreur : ' + msg);
                    }
                });
            });

            $('#popup-remboursement form').on('submit', function(e) {
                e.preventDefault();

                let $form = $(this);
                let $btn = $form.find('button[type="submit"]');

                $btn.prop('disabled', true).text('Enregistrement...');

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        $.magnificPopup.close(); // Fermer la modale

                        // Notification (Simple alert ou SweetAlert2)
                        alert(response.message);

                        // Actualiser les données du tableau si nécessaire
                        location.reload();
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Valider le Remboursement');
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Erreur réseau';
                        alert('Erreur : ' + msg);
                    }
                });
            });

            // LOGIQUE RACHAT FCP
            const rachatFcpForm = $('#form-rachat-fcp');
            const rachatFcpProduct = $('#rachat-fcp-product');
            const rachatFcpDate = $('#rachat-fcp-date');
            const rachatFcpAmount = $('#rachat-fcp-amount');
            const rachatFcpFrais = $('#rachat-fcp-frais');
            const rachatFcpPreview = $('#rachat-fcp-preview');
            const customerId = "{{ $customerId }}";

            // Initialisation du calendrier stylisé
            const pickerRachat = datepicker('#rachat-fcp-date', {
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
                    updateFcpPreview();
                }
            });

            function updateFcpPreview() {
                const prodId = rachatFcpProduct.val();
                const date = rachatFcpDate.val();
                const amount = parseFloat(rachatFcpAmount.val()) || 0;
                const frais = parseFloat(rachatFcpFrais.val()) || 0;

                if (!prodId || !date) {
                    rachatFcpPreview.addClass('hidden');
                    return;
                }

                // Appeler l'API de holdings
                fetch(`/api/product-holdings/${customerId}/${prodId}/${date}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            rachatFcpPreview.removeClass('hidden');

                            const vl = data.vl;
                            const totalValo = data.valuation;
                            const partsARetirer = amount / vl;
                            const netClient = amount - frais;
                            const restant = totalValo - amount;

                            $('#preview-fcp-vl').text(vl.toLocaleString() + ' XAF');
                            $('#preview-fcp-parts').text(partsARetirer.toFixed(4));
                            $('#preview-fcp-net').text(netClient.toLocaleString() + ' XAF');
                            $('#preview-fcp-restant').text(restant.toLocaleString() + ' XAF');
                            $('#rachat-fcp-available-label').text('Disponible à cette date : ' + totalValo
                                .toLocaleString() + ' XAF (' + data.parts.toFixed(4) + ' parts)');

                            if (amount > totalValo) {
                                rachatFcpAmount.addClass('border-red-500');
                                $('#btn-confirm-rachat-fcp').prop('disabled', true).addClass('opacity-50');
                            } else {
                                rachatFcpAmount.removeClass('border-red-500');
                                $('#btn-confirm-rachat-fcp').prop('disabled', false).removeClass('opacity-50');
                            }
                        }
                    });
            }

            rachatFcpProduct.on('change', updateFcpPreview);
            rachatFcpDate.on('change', updateFcpPreview);
            rachatFcpAmount.on('input', updateFcpPreview);
            rachatFcpFrais.on('input', updateFcpPreview);

            rachatFcpForm.on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btn-confirm-rachat-fcp');
                btn.prop('disabled', true).text('VALIDATION...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert(response.message);
                        location.reload();
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('CONFIRMER LE RACHAT FCP');
                        const msg = xhr.responseJSON ? xhr.responseJSON.message :
                            'Erreur réseau';
                        alert('Erreur : ' + msg);
                    }
                });
            });
        });
    </script>
@endsection
