<div class="bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden">
    <div class="p-8 border-b border-n30 bg-n10/20 flex justify-between items-center">
        <h3 class="font-bold text-n900 flex items-center gap-3 italic uppercase">
            <i class="las la-history text-primary text-xl"></i>
            Historique des Envois de Relevés (Batch)
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-n10 text-n500 text-[11px] uppercase tracking-widest font-extrabold border-b border-n30">
                <tr>
                    <th class="px-6 py-5">Date & Heure</th>
                    <th class="px-6 py-5">Opérateur</th>
                    <th class="px-6 py-5 text-center">Période</th>
                    <th class="px-6 py-5 text-center">Impact</th>
                    <th class="px-6 py-5 text-center">Statut / Rapport</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-n30">
                @forelse($batches as $batch)
                <tr class="hover:bg-n10 transition-all italic">
                    <td class="px-6 py-4">
                        <span class="text-xs font-bold text-n900">{{ $batch->created_at->format('d/m/Y H:i') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                             <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold uppercase">
                                {{ substr($batch->user->name ?? '?', 0, 2) }}
                             </div>
                             <span class="text-xs font-bold text-n800">{{ $batch->user->name ?? 'Système' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 bg-n900 text-white text-[10px] rounded-lg font-bold uppercase">{{ $batch->periode }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-col gap-1 items-center">
                            <span class="text-[11px] font-bold text-primary">{{ $batch->client_count }} Destinataires</span>
                            <div class="flex gap-2">
                                <span class="text-[9px] font-bold text-success">{{ $batch->success_count }} Succès</span>
                                @if($batch->error_count > 0)
                                <span class="text-[9px] font-bold text-danger">{{ $batch->error_count }} Échec(s)</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $downloadRoute = Route::currentRouteName() == 'dg.statements-history' ? 'dg.statements-download' : 'compliance.statements-download';
                        @endphp
                        <a href="{{ route($downloadRoute, $batch->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-success text-white text-[10px] font-bold rounded-xl hover:bg-success/90 transition-all shadow-sm">
                            <i class="las la-file-csv text-sm"></i> Rapport.csv
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-n400 italic font-bold">Aucun historique d'envoi disponible.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($batches->hasPages())
    <div class="p-8 border-t border-n30 bg-n10/10">
        {{ $batches->links('partials.pagination') }}
    </div>
    @endif
</div>
