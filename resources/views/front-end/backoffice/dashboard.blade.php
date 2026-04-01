@extends('front-end/app/app-home-asset', ['title' => 'Tableau de Bord Backoffice'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-6 md:gap-8 min-h-screen">

                <!-- Header Section -->
                <div class="flex justify-between items-center sm:flex-row flex-col gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-n900 mb-1">Tableau de bord Backoffice</h2>
                        <p class="text-n500 text-sm">Gestion des validations et suivi des expirations PMG.</p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm flex items-center gap-4">
                        <div class="p-4 bg-primary/10 rounded-2xl">
                            <i class="las la-file-invoice text-primary text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-n500 text-xs font-bold uppercase tracking-wider mb-1">En attente (Global)</p>
                            <h3 class="text-2xl font-bold text-n900">{{ $pendingCount + $pendingSuppCount }}</h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm flex items-center gap-4">
                        <div class="p-4 bg-warning/10 rounded-2xl">
                            <i class="las la-hourglass-end text-warning text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-n500 text-xs font-bold uppercase tracking-wider mb-1">PMG Expirent Bientôt</p>
                            <h3 class="text-2xl font-bold text-n900">{{ $expiringPmg->count() }}</h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm flex items-center gap-4">
                        <div class="p-4 bg-success/10 rounded-2xl">
                            <i class="las la-check-circle text-success text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-n500 text-xs font-bold uppercase tracking-wider mb-1">Rôle Actuel</p>
                            <h3 class="text-xl font-bold text-n900">ADMIN / BO</h3>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    
                    <!-- Pending Operations -->
                    <div class="bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden min-h-[400px]">
                        <div class="p-6 border-b border-n30 flex justify-between items-center bg-gray-50/50">
                            <h3 class="font-bold text-n900 flex items-center gap-2">
                                <i class="las la-list text-primary text-xl"></i>
                                Opérations en attente de validation
                            </h3>
                            <a href="{{ route('backoffice.transactions', ['filter' => 'pending']) }}" class="text-primary text-sm font-semibold hover:underline">Voir tout</a>
                        </div>
                        <div class="p-0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-n20/30 text-n500 text-[11px] uppercase tracking-wider font-bold">
                                            <th class="px-6 py-4">Client / Produit</th>
                                            <th class="px-6 py-4 text-right">Montant</th>
                                            <th class="px-6 py-4">Workflow</th>
                                            <th class="px-6 py-4 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-n30">
                                        @forelse($pendingTransactions as $trans)
                                            <tr class="hover:bg-n20/20 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-n900 text-sm italic">{{ $trans->user->name ?? 'N/A' }}</span>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-[10px] text-n500">{{ $trans->product->title ?? 'N/A' }}</span>
                                                            @if(($trans->type_flux ?? 'main') == 'main')
                                                                <span class="text-[8px] bg-primary/10 text-primary px-1 rounded font-bold uppercase">Initial</span>
                                                            @else
                                                                <span class="text-[8px] bg-secondary/10 text-secondary px-1 rounded font-bold uppercase">Ajout</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <span class="text-marron font-black text-base italic">{{ number_format($trans->amount + ($trans->fees ?? 0), 0, '.', ' ') }}</span> 
                                                    <span class="text-[9px] text-n400 font-bold ml-1">XAF</span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex gap-1 items-center">
                                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] {{ $trans->is_compliance_validated ? 'bg-success text-white' : 'bg-gray-200 text-gray-500' }}" title="Compliance">C</div>
                                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] {{ $trans->is_backoffice_validated ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500' }}" title="Backoffice">B</div>
                                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] {{ $trans->is_dg_validated ? 'bg-secondary text-white' : 'bg-gray-200 text-gray-500' }}" title="DG">D</div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button 
                                                            onclick="openTransactionModal({{ $trans->toJson() }}, '{{ route('backoffice.validate-transaction', [$trans->id, $trans->type_flux ?? 'main']) }}')"
                                                            class="p-2 bg-marron/10 text-marron rounded-xl hover:bg-marron hover:text-white transition-all shadow-sm" 
                                                            title="Audit Transactionnel">
                                                            <i class="las la-eye text-lg"></i>
                                                        </button>
                                                        <form action="{{ route('backoffice.validate-transaction', [$trans->id, $trans->type_flux ?? 'main']) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="p-2 bg-primary/10 text-primary rounded-xl hover:bg-primary hover:text-white transition-all shadow-sm transform active:scale-95 transition-all">
                                                                <i class="las la-check"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-6 py-12 text-center text-n500 italic">Aucune opération en attente</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Expiring PMG -->
                    <div class="bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden min-h-[400px]">
                        <div class="p-6 border-b border-n30 flex justify-between items-center bg-gray-50/50">
                            <h3 class="font-bold text-n900 flex items-center gap-2">
                                <i class="las la-clock text-warning text-xl"></i>
                                Expirations PMG (30 jours)
                            </h3>
                        </div>
                        <div class="p-0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-n20/30 text-n500 text-[11px] uppercase tracking-wider font-bold">
                                            <th class="px-6 py-4">Produit / Client</th>
                                            <th class="px-6 py-4">Échéance</th>
                                            <th class="px-6 py-4 text-right">Capital</th>
                                            <th class="px-6 py-4 text-center">Délai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-n30">
                                        @forelse($expiringPmg as $exp)
                                            <tr class="hover:bg-n20/20 transition-colors">
                                                <td class="px-6 py-4 italic">
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-n900 text-sm">{{ $exp->product->title }}</span>
                                                        <span class="text-xs text-n500">{{ $exp->user->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($exp->date_echeance)->format('d/m/Y') }}</td>
                                                <td class="px-6 py-4 text-right font-bold text-sm">{{ number_format($exp->amount, 0, '.', ' ') }} XAF</td>
                                                <td class="px-6 py-4 text-center">
                                                    @php
                                                        $days = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($exp->date_echeance));
                                                    @endphp
                                                    <span class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-[10px] font-bold">{{ $days }} j</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-6 py-12 text-center text-n500 italic">Aucune expiration prochaine</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </main>
    @include('partials.transaction-details-modal')
@endsection
