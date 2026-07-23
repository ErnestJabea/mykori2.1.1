@extends('front-end/app/app-home-asset', [
    'Fiche de Contrôle Relevé',
    'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 control-show-page',
])

@section('content')
<main class="main-content has-sidebar p-4 md:p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('control-adjustments.index') }}" class="text-sm text-blue-600 hover:underline">← Retour à la liste</a>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">Fiche de Contrôle : {{ $client->name }}</h2>
            <p class="text-sm text-gray-500">Email: {{ $client->email }} | Téléphone: {{ $client->phone ?? 'N/A' }}</p>
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

    <!-- Portfolio Summary & Contracts -->
    <div class="grid grid-cols-1 gap-6 mb-8">
        @foreach($statementData as $item)
            @php $trans = $item['transaction']; @endphp
            <div class="bg-white dark:bg-bg2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-4 mb-4 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-bold uppercase">
                            {{ $trans->product->products_category_id == 2 ? 'PMG' : 'FCP' }}
                        </span>
                        <h4 class="text-lg font-bold text-gray-800 dark:text-white inline ml-2">{{ $trans->product->title ?? $trans->product->name }}</h4>
                        <p class="text-xs text-gray-400 font-mono mt-1">Réf: {{ $trans->ref }} | Date valeur: {{ $trans->date_validation ? $trans->date_validation->format('d/m/Y') : 'N/A' }}</p>
                    </div>
                    <div class="text-right mt-2 md:mt-0">
                        <p class="text-xs text-gray-400 uppercase font-semibold">Valorisation Relevé</p>
                        <h3 class="text-xl font-bold text-emerald-600">{{ number_format($item['valuation'], 2, ',', ' ') }} FCFA</h3>
                    </div>
                </div>

                <!-- Parameters Table -->
                <div class="mb-4">
                    <h5 class="text-xs uppercase font-bold text-gray-500 mb-2">Paramètres Contractuels Source</h5>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg text-xs">
                        <div>
                            <span class="text-gray-400 block">Capital / Montant Initial:</span>
                            <span class="font-semibold">{{ number_format($trans->montant_initiale ?? $trans->amount, 2, ',', ' ') }} FCFA</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">Taux / VL d'achat:</span>
                            <span class="font-semibold">{{ $trans->vl_buy }} {{ $trans->product->products_category_id == 2 ? '%' : 'FCFA' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">Date d'échéance:</span>
                            <span class="font-semibold">{{ $trans->date_echeance ? Carbon\Carbon::parse($trans->date_echeance)->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">Capitalisation / Mode:</span>
                            <span class="font-semibold">{{ $trans->interest_management ?? 'Standard' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Whitelisted Correction Form Button -->
                <div class="flex justify-end">
                    <button onclick="openCorrectionModal('Transaction', {{ $trans->id }}, {{ json_encode($item['whitelisted_fields']) }})" class="px-4 py-2 bg-amber-600 text-white text-xs font-bold rounded hover:bg-amber-700 flex items-center gap-1">
                        ✏️ Demander une correction sur cette souscription
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- History of Corrections for this client -->
    <div class="bg-white dark:bg-bg2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 mb-8">
        <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Historique des Corrections & Workflow</h4>
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="p-3">Date</th>
                    <th class="p-3">Champ</th>
                    <th class="p-3">Ancienne Valeur</th>
                    <th class="p-3">Nouvelle Valeur</th>
                    <th class="p-3">Motif</th>
                    <th class="p-3">Opérateur</th>
                    <th class="p-3">Contrôleur</th>
                    <th class="p-3">Statut</th>
                    <th class="p-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                @forelse($history as $h)
                    <tr>
                        <td class="p-3">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3 font-semibold">{{ $h->field_name }}</td>
                        <td class="p-3 text-red-500">{{ $h->old_value }}</td>
                        <td class="p-3 text-green-600 font-bold">{{ $h->new_value }}</td>
                        <td class="p-3">{{ $h->reason }}</td>
                        <td class="p-3">{{ $h->operator->name ?? 'Opérateur' }}</td>
                        <td class="p-3">{{ $h->controller->name ?? '-' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-bold {{ $h->status == 'Valide' ? 'bg-green-100 text-green-800' : ($h->status == 'Rejete' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ $h->status }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            @if($h->status === 'A_controler' && auth()->id() !== $h->operator_id)
                                <form method="POST" action="{{ route('control-adjustments.validate', $h->id) }}" class="inline">
                                    @csrf
                                    <button class="px-2 py-1 bg-emerald-600 text-white rounded font-bold" onclick="return confirm('Valider cette correction ?')">Valider</button>
                                </form>
                            @elseif($h->status === 'A_controler' && auth()->id() === $h->operator_id)
                                <span class="text-gray-400 italic">Auto-validation interdite</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-4 text-center text-gray-400">Aucune modification historique pour ce client.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- MODAL FOR CORRECTION -->
    <div id="correctionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-bg2 rounded-xl shadow-xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h4 class="font-bold text-lg text-gray-800 dark:text-white">Demande de Correction (Liste Blanche)</h4>
                <button onclick="closeCorrectionModal()" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form method="POST" action="{{ route('control-adjustments.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="target_entity" id="modal_target_entity">
                <input type="hidden" name="target_id" id="modal_target_id">

                <div>
                    <label class="block text-xs font-semibold mb-1">Champ à modifier (Liste Blanche)</label>
                    <select name="field_name" id="modal_field_name" onchange="runSimulation()" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900" required>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Nouvelle Valeur</label>
                    <input type="text" name="new_value" id="modal_new_value" oninput="runSimulation()" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900" placeholder="Ex: 5.5 ou 1000000" required>
                </div>

                <!-- Simulation Output Container -->
                <div id="simulationBox" class="p-3 bg-blue-50 dark:bg-gray-900 rounded border border-blue-100 hidden">
                    <p class="text-xs font-bold text-blue-800 mb-1">Simulation d'Écart avant Enregistrement :</p>
                    <div class="text-xs space-y-1 text-gray-700 dark:text-gray-300">
                        <div>Ancienne Valorisation: <span id="sim_old" class="font-semibold"></span></div>
                        <div>Nouvelle Valorisation: <span id="sim_new" class="font-semibold"></span></div>
                        <div>Écart: <span id="sim_delta" class="font-bold"></span></div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Motif obligatoire</label>
                    <textarea name="reason" class="w-full border rounded p-2 text-sm bg-gray-50 dark:bg-gray-900" rows="2" placeholder="Expliquez la raison de la correction..." required></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Pièce Justificative (Optionnelle)</label>
                    <input type="file" name="attachment" class="w-full border rounded p-1 text-xs">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" onclick="closeCorrectionModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs rounded">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700">Soumettre la demande</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCorrectionModal(entity, id, whitelistedFields) {
            document.getElementById('modal_target_entity').value = entity;
            document.getElementById('modal_target_id').value = id;
            
            let select = document.getElementById('modal_field_name');
            select.innerHTML = '';
            for (let key in whitelistedFields) {
                let opt = document.createElement('option');
                opt.value = key;
                opt.textContent = whitelistedFields[key];
                select.appendChild(opt);
            }
            
            document.getElementById('correctionModal').classList.remove('hidden');
        }

        function closeCorrectionModal() {
            document.getElementById('correctionModal').classList.add('hidden');
        }

        function runSimulation() {
            let entity = document.getElementById('modal_target_entity').value;
            let id = document.getElementById('modal_target_id').value;
            let field = document.getElementById('modal_field_name').value;
            let newVal = document.getElementById('modal_new_value').value;

            if (!newVal) return;

            fetch('{{ route("control-adjustments.simulate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    target_entity: entity,
                    target_id: id,
                    field_name: field,
                    new_value: newVal
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('sim_old').textContent = data.data.old_valuation.toLocaleString() + ' FCFA';
                    document.getElementById('sim_new').textContent = data.data.new_valuation.toLocaleString() + ' FCFA';
                    document.getElementById('sim_delta').textContent = data.data.delta_amount.toLocaleString() + ' FCFA (' + data.data.delta_percent + '%)';
                    document.getElementById('sim_delta').className = data.data.delta_amount >= 0 ? 'font-bold text-green-600' : 'font-bold text-red-600';
                    document.getElementById('simulationBox').classList.remove('hidden');
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</main>
@endsection
