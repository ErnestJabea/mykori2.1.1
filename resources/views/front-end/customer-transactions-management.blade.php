@extends('front-end/app/app-home-asset', [
    'title' => $customer->name . ' | Clients ',
    'body_class' => 'vertical
bg-secondary1/5 dark:bg-bg3 my-products-page other-page',
])


@section('inner-head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magnific-popup@1.2.0/dist/magnific-popup.css">
    <link rel="stylesheet" href="https://unpkg.com/js-datepicker/dist/datepicker.min.css">
    <style>
        /* ── KORI TABLE STYLING ── */
        .kori-table-wrapper {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            margin: 30px 0;
            width: 100%;
        }

        .kori-fcp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            background: white;
            table-layout: auto;
        }

        .kori-fcp-table thead th {
            background: #2a0e05 !important;
            color: #ffffff !important;
            text-align: left;
            padding: 16px 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 11px;
            border-bottom: 4px solid #ebb009;
        }

        .kori-fcp-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .kori-fcp-table tbody tr:hover {
            background: #fdf3f0;
        }

        .kori-fcp-table td {
            padding: 16px 20px;
            vertical-align: middle;
            color: #334155;
            line-height: 1.5;
        }

        .kori-fcp-table .text-right {
            text-align: right;
        }

        .kori-fcp-table .text-center {
            text-align: center;
        }

        /* Category Badges */
        .kori-badge {
            display: inline-block;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 8px;
            letter-spacing: 0.05em;
            text-align: center;
            line-height: 1;
        }

        .kori-badge-pmg {
            background-color: rgba(235, 176, 9, 0.12);
            color: #c4890a;
            border: 1px solid rgba(235, 176, 9, 0.3);
        }

        .kori-badge-fcp {
            background-color: rgba(83, 29, 9, 0.1);
            color: #531d09;
            border: 1px solid rgba(83, 29, 9, 0.25);
        }

        /* Operation Type Badges */
        .op-badge {
            display: inline-block;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 8px;
            line-height: 1;
            text-align: center;
        }

        .op-precompte {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .op-paiement {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .op-rachat {
            background-color: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }

        .op-souscription {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #dcfce7;
        }

        .op-default {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* Product Title */
        .product-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 13px;
        }

        /* Subtexts */
        .sub-info {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
            display: block;
        }

        /* ── MODAL EDIT OPERATION PREMIUM STYLING ── */
        #modal-edit-operation {
            position: fixed !important;
            inset: 0 !important;
            z-index: 99999 !important;
            /* Ensure it is on top of EVERYTHING */
            background: rgba(15, 23, 42, 0.65) !important;
            /* Dark blue/gray modern tint */
            backdrop-filter: blur(8px) !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            padding: 40px 16px !important;
            /* Provide top and bottom margin for scrolling */
            width: 100vw !important;
            height: 100vh !important;
            overflow-y: auto !important;
            /* Allow modal container to scroll if contents exceed viewport */
        }

        #modal-edit-operation.modalshow {
            display: flex !important;
        }

        #modal-edit-operation.modalhide {
            display: none !important;
        }

        #modal-edit-operation .modal-inner {
            background: #ffffff !important;
            border-radius: 24px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            width: 100% !important;
            max-width: 520px !important;
            max-height: none !important;
            /* Prevent the white card from being limited by global max-height */
            overflow: visible !important;
            /* Allow the datepicker popover to render outside modal boundaries */
            margin: auto !important;
            /* Center the modal card */
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            animation: modalScaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            position: relative !important;
        }

        @keyframes modalScaleIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        #modal-edit-operation .bg-primary\/10 {
            background-color: #531d09 !important;
            /* Deep Kori brown for header */
            padding: 20px 24px !important;
            border-bottom: 3px solid #ebb009 !important;
            /* Accent gold line */
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-top-left-radius: 24px !important;
            /* Keep header corners rounded since modal-inner overflow is visible */
            border-top-right-radius: 24px !important;
            /* Keep header corners rounded since modal-inner overflow is visible */
        }

        #modal-edit-operation h3.text-xl {
            color: #ffffff !important;
            font-size: 18px !important;
            font-weight: 700 !important;
            margin: 0 !important;
            letter-spacing: 0.5px !important;
        }

        #modal-edit-operation button[onclick="closeEditOpModal()"] {
            width: 36px !important;
            height: 36px !important;
            border-radius: 50% !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border: none !important;
            color: #ffffff !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        #modal-edit-operation button[onclick="closeEditOpModal()"]:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            transform: rotate(90deg) !important;
        }

        #modal-edit-operation button[onclick="closeEditOpModal()"] i {
            font-size: 20px !important;
            color: #ffffff !important;
        }

        #modal-edit-operation .p-8 {
            padding: 24px 28px !important;
        }

        #edit-operation-form {
            display: flex !important;
            flex-direction: column !important;
            gap: 18px !important;
        }

        #edit-operation-form>div {
            display: flex !important;
            flex-direction: column !important;
        }

        #edit-operation-form label {
            font-family: 'Inter', sans-serif !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.8px !important;
            color: #64748b !important;
            margin-bottom: 6px !important;
        }

        #edit-operation-form input,
        #edit-operation-form textarea {
            width: 100% !important;
            padding: 12px 16px !important;
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            background-color: #f8fafc !important;
            color: #1e293b !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            outline: none !important;
            transition: all 0.2s ease !important;
            height: auto !important;
            /* Reset any potential height constraints */
        }

        #edit-operation-form input {
            height: 48px !important;
        }

        #edit-operation-form input:focus,
        #edit-operation-form textarea:focus {
            border-color: #531d09 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(83, 29, 9, 0.12) !important;
        }

        #edit-operation-form input[readonly] {
            background-color: #e2e8f0 !important;
            color: #475569 !important;
            cursor: not-allowed !important;
        }

        #edit-operation-form textarea {
            min-height: 90px !important;
            resize: vertical !important;
        }

        /* Response message spacing and design */
        #edit-op-response-msg {
            margin-top: 10px !important;
            margin-bottom: 10px !important;
        }

        /* Footer Buttons Container */
        #edit-operation-form .flex.gap-4 {
            display: flex !important;
            flex-direction: row !important;
            gap: 14px !important;
            padding-top: 10px !important;
        }

        /* Individual Buttons override */
        #edit-operation-form button[onclick="closeEditOpModal()"],
        #btn-save-edit-op {
            flex: 1 !important;
            height: 48px !important;
            border-radius: 12px !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
            /* Reset any margins */
            padding: 0 !important;
        }

        /* Annuler Button style */
        #edit-operation-form button[onclick="closeEditOpModal()"] {
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #475569 !important;
        }

        #edit-operation-form button[onclick="closeEditOpModal()"]:hover {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            border-color: #94a3b8 !important;
            transform: none !important;
            /* cancel rotation */
        }

        /* Sauvegarder Button style */
        #btn-save-edit-op {
            background-color: #ebb009 !important;
            /* Kori Gold */
            border: none !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(235, 176, 9, 0.25) !important;
        }

        #btn-save-edit-op:hover {
            background-color: #d99e04 !important;
            box-shadow: 0 6px 16px rgba(235, 176, 9, 0.35) !important;
        }

        #btn-save-edit-op:disabled {
            background-color: #cbd5e1 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }
    </style>
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
                        <a href="#popup-paiement-liquidite-pmg" class="open-popup-link">
                            <div class="content-card-body">
                                <div class="content-card-icon">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </div>
                                <div class="content-card-info">
                                    <h4>Payer la liquidite</h4>
                                    <p>Verser au client les interets, le capital ou le total disponible.</p>
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
                                            <th class="text-right">SOLDE AVANT</th>
                                            <th class="text-right">MONTANT BRUT</th>
                                            <th class="text-right">SOLDE APRÈS</th>
                                            <th class="text-right">PARTS (FCP)</th>
                                            <th>COMMENTAIRE</th>
                                            <th class="text-center">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($allOperations as $op)
                                            <tr>
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="font-bold text-n900">{{ \Carbon\Carbon::parse($op->date_op)->format('d/m/Y') }}</span>
                                                        <span
                                                            class="text-xs opacity-60">{{ \Carbon\Carbon::parse($op->date_op)->format('H:i') }}</span>
                                                    </div>
                                                </td>

                                                <td class="text-center">
                                                    @if ($op->category == 'PMG')
                                                        <span class="kori-badge kori-badge-pmg">PMG</span>
                                                    @else
                                                        <span class="kori-badge kori-badge-fcp">FCP</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <span class="product-title">{{ $op->product_title }}</span>
                                                </td>

                                                <td>
                                                    <span
                                                        class="font-mono text-xs opacity-70">{{ $op->reference ?? '-' }}</span>
                                                </td>

                                                <td>
                                                    @if ($op->type == 'precompte_interets')
                                                        <span class="op-badge op-precompte">Précompte Int.</span>
                                                    @elseif($op->type == 'paiement_interets')
                                                        <span class="op-badge op-paiement">Paiement Int.</span>
                                                    @elseif($op->type == 'liquidite_interets')
                                                        <span class="op-badge op-paiement">Liquidite Int.</span>
                                                    @elseif($op->type == 'liquidite_capital')
                                                        <span class="op-badge op-paiement">Liquidite Capital</span>
                                                    @elseif($op->type == 'paiement_capital')
                                                        <span class="op-badge op-paiement">Paiement Capital</span>
                                                    @elseif($op->type == 'rachat_partiel' || $op->type == 'rachat' || $op->type == 'rachat_total')
                                                        <span class="op-badge op-rachat">Rachat</span>
                                                    @elseif($op->type == 'souscription')
                                                        <span class="op-badge op-souscription">Souscription</span>
                                                    @else
                                                        <span
                                                            class="op-badge op-default">{{ ucfirst(str_replace('_', ' ', $op->type)) }}</span>
                                                    @endif
                                                </td>

                                                <td class="text-right">
                                                    <span class="font-mono text-xs opacity-80">
                                                        @if ($op->category == 'PMG')
                                                            {{ number_format($op->balance_before, 0, ',', ' ') }} XAF
                                                        @else
                                                            {{ number_format($op->balance_before, 0, ',', ' ') }} XAF
                                                            <br>
                                                            <small
                                                                class="opacity-60 text-[10px]">{{ number_format($op->parts_before, 4) }}
                                                                parts</small>
                                                        @endif
                                                    </span>
                                                </td>

                                                <td class="text-right">
                                                    <span class="font-bold text-n900">
                                                        {{ number_format($op->amount, 0, ',', ' ') }} XAF
                                                    </span>
                                                </td>

                                                <td class="text-right">
                                                    <span class="font-mono text-xs font-bold text-n900">
                                                        @if ($op->category == 'PMG')
                                                            {{ number_format($op->balance_after, 0, ',', ' ') }} XAF
                                                        @else
                                                            {{ number_format($op->balance_after, 0, ',', ' ') }} XAF
                                                            <br>
                                                            <small
                                                                class="opacity-60 font-normal text-[10px]">{{ number_format($op->parts_after, 4) }}
                                                                parts</small>
                                                        @endif
                                                    </span>
                                                </td>

                                                <td class="text-right">
                                                    @if ($op->parts_change !== null)
                                                        <span
                                                            class="inline-flex px-2 py-1 rounded {{ $op->parts_change < 0 ? 'bg-red-50 text-red-600' : 'bg-green-100 text-green-700' }} text-xs font-bold font-mono">
                                                            {{ $op->parts_change > 0 ? '+' : '' }}{{ number_format($op->parts_change, 4) }}
                                                        </span>
                                                    @else
                                                        <span class="opacity-30 font-bold">-</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <p class="text-xs opacity-70 italic max-w-[200px] truncate m-0"
                                                        title="{{ $op->comment }}">{{ $op->comment ?? '-' }}</p>
                                                    @if (!empty($op->payment_reference))
                                                        <div class="text-[10px] mt-1 leading-4">
                                                            <span class="font-bold">Paiement:</span>
                                                            {{ strtoupper(str_replace('_', ' ', $op->payment_method ?? '-')) }}
                                                            / Ref. {{ $op->payment_reference }}
                                                            @if (!empty($op->payment_proof_path))
                                                                <br>
                                                                <a href="{{ asset('storage/' . $op->payment_proof_path) }}" target="_blank" class="text-primary font-bold">
                                                                    Justificatif
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    <button type="button"
                                                        class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 hover:bg-orange-200 transition-all flex items-center justify-center mx-auto"
                                                        onclick="openEditOpModal('{{ $op->id }}', '{{ $op->category }}', '{{ $op->type }}', '{{ $op->amount }}', '{{ $op->vl_applied ?? 0 }}', '{{ \Carbon\Carbon::parse($op->date_op)->toDateString() }}', '{{ rawurlencode($op->comment ?? '') }}')">
                                                        <i class="las la-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center py-8 opacity-50">
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
                    <form action="{{ route('transactions.precompte') }}" method="POST" enctype="multipart/form-data">
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
                        <div class="form-group mb-3">
                            <label>Date de paiement</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Mode de paiement</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="virement">Virement bancaire</option>
                                <option value="cheque">Cheque</option>
                                <option value="mobile_money">Mobile money</option>
                                <option value="especes">Especes</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Reference de paiement</label>
                            <input type="text" name="payment_reference" class="form-control" placeholder="Ex: ref virement, cheque, transaction" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Justificatif de paiement</label>
                            <input type="file" name="payment_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
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
                            <input type="number" name="amount_frais" class="form-control"
                                placeholder="Frais de gestion " required>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-warning">Confirmer le Rachat</button>
                        </div>
                    </form>
                </div>

                <div id="popup-paiement-liquidite-pmg" class="mfp-hide white-popup-block">
                    <h3>Paiement de la liquidite PMG</h3>
                    <hr>
                    <form action="{{ route('transactions.paiement-liquidite-pmg') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Selectionner le mandat (PMG)</label>
                            <select name="transaction_id" class="form-control" required>
                                @foreach ($transactionsUsers as $trans)
                                    @php
                                        $product = App\Models\Product::where('id', $trans->product_id)->first();
                                    @endphp
                                    <option value="{{ $trans->id }}">
                                        {{ $product->title ?? 'PMG' }} - {{ $trans->ref ?? 'Sans ref.' }} - Initial:
                                        {{ number_format($trans->amount, 0, ',', ' ') }} XAF
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Type de paiement</label>
                            <select name="scope" class="form-control" required>
                                <option value="interets">Interets uniquement</option>
                                <option value="capital">Capital uniquement</option>
                                <option value="total">Capital + interets</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Montant (optionnel)</label>
                            <input type="number" name="amount" class="form-control" placeholder="Laisser vide pour payer tout le solde">
                        </div>
                        <div class="form-group mb-3">
                            <label>Date de paiement</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Mode de paiement</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="virement">Virement bancaire</option>
                                <option value="cheque">Cheque</option>
                                <option value="mobile_money">Mobile money</option>
                                <option value="especes">Especes</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Reference de paiement</label>
                            <input type="text" name="payment_reference" class="form-control" placeholder="Ex: ref virement, cheque, transaction" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Justificatif de paiement</label>
                            <input type="file" name="payment_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-success">Valider le paiement</button>
                        </div>
                    </form>
                </div>

                <div id="popup-remboursement" class="mfp-hide white-popup-block">
                    <h3>Remboursement des Intérêts</h3>
                    <hr>
                    <form action="{{ route('transactions.remboursement-interets') }}" method="POST" enctype="multipart/form-data">
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
                        <div class="form-group mb-3">
                            <label>Date de paiement</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Mode de paiement</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="virement">Virement bancaire</option>
                                <option value="cheque">Cheque</option>
                                <option value="mobile_money">Mobile money</option>
                                <option value="especes">Especes</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Reference de paiement</label>
                            <input type="text" name="payment_reference" class="form-control" placeholder="Ex: ref virement, cheque, transaction" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Justificatif de paiement</label>
                            <input type="file" name="payment_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
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

            <!-- MODALE MODIFICATION OPERATION -->
            <div id="modal-edit-operation"
                class="ac-modal-overlay modalhide fixed inset-0 z-[100] bg-n900/50 backdrop-blur-sm flex items-center justify-center p-4"
                style="display:none;">
                <div
                    class="modal-inner bg-white dark:bg-bg4 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-modal-in">
                    <div class="p-6 border-b border-n30 flex justify-between items-center bg-primary/10">
                        <h3 class="text-xl font-bold text-n900 dark:text-n0">Modifier l'Opération</h3>
                        <button type="button" onclick="closeEditOpModal()"
                            class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-white transition-all text-n500">
                            <i class="las la-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-8">
                        <form id="edit-operation-form" class="space-y-5">
                            @csrf
                            <input type="hidden" name="op_id" id="edit-op-id">
                            <input type="hidden" name="op_category" id="edit-op-category">
                            <input type="hidden" name="op_type" id="edit-op-type">

                            <div>
                                <label
                                    class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic">Montant
                                    de l'Opération (XAF)</label>
                                <input type="number" step="0.01" name="amount" id="edit-op-amount"
                                    class="w-full h-[50px] p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50"
                                    required>
                            </div>

                            <div id="edit-op-vl-container">
                                <label id="edit-op-vl-label"
                                    class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic">Taux/VL</label>
                                <input type="number" step="0.000001" name="vl_applied" id="edit-op-vl"
                                    class="w-full h-[50px] p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50"
                                    readonly>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic">Date
                                    d'effet</label>
                                <input type="text" name="date_operation" id="edit-op-date"
                                    class="w-full h-[50px] p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50"
                                    readonly required>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-bold uppercase text-n500 mb-2 font-Inter tracking-widest italic font-bold">Commentaire
                                    / Justification</label>
                                <textarea name="comments" id="edit-op-comments" rows="3"
                                    class="w-full p-4 rounded-xl border border-n30 focus:border-primary outline-none text-sm font-bold bg-n10/50"></textarea>
                            </div>

                            <div id="edit-op-response-msg" class="hidden"></div>

                            <div class="flex gap-4 pt-4">
                                <button type="button" onclick="closeEditOpModal()"
                                    class="flex-1 h-[50px] rounded-xl border border-n30 text-n500 font-bold uppercase tracking-wider hover:bg-n10 transition-all">
                                    Annuler
                                </button>
                                <button type="submit" id="btn-save-edit-op"
                                    class="flex-1 h-[50px] rounded-xl bg-primary text-white font-bold uppercase tracking-wider hover:bg-primary/90 transition-all shadow-lg shadow-primary/20">
                                    Sauvegarder
                                </button>
                            </div>
                        </form>
                    </div>
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
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
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

            $('#popup-paiement-liquidite-pmg form').on('submit', function(e) {
                e.preventDefault();

                let $form = $(this);
                let $btn = $form.find('button[type="submit"]');

                $btn.prop('disabled', true).text('Enregistrement...');

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $.magnificPopup.close();
                        alert(response.message);
                        location.reload();
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text('Valider le paiement');
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Erreur reseau';
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
            // MODIFICATION OPERATION LOGIQUE
            let pickerEditOpDate = null;

            window.openEditOpModal = function(id, category, type, amount, vl, dateOp, commentEncoded) {
                console.log("Opening edit op modal for ID:", id);
                const modal = $('#modal-edit-operation');
                if (!modal.length) {
                    console.error("Modal #modal-edit-operation not found in DOM");
                    return;
                }

                const comment = decodeURIComponent(commentEncoded);

                $('#edit-op-id').val(id);
                $('#edit-op-category').val(category);
                $('#edit-op-type').val(type);
                $('#edit-op-amount').val(amount);
                $('#edit-op-vl').val(vl);
                $('#edit-op-date').val(dateOp);
                $('#edit-op-comments').val(comment);

                if (category === 'PMG') {
                    $('#edit-op-vl-label').text("Taux d'intérêt (%)");
                } else {
                    $('#edit-op-vl-label').text("Valeur Liquidative (VL)");
                }
                $('#edit-op-vl-container').show();

                if (!pickerEditOpDate) {
                    pickerEditOpDate = datepicker('#edit-op-date', {
                        formatter: (input, date, instance) => {
                            const value = date.getFullYear() + '-' + String(date.getMonth() + 1)
                                .padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                            input.value = value;
                        },
                        startDay: 1,
                        customDays: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                        customMonths: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet',
                            'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
                        ],
                        overlayButton: "Valider",
                        overlayPlaceholder: "Année (4 chiffres)"
                    });

                    // Ensure calendar opens explicitly when clicking the date input field
                    $('#edit-op-date').on('click', function(e) {
                        e.stopPropagation();
                        pickerEditOpDate.show();
                    });
                }

                modal.removeClass('modalhide').addClass('modalshow').css('display', 'flex');

                // Force calendar to be hidden when the modal is opened
                if (pickerEditOpDate) {
                    pickerEditOpDate.hide();
                }
            };

            window.closeEditOpModal = function() {
                $('#modal-edit-operation').addClass('modalhide').removeClass('modalshow').hide();
                $('#edit-op-response-msg').addClass('hidden').html('');
            };

            const editOpForm = document.getElementById('edit-operation-form');
            if (editOpForm) {
                editOpForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btn-save-edit-op');
                    const resp = document.getElementById('edit-op-response-msg');
                    btn.disabled = true;
                    btn.textContent = "Mise à jour...";

                    $.ajax({
                        url: "{{ route('financial-movement.edit') }}",
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
                        error: function(xhr) {
                            const msg = xhr.responseJSON ? xhr.responseJSON.message :
                                "Erreur lors de la modification.";
                            resp.textContent = msg;
                            resp.className =
                                "block bg-red-100 text-red-700 p-3 rounded-xl text-xs font-bold uppercase italic mt-4";
                            resp.classList.remove('hidden');
                            btn.disabled = false;
                            btn.textContent = "Sauvegarder";
                        }
                    });
                });
            }
        });
    </script>
@endsection
