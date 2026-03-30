@extends('front-end/app/app-home-asset', ['title' => 'Direction Générale - Pilotage Stratégique'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-8 min-h-screen">

                <!-- Header Section Stratégique -->
                <div class="flex justify-between items-center sm:flex-row flex-col gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-primary text-3xl border border-n30">
                            <i class="las la-tachometer-alt"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-n900 mb-1 italic uppercase">Pilotage Direction Générale</h2>
                            <p class="text-n500 text-sm italic font-medium">Vue consolidée des actifs et de la performance globale.</p>
                        </div>
                    </div>
                </div>

                <!-- Strategic KPIs (Même ligne) -->
                <div class="flex flex-wrap md:flex-nowrap gap-6">
                    <div class="flex-1 min-w-[240px] bg-primary text-white p-6 rounded-3xl shadow-lg relative overflow-hidden group border border-primary/20">
                        <div class="relative z-10">
                            <p class="text-white/70 text-[10px] font-bold uppercase tracking-widest mb-1 italic">Investisseurs Actifs</p>
                            <h3 class="text-3xl font-bold">{{ number_format($totalClients, 0, '.', ' ') }}</h3>
                        </div>
                        <i class="las la-user-tie absolute -bottom-4 -right-2 text-8xl opacity-10 group-hover:scale-110 transition-transform"></i>
                    </div>

                    <div class="flex-1 min-w-[240px] bg-success text-white p-6 rounded-3xl shadow-lg relative overflow-hidden group border border-success/20">
                        <div class="relative z-10">
                            <p class="text-white/70 text-[10px] font-bold uppercase tracking-widest mb-1 italic">Actifs Sous Gestion (AUM)</p>
                            <h3 class="text-3xl font-bold">{{ number_format($totalAum, 0, '.', ' ') }} <span class="text-sm">XAF</span></h3>
                        </div>
                        <i class="las la-piggy-bank absolute -bottom-4 -right-2 text-8xl opacity-10 group-hover:scale-110 transition-transform"></i>
                    </div>

                    <div class="flex-1 min-w-[240px] bg-secondary text-white p-6 rounded-3xl shadow-lg relative overflow-hidden group border border-secondary/20">
                        <div class="relative z-10">
                            <p class="text-white/70 text-[10px] font-bold uppercase tracking-widest mb-1 italic">Paiements Intérêts</p>
                            <h3 class="text-3xl font-bold">{{ number_format($totalInterets, 0, '.', ' ') }} <span class="text-sm">XAF</span></h3>
                        </div>
                        <i class="las la-hand-holding-usd absolute -bottom-4 -right-2 text-8xl opacity-10 group-hover:scale-110 transition-transform"></i>
                    </div>

                    <div class="flex-1 min-w-[240px] bg-n900 text-white p-6 rounded-3xl shadow-lg relative overflow-hidden group border border-white/10">
                        <div class="relative z-10">
                            <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1 italic">Ticket Moyen</p>
                            <h3 class="text-3xl font-bold italic">{{ number_format($totalClients > 0 ? $totalAum / $totalClients : 0, 0, '.', ' ') }} <span class="text-[10px] opacity-60">XAF</span></h3>
                        </div>
                        <i class="las la-calculator absolute -bottom-4 -right-2 text-8xl opacity-10 group-hover:scale-110 transition-transform"></i>
                    </div>
                </div>

                <!-- Strategic Distribution -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Répartition par Classe -->
                    <div class="bg-white rounded-3xl border border-n30 p-8 shadow-sm">
                        <h4 class="text-sm font-bold text-n900 uppercase italic mb-8 flex items-center gap-2 border-b border-n30 pb-4">
                            <i class="las la-chart-pie text-primary text-xl"></i> Répartition par Catégorie Produit
                        </h4>
                        <div class="flex flex-col gap-8">
                            @foreach($aumByType as $type)
                            <div class="flex flex-col gap-3">
                                <div class="flex justify-between items-end">
                                    <span class="text-xs font-bold text-n600 uppercase italic">{{ $type->category }}</span>
                                    <span class="text-sm font-bold text-primary italic">{{ number_format($type->total, 0, '.', ' ') }} XAF</span>
                                </div>
                                <div class="w-full h-3 bg-n20 rounded-full overflow-hidden">
                                    @php $pct = ($totalAum > 0) ? ($type->total / $totalAum) * 100 : 0; @endphp
                                    <div class="h-full bg-primary rounded-full transition-all duration-1000" style="width: {{ $pct }}%"></div>
                                </div>
                                <p class="text-[10px] text-n400 text-right italic font-bold opacity-60">{{ number_format($pct, 1) }}% du portefeuille global</p>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Top Produits -->
                    <div class="bg-white rounded-3xl border border-n30 p-8 shadow-sm">
                        <h4 class="text-sm font-bold text-n900 uppercase italic mb-8 flex items-center gap-2 border-b border-n30 pb-4">
                            <i class="las la-crown text-secondary text-xl"></i> Top 5 Produits les plus porteurs
                        </h4>
                        <div class="flex flex-col gap-4">
                            @forelse($topProducts as $idx => $prod)
                                <div class="flex items-center justify-between p-4 bg-n10 rounded-2xl hover:bg-n20 transition-all border border-transparent hover:border-n30 group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center font-bold text-primary border border-n30 italic shadow-sm">
                                            #{{ $idx + 1 }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-n900 italic uppercase leading-tight">{{ $prod->title }}</p>
                                            <p class="text-[10px] text-n500 italic">Volume total géré</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-bold text-primary italic">{{ number_format($prod->total, 0, '.', ' ') }} XAF</p>
                                        @php $sh = ($totalAum > 0) ? ($prod->total / $totalAum) * 100 : 0; @endphp
                                        <p class="text-[9px] font-bold text-success opacity-70">{{ number_format($sh, 1) }}% market share</p>
                                    </div>
                                </div>
                            @empty
                                <div class="py-12 text-center text-n400 italic font-bold">Données insuffisantes.</div>
                            @endforelse
                        </div>
                    </div>

                </div>

                <!-- Bottom Section: Recent Activities & Alerts -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                    
                    <!-- Mandats arrivant à échéance (ALERTE) -->
                    <div class="xl:col-span-5 flex flex-col gap-6">
                        <div class="bg-white rounded-3xl border border-warning/30 p-8 shadow-md relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-16 h-16 bg-warning/5 rounded-bl-full flex items-center justify-center p-4">
                                <i class="las la-bell text-warning text-2xl animate-bounce"></i>
                            </div>
                            <h4 class="text-sm font-bold text-n900 uppercase italic mb-6 flex items-center gap-3">
                                <i class="las la-hourglass-end text-warning text-xl"></i> Échéances de Mandats (30j)
                            </h4>
                            
                            <div class="flex flex-col gap-4">
                                @forelse($expiringMandats as $exp)
                                    <div class="p-5 rounded-2xl bg-n10 border-l-4 border-warning flex justify-between items-center group hover:bg-white hover:shadow-sm transition-all">
                                        <div>
                                            <p class="text-xs font-bold text-n900 italic uppercase mb-1">{{ $exp->product->title }}</p>
                                            <div class="flex items-center gap-2">
                                                <i class="las la-user-circle text-n400"></i>
                                                <span class="text-[10px] font-bold text-n500">{{ $exp->user->name }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs font-extrabold text-danger mb-1">{{ \Carbon\Carbon::parse($exp->date_echeance)->format('d/m/Y') }}</p>
                                            @php $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($exp->date_echeance)) @endphp
                                            <span class="px-2 py-0.5 bg-danger/10 text-danger text-[9px] font-bold rounded-lg uppercase italic">Échéance à {{ $daysLeft }} jours</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-12 text-center bg-success/5 rounded-3xl border border-dashed border-success/30">
                                        <i class="las la-check-circle text-success text-3xl mb-3"></i>
                                        <p class="text-[11px] text-success font-bold uppercase italic tracking-tighter">Aucune échéance critique sous 30 jours.</p>
                                    </div>
                                @endforelse
                            </div>

                            @if($expiringMandats->count() > 0)
                                <div class="mt-8 p-4 bg-warning/10 rounded-2xl border border-warning/20">
                                    <p class="text-[10px] text-n600 italic font-bold text-center">
                                        <i class="las la-exclamation-triangle"></i> Ces dossiers nécessitent une anticipation stratégique (Réinvestissement ou Rachat).
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Dossiers Récents Traités -->
                    <div class="xl:col-span-7 bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden border-t-8 border-t-primary">
                        <div class="p-8 border-b border-n30 flex justify-between items-center bg-n10/20">
                            <h3 class="font-bold text-n900 flex items-center gap-3">
                                <i class="las la-file-signature text-primary text-xl"></i>
                                Dossiers Finalisés Récemment
                            </h3>
                            <span class="px-3 py-1 bg-success text-white text-[10px] font-bold rounded-full uppercase italic">Live monitoring</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-n10/30 text-n500 text-[11px] uppercase tracking-widest font-extrabold border-b border-n30">
                                    <tr>
                                        <th class="px-6 py-5">Date</th>
                                        <th class="px-6 py-5">Client Impacté</th>
                                        <th class="px-6 py-5">Produit Cible</th>
                                        <th class="px-6 py-5 text-right">Volume</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-n30 italic">
                                    @forelse($recentSuccessFlows as $flow)
                                        <tr class="hover:bg-n10 transition-all">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-bold text-n600 uppercase">{{ $flow->updated_at->format('d/m/Y') }}</span>
                                                    <span class="text-[10px] text-n400 italic">Réf: {{ $flow->ref }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-bold text-n900 text-sm italic">{{ $flow->user->name ?? 'Client KORI' }}</td>
                                            <td class="px-6 py-4 italic">
                                                <span class="px-2 py-1 bg-primary/10 text-primary text-[10px] rounded-lg font-bold uppercase">{{ $flow->product->title ?? 'N/A' }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold text-n800">{{ number_format($flow->amount, 0, '.', ' ') }} XAF</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-12 text-center text-n400 italic font-bold uppercase">Historique vide.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </main>
@endsection
