@extends('front-end/app/app-home-asset', ['title' => 'Dossier Audit Client | ' . $client->name, 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page other-page compliance-profil'])

@section('content')
    <main class="main-content has-sidebar my-products-page other-page">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Header Section -->
            <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                <div
                    class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('compliance.clients') }}"
                            class="w-10 h-10 rounded-full border border-n30 flex items-center justify-center hover:bg-primary hover:text-white duration-300">
                            <i class="las la-arrow-left text-xl"></i>
                        </a>
                        <div>
                            <h3 class="h3 uppercase">DOSSIER CLIENT : {{ $client->name }}</h3>
                            <p class="text-sm opacity-70 font-medium text-primary">ID: #{{ $client->id }} • Inscrit le
                                {{ $client->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Sidebar -->
            <div class="col-span-12 lg:col-span-4 self-start">
                <div class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm">
                    <div class="flex flex-col items-center mb-6">
                        <div
                            class="w-20 h-20 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold mb-4 shadow-lg uppercase">
                            {{ substr($client->name, 0, 2) }}
                        </div>
                        <h4 class="h4 text-center">{{ $client->name }}</h4>
                        <span class="text-sm opacity-60 text-center">{{ $client->email }}</span>
                    </div>

                    <div class="space-y-4 border-t border-n30 pt-6">
                        <div class="flex justify-between text-sm">
                            <span class="opacity-60">Localisation:</span>
                            <span class="font-medium">{{ $client->localisation ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="opacity-60">Genre:</span>
                            <span class="font-medium">{{ $client->genre == 1 ? 'Femme' : 'Homme' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="opacity-60">Boîte Postale:</span>
                            <span class="font-medium">{{ $client->bp ?? 'N/A' }}</span>
                        </div>
                        <hr class="border-n30 my-4">
                        <form action="{{ route('compliance.export') }}" method="GET" class="space-y-4">
                            <input type="hidden" name="type" value="transactions">
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <div>
                                <label class="text-xs font-bold uppercase opacity-60 mb-2 block">Période d'Audit</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="date" name="start_date" value="{{ $startDate }}"
                                        class="w-full bg-n10 border border-n30 rounded px-2 py-1.5 text-xs focus:outline-none focus:border-primary">
                                    <input type="date" name="end_date" value="{{ $endDate }}"
                                        class="w-full bg-n10 border border-n30 rounded px-2 py-1.5 text-xs focus:outline-none focus:border-primary">
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full btn bg-success text-white rounded-lg py-3 flex items-center justify-center gap-2 hover:opacity-90 duration-300 font-bold uppercase text-xs">
                                <i class="las la-file-csv text-xl"></i> Exporter l'Audit Complet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Audit Tables -->
            <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
                <!-- Transactions table -->
                <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-n30 bg-n10/30 flex justify-between items-center">
                        <h4 class="h4 flex items-center gap-2">
                            <i class="las la-history text-primary"></i> Transactions Initiales & Mensuelles
                        </h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead>
                                <tr class="bg-n20 dark:bg-bg3 text-n500 uppercase text-[11px] font-bold">
                                    <th class="py-4 px-6 text-start">Date</th>
                                    <th class="py-4 px-6 text-start">Produit</th>
                                    <th class="py-4 px-6 text-end">Montant</th>
                                    <th class="py-4 px-6 text-center">Statut</th>
                                    <th class="py-4 px-6 text-start">Référence</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30">
                                @foreach ($transactions as $t)
                                    <tr class="hover:bg-primary/5 duration-200">
                                        <td class="py-4 px-6 text-sm">{{ $t->created_at->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6">
                                            <span
                                                class="px-2 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold uppercase">
                                                {{ $t->product->title ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-end font-bold text-n700">
                                            {{ number_format($t->amount, 0, ' ', ' ') }} XAF</td>
                                        <td class="py-4 px-6 text-center">
                                            <span
                                                class="px-3 py-1 rounded-full text-[10px] font-bold {{ $t->status == 'Succès' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }}">
                                                {{ $t->status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-n500">{{ $t->ref }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- FM History -->
                @if ($pmgMovements->count() > 0)
                    <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-n30 bg-secondary1/5">
                            <h4 class="h4 flex items-center gap-2">
                                <i class="las la-chart-bar text-secondary1"></i> Traçabilité des Gains ( Audit PMG )
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full whitespace-nowrap">
                                <thead>
                                    <tr class="bg-n20 dark:bg-bg3 text-n500 uppercase text-[11px] font-bold">
                                        <th class="py-4 px-6 text-start">Date</th>
                                        <th class="py-4 px-6 text-start">Type de Flux</th>
                                        <th class="py-4 px-6 text-end">Variation</th>
                                        <th class="py-4 px-6 text-end">Solde Capitalisé</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-n30">
                                    @foreach ($pmgMovements as $m)
                                        <tr class="hover:bg-secondary1/5 duration-200">
                                            <td class="py-4 px-6 text-sm">
                                                {{ Carbon\Carbon::parse($m->date_operation)->format('d/m/Y') }}</td>
                                            <td class="py-4 px-6">
                                                <span class="text-sm font-medium">{{ $m->type }}</span>
                                            </td>
                                            @php
                                                $isNegative = in_array($m->type, ['rachat_partiel', 'rachat_total', 'precompte_interets', 'paiement_interets', 'remboursement']);
                                            @endphp
                                            <td
                                                class="py-4 px-6 text-end font-bold {{ $isNegative ? 'text-danger' : 'text-success' }}">
                                                {{ $isNegative ? '-' : '+' }}{{ number_format(abs($m->amount), 0, ' ', ' ') }}
                                            </td>
                                            <td class="py-4 px-6 text-end font-extrabold text-secondary1">
                                                {{ number_format($m->capital_after, 0, ' ', ' ') }} XAF</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- FCP Movements -->
                @if ($fcpMovements->count() > 0)
                    <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-n30 bg-primary/5">
                            <h4 class="h4 flex items-center gap-2">
                                <i class="las la-wallet text-primary"></i> Mouvements de Trésorerie ( Audit FCP )
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full whitespace-nowrap">
                                <thead>
                                    <tr class="bg-n20 dark:bg-bg3 text-n500 uppercase text-[11px] font-bold">
                                        <th class="py-4 px-6 text-start">Date</th>
                                        <th class="py-4 px-6 text-start">Type</th>
                                        <th class="py-4 px-6 text-center">VL Appliquée</th>
                                        <th class="py-4 px-6 text-end">Parts +/-</th>
                                        <th class="py-4 px-6 text-end">Montant (XAF)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-n30">
                                    @foreach ($fcpMovements as $f)
                                        <tr class="hover:bg-primary/5 duration-200">
                                            <td class="py-4 px-6 text-sm">
                                                {{ Carbon\Carbon::parse($f->date_operation)->format('d/m/Y') }}</td>
                                            <td class="py-4 px-6 text-sm font-medium">{{ $f->type }}</td>
                                            <td class="py-4 px-6 text-center text-xs">
                                                {{ number_format($f->vl_applied, 2, ',', ' ') }}</td>
                                            <td class="py-4 px-6 text-end font-bold text-n700">
                                                {{ $f->nb_parts_change > 0 ? '+' : '' }}{{ number_format($f->nb_parts_change, 4, ',', ' ') }}
                                            </td>
                                            <td class="py-4 px-6 text-end font-extrabold text-primary">
                                                {{ number_format($f->amount_xaf, 0, ' ', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection
