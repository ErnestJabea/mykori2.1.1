@extends('front-end/app/app-home-asset', ['title' => 'Audit de Portefeuille | Compliance', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page other-page compliance-profil'])

@section('content')
    <main class="main-content has-sidebar my-products-page other-page">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Header Section -->
            <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                <div
                    class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div>
                        <h3 class="h3 uppercase text-primary">AUDIT DE PORTEFEUILLE</h3>
                        <p class="text-sm opacity-70 font-medium">Situation consolidée par client et par catégorie</p>
                    </div>
                    <form action="{{ route('compliance.portfolio-audit') }}" method="GET" class="flex items-center gap-4">
                        <div class="flex gap-2">
                            <input type="text" name="start_date" id="audit_start_date" value="{{ $startDate }}"
                                readonly
                                class="bg-n10 dark:bg-bg4 border border-n30 rounded-lg px-4 py-2 text-xs focus:outline-none focus:border-primary w-[130px] cursor-pointer"
                                placeholder="Début">
                            <input type="text" name="end_date" id="audit_end_date" value="{{ $endDate }}" readonly
                                class="bg-n10 dark:bg-bg4 border border-n30 rounded-lg px-4 py-2 text-xs focus:outline-none focus:border-primary w-[130px] cursor-pointer"
                                placeholder="Fin">
                        </div>
                        <button type="submit" class="p-3 bg-primary text-white rounded-lg hover:opacity-90 duration-200">
                            <i class="las la-filter text-xl"></i>
                        </button>
                        <button type="button" id="btn-export-selected"
                            class="p-3 bg-primary text-white rounded-lg hover:opacity-90 duration-200 flex items-center gap-2 font-bold text-xs uppercase">
                            <i class="las la-file-excel text-xl"></i> Exporter la sélection
                        </button>
                    </form>

                    <form id="export-form" action="{{ route('compliance.portfolio-audit.export') }}" method="POST"
                        style="display:none;">
                        @csrf
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                        <input type="hidden" name="client_ids" id="selected-client-ids">
                    </form>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="flex flex-wrap stats-wrapper col-span-12">
                <div class="col-span-12 md:col-span-4">
                    <div
                        class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center text-2xl">
                            <i class="las la-wallet"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold opacity-50 uppercase tracking-wider mb-1">Valorisation Totale FCP
                            </p>
                            <h3 class="h4 mb-0 text-n900">{{ number_format($globalValuationFcp, 0, ' ', ' ') }} XAF</h3>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 md:col-span-4">
                    <div
                        class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-secondary1/10 text-secondary1 flex items-center justify-center text-2xl">
                            <i class="las la-hand-holding-usd"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold opacity-50 uppercase tracking-wider mb-1">Valorisation Totale PMG
                            </p>
                            <h3 class="h4 mb-0 text-n900">{{ number_format($globalValuationPmg, 0, ' ', ' ') }} XAF</h3>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 md:col-span-4">
                    <div
                        class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-success/10 text-success flex items-center justify-center text-2xl">
                            <i class="las la-chart-bar"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold opacity-50 uppercase tracking-wider mb-1">Valorisation Totale
                                Portefeuilles</p>
                            <h3 class="h4 mb-0 text-success">{{ number_format($globalValuation, 0, ' ', ' ') }} XAF</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12">
                <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-n30 bg-n10/30 flex justify-between items-center flex-wrap gap-4">
                        <h4 class="h4 flex items-center gap-2 italic uppercase">
                            <i class="las la-list text-primary font-bold"></i> Situation Détallée des Portefeuilles
                        </h4>
                        <!-- Dynamic Search Input -->
                        <div class="relative min-w-[300px]">
                            <input type="text" id="client-search" placeholder="Rechercher un client..."
                                class="w-full bg-white dark:bg-bg4 border border-n30 rounded-full px-5 py-2 text-sm focus:outline-none focus:border-primary pr-10">
                            <i class="las la-search absolute right-4 top-1/2 -translate-y-1/2 text-n500 text-lg"></i>
                        </div>
                    </div>
                    <div class="overflow-x-auto lg:overflow-x-visible">
                        <table class="w-full table-fixed md:table-auto">
                            <thead>
                                <tr class="bg-n20 dark:bg-bg3 text-n500 uppercase text-[11px] font-bold">
                                    <th class="py-4 px-6 text-center w-[50px]">
                                        <input type="checkbox" id="select-all-clients"
                                            class="w-4 h-4 rounded border-n30 text-primary focus:ring-primary">
                                    </th>
                                    <th class="py-4 px-6 text-start w-[200px]">Client</th>
                                    <th class="py-4 px-6 text-end">Valorisation FCP (XAF)</th>
                                    <th class="py-4 px-6 text-end">Valorisation PMG (XAF)</th>
                                    <th class="py-4 px-6 text-end">Valorisation Totale (XAF)</th>
                                    <th class="py-4 px-6 text-center w-[120px]">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30" id="audit-table-body">
                                @foreach ($auditData as $data)
                                    <tr class="hover:bg-primary/5 duration-200">
                                        <td class="py-4 px-6 text-center">
                                            <input type="checkbox" name="client_checkbox" value="{{ $data->client->id }}"
                                                class="client-checkbox w-4 h-4 rounded border-n30 text-primary focus:ring-primary">
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex">
                                                <span class="text-sm font-bold text-n800">{{ $data->client->name }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-end font-medium">
                                            {{ number_format($data->valuation_fcp, 0, ' ', ' ') }}
                                        </td>
                                        <td class="py-4 px-6 text-end font-medium">
                                            {{ number_format($data->valuation_pmg, 0, ' ', ' ') }}
                                        </td>
                                        <td class="py-4 px-6 text-end font-extrabold text-primary">
                                            {{ number_format($data->total_valuation, 0, ' ', ' ') }}
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <a href="{{ route('compliance.client-history', $data->client->id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                                                class="text-primary hover:underline text-xs font-bold uppercase italic">
                                                Voir details <i class="las la-arrow-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                @if (empty($auditData))
                                    <tr>
                                        <td colspan="6" class="py-20 text-center opacity-50 italic">Aucune donnée trouvée
                                            pour cette période.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('script_front_end')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration des Datepickers
            const dpStart = datepicker('#audit_start_date', {
                formatter: (input, date, instance) => {
                    input.value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2,
                        '0') + '-' + String(date.getDate()).padStart(2, '0');
                },
                startDay: 1,
                customDays: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                customMonths: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août',
                    'Septembre', 'Octobre', 'Novembre', 'Décembre'
                ],
                overlayButton: "Valider",
                overlayPlaceholder: "Année (4 chiffres)"
            });

            const dpEnd = datepicker('#audit_end_date', {
                formatter: (input, date, instance) => {
                    input.value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2,
                        '0') + '-' + String(date.getDate()).padStart(2, '0');
                },
                startDay: 1,
                customDays: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                customMonths: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août',
                    'Septembre', 'Octobre', 'Novembre', 'Décembre'
                ],
                overlayButton: "Valider",
                overlayPlaceholder: "Année (4 chiffres)"
            });

            // Gestion de la sélection globale (Améliorée pour tenir compte de la recherche)
            const selectAll = document.getElementById('select-all-clients');
            const checkboxes = document.querySelectorAll('.client-checkbox');

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        const row = cb.closest('tr');
                        // On ne sélectionne que si la ligne est visible
                        if (row.style.display !== 'none') {
                            cb.checked = selectAll.checked;
                        } else {
                            // On décoche les lignes masquées pour éviter les exports par erreur
                            cb.checked = false;
                        }
                    });
                });
            }

            // Gestion de l'exportation
            const btnExport = document.getElementById('btn-export-selected');
            const exportForm = document.getElementById('export-form');
            const inputIds = document.getElementById('selected-client-ids');

            if (btnExport) {
                btnExport.addEventListener('click', function() {
                    const ids = Array.from(checkboxes)
                        .filter(cb => cb.checked && cb.closest('tr').style.display !== 'none')
                        .map(cb => cb.value);

                    if (ids.length === 0) {
                        alert("Veuillez sélectionner au moins un client visible à exporter.");
                        return;
                    }

                    inputIds.value = ids.join(',');
                    exportForm.submit();
                });
            }

            // RECHERCHE DYNAMIQUE
            const searchInput = document.getElementById('client-search');
            const tableBody = document.getElementById('audit-table-body');
            const rows = tableBody.getElementsByTagName('tr');

            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toUpperCase();
                    let visibleCount = 0;

                    for (let i = 0; i < rows.length; i++) {
                        const clientCell = rows[i].getElementsByTagName('td')[1]; // Colonne Nom Client
                        if (clientCell) {
                            const textValue = clientCell.textContent || clientCell.innerText;
                            if (textValue.toUpperCase().indexOf(filter) > -1) {
                                rows[i].style.display = "";
                                visibleCount++;
                            } else {
                                rows[i].style.display = "none";
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection
