@extends('front-end.app.app-home-asset', ['title' => 'Tableau de Bord Compliance', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page other-page compliance-profil'])

@section('content')
    <main class="main-content has-sidebar my-products-page other-page">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Header Section -->
            <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                <div
                    class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div>
                        <h3 class="h3 uppercase">CONTRÔLE ET CONFORMITÉ</h3>
                        <p class="text-sm opacity-70 text-primary font-medium">Tableau de bord de surveillance</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden md:block text-right mr-4 border-r border-n30 pr-4">
                            <p class="font-medium">{{ date('d-m-Y') }}</p>
                            <span class="text-xs opacity-50">État du système</span>
                        </div>
                        <a href="{{ route('compliance.export') }}?type=transactions"
                            class="btn bg-primary text-white rounded-lg px-4 py-2 hover:opacity-90 duration-300 flex items-center gap-2 text-sm shadow-sm">
                            <i class="las la-file-export"></i> Export Global CSV
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                <div
                    class="box bg-primary p-6 rounded-2xl text-white relative overflow-hidden h-full min-h-[140px] flex flex-col justify-center shadow-lg">
                    <div class="relative z-10">
                        <p class="text-sm opacity-80 mb-1 font-medium text-white/90">Total Clients Sous Surveillance</p>
                        <h2 class="h2 mb-0 text-white">{{ $totalClients }}</h2>
                    </div>
                    <i class="las la-users -bottom-4 -right-4 text-[100px] opacity-10 rotate-12"></i>
                </div>
            </div>

            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                <div
                    class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 flex flex-col justify-center h-full min-h-[140px] shadow-sm">
                    <div>
                        <p class="text-sm opacity-70 mb-1 font-medium text-n500">Transactions Validées</p>
                        <div class="flex items-end gap-4">
                            <h2 class="h2 text-secondary1 mb-0">{{ $totalTransactions }}</h2>
                            <p class="text-xs mb-1 text-success font-medium flex items-center gap-1">
                                <i class="las la-check-circle text-lg"></i> Statut Succès
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-n30">
                        <p class="text-[10px] opacity-50 italic">Données consolidées des flux PMG & FCP</p>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div
                    class="box bg-[#5F4607] p-6 rounded-2xl text-white relative overflow-hidden h-full min-h-[140px] flex flex-col justify-center shadow-lg">
                    <div class="relative z-10">
                        <h3 class="h2 mb-0 text-white">
                            <i class="las la-chart-area -bottom-4 -right-4 text-[100px] opacity-10 rotate-12"></i>
                            Historique des VL
                        </h3>
                        <div class="mt-3 flex align-center">
                            <a href="{{ route('compliance.vl-history') }}"
                                class="text-xs bg-primary text-white px-3 py-1 rounded-full font-bold hover:bg-n30 duration-300">
                                <span>Voir évolution</span> <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unified Validation Flows Table -->
            <div class="col-span-12">
                <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-n30 flex justify-between items-center bg-n10/30">
                        <h4 class="h4 flex items-center gap-2">
                            <i class="las la-exchange-alt text-primary font-bold"></i> Flux de Souscriptions à Valider
                            (Initiale & Complémentaire)
                        </h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead>
                                <tr class="bg-n20 dark:bg-bg3 text-n500 uppercase text-[11px] font-bold">
                                    <th class="py-4 px-6 text-start">Date</th>
                                    <th class="py-4 px-6 text-start">Client</th>
                                    <th class="py-4 px-6 text-start">Produit</th>
                                    {{-- <th class="py-4 px-6 text-center">Type Flux</th> --}}
                                    <th class="py-4 px-6 text-end">Montant</th>
                                    {{-- <th class="py-4 px-6 text-center">Workflow</th> --}}
                                    <th class="py-4 px-6 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30">
                                @forelse ($allPending as $transaction)
                                    <tr class="hover:bg-n10/50 duration-200">
                                        <td class="py-4 px-6 text-sm">{{ $transaction->created_at->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium">{{ $transaction->user->name ?? 'N/A' }}</span>
                                                <span
                                                    class="text-[10px] opacity-60">{{ $transaction->ref ?? 'T-' . $transaction->id }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span
                                                class="px-2 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold uppercase">
                                                {{ $transaction->product->title ?? 'N/A' }}
                                            </span>
                                        </td>
                                        {{-- <td class="py-4 px-6 text-center">
                                            @if ($transaction->type_flux == 'main')
                                                <span class="text-[10px] bg-primary/10 text-primary px-2 py-0.5 rounded font-bold">INITIALE</span>
                                            @else
                                                <span class="text-[10px] bg-secondary1/10 text-secondary1 px-2 py-0.5 rounded font-bold">COMPLÉMENTAIRE</span>
                                            @endif
                                        </td> --}}
                                        <td class="py-4 px-6 text-end font-bold text-n700">
                                            {{ number_format($transaction->amount, 0, ' ', ' ') }} XAF
                                        </td>
                                        {{-- <td class="py-4 px-6 text-center">
                                            <div class="flex gap-1 items-center justify-center">
                                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] {{ $transaction->is_compliance_validated ? 'bg-success text-white' : 'bg-gray-200 text-gray-400' }}"
                                                    title="Compliance">C</div>
                                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] {{ $transaction->is_backoffice_validated ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400' }}"
                                                    title="Backoffice">B</div>
                                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] {{ $transaction->is_dg_validated ? 'bg-secondary text-white' : 'bg-gray-200 text-gray-400' }}"
                                                    title="DG">D</div>
                                            </div>
                                        </td> --}}
                                        <td class="py-4 px-6 text-center flex items-center justify-center gap-2">
                                            <!-- View Details Button -->
                                            <button
                                                onclick="openTransactionModal({{ $transaction->toJson() }}, '{{ route('backoffice.validate-transaction', [$transaction->id, $transaction->type_flux]) }}')"
                                                class="p-1.5 bg-marron/10 text-marron rounded-lg hover:bg-marron hover:text-white transition-all shadow-sm"
                                                title="Voir Détails">
                                                <i class="las la-eye text-lg"></i>
                                            </button>

                                            @if (!$transaction->is_compliance_validated)
                                                <form
                                                    action="{{ route('backoffice.validate-transaction', [$transaction->id, $transaction->type_flux]) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="p-1.5 bg-success/10 text-success rounded-lg hover:bg-success hover:text-white transition-all shadow-sm"
                                                        title="Valider l'opération">
                                                        <i class="las la-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('compliance.client-history', $transaction->user_id) }}"
                                                class="text-primary hover:text-secondary1 text-lg"
                                                title="Historique Client">
                                                <i class="las la-user-shield"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 text-center opacity-50 italic">Aucune opération en
                                            attente de contrôle.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('front-end.partials.transaction-details-modal')
    @include('front-end.partials.ajax-validation-script')
@endsection
