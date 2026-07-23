@extends('front-end/app/app-home-asset', [
    'Journal d\'Audit Append-Only',
    'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 control-history-page',
])

@section('content')
<main class="main-content has-sidebar p-4 md:p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('control-adjustments.index') }}" class="text-sm text-blue-600 hover:underline">← Retour aux contrôles</a>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">Journal d'Audit Intégral (Append-Only)</h2>
            <p class="text-sm text-gray-500">Traçabilité absolue des événements, demandes de correction, simulations, validations et rejets.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-bg2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 mb-6">
        <form method="GET" action="{{ route('control-adjustments.history') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1">Type d'Événement</label>
                <select name="event_type" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900">
                    <option value="">Tous les événements</option>
                    <option value="DEMANDE_CORRECTION" {{ request('event_type') == 'DEMANDE_CORRECTION' ? 'selected' : '' }}>DEMANDE_CORRECTION</option>
                    <option value="VALIDATION" {{ request('event_type') == 'VALIDATION' ? 'selected' : '' }}>VALIDATION</option>
                    <option value="REJET" {{ request('event_type') == 'REJET' ? 'selected' : '' }}>REJET</option>
                    <option value="SIMULATION" {{ request('event_type') == 'SIMULATION' ? 'selected' : '' }}>SIMULATION</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Client Concerné</label>
                <select name="client_id" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900">
                    <option value="">Tous les clients</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Filtrer l'Audit</button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-bg2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="p-3">Horodatage</th>
                    <th class="p-3">Événement</th>
                    <th class="p-3">Client</th>
                    <th class="p-3">Champ / Cible</th>
                    <th class="p-3">Modifications</th>
                    <th class="p-3">Motif / Commentaire</th>
                    <th class="p-3">Auteur (IP)</th>
                    <th class="p-3">Contrôleur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="p-3 font-mono text-gray-500">{{ $log->action_at->format('d/m/Y H:i:s') }}</td>
                        <td class="p-3 font-bold">
                            @if($log->event_type === 'VALIDATION')
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold">VALIDATION</span>
                            @elseif($log->event_type === 'REJET')
                                <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded font-bold">REJET</span>
                            @else
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded font-bold">{{ $log->event_type }}</span>
                            @endif
                        </td>
                        <td class="p-3 font-medium">{{ $log->user->name ?? 'Client #'.$log->user_id }}</td>
                        <td class="p-3"><span class="font-semibold">{{ $log->target_entity }}</span>: {{ $log->field_name }}</td>
                        <td class="p-3">
                            <span class="line-through text-red-500">{{ $log->old_value }}</span> ➔ <span class="text-green-600 font-bold">{{ $log->new_value }}</span>
                        </td>
                        <td class="p-3">{{ $log->reason ?? $log->comment ?? '-' }}</td>
                        <td class="p-3">{{ $log->operator->name ?? 'Opérateur' }} <span class="text-gray-400 font-mono">({{ $log->ip_address }})</span></td>
                        <td class="p-3">{{ $log->controller->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-gray-400">Aucun enregistrement d'audit trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $logs->links() }}
        </div>
    </div>
</main>
@endsection
