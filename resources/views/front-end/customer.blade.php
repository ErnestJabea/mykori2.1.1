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

    <div class="box col-span-12">
        <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
            <h4 class="h4">Récapitulatif des Portefeuilles</h4>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px]">
                <thead>
                    <tr class="bg-secondary1/5 dark:bg-bg4">
                        <th class="px-6 py-4 text-left">Noms & Prénoms</th>
                        <th class="px-6 py-4 text-left">Total Investissement</th>
                        <th class="px-6 py-4 text-left">Total Intérêts</th>
                        <th class="px-6 py-4 text-left">Portefeuille Global</th>
                        {{-- <th class="px-6 py-4 text-center">Produits</th> --}}
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                 <small>Tous les montants ci-dessous sont en XAF</small>
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

                        <td class="px-6 py-4">
                            <p class="font-medium ">
                                {{ number_format($client->total_capital, 0, ' ', ' ') }} <span class="text-[10px]"></span>
                            </p>
                        </td>

                        <td class="px-6 py-4">
                            <p class="font-medium text-primary" style="color: green">
                                + {{ number_format($client->total_interets, 0, ' ', ' ') }} <span class="text-[10px]"></span>
                            </p>
                        </td>

                        <td class="px-6 py-4">
                            <div class="rounded bg-primary/10 px-3 py-1 inline-block">
                                <p class="font-bold text-primary" >
                                    {{ number_format($client->portefeuille_total, 0, ' ', ' ') }} <span class="text-[10px]"></span>
                                </p>
                            </div>
                        </td>

                        {{-- <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-secondary1/10 px-2 py-1 text-xs">
                                {{ $client->product_count }} contrat(s)
                            </span>
                        </td> --}}

                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('customer-detail', ['customer' => $client->id]) }}" 
                               class="btn-outline border-primary text-primary hover:bg-primary hover:text-white px-3 py-1 rounded-md text-sm duration-300">
                                <i class="las la-eye"></i> Détails
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection