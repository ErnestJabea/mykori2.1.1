@extends('front-end/app/app-home-asset', ['title' => 'Relevés Clients | KORI', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@php
    // dd($customers);
@endphp
@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <h3>RELEVÉS MENSUELS DES CLIENTS</h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4 text-right">
                <p>{{ date('d-m-Y') }}</p>
            </div>
        </div>

        <div class="content-separator" style="height:30px"></div>

        <div class="box col-span-12 shadow-sm border border-n30">
            <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                <h4 class="h4 flex items-center gap-2">
                    <i class="las la-file-alt text-primary"></i> Consultation des Relevés
                </h4>

                <div class="flex items-center gap-4">
                    <form action="{{ route('customer.statements') }}" method="GET" class="relative">
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Chercher un client..."
                            class="w-64 rounded-full border border-n30 bg-secondary1/5 px-6 py-2 dark:border-n500 dark:bg-bg3 focus:border-primary focus:ring-1 focus:ring-primary outline-none text-sm">
                        <button type="submit"
                            class="absolute right-1 top-1/2 -translate-y-1/2 bg-primary text-white p-1 rounded-full w-8 h-8 flex items-center justify-center hover:bg-primary/90 transition-all">
                            <i class="las la-search text-base"></i>
                        </button>
                    </form>
                    @if (!empty($search))
                        <a href="{{ route('customer.statements') }}"
                            class="text-xs text-primary underline">Réinitialiser</a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto pb-4">
                <table class="w-full min-w-[1000px]">
                    <thead>
                        <tr class="bg-secondary1/5 dark:bg-bg4">
                            <th class="px-6 py-5 text-left font-semibold opacity-70">Client</th>
                            <th class="px-6 py-5 text-right font-semibold opacity-70">Total Investi (XAF)</th>
                            <th class="px-6 py-5 text-right font-semibold opacity-70">Total Portefeuille (XAF)</th>
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

            <div class="mt-6">
                {{ $customers->appends(['search' => $search])->links() }}
            </div>
        </div>

        <!-- MODAL HISTORIQUE -->
        <div id="historyModal"
            class="ac-modal-overlay fixed inset-0 z-[100] modalhide bg-black/60 flex items-center justify-center p-4 duration-500 overflow-y-auto">
            <div
                class="modal-inner bg-white dark:bg-bg3 w-full max-w-2xl rounded-2xl shadow-2xl p-6 md:p-8 relative transform duration-300">
                <div class="flex items-center justify-between mb-6 header-modal-statement">
                    <h3 id="modalTitle" class="uppercase border-b border-dashed pb-4">RELEVÉS DE [NOM]</h3>
                    <button onclick="closeHistoryModal()"
                        class="absolute top-4 right-4 text-3xl hover:text-red-500 duration-300 close-times">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div id="modalContent" class="max-h-[65vh] overflow-y-auto pr-2">
                    {{-- Contenu chargé par JS --}}
                    <div class="py-10 text-center">
                        <div
                            class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary border-r-transparent">
                        </div>
                        <p class="mt-4">Chargement des relevés...</p>
                    </div>
                </div>
                <div id="modalPagination" class="mt-6 pt-4 border-t border-dashed flex justify-center gap-2"></div>
            </div>
        </div>

    </main>
@endsection

@section('script_front_end')
    <script>
        let allStatementData = [];
        let itemsPerPage = 5;

        function openHistoryModal(userId, userName) {
            const modal = document.getElementById('historyModal');
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');
            const pagination = document.getElementById('modalPagination');

            title.innerText = "HISTORIQUE DES RELEVÉS DU CLIENT : " + userName.toUpperCase();
            modal.classList.remove('modalhide');
            modal.classList.add('modalshow');

            content.innerHTML = `
            <div class="py-10 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary border-r-transparent"></div>
                <p class="mt-4 font-medium">Récupération des données...</p>
            </div>
        `;
            pagination.innerHTML = "";

            fetch(`/api/customer-available-months/${userId}`)
                .then(response => response.json())
                .then(data => {
                    allStatementData = data;
                    if (data.length === 0) {
                        content.innerHTML = `
                        <div class="py-12 text-center opacity-60">
                            <i class="las la-folder-open text-6xl mb-4 text-primary/40 block"></i>
                            <p class="text-lg font-medium">Aucun relevé disponible pour ce client.</p>
                            <p class="text-sm">Les relevés apparaissent une fois les transactions validées et apres le premier mois.</p>
                        </div>`;
                        return;
                    }

                    renderStatementPage(1, userId);
                })
                .catch(err => {
                    content.innerHTML =
                        `<div class="py-10 text-center text-red-500 font-medium"><i class="las la-exclamation-triangle text-3xl mb-2"></i><p>Une erreur est survenue lors du chargement.</p></div>`;
                });
        }

        function renderStatementPage(page, userId) {
            const content = document.getElementById('modalContent');
            const pagination = document.getElementById('modalPagination');

            const totalPages = Math.ceil(allStatementData.length / itemsPerPage);
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageData = allStatementData.slice(start, end);

            let html = `
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-primary/5 dark:bg-bg4 border-b border-primary/10">
                            <th class="px-6 py-4 text-start text-xs font-bold uppercase tracking-wider">Mois / Période</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider">Téléchargements</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-n30 dark:divide-bg4">
        `;

            pageData.forEach(m => {
                html += `
                <tr class="hover:bg-secondary1/5 duration-200 transition-all">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                <i class="las la-calendar-check text-xl"></i>
                            </div>
                            <span class="font-bold text-n700 dark:text-n0 text-sm uppercase">${m.label}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center gap-3">
            `;

                if (m.has_pmg) {
                    html += `
                    <a href="/customer-statement/monthly/${m.year}/${m.month}/pmg/${userId}" 
                       class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white hover:bg-primary/90 hover:scale-105 active:scale-95 shadow-sm transition-all duration-300">
                        <i class="las la-download text-lg"></i> PMG
                    </a>
                `;
                }

                if (m.has_fcp) {
                    html += `
                    <a href="/customer-statement/monthly/${m.year}/${m.month}/fcp/${userId}" 
                       class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold text-white hover:opacity-90 hover:scale-105 active:scale-95 shadow-sm transition-all duration-300" style="background-color: #ebb009">
                        <i class="las la-download text-lg"></i> FCP
                    </a>
                `;
                }

                html += `
                        </div>
                    </td>
                </tr>
            `;
            });

            html += `</tbody></table></div>`;
            content.innerHTML = html;

            // Render Pagination Controls
            if (totalPages > 1) {
                let pagHtml = "";
                for (let i = 1; i <= totalPages; i++) {
                    const isActive = i === page;
                    pagHtml += `
                    <button onclick="renderStatementPage(${i}, '${userId}')" 
                            class="w-10 h-10 rounded-full border transition-all duration-300 font-bold flex items-center justify-center text-sm
                            ${isActive ? 'bg-primary text-white border-primary shadow-md' : 'bg-transparent text-n500 border-n30 hover:border-primary hover:text-primary'}">
                        ${i}
                    </button>
                `;
                }
                pagination.innerHTML = pagHtml;
            } else {
                pagination.innerHTML = "";
            }
        }

        function closeHistoryModal() {
            const modal = document.getElementById('historyModal');
            modal.classList.add('modalhide');
            modal.classList.remove('modalshow');
        }

        // Fermer si clic à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('historyModal');
            if (event.target == modal) {
                closeHistoryModal();
            }
        }
    </script>
@endsection
