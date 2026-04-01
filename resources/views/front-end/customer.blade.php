@extends('front-end/app/app-home-asset', ['title' => 'Liste des Clients | KORI', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@php
    // dd($customers);
@endphp
@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <h3>GESTION DES CLIENTS</h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4">
                <div class="row flex items-center justify-end gap-3">
                    <p style="text-align: right">{{ date('d-m-Y') }}</p>
                    <div class="content-right">
                        <button class="btn ac-modal-btn buy">
                            <a href="{{ route('releve-client') }}">Validation des relevés</a>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-separator" style="height:30px"></div>

        <div class="flex flex-wrap gap-4 xxl:gap-4 mb-8 w-full">
            <!-- Card 1: Total Investi -->
            <div
                class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-primary/50 duration-300 transition-all shadow-sm">
                <div class="w-10 h-10 rounded-full bg-n30 flex items-center justify-center text-n500 shrink-0">
                    <i class="las la-wallet text-xl"></i>
                </div>
                <div>
                    <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none">Capital Investi</p>
                    <h4 class="text-base font-bold mb-0 text-n700 leading-none whitespace-nowrap">
                        {{ number_format($globalTotalInvesti, 0, ' ', ' ') }}</h4>
                </div>
            </div>

            <!-- Card 2: Total Intérêts -->
            <div
                class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-success/50 duration-300 transition-all shadow-sm">
                <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center text-success shrink-0">
                    <i class="las la-chart-bar text-xl"></i>
                </div>
                <div>
                    <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-success">Total Intérêts</p>
                    <h4 class="text-base font-bold mb-0 text-success leading-none whitespace-nowrap">
                        +{{ number_format($globalTotalInterets, 0, ' ', ' ') }}</h4>
                </div>
            </div>

            <!-- Card 3: Global AUM -->
            {{-- <div class="flex-1 min-w-[200px] box bg-primary p-4 rounded-2xl flex items-center gap-3 shadow-lg">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white shrink-0">
                <i class="las la-piggy-bank text-xl"></i>
            </div>
            <div>
                <p class="text-[9px] uppercase font-bold text-white/80 mb-1 leading-none">Encours (AUM)</p>
                <h4 class="text-base font-bold mb-0 text-white leading-none whitespace-nowrap">{{ number_format($globalTotalAum, 0, ' ', ' ') }}</h4>
            </div>
        </div> --}}

            <!-- Card 4: Clients Actifs -->
            <div
                class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-secondary1/50 duration-300 transition-all shadow-sm">
                <div
                    class="w-10 h-10 rounded-full bg-secondary1/10 flex items-center justify-center text-secondary1 shrink-0">
                    <i class="las la-user-check text-xl"></i>
                </div>
                <div>
                    <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-secondary1">Clients Actifs
                    </p>
                    <h4 class="text-base font-bold mb-0 text-secondary1 leading-none whitespace-nowrap">
                        {{ $activeClientsCount }}</h4>
                </div>
            </div>

            <!-- Card 5: Clients Inactifs -->
            <div
                class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-red-500/50 duration-300 transition-all shadow-sm">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-500 shrink-0">
                    <i class="las la-user-minus text-xl"></i>
                </div>
                <div>
                    <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-red-500">Clients Inactifs</p>
                    <h4 class="text-base font-bold mb-0 text-red-500 leading-none whitespace-nowrap">
                        {{ $inactiveClientsCount }}</h4>
                </div>
            </div>
        </div>

        <div class="box col-span-12 shadow-sm border border-n30">
            <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                <h4 class="h4 flex items-center gap-2">
                    <i class="las la-users text-primary"></i> Récapitulatif des Portefeuilles
                </h4>

                <!-- Search Bar in Top Right of Table Header -->
                <div class="flex items-center gap-4">
                    <form action="{{ route('customer') }}" method="GET" class="relative">
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Chercher un client..."
                            class="w-64 rounded-full border border-n30 bg-secondary1/5 px-6 py-2 dark:border-n500 dark:bg-bg3 focus:border-primary focus:ring-1 focus:ring-primary outline-none text-sm">
                        <button type="submit"
                            class="absolute right-1 top-1/2 -translate-y-1/2 bg-primary text-white p-1 rounded-full w-8 h-8 flex items-center justify-center hover:bg-primary/90 transition-all">
                            <i class="las la-search text-base"></i>
                        </button>
                    </form>
                    @if (!empty($search))
                        <a href="{{ route('customer') }}" class="text-xs text-primary underline">Réinitialiser</a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto pb-4">
                <table class="w-full min-w-[1000px]">
                    <thead>
                        <tr class="bg-secondary1/5 dark:bg-bg4">
                            <th class="px-6 py-5 text-left font-semibold opacity-70">Noms & Prénoms</th>
                            <th class="px-6 py-5 text-right font-semibold opacity-70">Total Investissement</th>
                            <th class="px-6 py-5 text-right font-semibold opacity-70">Total Intérêts</th>
                            <th class="px-6 py-5 text-right font-semibold opacity-70">Portefeuille Global</th>
                            <th class="px-6 py-5 text-center font-semibold opacity-70">Action</th>
                        </tr>
                    </thead>
                    <div class="px-6 py-2">
                        <small class="text-xs text-muted">Affichage de {{ $customers->firstItem() }} à
                            {{ $customers->lastItem() }} sur {{ $customers->total() }} clients</small>
                    </div>
                    <tbody>
                        @foreach ($customers as $client)
                            <tr class="border-b border-secondary1/10 dark:border-bg4 hover:bg-primary/5 duration-300">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="text-left">
                                            <p class="font-semibold text-base">{{ $client->name }}</p>
                                            <span class="text-xs opacity-70">{{ $client->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <p class="font-medium">
                                        {{ number_format($client->total_capital, 0, ' ', ' ') }}
                                    </p>
                                </td>

                                <td class="px-6 py-4 text-right" style="white-space: nowrap;">
                                    <p class="font-medium text-success" style="color: #10b981">
                                        + {{ number_format($client->total_interets, 0, ' ', ' ') }}
                                    </p>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="rounded bg-primary/10 px-3 py-1 inline-block">
                                        <p class="font-bold text-primary">
                                            {{ number_format($client->portefeuille_total, 0, ' ', ' ') }}
                                        </p>
                                    </div>
                                </td>

                                {{-- <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-secondary1/10 px-2 py-1 text-xs">
                                {{ $client->product_count }} contrat(s)
                            </span>
                        </td> --}}

                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('customer-detail', ['customer' => $client->id]) }}"
                                        class="btn-outline border-primary text-primary hover:bg-primary hover:text-white px-3 py-1 rounded-md text-sm duration-300">
                                        <i class="las la-eye"></i> Détails
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $customers->appends(['search' => $search])->links() }}
            </div>
        </div>
    </main>
@endsection
