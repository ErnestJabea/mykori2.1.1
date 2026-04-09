<div class="overflow-x-auto pb-4">
    <table class="w-full min-w-[1000px]">
        <thead>
            @php
                $sortUrl = function($field) use ($sortBy, $order, $search) {
                    $newOrder = ($sortBy == $field && $order == 'asc') ? 'desc' : 'asc';
                    return route('customer.statements', [
                        'sort_by' => $field,
                        'order' => $newOrder,
                        'search' => $search ?? ''
                    ]);
                };
                
                $sortIcon = function($field) use ($sortBy, $order) {
                    if (($sortBy ?? 'name') != $field) return '<i class="las la-sort opacity-30"></i>';
                    return ($order ?? 'asc') == 'asc' ? '<i class="las la-sort-up text-primary"></i>' : '<i class="las la-sort-down text-primary"></i>';
                };
            @endphp
            <tr class="bg-secondary1/5 dark:bg-bg4">
                <th class="px-6 py-5 text-left font-semibold opacity-70 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('name') }}" class="flex items-center gap-2 ajax-sort">
                        Client {!! $sortIcon('name') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold opacity-70 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('total_capital') }}" class="flex items-center justify-end gap-2 ajax-sort italic">
                        Total Investi (Brut) {!! $sortIcon('total_capital') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-right font-semibold opacity-70 cursor-pointer hover:bg-secondary1/10 duration-300">
                    <a href="{{ $sortUrl('portefeuille_total') }}" class="flex items-center justify-end gap-2 ajax-sort">
                        Total Portefeuille (XAF) {!! $sortIcon('portefeuille_total') !!}
                    </a>
                </th>
                <th class="px-6 py-5 text-center font-semibold opacity-70">Actions</th>
            </tr>
        </thead>
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

                    <td class="px-6 py-4 text-right font-medium">
                        {{ number_format($client->total_capital, 0, ' ', ' ') }}
                    </td>

                    <td class="px-6 py-4 text-right">
                        <p class="font-bold text-primary">
                            {{ number_format($client->portefeuille_total, 0, ' ', ' ') }}
                        </p>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <button onclick="openHistoryModal('{{ $client->id }}', '{{ $client->name }}')"
                            class="btn-outline border-primary text-primary  px-3 py-1 rounded-md text-sm duration-300"
                            style="border-color: #ebb009; color: #ebb009">
                            <i class="las la-history"></i> Voir Relevés
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-6 ajax-pagination">
    {{ $customers->appends([
        'search' => $search,
        'sort_by' => $sortBy ?? 'name',
        'order' => $order ?? 'asc'
    ])->links() }}
</div>
