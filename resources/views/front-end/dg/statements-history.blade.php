@extends('front-end/app/app-home-asset', ['title' => 'Direction Générale - Historique des Envois'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-8 min-h-screen">

                <!-- Header Section Stratégique -->
                <div class="flex justify-between items-center sm:flex-row flex-col gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-primary text-3xl border border-n30">
                            <i class="las la-mail-bulk text-secondary"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-n900 mb-1 italic uppercase tracking-tighter">Pilotage
                                Direction Générale</h2>
                            <p class="text-n500 text-sm italic font-medium">Suivi et audit des envois de relevés mensuels
                                clients.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <a href="{{ route('dg.dashboard') }}"
                            class="px-6 py-2.5 bg-n900 text-white text-xs font-bold rounded-2xl hover:bg-n800 transition-all shadow-sm italic uppercase tracking-widest"><i
                                class="las la-arrow-left"></i> Dashboard HG</a>
                    </div>
                </div>

                <!-- Shared Table Component -->
                @include('front-end.partials.statements-history-table')



            </div>
        </div>
    </main>
@endsection
