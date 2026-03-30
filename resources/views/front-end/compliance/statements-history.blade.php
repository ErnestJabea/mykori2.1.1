@extends('front-end/app/app-home-asset', ['title' => 'Compliance - Historique des Envois'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-8 min-h-screen">

                <!-- Header Section Compliance -->
                <div class="flex justify-between items-center sm:flex-row flex-col gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-primary text-3xl border border-n30">
                            <i class="las la-mail-bulk text-secondary"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-n900 mb-1 italic uppercase tracking-tighter">Pilotage Compliance & Audit</h2>
                            <p class="text-n500 text-sm italic font-medium">Audit exhaustif et traçabilité des communications opérationnelles.</p>
                        </div>
                    </div>
                </div>

                <!-- Shared Table Component -->
                @include('partials.statements-history-table')

                <!-- Compliance Note -->
                <div class="p-8 bg-success/5 rounded-3xl border border-success/10 border-dashed">
                    <h5 class="flex items-center gap-3 text-n800 font-bold mb-4 uppercase text-xs italic">
                        <i class="las la-shield-alt text-success text-xl"></i> Note Audit & Compliance
                    </h5>
                    <p class="text-[11px] text-n600 italic leading-relaxed">
                        L'audit trail des communications clients est un prérequis réglementaire. Ce tableau fournit la preuve de l'envoi des documents périodiques, avec le détail complet des succès et des erreurs potentielles rencontrées par le moteur de mailing. 
                    </p>
                </div>

            </div>
        </div>
    </main>
@endsection
