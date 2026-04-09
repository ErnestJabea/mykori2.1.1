@extends('front-end/app/app-home-asset', ['title' => $customerToEdit ? 'Modifier le client : ' . $customerToEdit->name : 'Créer un nouveau client', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Formulaire -->
            <div class="col-span-12">
                <div class="box bg-n0 dark:bg-bg4 p-6 rounded-2xl shadow-sm border border-n30 dark:border-n500">
                    <div class="bb-dashed mb-6 flex items-center justify-between pb-4 border-b border-dashed border-n40">
                        <h3 class="h3 flex items-center gap-3">
                            <i class="las {{ $customerToEdit ? 'la-user-edit' : 'la-user-plus' }} text-primary"></i> 
                            {{ $customerToEdit ? 'MODIFIER LES INFORMATIONS DU CLIENT' : 'CRÉER UN NOUVEAU CLIENT' }}
                        </h3>
                        <div class="flex gap-3">
                            @if($customerToEdit)
                                <a href="{{ route('asset-manager.create-customer') }}"
                                    class="btn border border-secondary2 text-secondary2 px-4 py-2 rounded-xl hover:bg-secondary2 hover:text-white duration-300">
                                    <i class="las la-plus"></i> Nouveau Client
                                </a>
                            @endif
                            <a href="{{ route('customer') }}"
                                class="btn border border-primary text-primary px-4 py-2 rounded-xl hover:bg-primary hover:text-white duration-300">
                                <i class="las la-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-secondary2/10 text-secondary2 rounded-xl border border-secondary2/20">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mb-6 p-4 bg-primary/10 text-primary rounded-xl border border-primary/20">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ $portfolioToEdit ? route('asset-manager.update-customer', $portfolioToEdit->id) : route('asset-manager.store-customer') }}" 
                        method="POST"
                        class="grid grid-cols-12 gap-6" id="create-customer-form">
                        @csrf

                        <div class="col-span-12 lg:col-span-4">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Type de Dossier</label>
                            <div class="relative">
                                <i class="las la-folder-open absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <select name="type" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300">
                                    <option value="PMG" {{ (old('type', $portfolioToEdit->type ?? '') == 'PMG') ? 'selected' : '' }}>Portefeuille PMG</option>
                                    <option value="FCP" {{ (old('type', $portfolioToEdit->type ?? '') == 'FCP') ? 'selected' : '' }}>Portefeuille FCP</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Référence Dossier</label>
                            <div class="relative">
                                <i class="las la-hashtag absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="text" name="reference" value="{{ old('reference', $portfolioToEdit->reference ?? '') }}" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300 {{ !$portfolioToEdit ? 'pointer-events-none opacity-50' : '' }}"
                                    placeholder="Auto-générée"
                                    {{ !$portfolioToEdit ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Civilité (Genre)</label>
                            <div class="relative">
                                <i
                                    class="las la-id-card-alt absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <select name="genre" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300">
                                    <option value="0" {{ (old('genre', $customerToEdit->genre ?? '') == 0) ? 'selected' : '' }}>Monsieur</option>
                                    <option value="1" {{ (old('genre', $customerToEdit->genre ?? '') == 1) ? 'selected' : '' }}>Madame</option>
                                    <option value="2" {{ (old('genre', $customerToEdit->genre ?? '') == 2) ? 'selected' : '' }}>Entreprise</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-12">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Nom complet</label>
                            <div class="relative">
                                <i class="las la-user absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="text" name="name" value="{{ old('name', $customerToEdit->name ?? '') }}" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="Ex: Jean Paul">
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-12">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Adresse Email</label>
                            @if(!$customerToEdit)
                                <p class="text-[10px] text-secondary1 mb-1 italic"><i class="las la-info-circle"></i> Si l'email existe déjà, un nouveau dossier lui sera simplement rattaché.</p>
                            @endif
                            <div class="relative">
                                <i
                                    class="las la-envelope absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="email" name="email" value="{{ old('email', $customerToEdit->email ?? '') }}" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="exemple@kori.cm">
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-6">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Localisation</label>
                            <div class="relative">
                                <i
                                    class="las la-map-marker absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="text" name="localisation" value="{{ old('localisation', $customerToEdit->localisation ?? '') }}" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="Ex: Douala, Akwa">
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-6">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Boite Postale (BP)</label>
                            <div class="relative">
                                <i
                                    class="las la-mail-bulk absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="text" name="bp" value="{{ old('bp', $customerToEdit->bp ?? '') }}"
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="Ex: BP 1234">
                            </div>
                        </div>

                        <div class="col-span-12 mt-4">
                            <button type="submit"
                                class="btn bg-primary text-white w-full lg:w-auto px-10 py-4 rounded-xl font-bold shadow-lg hover:opacity-90 duration-300 flex items-center justify-center gap-3 uppercase">
                                <i class="las la-save text-xl"></i> {{ $customerToEdit ? 'Mettre à jour le client' : 'Créer le dossier client' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des dossiers -->
            <div class="col-span-12 mt-6">
                <div class="box bg-n0 dark:bg-bg4 p-6 rounded-2xl shadow-sm border border-n30 dark:border-n500">
                    <div class="bb-dashed mb-6 flex items-center justify-between pb-4 border-b border-dashed border-n40">
                        <h3 class="h3 flex items-center gap-3">
                            <i class="las la-users text-primary"></i> LISTE DES DOSSIERS
                        </h3>
                        <div class="flex items-center gap-3">
                            <div class="relative group">
                                <i class="las la-search absolute left-4 top-1/2 -translate-y-1/2 text-lg text-primary/40 group-focus-within:text-primary transition-colors"></i>
                                <input type="text" id="portfolio-search" name="search" value="{{ $search }}" 
                                    placeholder="Rechercher un client ou une référence..."
                                    class="bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-2xl py-2.5 pl-12 pr-10 text-sm focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/5 shadow-sm hover:border-primary/50 transition-all w-[280px] md:w-[350px]">
                                
                                <div id="search-spinner" class="absolute right-4 top-1/2 -translate-y-1/2 hidden">
                                    <div class="animate-spin h-4 w-4 border-2 border-primary border-t-transparent rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="portfolios-table-container">
                        @include('front-end.partials.portfolios-table')
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let searchTimer;
            const searchInput = $('#portfolio-search');
            const spinner = $('#search-spinner');
            const tableContainer = $('#portfolios-table-container');

            function performSearch() {
                const search = searchInput.val();
                spinner.removeClass('hidden');

                $.ajax({
                    url: "{{ route('asset-manager.create-customer') }}",
                    method: 'GET',
                    data: { search: search },
                    success: function(response) {
                        tableContainer.html(response);
                        spinner.addClass('hidden');
                        
                        // Réattacher les événements de pagination AJAX si nécessaire
                        bindPagination();
                    },
                    error: function() {
                        spinner.addClass('hidden');
                    }
                });
            }

            function bindPagination() {
                $('.portfolios-pagination a').on('click', function(e) {
                    e.preventDefault();
                    const url = $(this).attr('href');
                    spinner.removeClass('hidden');

                    $.ajax({
                        url: url,
                        success: function(response) {
                            tableContainer.html(response);
                            spinner.addClass('hidden');
                            bindPagination();
                            $('html, body').animate({
                                scrollTop: tableContainer.offset().top - 100
                            }, 500);
                        }
                    });
                });
            }

            searchInput.on('keyup', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(performSearch, 500);
            });

            bindPagination();
        });
    </script>
@endsection
