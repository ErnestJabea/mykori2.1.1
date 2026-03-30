@extends('front-end/app/app-home-asset', ['title' => 'Audit Trail - Admin Frontend'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8 italic">
            <div class="flex flex-col gap-6 md:gap-8 min-h-screen transition-all transition-duration-500">

                <div class="flex justify-between items-center sm:flex-row flex-col gap-4">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.front.dashboard') }}" class="p-2 bg-white rounded-xl shadow-sm hover:text-primary transition-all shadow-md">
                            <i class="las la-arrow-left text-xl"></i>
                        </a>
                        <h2 class="text-2xl font-bold text-n900 tracking-tighter uppercase italic">Journal d'Audit Complet</h2>
                    </div>
                    <a href="{{ route('admin.front.logs.export') }}" class="btn bg-success text-white px-6 py-2 rounded-2xl text-xs font-bold hover:bg-n900 hover:shadow-xl transition-all flex items-center gap-2 italic">
                        <i class="las la-file-excel text-lg"></i>
                        Exporter les journaux (Excel)
                    </a>
                </div>

                <div class="bg-white rounded-3xl border border-n30 shadow-2xl overflow-hidden transition-all hover:border-primary">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-n20/30 text-n500 text-[10px] uppercase font-bold italic tracking-widest border-b border-n30">
                                <tr>
                                    <th class="px-6 py-4">Horodatage de l'action</th>
                                    <th class="px-6 py-4">Utilisateur / Auteur</th>
                                    <th class="px-6 py-4">Type d'Action</th>
                                    <th class="px-6 py-4">Détails de l'opération</th>
                                    <th class="px-6 py-4">Cible / ID</th>
                                    <th class="px-6 py-4">IP Source</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30 italic">
                                @foreach($logs as $log)
                                    <tr class="hover:bg-n10/80 transition-all group">
                                        <td class="px-6 py-4">
                                            <span class="text-xs text-n500 font-bold italic">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-bold text-n900 group-hover:text-primary transition-all italic">{{ $log->user->name ?? 'Système' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-xs italic">
                                            <span class="px-3 py-1 bg-primary/10 text-primary rounded-full font-black text-[9px] uppercase tracking-tighter italic">
                                                {{ $log->action }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-medium text-n600 max-w-xs truncate italic">
                                            {{ $log->description }}
                                        </td>
                                        <td class="px-6 py-4 italic">
                                            <span class="text-[10px] bg-secondary/10 text-secondary px-2 py-0.5 rounded font-bold italic">
                                                {{ str_replace('App\\Models\\', '', $log->target_type) }} #{{ $log->target_id }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-[10px] text-n400 italic">
                                            {{ $log->ip_address }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-6 py-6 border-t border-n30 mt-auto italic">
                    {{ $logs->links() }}
                </div>

            </div>
        </div>
    </main>
@endsection
