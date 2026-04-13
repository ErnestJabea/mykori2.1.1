@extends('front-end/app/app-home-asset', ['title' => 'Compliance - Historique des Envois'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-8 min-h-screen">

                <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                    <div
                        class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                        <div>
                            <h3 class="h3 uppercase">HISTORIQUE DES ENVOIS DE RELEVÉS</h3>
                            <p class="text-sm opacity-70 text-primary font-medium">Supervision des envois de relevés clients
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Shared Table Component -->
                @include('front-end.partials.statements-history-table')

            </div>
        </div>
    </main>
@endsection
