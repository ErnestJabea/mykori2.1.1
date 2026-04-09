<div class="mt-4 bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden">
    <div class="p-8 border-b border-n30 bg-n10/20 flex justify-between items-center header-history">
        <h3 class="font-bold text-n900 flex items-center gap-3 italic uppercase">
            <i class="las la-history text-primary text-xl"></i>
            Historique des Envois de Relevés
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-n10 text-n500 text-[11px] uppercase tracking-widest font-extrabold border-b border-n30">
                <tr>
                    <th class="px-6 py-5 text-left font-extrabold uppercase tracking-widest text-n500 italic">Date &
                        Heure</th>
                    <th class="px-6 py-5 text-left font-extrabold uppercase tracking-widest text-n500 italic">Opérateur
                    </th>
                    <th class="px-6 py-5 text-center font-extrabold uppercase tracking-widest text-n500 italic">Période
                    </th>
                    <th class="px-6 py-5 text-center font-extrabold uppercase tracking-widest text-n500 italic">
                        Nombre de clients</th>
                    <th class="px-6 py-5 text-center font-extrabold uppercase tracking-widest text-n500 italic">Actions
                        Audit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-n30 bg-white">
                @forelse($batches as $batch)
                    <tr class="hover:bg-n10/50 transition-all group">
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-bold text-n400 italic">#{{ $batch->id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-n800">{{ $batch->user->name ?? 'Système' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="px-3 py-1 bg-n900 text-ms text-[10px] rounded-lg font-bold uppercase">{{ $batch->periode }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col gap-1 items-center">

                                <div class="flex gap-3">
                                    <div class="flex items-center gap-1">
                                        <div class="w-1.5 h-1.5 rounded-full bg-success"></div>
                                        <span
                                            class="text-[11px] font-black text-success">{{ $batch->success_count }}</span>
                                    </div>
                                    @if ($batch->error_count > 0)
                                        <div class="flex items-center gap-1 border-l border-n30 pl-3">
                                            <div class="w-1.5 h-1.5 rounded-full bg-danger"></div>
                                            <span
                                                class="text-[11px] font-black text-failed">{{ $batch->error_count }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $downloadRoute = Request::is('dg*')
                                    ? 'dg.statements-download'
                                    : 'compliance.statements-download';
                            @endphp
                            <a href="{{ route($downloadRoute, $batch->id) }}"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-marron text-white text-[10px] font-black rounded-xl hover:shadow-lg hover:scale-105 active:scale-95 transition-all uppercase tracking-widest border border-white/10">
                                <i class="las la-file-csv text-sm"></i> Rapport.csv
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-n400 italic font-bold">Aucune historique
                            d'envoi disponible.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($batches->hasPages())
        <div class="p-8 border-t border-n30 bg-n10/10">
            {{ $batches->links('partials.pagination') }}
        </div>
    @endif
</div>
