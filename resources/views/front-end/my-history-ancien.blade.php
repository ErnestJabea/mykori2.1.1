@extends('front-end/app/app-home', ['title' => 'Mon historique', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Latest Transactions -->
            <div class="col-span-12">
                <div class="box col-span-12 lg:col-span-6">
                    <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                        <h4 class="h4">Historique de transaction</h4>
                        <div class="flex items-center gap-4">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap select-all-table" id="transactionTable">
                            <thead>
                                <tr class="bg-secondary1/5 dark:bg-bg3">
                                    <!-- Entêtes de colonnes -->
                                </tr>
                            </thead>
                            <tbody id="transactionContainer">
                                <!-- Contenu des transactions -->
                                @include('partials.transactions_partial', [
                                    'transactions' => $transactions,
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).on('click', '#transactionContainer .pagination a', function(e) {
            e.preventDefault();

            var url = $(this).attr('href');
            $.ajax({
                url: url,
                success: function(data) {
                    $('#transactionContainer').html(data);
                }
            });
        });
    </script>
@endpush
