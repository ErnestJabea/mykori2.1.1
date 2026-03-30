@extends('front-end/app/app-home-asset', [
    'title' => $customer->name . ' | Clients ',
    'body_class' => 'vertical
bg-secondary1/5 dark:bg-bg3 my-products-page other-page',
])


@section('inner-head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magnific-popup@1.2.0/dist/magnific-popup.css">
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
                    <div class="content-card">
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
                                        {{ $product->title }} - (Initial: {{ number_format($trans->amount, 0, ',', ' ') }} XAF)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Montant du remboursement (XAF)</label>
                            <input type="number" name="amount" class="form-control" placeholder="Montant à rembourser" required>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-success">Valider le Remboursement</button>
                        </div>
                    </form>
                </div>
            </div>
    </main>
@endsection


@section('script_front_end')
    <script src="https://cdn.jsdelivr.net/npm/magnific-popup@1.2.0/dist/jquery.magnific-popup.min.js"></script>
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
        });
    </script>
@endsection
