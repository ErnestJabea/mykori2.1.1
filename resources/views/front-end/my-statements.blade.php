@extends('front-end/app/app-home', ['Mes Relevés', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner-content px-4 py-8 lg:px-6 lg:py-10">
            <div
                class="bb-dashed mb-6 flex flex-wrap items-center justify-between gap-3 pb-6 border-b border-n30 dark:border-n500">
                <h4 class="h4 text-2xl font-bold text-n900 dark:text-n0">Historique de mes relevés mensuels</h4>
                <p class="text-sm text-n100 dark:text-n50">Retrouvez ici tous vos relevés de performance passés.</p>
            </div>

            <div class="col-span-12">
                <div class="box rounded-2xl bg-n0 p-6 shadow-sm dark:bg-bg4">
                    @if (count($months) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full whitespace-nowrap select-all-table">
                                <thead>
                                    <tr class="bg-secondary1/5 dark:bg-bg3">
                                        <th class="px-6 py-5 text-start font-semibold text-n900 dark:text-n0">Période</th>
                                        <th class="px-6 py-5 text-center font-semibold text-n900 dark:text-n0">Type de
                                            Relevé</th>
                                        <th class="px-6 py-5 text-center font-semibold text-n900 dark:text-n0">
                                            Téléchargements</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($months as $m)
                                        <tr
                                            class="border-b border-n30 last:border-0 hover:bg-secondary1/5 dark:border-n500 dark:hover:bg-bg3 duration-200">
                                            <td class="px-6 py-5">
                                                <div class="flex items-center gap-4">
                                                    <div
                                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                        <i class="las la-calendar text-xl"></i>
                                                    </div>
                                                    <div>
                                                        <span
                                                            class="block text-lg font-semibold text-n900 dark:text-n0">{{ $m['label'] }}</span>
                                                        <span class="text-xs text-n100 dark:text-n50">Relevé de fin de
                                                            mois</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    @if ($m['has_pmg'])
                                                        <span
                                                            class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary border border-primary/20">
                                                            <i class="las la-shield-alt mr-1"></i> PMG
                                                        </span>
                                                    @endif
                                                    @if ($m['has_fcp'])
                                                        <span
                                                            class="inline-flex items-center rounded-full bg-secondary3/10 px-3 py-1 text-xs font-bold text-secondary3 border border-secondary3/20">
                                                            <i class="las la-chart-pie mr-1"></i> FCP
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex justify-center gap-4">
                                                    @if ($m['has_pmg'])
                                                        <a href="{{ route('my-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'pmg']) }}"
                                                            class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-n0 shadow-lg shadow-primary/20 transition-all hover:scale-105 hover:bg-primary/90 active:scale-95">
                                                            <i class="las la-download text-lg"></i>
                                                            PMG
                                                        </a>
                                                    @endif
                                                    @if ($m['has_fcp'])
                                                        <a href="{{ route('my-statement.monthly', ['year' => $m['year'], 'month' => $m['month'], 'type' => 'fcp']) }}"
                                                            class="btn flex items-center gap-2 rounded-xl bg-secondary3 px-5 py-2.5 text-sm font-bold text-n0 shadow-lg shadow-secondary3/20 transition-all hover:scale-105 hover:bg-secondary3/90 active:scale-95">
                                                            <i class="las la-download text-lg"></i>
                                                            FCP
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-8">
                            {{ $months->links('front-end.partials.pagination') }}
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-20 text-center">
                            <div class="mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-n30 dark:bg-bg3">
                                <i class="las la-folder-open text-5xl text-n100"></i>
                            </div>
                            <h5 class="h5 mb-2 font-bold text-n900 dark:text-n0">Aucun relevé disponible</h5>
                            <p class="max-w-xs text-n100 dark:text-n50">Vos relevés mensuels apparaîtront ici dès que vos
                                investissements auront généré leur première performance mensuelle.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
