<!-- Category Tabs -->
<div class="flex items-center gap-4 mb-6 border-b border-n30 overflow-x-auto whitespace-nowrap pb-2">
    <a href="{{ route('customer', ['search' => $search ?? '', 'category' => 'all', 'filter' => '', 'status' => 'all', 'sort_by' => 'name', 'order' => 'asc', 'page' => 1]) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($categoryFilter ?? 'all') == 'all' ? 'border-primary text-primary' : 'border-transparent opacity-50 hover:opacity-100' }}">
        TOUS LES CLIENTS
    </a>
    <a href="{{ route('customer', ['search' => $search ?? '', 'category' => '1', 'filter' => '', 'status' => 'all', 'sort_by' => 'name', 'order' => 'asc', 'page' => 1]) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($categoryFilter ?? '') == '1' && empty($filter) ? 'border-primary text-primary' : 'border-transparent opacity-50 hover:opacity-100' }}">
        CLIENTS FCP
    </a>
    <a href="{{ route('customer', ['search' => $search ?? '', 'category' => '2', 'filter' => '', 'status' => 'all', 'sort_by' => 'name', 'order' => 'asc', 'page' => 1]) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($categoryFilter ?? '') == '2' && empty($filter) ? 'border-primary text-primary' : 'border-transparent opacity-50 hover:opacity-100' }}">
        CLIENTS PMG
    </a>
    <a href="{{ route('customer', ['search' => $search ?? '', 'category' => '2', 'filter' => 'expiring_pmg', 'status' => 'all', 'sort_by' => 'name', 'order' => 'asc', 'page' => 1]) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($filter ?? '') == 'expiring_pmg' ? 'border-red-500 text-red-500' : 'border-transparent opacity-50 hover:opacity-100 hover:text-red-500' }}">
        ÉCHÉANCES PMG DU MOIS
    </a>
    <a href="{{ route('customer', ['search' => $search ?? '', 'category' => '2', 'filter' => 'anniversaries', 'status' => 'all', 'sort_by' => 'name', 'order' => 'asc', 'page' => 1]) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($filter ?? '') == 'anniversaries' ? 'border-blue-500 text-blue-500' : 'border-transparent opacity-50 hover:opacity-100 hover:text-blue-500' }}">
        ANNIVERSAIRES PMG
    </a>
</div>

@php
    $cardUrl = function (array $params) {
        return route('customer', array_merge(request()->query(), $params, ['page' => 1]));
    };
@endphp

<div class="flex flex-wrap gap-4 xxl:gap-4 mb-8 w-full">

    <!-- Card 1: Total Investi -->
    <a href="{{ $cardUrl(['category' => 'all', 'filter' => '', 'status' => 'active', 'sort_by' => 'total_capital', 'order' => 'desc']) }}"
       class="ajax-card flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 dark:border-n700 p-4 rounded-2xl flex items-center gap-3 hover:border-primary/50 duration-300 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
        <div class="w-10 h-10 rounded-full bg-n30 dark:bg-n700 flex items-center justify-center text-n500 dark:text-white shrink-0">
            <i class="las la-wallet text-xl"></i>
        </div>
        <div>
            <p class="text-[10px] uppercase font-bold opacity-80 text-n500 dark:text-n30 mb-1 leading-none">Capital Investi</p>
            <h4 class="text-base font-bold mb-0 text-n700 dark:text-white leading-none whitespace-nowrap">
                {{ number_format($globalTotalInvesti, 0, ' ', ' ') }}</h4>
        </div>
    </a>

    <!-- Card 2: Total Gains FCP -->
    <a href="{{ $cardUrl(['category' => '1', 'filter' => '', 'status' => 'active', 'sort_by' => 'total_interets', 'order' => 'desc']) }}"
       class="ajax-card flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-success/50 duration-300 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-success/30">
        <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center text-success shrink-0">
            <i class="las la-chart-pie text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-success">Total Gains FCP</p>
            <h4 class="text-base font-bold mb-0 text-success leading-none whitespace-nowrap">
                +{{ number_format($globalTotalInterestsFcp ?? 0, 0, ' ', ' ') }}</h4>
        </div>
    </a>

    <!-- Card 3: Total Gains Actifs PMG -->
    <a href="{{ $cardUrl(['category' => '2', 'filter' => '', 'status' => 'active', 'sort_by' => 'total_interets', 'order' => 'desc']) }}"
       class="ajax-card flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-primary/50 duration-300 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
            <i class="las la-chart-line text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-primary">Gains Actifs PMG</p>
            <h4 class="text-base font-bold mb-0 text-primary leading-none whitespace-nowrap">
                +{{ number_format($globalTotalInterestsPmg ?? 0, 0, ' ', ' ') }}</h4>
        </div>
    </a>

    <!-- Card 3: Clients Actifs -->
    <a href="{{ $cardUrl(['category' => 'all', 'filter' => '', 'status' => 'active', 'sort_by' => 'name', 'order' => 'asc']) }}"
       class="ajax-card flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-secondary1/50 duration-300 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary1/30">
        <div class="w-10 h-10 rounded-full bg-secondary1/10 flex items-center justify-center text-secondary1 shrink-0">
            <i class="las la-user-check text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-secondary1">Clients Actifs</p>
            <h4 class="text-base font-bold mb-0 text-secondary1 leading-none whitespace-nowrap">
                {{ $activeClientsCount }}</h4>
        </div>
    </a>

    <!-- Card 4: Clients Inactifs -->
    <a href="{{ $cardUrl(['category' => 'all', 'filter' => '', 'status' => 'inactive', 'sort_by' => 'name', 'order' => 'asc']) }}"
       class="ajax-card flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-red-500/50 duration-300 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500/30">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-500 shrink-0">
            <i class="las la-user-minus text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-red-500">Clients Inactifs</p>
            <h4 class="text-base font-bold mb-0 text-red-500 leading-none whitespace-nowrap">
                {{ $inactiveClientsCount }}</h4>
        </div>
    </a>
</div>

<div class="box col-span-12 shadow-sm border border-n30 dark:border-n700 bg-white dark:bg-bg3 p-6 rounded-2xl">
    <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6 dark:border-n700">
        <h4 class="h4 flex items-center gap-2 text-n700 dark:text-white font-bold" id="table-title">
            <i class="las la-users text-primary"></i> 
            @if(($categoryFilter ?? 'all') == '1')
                Récapitulatif des Portefeuilles FCP
            @elseif(($categoryFilter ?? 'all') == '2')
                Récapitulatif des Portefeuilles PMG
            @else
                Récapitulatif de tous les Portefeuilles
            @endif
            @if(($statusFilter ?? 'all') == 'active')
                <span class="rounded-full bg-secondary1/10 px-3 py-1 text-xs font-bold text-secondary1">Actifs</span>
            @elseif(($statusFilter ?? 'all') == 'inactive')
                <span class="rounded-full bg-red-100 dark:bg-red-900/30 px-3 py-1 text-xs font-bold text-red-500">Inactifs</span>
            @endif
        </h4>

        <!-- Search Bar -->
        <div class="flex items-center gap-4">
            <form action="{{ route('customer') }}" method="GET" class="relative" id="search-form">
                @if(isset($categoryFilter)) <input type="hidden" name="category" value="{{ $categoryFilter }}"> @endif
                @if(isset($sortBy)) <input type="hidden" name="sort_by" value="{{ $sortBy }}"> @endif
                @if(isset($order)) <input type="hidden" name="order" value="{{ $order }}"> @endif
                @if(isset($filter)) <input type="hidden" name="filter" value="{{ $filter }}"> @endif
                @if(isset($statusFilter)) <input type="hidden" name="status" value="{{ $statusFilter }}"> @endif
                
                <input type="text" name="search" id="ajax-search" value="{{ $search ?? '' }}" placeholder="Chercher un client..."
                    class="w-64 rounded-full border border-n30 bg-secondary1/5 px-6 py-2 dark:border-n500 dark:bg-bg4 text-n700 dark:text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none text-sm">
                <button type="submit"
                    class="absolute right-1 top-1/2 -translate-y-1/2 bg-primary text-white p-1 rounded-full w-8 h-8 flex items-center justify-center hover:bg-primary/90 transition-all">
                    <i class="las la-search text-base"></i>
                </button>
            </form>
            @if (!empty($search))
                <a href="{{ route('customer', ['category' => $categoryFilter ?? 'all']) }}" class="text-xs text-primary underline" id="reset-search">Réinitialiser</a>
            @endif
            <button type="button" id="open-export-modal"
            class="flex items-center gap-2 rounded-lg bg-success px-4 py-2 text-xs font-bold text-white hover:bg-success/90 transition-all ml-4"
            style="background-color: #10b981">
                <i class="las la-file-csv text-base"></i> OPTIONS D'EXPORTATION
            </button>
        </div>
    </div>

    <div class="overflow-x-auto pb-4">


    <table class="w-full min-w-[1150px]">
        <thead>
            @php
                $sortUrl = function($field) use ($sortBy, $order, $categoryFilter, $search, $filter, $statusFilter) {
                    $newOrder = ($sortBy == $field && $order == 'asc') ? 'desc' : 'asc';
                    return route('customer', [
                        'sort_by' => $field,
                        'order' => $newOrder,
                        'category' => $categoryFilter ?? 'all',
                        'filter' => $filter ?? '',
                        'status' => $statusFilter ?? 'all',
                        'search' => $search ?? ''
                    ]);
                };
                
                $sortIcon = function($field) use ($sortBy, $order) {
                    if ($sortBy != $field) return '<i class="las la-sort opacity-30"></i>';
                    return $order == 'asc' ? '<i class="las la-sort-up text-primary"></i>' : '<i class="las la-sort-down text-primary"></i>';
                };
            @endphp
            <tr class="bg-secondary1/5 dark:bg-bg4 border-b border-n30 dark:border-n700">
                <th class="px-6 py-5 text-left font-semibold text-n700 dark:text-slate-200 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('name') }}" class="flex items-center gap-2 w-full ajax-sort text-n700 dark:text-white">
                        Noms & Prénoms {!! $sortIcon('name') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold text-n700 dark:text-slate-200 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('total_capital') }}" class="flex items-center justify-end gap-2 w-full ajax-sort text-n700 dark:text-white">
                        Total Investi {!! $sortIcon('total_capital') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold text-n700 dark:text-slate-200 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('total_interets') }}" class="flex items-center justify-end gap-2 w-full ajax-sort text-n700 dark:text-white">
                        Gains Actifs {!! $sortIcon('total_interets') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold text-n700 dark:text-slate-200 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('total_liquidite_interets') }}" class="flex items-center justify-end gap-2 w-full ajax-sort text-n700 dark:text-white">
                        Liquidité {!! $sortIcon('total_liquidite_interets') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold text-n700 dark:text-slate-200 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('portefeuille_total') }}" class="flex items-center justify-end gap-2 w-full ajax-sort text-n700 dark:text-white">
                        Portefeuille Global {!! $sortIcon('portefeuille_total') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-center font-semibold text-n700 dark:text-slate-200">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $client)
                <tr class="border-b border-n30/50 dark:border-n700 hover:bg-primary/5 dark:hover:bg-bg4/50 duration-300">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="text-left">
                                <p class="font-bold text-base text-n900 dark:text-white">{{ $client->name }}</p>
                                <span class="text-xs text-n500 dark:text-slate-300">{{ $client->email }}</span>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <p class="font-semibold text-n700 dark:text-white">
                            {{ number_format($client->total_capital, 0, ' ', ' ') }}
                        </p>
                    </td>

                    <td class="px-6 py-4 text-right" style="white-space: nowrap;">
                        <p class="font-semibold text-success dark:text-emerald-400">
                            + {{ number_format($client->total_interets, 0, ' ', ' ') }}
                        </p>
                    </td>

                    <td class="px-6 py-4 text-right" style="white-space: nowrap;">
                        <p class="font-semibold text-secondary1 dark:text-blue-400">
                            {{ number_format($client->total_liquidite_interets ?? 0, 0, ' ', ' ') }}
                        </p>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <div class="rounded-xl bg-primary/10 px-3 py-1.5 inline-block border border-primary/20">
                            <p class="font-extrabold text-primary dark:text-yellow-400">
                                {{ number_format($client->portefeuille_total, 0, ' ', ' ') }}
                            </p>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('customer-detail', ['customer' => $client->id]) }}"
                            class="btn-outline border border-primary/40 text-primary dark:text-yellow-400 hover:bg-primary hover:text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-300 inline-flex items-center gap-1 shadow-sm">
                            <i class="las la-eye text-sm"></i> Détails
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-6 ajax-pagination">
    {{ $customers->appends([
        'search' => $search,
        'category' => $categoryFilter ?? 'all',
        'filter' => $filter ?? '',
        'status' => $statusFilter ?? 'all',
        'sort_by' => $sortBy ?? 'name',
        'order' => $order ?? 'asc'
    ])->links('front-end.partials.pagination') }}
</div>
</div>
