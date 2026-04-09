@extends('front-end/app/app-home-asset', ['title' => 'Direction Générale - Pilotage Stratégique'])

@section('content')
    <main class="main-content has-sidebar my-products-page other-page">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-8 min-h-screen">


                <!-- Header Section -->
                <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                    <div
                        class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                        <div>
                            <h3 class="h3 uppercase">Pilotage Direction Générale</h3>
                            <p class="text-sm opacity-70 text-primary font-medium">Vue consolidée des actifs et de la
                                performance globale.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="hidden md:block text-right mr-4 border-r border-n30 pr-4">
                                <p class="font-medium">{{ date('d-m-Y') }}</p>
                                <span class="text-xs opacity-50">État du système</span>
                            </div>
                            {{-- <a href="{{ route('compliance.export') }}?type=transactions"
                            class="btn bg-primary text-white rounded-lg px-4 py-2 hover:opacity-90 duration-300 flex items-center gap-2 text-sm shadow-sm">
                            <i class="las la-file-export"></i> Export Global CSV
                        </a> --}}
                        </div>
                    </div>
                </div>


                <!-- Strategic KPIs (Granular Pilotage) -->
                <div class="flex flex-wrap py-2 stats-wrapper">

                    <!-- LINE 1: GLOBAL PERFORMANCE -->
                    <!-- 1. AUM Global (Total) -->
                    <div
                        class="box-card bg-marron text-white p-5 rounded-2xl shadow-xl border border-white/5 transition-all hover:-translate-y-1 group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-white/60 text-[10px] font-bold uppercase tracking-widest italic">AUM Global
                                (Total)</span>
                            <i
                                class="las la-globe text-lg text-white opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white tracking-tighter">
                            {{ number_format($totalAum, 0, '.', ' ') }} <span class="text-xs opacity-50">XAF</span></h3>
                        <p class="text-[9px] mt-2 text-white/50 font-bold uppercase italic border-t border-white/10 pt-2">
                            Valorisation Consolidée (Capital + Gains)</p>
                    </div>

                    <!-- 2. Placement Global (Total Net) -->
                    <div
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border border-n30 transition-all hover:border-secondary/50 group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-n500 text-[10px] font-bold uppercase tracking-widest italic">Placement Global
                                (Net)</span>
                            <i
                                class="las la-file-invoice-dollar text-lg text-secondary opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-2xl font-black text-secondary tracking-tighter">
                            {{ number_format($totalPlacementGlobal, 0, '.', ' ') }} <span
                                class="text-xs opacity-40">XAF</span></h3>
                        <p class="text-[9px] mt-2 text-n400 font-bold uppercase italic border-t border-n30 pt-2">Capital Net
                            Investi (Historique)</p>
                    </div>

                    <!-- 3. Placement (Clients Actifs Uniquement) -->
                    <div
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border-2 border-primary/20 transition-all hover:border-primary group bg-primary/5">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-primary text-[10px] font-bold uppercase tracking-widest italic">Placement
                                Clients Actifs</span>
                            <i class="las la-user-check text-lg text-primary"></i>
                        </div>
                        <h3 class="text-2xl font-black text-primary tracking-tighter">
                            {{ number_format($totalPlacementActiveClients, 0, '.', ' ') }} <span
                                class="text-xs opacity-40">XAF</span></h3>
                        <p
                            class="text-[9px] mt-2 text-primary/60 font-bold uppercase italic border-t border-primary/10 pt-2">
                            Encours de Capital "Vivant"</p>
                    </div>

                    <!-- LINE 2: FCP vs PMG -->
                    <!-- 4. Total Portefeuille FCP -->
                    <a href="{{ route('products') }}?category=1"
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border border-n30 hover:border-primary/50 transition-all group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-n500 text-[10px] font-bold uppercase tracking-widest italic">Encours Capital
                                FCP</span>
                            <i
                                class="las la-chart-pie text-lg text-primary opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-xl font-bold text-n900 dark:text-n200 tracking-tighter">
                            {{ number_format($totalAumFcp, 0, '.', ' ') }} <span class="text-xs opacity-40">XAF</span></h3>
                        <p class="text-[8px] mt-2 text-primary font-bold uppercase italic">Détails produits FCP <i
                                class="las la-arrow-right"></i></p>
                    </a>

                    <!-- 5. Total Portefeuille PMG -->
                    <a href="{{ route('products') }}?category=2"
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border border-n30 hover:border-marron/50 transition-all group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-n500 text-[10px] font-bold uppercase tracking-widest italic">Encours Capital
                                PMG</span>
                            <i
                                class="las la-wallet text-lg text-marron opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-xl font-bold text-marron tracking-tighter">
                            {{ number_format($totalAumPmg, 0, '.', ' ') }} <span class="text-xs opacity-40">XAF</span></h3>
                        <p class="text-[8px] mt-2 text-marron font-bold uppercase italic">Détails mandats PMG <i
                                class="las la-arrow-right"></i></p>
                    </a>

                    <!-- 6. Intérêts & Plus-Values -->
                    <div
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border border-n30 hover:border-success/50 transition-all group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-n500 text-[10px] font-bold uppercase tracking-widest italic">Gains &
                                Performance</span>
                            <i
                                class="las la-hand-holding-usd text-lg text-success opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-xl font-bold text-success tracking-tighter">
                            {{ number_format($totalInterets, 0, '.', ' ') }} <span class="text-xs opacity-40">XAF</span>
                        </h3>
                        <p class="text-[8px] mt-2 text-success font-bold uppercase italic">Intérêts + Plus-values latentes
                        </p>
                    </div>

                    <!-- LINE 3: CUSTOMERS & OPERATIONS -->
                    <!-- 7. Clients Actifs -->
                    <a href="{{ route('customer') }}"
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border border-n30 hover:bg-n10 transition-all group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-n500 text-[10px] font-bold uppercase tracking-widest italic">Base Clients
                                Actifs</span>
                            <i
                                class="las la-users text-lg text-primary opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-xl font-bold text-n900 dark:text-n200 tracking-tighter">{{ $totalActiveClients }}
                            <span class="text-xs opacity-40">Entités</span>
                        </h3>
                        <p class="text-[8px] mt-2 text-primary font-bold uppercase italic">Liste détaillée <i
                                class="las la-arrow-right"></i></p>
                    </a>

                    <!-- 8. Clients Inactifs -->
                    <a href="{{ route('customer') }}?status=inactive"
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border border-n30 hover:bg-danger/5 transition-all group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-n500 text-[10px] font-bold uppercase tracking-widest italic">Base Clients
                                Inactifs</span>
                            <i
                                class="las la-user-slash text-lg text-danger opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-xl font-bold text-danger tracking-tighter">{{ $totalInactiveClients }} <span
                                class="text-xs opacity-40">Prospects/Closed</span></h3>
                        <p class="text-[8px] mt-2 text-danger font-bold uppercase italic">Relances <i
                                class="las la-arrow-right"></i></p>
                    </a>

                    <!-- 9. Relevés Envoyés (M-1) -->
                    <a href="{{ route('dg.statements-history') }}"
                        class="box-card bg-white dark:bg-bg3 p-5 rounded-2xl shadow-sm border border-n30 hover:bg-n10 transition-all group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-n500 text-[10px] font-bold uppercase tracking-widest italic">Relevés Produits
                                (M-1)</span>
                            <i
                                class="las la-envelope-open-text text-lg text-primary opacity-40 group-hover:opacity-100 transition-all"></i>
                        </div>
                        <h3 class="text-xl font-bold text-n900 dark:text-n200 tracking-tighter">
                            {{ $totalStatementsLastMonth }} <span class="text-xs opacity-40">Envois</span></h3>
                        <p class="text-[8px] mt-2 text-primary font-bold uppercase italic">Historique de distribution <i
                                class="las la-arrow-right"></i></p>
                    </a>

                </div>
                <!-- Bottom Section: Recent Activities & Alerts -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">

                    <!-- Mandats arrivant à échéance (ALERTE) -->
                    <div class="xl:col-span-5 flex flex-col gap-6 header-bloc-wrapper inner-margin">
                        <div class="bg-white rounded-3xl border border-warning/30 p-8 shadow-md relative overflow-hidden ">
                            <div
                                class="absolute top-0 right-0 w-16 h-16 bg-warning/5 rounded-bl-full flex items-center justify-center p-4">
                                <i class="las la-bell text-warning text-2xl animate-bounce"></i>
                            </div>
                            <h4 class="text-sm font-bold text-n900 uppercase italic mb-6 flex items-center gap-3 ">
                                <i class="las la-hourglass-end text-warning text-xl"></i> Échéances de Mandats (30j)
                            </h4>

                            <div class="flex flex-col gap-4">
                                @forelse($expiringMandats as $exp)
                                    <div
                                        class="p-5 rounded-2xl bg-n10 border-l-4 border-warning flex justify-between items-center group hover:bg-white hover:shadow-sm transition-all">
                                        <div>
                                            <p class="text-xs font-bold text-n900 italic uppercase mb-1">
                                                {{ $exp->product->title }}</p>
                                            <div class="flex items-center gap-2">
                                                <i class="las la-user-circle text-n400"></i>
                                                <span class="text-[10px] font-bold text-n500">{{ $exp->user->name }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs font-extrabold text-danger mb-1">
                                                {{ \Carbon\Carbon::parse($exp->date_echeance)->format('d/m/Y') }}</p>
                                            @php $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($exp->date_echeance)) @endphp
                                            <span
                                                class="px-2 py-0.5 bg-danger/10 text-danger text-[9px] font-bold rounded-lg uppercase italic">Échéance
                                                à {{ $daysLeft }} jours</span>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="py-12 text-center bg-success/5 rounded-3xl border border-dashed border-success/30">
                                        <i class="las la-check-circle text-success text-3xl mb-3"></i>
                                        <p class="text-[11px] text-success font-bold uppercase italic tracking-tighter">
                                            Aucune échéance critique sous 30 jours.</p>
                                    </div>
                                @endforelse
                            </div>

                            @if ($expiringMandats->count() > 0)
                                <div class="mt-8 p-4 bg-warning/10 rounded-2xl border border-warning/20">
                                    <p class="text-[10px] text-n600 italic font-bold text-center">
                                        <i class="las la-exclamation-triangle"></i> Ces dossiers nécessitent une
                                        anticipation stratégique (Réinvestissement ou Rachat).
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Dossiers En Attente de Signature DG -->
                    <div
                        class="xl:col-span-7 bg-white rounded-3xl border border-warning/30 shadow-sm overflow-hidden border-t-8 border-t-warning header-bloc-wrapper">
                        <div class="p-8 border-b border-n30  bg-warning/5 inner-margin">
                            <h4 class="font-bold text-n900 flex items-center gap-3">
                                <i class="las la-file-signature text-warning text-2xl"></i>
                                Dossiers en attente de signature finale
                            </h4>
                        </div>
                        <div class="overflow-x-auto min-h-[300px]">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-gray-50 text-n500 text-[11px] uppercase tracking-widest font-extrabold border-b border-n30">
                                    <tr>
                                        <th class="px-6 py-5">Date</th>
                                        <th class="px-6 py-5">Client</th>
                                        <th class="px-6 py-5">Placement</th>
                                        <th class="px-6 py-5 text-right font-bold">Montant</th>
                                        <th class="px-6 py-5 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-n30 italic">
                                    @forelse($allPending as $flow)
                                        <tr
                                            class="hover:bg-n10 transition-all border-l-4 border-transparent hover:border-marron shadow-sm/0 hover:shadow-md">
                                            <td class="px-6 py-5">
                                                <span
                                                    class="text-[12px] font-extrabold text-n700 uppercase tracking-tighter">{{ $flow->created_at->format('d/m/Y') }}</span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="font-bold text-n900 text-base italic leading-tight">{{ $flow->user->name ?? 'Client KORI' }}</span>

                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span
                                                    class="bg-gray-50 py-1 text-[11px] text-marron uppercase">{{ $flow->product->title ?? 'N/A' }}</span>
                                            </td>
                                            <td class="px-6 py-5 text-right">
                                                <span
                                                    class="text-lg font-black text-marron tracking-tight">{{ number_format($flow->amount + ($flow->fees ?? 0), 0, '.', ' ') }}</span>
                                                <span class="text-[10px] font-bold text-n400 uppercase ml-1">XAF</span>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button
                                                        onclick="openTransactionModal({{ $flow->toJson() }}, '{{ route('backoffice.validate-transaction', [$flow->id, $flow->type_flux]) }}')"
                                                        class="w-10 h-10 bg-marron/10 text-marron rounded-2xl flex items-center justify-center hover:bg-marron hover:text-white transition-all shadow-sm"
                                                        title="Auditer avant signature">
                                                        <i class="las la-eye text-xl"></i>
                                                    </button>
                                                    <form
                                                        action="{{ route('backoffice.validate-transaction', [$flow->id, $flow->type_flux]) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                            class="w-10 h-10 bg-marron text-white rounded-2xl shadow-lg hover:rotate-6 active:scale-95 transition-all flex items-center justify-center group"
                                                            title="Signature DG (Activation finale)">
                                                            <i class="las la-pen-nib text-xl group-hover:scale-110"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-24 text-center">
                                                <div class="flex flex-col items-center gap-4 opacity-30">
                                                    <i class="las la-folder-open text-6xl"></i>
                                                    <p class="text-sm font-bold italic uppercase tracking-widest">Aucun
                                                        dossier en attente de signature.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 bg-marron/5 border-t border-n30">
                            <p class="text-[10px] text-n500 italic text-center font-medium">La signature Direction Générale
                                active définitivement le versement et déclenche la génération des parts.</p>
                        </div>
                    </div>

                    <!-- Dossiers Récents Traités -->
                    <div
                        class="xl:col-span-12 bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden border-t-8 border-t-marron mt-8 header-bloc-wrapper">
                        <div class="p-8 border-b border-n30 bg-n10/20 inner-margin">
                            <h4 class="font-bold text-n900 flex items-center gap-3">
                                <i class="las la-check-circle text-success text-xl"></i>
                                Historique des Derniers Investissements Finalisés
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-n10/30 text-n500 text-[11px] uppercase tracking-widest font-extrabold border-b border-n30">
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
                                                    <span
                                                        class="text-[11px] font-bold text-n600 uppercase">{{ $flow->updated_at->format('d/m/Y') }}</span>
                                                    <span class="text-[10px] text-n400 italic">Réf:
                                                        {{ $flow->ref }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-bold text-n900 text-sm italic">
                                                {{ $flow->user->name ?? 'Client KORI' }}</td>
                                            <td class="px-6 py-4 italic">
                                                <span
                                                    class="px-2 py-1 bg-primary/10 text-primary text-[10px] rounded-lg font-bold uppercase">{{ $flow->product->title ?? 'N/A' }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold text-n800">
                                                {{ number_format($flow->amount, 0, '.', ' ') }} XAF</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4"
                                                class="px-6 py-12 text-center text-n400 italic font-bold uppercase">
                                                Historique vide.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Strategic Distribution -->
                <div class="grid grid-cols-2 gap-6 xxl:gap-8">

                    <!-- Répartition par Catégorie -->
                    <div
                        class="bg-white dark:bg-bg3 rounded-3xl border border-n30 dark:border-n500 p-6 shadow-sm header-bloc-wrapper">
                        <h4
                            class="text-xs font-bold text-n900 dark:text-n200 uppercase italic mb-6 flex items-center gap-2 border-b border-n30 dark:border-n500 pb-3">
                            <i class="las la-chart-pie text-primary text-lg"></i> Répartition par Catégorie
                        </h4>
                        <div class="flex flex-col gap-6">
                            @foreach ($aumByType as $type)
                                <div class="flex flex-col gap-2">
                                    <div class="flex justify-between items-end">
                                        <span
                                            class="text-[10px] font-bold text-n600 dark:text-n400 uppercase italic">{{ $type->category }}</span>
                                        <span
                                            class="text-xs font-bold text-primary italic">{{ number_format($type->total, 0, '.', ' ') }}
                                            XAF</span>
                                    </div>
                                    <div class="w-full h-2 bg-n20 dark:bg-bg4 rounded-full overflow-hidden">
                                        @php $pct = ($totalAum > 0) ? ($type->total / $totalAum) * 100 : 0; @endphp
                                        <div class="h-full bg-primary rounded-full transition-all duration-1000"
                                            style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="text-[9px] text-n400 text-right italic font-bold opacity-70">
                                        {{ number_format($pct, 1) }}% du global</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Top Produits -->
                    <div
                        class="bg-white dark:bg-bg3 rounded-3xl border border-n30 dark:border-n500 p-6 shadow-sm header-bloc-wrapper">
                        <h4
                            class="text-xs font-bold text-n900 dark:text-n200 uppercase italic mb-6 flex items-center gap-2 border-b border-n30 dark:border-n500 pb-3">
                            <i class="las la-crown text-secondary text-lg"></i> Top 5 Produits Premium
                        </h4>
                        <div class="flex flex-col gap-3">
                            @forelse($topProducts as $idx => $prod)
                                <div
                                    class="flex items-center justify-between p-3 bg-n10 dark:bg-bg4 rounded-xl hover:bg-n20 transition-all border border-transparent hover:border-n30 group">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-white dark:bg-bg3 flex items-center justify-center font-bold text-primary border border-n30 italic shadow-sm text-xs">
                                            #{{ $idx + 1 }}
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] font-bold text-n900 dark:text-n200 italic uppercase leading-tight truncate w-32 md:w-48">
                                                {{ $prod->title }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-bold text-primary italic">
                                            {{ number_format($prod->total, 0, '.', ' ') }} XAF</p>
                                        @php $sh = ($totalAum > 0) ? ($prod->total / $totalAum) * 100 : 0; @endphp

                                    </div>
                                </div>
                            @empty
                                <div class="py-10 text-center text-n400 italic font-bold">Données insuffisantes.</div>
                            @endforelse
                        </div>
                    </div>

                </div>


            </div>
        </div>
    </main>
    @include('partials.transaction-details-modal')
@endsection
