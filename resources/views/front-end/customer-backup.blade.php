@extends('front-end/app/app-home-asset', ['Clients ', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])


@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
                <h3>CLIENTS</h3>
            </div>
            <div class="col-span-12 md:col-span-5 lg:col-span-4">
                <p style="text-align: right">{{ date('d-m-Y') }}</p>
            </div>

        </div>
        <div class="content-separator" style="height:30px">

        </div>
        <!-- Transaction account -->
        <div class="box col-span-12 lg:col-span-6">
            <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                <h4 class="h4">Clients et Portefeuille</h4>

            </div>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <thead>
                        <tr class="bg-secondary1/5 dark:bg-bg3">
                            <th class="min-w-[280px] cursor-pointer px-6 py-5 text-start">
                                <div class="flex items-center gap-1">Noms & prénoms</div>
                            </th>
                            <th class="w-[15%] cursor-pointer px-6 py-5 text-start">
                                <div class="flex items-center gap-1">Contact</div>
                            </th>
                            <th class="w-[15%] cursor-pointer px-6 py-5 text-start">
                                <div class="flex items-center gap-1">Portefeuille</div>
                            </th>
                            <th class="w-[15%] cursor-pointer px-6 py-5 text-start">
                                <div class="flex items-center gap-1">Nb produit</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Transactions Data -->
                        @foreach ($customers as $client)
                            <tr class="line-customer" class="even:bg-secondary1/5 dark:even:bg-bg3">
                                <td class="px-6 py-2">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <p class="mb-1 font-medium">{{ $client->name }}</p>
                                            <a href="{{ route('customer-detail', ['customer' => $client->id]) }}"><span
                                                    class="text-xs">En savoir +</span></a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-2">
                                    <div>
                                        <p class="font-medium">{{ $client->email }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-2">
                                    <div>
                                        <p class="font-medium">XAF
                                            {{ number_format($client->portefeuille_total, 0, ' ', ' ') }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-2" align="center">
                                    <div>
                                        <p class="font-medium">{{ $client->product_count }}</p>
                                        <a href="{{ route('customer-detail', ['customer' => $client->id]) }}"><span
                                                class="text-xs">En savoir +</span></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>
@endsection
