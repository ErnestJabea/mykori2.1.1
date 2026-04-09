@extends('front-end/app/app-home-asset', ['title' => 'Liste des Clients | KORI', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@php
    // dd($customers);
@endphp
@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <h3>GESTION DES CLIENTS</h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4">
                <div class="row flex items-center justify-end gap-3">
                    <p style="text-align: right">{{ date('d-m-Y') }}</p>
                    <div class="content-right">
                        <button class="btn ac-modal-btn buy">
                            <a href="{{ route('releve-client') }}">Validation des relevés</a>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-separator" style="height:30px"></div>


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

        // Handle global click for sort and pagination
        $(document).on('click', '.ajax-sort, .ajax-pagination a, .ajax-tab', function(e) {
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
            
            let url = `{{ route('customer') }}?search=${search}&category=${category}&sort_by=${sort_by}&order=${order}`;
            
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
