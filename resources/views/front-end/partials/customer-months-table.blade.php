@if (isset($availableMonths) && count($availableMonths) > 0)
    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap">
            <thead>
                <tr class="bg-secondary1/5 dark:bg-bg3">
                    <th class="px-6 py-4 text-start font-semibold">Période</th>
                    <th class="px-6 py-4 text-center font-semibold">Types</th>
                    <th class="px-6 py-4 text-center font-semibold">Aperçu & Téléchargement</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($availableMonths as $m)
                    <tr class="border-b border-n30 last:border-0 hover:bg-secondary1/5 duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <i class="las la-calendar text-xl"></i>
                                </div>
                                <span class="font-bold">{{ $m['label'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                @if ($m['has_pmg'])
                                    <span
                                        class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary border border-primary/20">PMG</span>
                                @endif
                                @if ($m['has_fcp'])
                                    <span
                                        class="rounded-full bg-secondary/10 px-3 py-1 text-xs font-bold text-secondary border border-secondary/20">FCP</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-3">
                                @if ($m['has_pmg'])
                                    <div class="flex flex-col gap-1">
                                        <a href="{{ route('customer-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'pmg', 'customer_id' => $customer->id]) }}?action=view"
                                            target="_blank"
                                            class="flex items-center justify-center gap-2 rounded-lg bg-n10 border border-n30 px-3 py-1.5 text-[10px] font-bold text-n700 hover:bg-n30 transition-all w-full">
                                            <i class="las la-eye text-sm"></i> Aperçu PMG
                                        </a>
                                        <a href="{{ route('customer-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'pmg', 'customer_id' => $customer->id]) }}"
                                            class="flex items-center justify-center gap-2 rounded-lg bg-primary px-3 py-1.5 text-[10px] font-bold text-white hover:bg-primary/90 transition-all w-full">
                                            <i class="las la-download text-sm"></i> PDF PMG
                                        </a>
                                    </div>
                                @endif
                                @if ($m['has_fcp'])
                                    <div class="flex flex-col gap-1">
                                        <a href="{{ route('customer-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'fcp', 'customer_id' => $customer->id]) }}?action=view"
                                            target="_blank"
                                            class="flex items-center justify-center gap-2 rounded-lg bg-n10 border border-n30 px-3 py-1.5 text-[10px] font-bold text-n700 hover:bg-n30 transition-all w-full">
                                            <i class="las la-eye text-sm"></i> Aperçu FCP
                                        </a>
                                        <a href="{{ route('customer-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'fcp', 'customer_id' => $customer->id]) }}"
                                            class="flex items-center justify-center gap-2 rounded-lg bg-secondary px-3 py-1.5 text-[10px] font-bold text-white hover:bg-secondary/90 transition-all w-full"
                                            style="background-color: #ebb009">
                                            <i class="las la-download text-sm"></i> PDF FCP
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 px-6 pb-6 ajax-pagination-detail">
        {{ $availableMonths->links('front-end.partials.pagination') }}
    </div>
@else
    <div class="py-10 text-center opacity-60">
        <i class="las la-folder-open text-5xl mb-2"></i>
        <p>Aucun relevé mensuel disponible pour ce client.</p>
    </div>
@endif
