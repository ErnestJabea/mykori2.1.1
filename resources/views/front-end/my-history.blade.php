@extends('front-end/app/app-home', ['Mon historique', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">

            {{-- Page Header --}}
            <div class="col-span-12">
                <div
                    class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div>
                        <h3 class="h3">Historique des Transactions</h3>
                        <p class="text-sm opacity-70 mt-1">Toutes vos entrées et sorties, en temps réel</p>
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- Legend --}}
                        <div class="hidden md:flex items-center gap-4 mr-4 pr-4 border-r border-n30">
                            <span class="flex items-center gap-1.5 text-xs font-medium text-green-600">
                                <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Entrant
                            </span>
                            <span class="flex items-center gap-1.5 text-xs font-medium text-red-500">
                                <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Sortant
                            </span>
                        </div>
                        {{-- Download PDF Button --}}
                        <a href="{{ route('my-history-pdf') }}"
                            class="btn bg-primary text-white rounded-lg px-4 py-2 hover:bg-primary/90 duration-300 flex items-center gap-2 text-sm shadow-sm">
                            <i class="las la-file-pdf text-lg"></i>
                            Télécharger PDF
                        </a>
                    </div>
                </div>
            </div>

            {{-- Summary Cards --}}
            @php
                $totalEntrant = $allMovements->where('sens', 'entrant')->sum('montant');
                $totalSortant = $allMovements->where('sens', 'sortant')->sum(fn($m) => abs($m->montant));
            @endphp

            <div class="col-span-12 flex flex-wrap">

                <div class="card-history">
                    <div class="bg-white dark:bg-bg3 rounded-2xl border border-n30 p-5 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <i class="las la-arrow-down text-2xl text-green-600 text-green"></i>
                        </div>
                        <div>
                            <p class="text-xs opacity-60 font-medium uppercase">Total entrant</p>
                            <p class="text-lg font-bold text-green-600">{{ number_format($totalEntrant, 0, ' ', ' ') }} XAF
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card-history">
                    <div class="bg-white dark:bg-bg3 rounded-2xl border border-n30 p-5 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <i class="las la-arrow-up text-2xl text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-xs opacity-60 font-medium uppercase">Total sortant</p>
                            <p class="text-lg font-bold text-red-500">{{ number_format($totalSortant, 0, ' ', ' ') }} XAF
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card-history">
                    <div class="bg-white dark:bg-bg3 rounded-2xl border border-n30 p-5 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i class="las la-exchange-alt text-2xl text-primary"></i>
                        </div>
                        <div>
                            <p class="text-xs opacity-60 font-medium uppercase">Opérations</p>
                            <p class="text-lg font-bold text-primary">{{ $allMovements->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions Table --}}
            <div class="col-span-12">
                <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                    <div class="bb-dashed mb-0 flex flex-wrap items-center justify-between gap-4 p-6">
                        <h4 class="h4">Toutes les opérations</h4>
                        <span class="text-xs px-3 py-1 bg-primary/10 text-primary rounded-full font-medium">
                            {{ $allMovements->count() }} opération(s)
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        @if ($allMovements->count() > 0)
                            <table class="w-full whitespace-nowrap" id="historyTable">
                                <thead>
                                    <tr class="bg-n30/60 dark:bg-bg4 text-xs font-semibold uppercase tracking-wide">
                                        <th class="px-6 py-4 text-left">Date</th>
                                        <th class="px-6 py-4 text-left">Opération</th>{{-- 
                                        <th class="px-6 py-4 text-left">Placement</th> --}}
                                        <th class="px-6 py-4 text-left">Référence</th>
                                        <th class="px-6 py-4 text-center">Type</th>
                                        <th class="px-6 py-4 text-right">Montant (XAF)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-n30 dark:divide-bg4">
                                    @foreach ($allMovements as $mvt)
                                        @php
                                            $isEntrant = $mvt->sens === 'entrant';
                                            $rowBg = $isEntrant
                                                ? 'hover:bg-green-50/60 dark:hover:bg-green-900/10'
                                                : 'hover:bg-red-50/60 dark:hover:bg-red-900/10';
                                        @endphp
                                        <tr class="duration-200 {{ $rowBg }}">
                                            {{-- Date --}}
                                            <td class="px-6 py-3.5">
                                                <div class="flex items-center gap-3">
                                                    {{-- <div
                                                        class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                                    {{ $isEntrant ? 'bg-green-100 dark:bg-green-900/30 text-green' : 'bg-red-100 dark:bg-red-900/30' }}">
                                                        <i
                                                            class="las {{ $isEntrant ? 'la-arrow-down text-green-600 text-green' : 'la-arrow-up text-red-500' }} text-lg"></i>
                                                    </div> --}}
                                                    <div>
                                                        <p class="font-medium text-sm">
                                                            {{ \Carbon\Carbon::parse($mvt->date)->format('d/m/Y') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Libellé --}}
                                            <td class="px-6 py-3.5">
                                                <p class="font-medium text-sm">{{ $mvt->libelle }}</p>
                                            </td>

                                            {{-- Produit --}}
                                            {{-- <td class="px-6 py-3.5">
                                                <span
                                                    class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold
                                                {{ str_contains(strtolower($mvt->produit ?? ''), 'fcp')
                                                    ? 'bg-secondary3/10 text-secondary3'
                                                    : 'bg-primary/10 text-primary' }}">
                                                    {{ $mvt->produit ?? 'N/A' }}
                                                </span>
                                            </td> --}}

                                            {{-- Référence --}}
                                            <td class="px-6 py-3.5">
                                                <span class="text-xs font-mono opacity-70">{{ $mvt->ref ?? '-' }}</span>
                                            </td>

                                            {{-- Type badge --}}
                                            <td class="px-6 py-3.5 text-center">
                                                @if ($isEntrant)
                                                    <span
                                                        class="inline-flex items-center gap-1 text-green px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                        <i class="las la-arrow-down text-sm"></i> Entrant
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                                        <i class="las la-arrow-up text-sm"></i> Sortant
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Montant --}}
                                            <td class="px-6 py-3.5 text-right">
                                                <p
                                                    class="font-bold text-base {{ $isEntrant ? 'text-green-600' : 'text-red-500' }}">
                                                    {{ $isEntrant ? '+' : '-' }}{{ number_format(abs($mvt->montant), 0, ' ', ' ') }}
                                                </p>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="flex flex-col items-center justify-center py-20 gap-4">
                                <div class="w-20 h-20 rounded-full bg-n30 flex items-center justify-center">
                                    <i class="las la-history text-4xl opacity-30"></i>
                                </div>
                                <p class="text-base opacity-50 font-medium">Aucune transaction pour le moment</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </main>
@endsection
