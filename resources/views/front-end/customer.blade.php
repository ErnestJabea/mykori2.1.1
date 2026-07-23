@extends('front-end/app/app-home-asset', ['title' => 'Liste des Clients | KORI', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@php
    // dd($customers);
@endphp
@section('content')
    <main class="main-content has-sidebar">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-bg3 p-4 sm:p-6 rounded-2xl shadow-sm border border-n30 dark:border-n700 mb-6">
            <div>
                <h3 class="text-xl sm:text-2xl font-extrabold tracking-tight text-n900 dark:text-white uppercase">GESTION DES CLIENTS</h3>
                <p class="text-xs sm:text-sm text-n500 dark:text-slate-300 mt-1">Supervision et suivi des portefeuilles investisseurs</p>
            </div>
            <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto justify-between sm:justify-end border-t sm:border-t-0 pt-3 sm:pt-0 border-n30 dark:border-n700">
                <div class="text-left sm:text-right">
                    <p class="font-bold text-xs sm:text-sm text-n700 dark:text-white">{{ date('d-m-Y') }}</p>
                    <span class="text-[10px] sm:text-xs text-n500 dark:text-slate-400">Date du jour</span>
                </div>
                <a href="{{ route('releve-client') }}"
                    class="btn bg-primary text-white rounded-xl px-4 py-2.5 hover:bg-primary/90 duration-300 flex items-center gap-2 text-xs sm:text-sm font-bold shadow-sm whitespace-nowrap">
                    <i class="las la-check-circle text-base"></i> Validation des relevés
                </a>
            </div>
        </div>


        <div id="ajax-container">
            @include('front-end.partials.customer-table')
        </div>

        <!-- Export Modal -->
        <div id="export-modal" class="p-4">
            <div class="bg-white dark:bg-bg3 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden border border-n30">
                <div class="p-6 border-b border-n30 flex justify-between items-center">
                    <h4 class="font-bold">Configuration de l'exportation</h4>
                    <button type="button" class="close-export-modal text-xl"><i class="las la-times"></i></button>
                </div>
                
                <form id="export-form" action="{{ route('customer.export') }}" method="GET" target="_blank">
                    <!-- Global Filters (Preserved) -->
                    <input type="hidden" name="search" id="modal-search">
                    <input type="hidden" name="category" id="modal-category">
                    <input type="hidden" name="sort_by" id="modal-sort-by">
                    <input type="hidden" name="order" id="modal-order">
                    <input type="hidden" name="filter" id="modal-filter">

                    <div class="p-6 space-y-6">
                        <!-- Status Selection -->
                        <div>
                            <p class="font-bold mb-3 text-xs uppercase opacity-50">Statut des clients</p>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="export_status" value="active" checked class="accent-primary">
                                    <span>Clients Actifs</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="export_status" value="inactive" class="accent-primary">
                                    <span>Clients Inactifs</span>
                                </label>
                            </div>
                        </div>

                        <!-- Fields Selection -->
                        <div id="active-fields-container">
                            <p class="font-bold mb-3 text-xs uppercase opacity-50">Champs à exporter</p>
                            <div class="grid grid-cols-2 gap-3 pb-4">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="name" checked checked class="accent-primary"> Nom & Prénom
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="email" checked class="accent-primary"> Email
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="first_placement" checked class="accent-primary"> 1er placement
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="placements_count" checked class="accent-primary"> Nb placements (Actifs/Inactifs)
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="total_invested" checked class="accent-primary"> Total investi (Actifs/Inactifs)
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="client_type" checked class="accent-primary"> Type de client (FCP/PMG)
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="portfolio_valo" checked class="accent-primary"> Valorisation globale
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="fields[]" value="total_gains" checked class="accent-primary"> Total intérêts
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-n30/30 flex justify-end gap-3">
                        <button type="button" class="close-export-modal px-4 py-2 text-sm font-bold opacity-60">Annuler</button>
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-primary/90 transition-all">
                            Générer l'exportation
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        function fetchCustomers(url) {
            $('#ajax-container').css('opacity', '0.5');
            $.ajax({
                url: url,
                success: function(data) {
                    $('#ajax-container').html(data);
                    $('#ajax-container').css('opacity', '1');
                    window.history.pushState({}, '', url);
                },
                error: function() {
                    alert('Erreur lors du chargement des données.');
                    $('#ajax-container').css('opacity', '1');
                }
            });
        }

        // Modal Management
        $(document).on('click', '#open-export-modal', function() {
            let params = new URLSearchParams(window.location.search);
            $('#modal-search').val(params.get('search') || '');
            $('#modal-category').val(params.get('category') || 'all');
            $('#modal-sort-by').val(params.get('sort_by') || 'name');
            $('#modal-order').val(params.get('order') || 'asc');
            $('#modal-filter').val(params.get('filter') || '');
            
            $('#export-modal').css('display', 'flex');
        });

        $(document).on('click', '.close-export-modal', function() {
            $('#export-modal').hide();
        });

        // Hide modal on submit
        $(document).on('submit', '#export-form', function() {
            $('#export-modal').hide();
        });

        // Toggle fields based on status
        $(document).on('change', 'input[name="export_status"]', function() {
            if ($(this).val() == 'inactive') {
                // ...
            }
        });

        // Close modal on escape
        $(window).on('keydown', function(e) {
            if (e.key === "Escape") $('#export-modal').hide();
        });

        // Handle global click for sort, pagination, tabs and dashboard cards
        $(document).on('click', '.ajax-sort, .ajax-pagination a, .ajax-tab, .ajax-card', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            fetchCustomers(url);
        });

        // Search with debounce
        let timer;
        $(document).on('keyup', '#ajax-search', function() {
            clearTimeout(timer);
            let search = $(this).val();
            let category = new URLSearchParams(window.location.search).get('category') || 'all';
            let sort_by = new URLSearchParams(window.location.search).get('sort_by') || 'name';
            let order = new URLSearchParams(window.location.search).get('order') || 'asc';
            let filter = new URLSearchParams(window.location.search).get('filter') || '';
            let status = new URLSearchParams(window.location.search).get('status') || 'all';
            
            let url = `{{ route('customer') }}?search=${search}&category=${category}&sort_by=${sort_by}&order=${order}&filter=${filter}&status=${status}`;
            
            timer = setTimeout(function() {
                fetchCustomers(url);
            }, 500);
        });

        $(document).on('submit', '#search-form', function(e) {
            e.preventDefault();
        });
        
        $(document).on('click', '#reset-search', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            fetchCustomers(url);
        });
    });
    </script>
@endsection
