@extends('front-end/app/app-home-asset', [
    'Contrôle et Ajustements',
    'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 control-adjustments-page',
])

@section('content')
<main class="main-content has-sidebar p-4 md:p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Module « Contrôle et Ajustements »</h2>
            <p class="text-sm text-gray-500">Supervision des corrections, simulations d'écarts et contrôles à 4 yeux.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('control-adjustments.history') }}" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 text-sm flex items-center gap-2">
                <i class="ti ti-history"></i> Journal d'Audit Append-Only
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 mb-4 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-gray-800 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif
    @if(session('info'))
        <div class="p-4 mb-4 text-sm text-blue-800 bg-blue-100 rounded-lg dark:bg-gray-800 dark:text-blue-400">
            {{ session('info') }}
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-bg2 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-gray-500 font-semibold">En attente de contrôle</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-1">{{ $pendingCount }}</h3>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-full flex items-center justify-center text-amber-600 text-xl font-bold">
                ⚠️
            </div>
        </div>
        <div class="bg-white dark:bg-bg2 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-gray-500 font-semibold">Corrections Validées</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ $validatedCount }}</h3>
            </div>
            <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600 text-xl font-bold">
                ✅
            </div>
        </div>
        <div class="bg-white dark:bg-bg2 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-gray-500 font-semibold">Demandes Rejetées</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-1">{{ $rejectedCount }}</h3>
            </div>
            <div class="w-12 h-12 bg-rose-50 rounded-full flex items-center justify-center text-rose-600 text-xl font-bold">
                ❌
            </div>
        </div>
    </div>

    <!-- Filters & List -->
    <div class="bg-white dark:bg-bg2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 mb-6">
        <form method="GET" action="{{ route('control-adjustments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1">Produit</label>
                <select name="product_id" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900">
                    <option value="">Tous les produits</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->title ?? $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Client</label>
                <select name="client_id" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900">
                    <option value="">Tous les clients</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->email }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Statut</label>
                <select name="status" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900">
                    <option value="">Tous les statuts</option>
                    <option value="A_controler" {{ request('status') == 'A_controler' ? 'selected' : '' }}>À contrôler</option>
                    <option value="Valide" {{ request('status') == 'Valide' ? 'selected' : '' }}>Validé</option>
                    <option value="Rejete" {{ request('status') == 'Rejete' ? 'selected' : '' }}>Rejeté</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Table of Corrections -->
    <div class="bg-white dark:bg-bg2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="p-3">ID / Date</th>
                    <th class="p-3">Client</th>
                    <th class="p-3">Champ Modifié</th>
                    <th class="p-3">Valeurs (Avant ➔ Après)</th>
                    <th class="p-3">Écart Calculé</th>
                    <th class="p-3">Opérateur</th>
                    <th class="p-3">Statut</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($corrections as $cor)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="p-3 font-mono text-xs">
                            #{{ $cor->id }}<br>
                            <span class="text-gray-400">{{ $cor->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="p-3 font-medium">
                            <a href="{{ route('control-adjustments.show', $cor->user_id) }}" class="text-blue-600 hover:underline">
                                {{ $cor->user->name ?? 'Client #'.$cor->user_id }}
                            </a>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold">{{ $cor->target_entity }}</span>: {{ $cor->field_name }}
                        </td>
                        <td class="p-3 text-xs">
                            <span class="line-through text-red-500 mr-1">{{ $cor->old_value }}</span>
                            ➔ <span class="font-bold text-green-600">{{ $cor->new_value }}</span>
                        </td>
                        <td class="p-3 text-xs">
                            @if(isset($cor->simulation_payload['delta_amount']))
                                <span class="{{ $cor->simulation_payload['delta_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                                    {{ number_format($cor->simulation_payload['delta_amount'], 2, ',', ' ') }} FCFA
                                    ({{ number_format($cor->simulation_payload['delta_percent'], 2) }}%)
                                </span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="p-3 text-xs">
                            {{ $cor->operator->name ?? 'Opérateur #'.$cor->operator_id }}
                        </td>
                        <td class="p-3">
                            @if($cor->status === 'A_controler')
                                <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">À contrôler</span>
                            @elseif($cor->status === 'Valide')
                                <span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold">Validé</span>
                            @else
                                <span class="px-2 py-1 bg-rose-100 text-rose-800 rounded-full text-xs font-semibold">Rejeté</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('control-adjustments.show', $cor->user_id) }}" class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200">
                                    Fiche Client
                                </a>
                                @if($cor->status === 'A_controler' && auth()->id() !== $cor->operator_id)
                                    <form method="POST" action="{{ route('control-adjustments.validate', $cor->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 text-white rounded text-xs hover:bg-emerald-700 font-semibold" onclick="return confirm('Confirmer la validation et le recalcul ?')">
                                            Valider
                                        </button>
                                    </form>
                                @elseif($cor->status === 'A_controler' && auth()->id() === $cor->operator_id)
                                    <span class="text-xs text-gray-400 italic" title="Auto-validation interdite par la règle des 4 yeux">En attente d'un second contrôle</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-gray-400">Aucune demande de correction enregistrée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $corrections->links() }}
        </div>
    </div>
</main>
@endsection
