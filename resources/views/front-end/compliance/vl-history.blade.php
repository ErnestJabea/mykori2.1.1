@extends('front-end/app/app-home-asset', ['title' => 'Évolution des VL | Compliance', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page other-page compliance-profil'])

@section('content')
    <main class="main-content has-sidebar my-products-page other-page">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Header Section -->
            <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                <div class="bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <h3 class="h3 uppercase">HISTORIQUE DES VL</h3>
                            <p class="text-sm opacity-70 font-medium text-primary">Traçabilité complète des changements de
                                valorisation</p>
                        </div>
                    </div>

                    <!-- Export Advanced Options -->
                    <div class="border-t border-n30 pt-6">
                        <h4 class="text-xs font-bold uppercase opacity-60 mb-4 flex items-center gap-2">
                            <i class="las la-file-export text-lg"></i> EXPORT PERSONNALISÉ (CSV)
                        </h4>
                        <form action="{{ route('compliance.export') }}" method="GET" class="grid grid-cols-12 gap-4">
                            <input type="hidden" name="type" value="vls">

                            <!-- Product Selection -->
                            <div class="col-span-12 lg:col-span-6">
                                <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Produits FCP à inclure
                                    :</label>
                                <div
                                    class="flex flex-wrap gap-3 bg-n10 p-4 rounded-xl border border-n30 max-h-[120px] overflow-y-auto">
                                    @foreach ($products as $p)
                                        <label
                                            class="flex items-center gap-2 cursor-pointer hover:text-primary duration-200">
                                            <input type="checkbox" name="product_ids[]" value="{{ $p->id }}"
                                                class="w-4 h-4 rounded border-n40 checked:bg-primary" checked>
                                            <span class="text-xs font-medium">{{ $p->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Date Range -->
                            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                                <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Période :</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-n10 p-3 rounded-xl border border-n30 relative">
                                        <label class="text-[9px] block opacity-60 uppercase mb-1">Du</label>
                                        <div class="flex items-center gap-2">
                                            <i class="las la-calendar text-primary text-sm"></i>
                                            <input type="text" name="start_date" id="date"
                                                class="bg-white border border-n30 rounded px-2 py-1 text-xs w-full focus:outline-none focus:border-primary cursor-pointer"
                                                placeholder="JJ/MM/AAAA" readonly>
                                        </div>
                                    </div>
                                    <div class="bg-n10 p-3 rounded-xl border border-n30 relative">
                                        <label class="text-[9px] block opacity-60 uppercase mb-1">Au</label>
                                        <div class="flex items-center gap-2">
                                            <i class="las la-calendar text-primary text-sm"></i>
                                            <input type="text" name="end_date" id="date2" value="{{ date('Y-m-d') }}"
                                                class="bg-white border border-n30 rounded px-2 py-1 text-xs w-full focus:outline-none focus:border-primary cursor-pointer"
                                                placeholder="JJ/MM/AAAA" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Export Action -->
                            <div class="col-span-12 md:col-span-6 lg:col-span-2 flex items-end">
                                <button type="submit"
                                    class="w-full btn bg-success text-white rounded-xl py-4 flex items-center justify-center gap-2 hover:opacity-90 duration-300 font-bold shadow-lg">
                                    <i class="las la-download text-xl"></i> EXPORTER
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-span-12">
                <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-n30 bg-n10/30">
                        <form action="{{ route('compliance.vl-history') }}" method="GET"
                            class="flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-bold opacity-60">Filtrer par Produit :</label>
                                <select name="product_id"
                                    class="bg-white border border-n30 rounded-lg px-4 py-2 text-sm focus:border-primary focus:outline-none min-w-[200px]">
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}"
                                            {{ $selectedProductId == $p->id ? 'selected' : '' }}>{{ $p->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn bg-primary text-white rounded-lg px-6 py-2">
                                Appliquer le filtre
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead>
                                <tr class="bg-n20 dark:bg-bg3 text-n500 uppercase text-[11px] font-bold">
                                    <th class="py-4 px-6 text-start">Date de la Valeur</th>
                                    <th class="py-4 px-6 text-start">Produit Associé</th>
                                    <th class="py-4 px-6 text-end">Valeur Liquidative (XAF)</th>
                                    <th class="py-4 px-6 text-end">Horodatage Système</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30">
                                @foreach ($vls as $v)
                                    <tr class="hover:bg-primary/5 duration-200">
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <i class="las la-calendar text-primary"></i>
                                                <span
                                                    class="text-sm font-bold">{{ Carbon\Carbon::parse($v->date_vl)->format('d/m/Y') }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span
                                                class="px-2 py-0.5 rounded bg-n20 text-n700 text-[10px] font-bold uppercase">
                                                {{ $v->product->title ?? 'Produit Inconnu' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-end font-extrabold text-primary text-lg">
                                            {{ number_format($v->vl, 2, ',', ' ') }}
                                        </td>
                                        <td class="py-4 px-6 text-end text-xs opacity-60 italic">
                                            Défini le {{ $v->created_at->format('d/m/Y à H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($vls->hasPages())
                        <div class="p-4 border-t border-n30 flex justify-center">
                            {{ $vls->appends(request()->input())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
