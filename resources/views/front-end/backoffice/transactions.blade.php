@extends('front-end/app/app-home-asset', ['title' => 'Liste des Transactions Backoffice'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-6 md:gap-8 min-h-screen">

                <div class="flex justify-between items-center sm:flex-row flex-col gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-n900 mb-1">Historique des Transactions</h2>
                        <p class="text-n500 text-sm italic">Suivi complet des opérations et statuts de validation.</p>
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-n30 bg-gray-50/50 flex gap-4">
                         <span class="px-4 py-2 bg-primary text-white shadow-md rounded-xl text-xs font-bold transition-all italic">Transactions en attente de validation</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-n20/30 text-n500 text-[11px] uppercase tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4 text-center">Ref</th>
                                    <th class="px-6 py-4">Client</th>
                                    <th class="px-6 py-4">Produit</th>
                                    <th class="px-6 py-4 text-center">Flux</th>
                                    <th class="px-6 py-4 text-right">Montant</th>
                                    <th class="px-6 py-4">Statut</th>
                                    <th class="px-6 py-4">Workflow</th>
                                    <th class="px-6 py-4 text-center italic">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30">
                                @foreach($transactions as $trans)
                                    <tr class="hover:bg-n20/20 transition-all group">
                                        <td class="px-6 py-4">
                                            <span class="text-xs text-n500 font-medium italic">{{ optional($trans->date_validation)->format('d/m/Y') ?? $trans->created_at->format('d/m/Y') }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-xs font-bold text-primary italic">{{ $trans->ref ?? 'T-'.$trans->id }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-bold text-n900 transition-all hover:text-primary cursor-default italic">{{ $trans->user->name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs text-n600 italic">{{ $trans->product->title ?? 'N/A' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold">
                                             @if($trans->type_flux == 'main')
                                                <span class="text-[10px] text-primary bg-primary/10 px-2 py-0.5 rounded italic">INITIALE</span>
                                             @else
                                                <span class="text-[10px] text-secondary bg-secondary/10 px-2 py-0.5 rounded italic">AJOUT</span>
                                             @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-sm font-bold text-n900">{{ number_format($trans->amount + ($trans->fees ?? 0), 0, '.', ' ') }} XAF</span>
                                        </td>
                                        <td class="px-6 py-4">
                                             @if($trans->status == 'Succès')
                                                <span class="px-3 py-1 bg-success/10 text-success rounded-full text-[10px] font-extrabold uppercase tracking-wide italic">Activé</span>
                                             @else
                                                <span class="px-3 py-1 bg-warning/10 text-warning rounded-full text-[10px] font-extrabold uppercase tracking-wide italic">En attente</span>
                                             @endif
                                        </td>
                                        <td class="px-6 py-4 italic">
                                            <div class="flex gap-1 items-center">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] {{ $trans->is_compliance_validated ? 'bg-success text-white' : 'bg-gray-200 text-gray-500 shadow-inner' }}" title="Compliance">C</div>
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] {{ $trans->is_backoffice_validated ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500 shadow-inner' }}" title="Backoffice">B</div>
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] {{ $trans->is_dg_validated ? 'bg-secondary text-white' : 'bg-gray-200 text-gray-500 shadow-inner' }}" title="DG">D</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button 
                                                    onclick="openTransactionModal({{ $trans->toJson() }}, '{{ route('backoffice.validate-transaction', [$trans->id, $trans->type_flux]) }}')"
                                                    class="p-2 bg-marron/10 text-marron rounded-xl hover:bg-marron hover:text-white transition-all shadow-sm" 
                                                    title="Audit Transactionnel">
                                                    <i class="las la-eye text-lg text-primary"></i>
                                                </button>
                                                @if($trans->status == 'En attente')
                                                <form action="{{ route('backoffice.validate-transaction', [$trans->id, $trans->type_flux]) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="p-2 bg-success/10 text-success rounded-xl hover:bg-success hover:text-white transition-all shadow-sm">
                                                        <i class="las la-check text-lg"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-6 italic">
                        {{ $transactions->links() }}
                    </div>
                </div>

            </div>
        </div>
    </main>
    @include('partials.transaction-details-modal')
    @include('partials.ajax-validation-script')
@endsection
