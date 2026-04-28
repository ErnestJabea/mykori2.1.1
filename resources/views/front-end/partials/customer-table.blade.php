<!-- Category Tabs -->
<div class="flex items-center gap-4 mb-6 border-b border-n30">
    <a href="{{ route('customer', array_merge(request()->query(), ['category' => 'all', 'page' => 1])) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($categoryFilter ?? 'all') == 'all' ? 'border-primary text-primary' : 'border-transparent opacity-50 hover:opacity-100' }}">
        TOUS LES CLIENTS
    </a>
    <a href="{{ route('customer', array_merge(request()->query(), ['category' => '1', 'page' => 1])) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($categoryFilter ?? '') == '1' ? 'border-primary text-primary' : 'border-transparent opacity-50 hover:opacity-100' }}">
        CLIENTS FCP
    </a>
    <a href="{{ route('customer', array_merge(request()->query(), ['category' => '2', 'page' => 1, 'filter' => ''])) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($categoryFilter ?? '') == '2' && empty($filter) ? 'border-primary text-primary' : 'border-transparent opacity-50 hover:opacity-100' }}">
        CLIENTS PMG
    </a>
    <a href="{{ route('customer', array_merge(request()->query(), ['filter' => 'expiring_pmg', 'page' => 1])) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($filter ?? '') == 'expiring_pmg' ? 'border-red-500 text-red-500' : 'border-transparent opacity-50 hover:opacity-100 hover:text-red-500' }}">
        ÉCHÉANCES PMG DU MOIS
    </a>
    <a href="{{ route('customer', array_merge(request()->query(), ['filter' => 'anniversaries', 'page' => 1])) }}" 
       class="ajax-tab px-6 py-3 text-sm font-bold border-b-2 transition-all duration-300 {{ ($filter ?? '') == 'anniversaries' ? 'border-blue-500 text-blue-500' : 'border-transparent opacity-50 hover:opacity-100 hover:text-blue-500' }}">
        ANNIVERSAIRES PMG
    </a>
</div>

<div class="flex flex-wrap gap-4 xxl:gap-4 mb-8 w-full">

    <!-- Card 1: Total Investi -->
    <div class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-primary/50 duration-300 transition-all shadow-sm">
        <div class="w-10 h-10 rounded-full bg-n30 flex items-center justify-center text-n500 shrink-0">
            <i class="las la-wallet text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none">Capital Investi</p>
            <h4 class="text-base font-bold mb-0 text-n700 leading-none whitespace-nowrap">
                {{ number_format($globalTotalInvesti, 0, ' ', ' ') }}</h4>
        </div>
    </div>

    <!-- Card 2: Total Gains FCP -->
    <div class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-success/50 duration-300 transition-all shadow-sm">
        <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center text-success shrink-0">
            <i class="las la-chart-pie text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-success">Total Gains FCP</p>
            <h4 class="text-base font-bold mb-0 text-success leading-none whitespace-nowrap">
                +{{ number_format($globalTotalInterestsFcp ?? 0, 0, ' ', ' ') }}</h4>
        </div>
    </div>

    <!-- Card 3: Total Gains Actifs PMG -->
    <div class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-primary/50 duration-300 transition-all shadow-sm">
        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
            <i class="las la-chart-line text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-primary">Gains Actifs PMG</p>
            <h4 class="text-base font-bold mb-0 text-primary leading-none whitespace-nowrap">
                +{{ number_format($globalTotalInterestsPmg ?? 0, 0, ' ', ' ') }}</h4>
        </div>
    </div>

    <!-- Card 3: Clients Actifs -->
    <div class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-secondary1/50 duration-300 transition-all shadow-sm">
        <div class="w-10 h-10 rounded-full bg-secondary1/10 flex items-center justify-center text-secondary1 shrink-0">
            <i class="las la-user-check text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] uppercase font-bold opacity-50 mb-1 leading-none text-secondary1">Clients Actifs</p>
            <h4 class="text-base font-bold mb-0 text-secondary1 leading-none whitespace-nowrap">
                {{ $activeClientsCount }}</h4>
        </div>
    </div>

    <!-- Card 4: Clients Inactifs -->
    <div class="flex-1 min-w-[200px] box bg-white dark:bg-bg3 border border-n30 p-4 rounded-2xl flex items-center gap-3 hover:border-red-500/50 duration-300 transition-all shadow-sm">
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

<div class="box col-span-12 shadow-sm border border-n30 p-6">
    <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
        <h4 class="h4 flex items-center gap-2" id="table-title">
            <i class="las la-users text-primary"></i> 
            @if(($categoryFilter ?? 'all') == '1')
                Récapitulatif des Portefeuilles FCP
            @elseif(($categoryFilter ?? 'all') == '2')
                Récapitulatif des Portefeuilles PMG
            @else
                Récapitulatif de tous les Portefeuilles
            @endif
        </h4>

        <!-- Search Bar -->
        <div class="flex items-center gap-4">
            <form action="{{ route('customer') }}" method="GET" class="relative" id="search-form">
                @if(isset($categoryFilter)) <input type="hidden" name="category" value="{{ $categoryFilter }}"> @endif
                @if(isset($sortBy)) <input type="hidden" name="sort_by" value="{{ $sortBy }}"> @endif
                @if(isset($order)) <input type="hidden" name="order" value="{{ $order }}"> @endif
                @if(isset($filter)) <input type="hidden" name="filter" value="{{ $filter }}"> @endif
                
                <input type="text" name="search" id="ajax-search" value="{{ $search ?? '' }}" placeholder="Chercher un client..."
                    class="w-64 rounded-full border border-n30 bg-secondary1/5 px-6 py-2 dark:border-n500 dark:bg-bg3 focus:border-primary focus:ring-1 focus:ring-primary outline-none text-sm">
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


    <table class="w-full min-w-[1000px]">
        <thead>
            @php
                $sortUrl = function($field) use ($sortBy, $order, $categoryFilter, $search, $filter) {
                    $newOrder = ($sortBy == $field && $order == 'asc') ? 'desc' : 'asc';
                    return route('customer', [
                        'sort_by' => $field,
                        'order' => $newOrder,
                        'category' => $categoryFilter ?? 'all',
                        'filter' => $filter ?? '',
                        'search' => $search ?? ''
                    ]);
                };
                
                $sortIcon = function($field) use ($sortBy, $order) {
                    if ($sortBy != $field) return '<i class="las la-sort opacity-30"></i>';
                    return $order == 'asc' ? '<i class="las la-sort-up text-primary"></i>' : '<i class="las la-sort-down text-primary"></i>';
                };
            @endphp
            <tr class="bg-secondary1/5 dark:bg-bg4">
                <th class="px-6 py-5 text-left font-semibold opacity-70 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('name') }}" class="flex items-center gap-2 w-full ajax-sort">
                        Noms & Prénoms {!! $sortIcon('name') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold opacity-70 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('total_capital') }}" class="flex items-center justify-end gap-2 w-full ajax-sort">
                        Total Investi {!! $sortIcon('total_capital') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold opacity-70 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('total_interets') }}" class="flex items-center justify-end gap-2 w-full ajax-sort">
                        Gains Actifs {!! $sortIcon('total_interets') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold opacity-70 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('portefeuille_total') }}" class="flex items-center justify-end gap-2 w-full ajax-sort">
                        Portefeuille Global {!! $sortIcon('portefeuille_total') !!}
                    </a>
                </th>
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

<div class="mt-6 ajax-pagination">
    {{ $customers->appends([
        'search' => $search,
        'category' => $categoryFilter ?? 'all',
        'filter' => $filter ?? '',
        'sort_by' => $sortBy ?? 'name',
        'order' => $order ?? 'asc'
    ])->links() }}
</div>
</div>
