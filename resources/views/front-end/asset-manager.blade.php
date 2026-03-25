@extends('front-end/app/app-home-asset', ['Dashboard ', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                <div
                    class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div>
                        <h3 class="h3">TABLEAU DE BORD</h3>
                        <p class="text-sm opacity-70">Bienvenue sur votre espace Asset Manager</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden md:block text-right mr-4 border-r border-n30 pr-4">
                            <p class="font-medium">{{ date('d-m-Y') }}</p>
                            <span class="text-xs opacity-50">Dernière mise à jour</span>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('releve-client') }}"
                                class="btn bg-primary text-white rounded-lg px-4 py-2 hover:bg-primary/90 duration-300 flex items-center gap-2 text-sm shadow-sm">
                                <i class="las la-file-alt"></i> Générer Relevés
                            </a>
                            <a href="{{ route('customer') }}"
                                class="btn bg-secondary1 text-white rounded-lg px-4 py-2 hover:bg-secondary1/90 duration-300 flex items-center gap-2 text-sm shadow-sm">
                                <i class="las la-user-cog"></i> Gérer Clients
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards Overview -->
            <div class="col-span-12 lg:col-span-6">
                <div
                    class="box bg-primary p-6 rounded-2xl text-white relative overflow-hidden h-full min-h-[160px] flex flex-col justify-center">
                    <div class="relative z-10">
                        <p class="text-sm opacity-80 mb-1 font-medium text-white/90">Encours Global Sous Gestion (AUM)</p>
                        <h2 class="h2 mb-0 text-white">{{ number_format($globalAum, 0, ' ', ' ') }} <span
                                class="text-sm">XAF</span></h2>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <a href="{{ route('customer') }}"
                                class="text-xs flex items-center gap-2 hover:gap-3 duration-300 text-white/80">
                                Détails par client <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <i class="las la-wallet -bottom-6 -right-6 text-[140px] opacity-10 rotate-12"></i>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6">
                <div
                    class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 flex flex-col justify-between h-full min-h-[160px]">
                    <div>
                        <p class="text-sm opacity-70 mb-1 font-medium">Portefeuille Clients</p>
                        <div class="flex items-end gap-4">
                            <h2 class="h2 text-secondary1 mb-0">{{ $activeClientsCount }}</h2>
                            <p class="text-sm mb-1 opacity-70">Clients Actifs</p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-n30 pt-4">
                        <span class="text-xs px-2 py-1 bg-secondary1/10 text-secondary1 rounded-full font-medium">Total:
                            {{ $customers->count() }} clients enregistrés</span>
                        <i class="las la-users text-4xl text-secondary1/20"></i>
                    </div>
                </div>
            </div>

            <!-- Graph Section -->
            <div class="col-span-12">
                <div class="grid grid-cols-12 gap-4 xxl:gap-6">
                    <!-- VL Evolution Chart -->
                    <div class="col-span-12 lg:col-span-8">
                        <div class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm h-full">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bb-dashed pb-4">
                                <h4 class="h4 flex items-center gap-2">
                                    <i class="las la-chart-line text-primary highlight-text"></i> Évolution des Valeurs
                                    Liquidatives (FCP)
                                </h4>
                                <div class="flex gap-2">
                                    @foreach ($fcpProducts as $product)
                                        <span
                                            class="text-[10px] px-2 py-1 rounded-full border border-n30 bg-primary/5 text-primary">{{ $product->title }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div id="fcp-vls-chart" class="w-full h-80"></div>
                        </div>
                    </div>

                    <!-- Portfolio Distribution Chart -->
                    <div class="col-span-12 lg:col-span-4">
                        <div class="box bg-white dark:bg-bg3 p-6 rounded-2xl border border-n30 shadow-sm h-full">
                            <div class="mb-6 bb-dashed pb-4">
                                <h4 class="h4 flex items-center gap-2">
                                    <i class="las la-chart-pie text-secondary1"></i> Répartition du Portefeuille
                                </h4>
                            </div>
                            <div id="portfolio-distribution-chart" class="w-full h-80 flex items-center justify-center">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-span-12 detail-produit-wrapper">
                <div class="grid grid-cols-1 gap-4 xxl:gap-6">
                    <!-- Payment account -->
                    <div class="box col-span-12 lg:col-span-6">
                        <div class="flex justify-between items-center gap-4 flex-wrap bb-dashed mb-4 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">Payment Account</h4>
                            <div class="flex items-center gap-4 flex-wrap grow sm:justify-end">
                                <button class="btn shrink-0 add-account-btn">
                                    Add Account
                                </button>
                                <form
                                    class="bg-primary/5 datatable-search dark:bg-bg3 border border-n30 dark:border-n500 flex gap-3 rounded-[30px] focus-within:border-primary p-1 items-center justify-between min-w-[200px] xxl:max-w-[319px] w-full">
                                    <input type="text" placeholder="Search"
                                        class="bg-transparent text-sm ltr:pl-4 rtl:pr-4 py-1 w-full border-none"
                                        id="payment-account-search" />
                                    <button
                                        class="bg-primary shrink-0 rounded-full w-7 h-7 lg:w-8 lg:h-8 flex justify-center items-center text-n0">
                                        <i class="las la-search text-lg"></i>
                                    </button>
                                </form>
                                <div class="flex items-center gap-3 whitespace-nowrap">
                                    <span>Sort By : </span>
                                    <select name="sort" class="nc-select green">
                                        <option value="day">Last 15 Days</option>
                                        <option value="week">Last 1 Month</option>
                                        <option value="year">Last 6 Month</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto pb-4 lg:pb-6">
                            <table class="w-full whitespace-nowrap" id="payment-account">
                                <thead>
                                    <tr class="bg-secondary1/5 dark:bg-bg3">
                                        <th class="text-start !py-5 px-6 min-w-[230px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Account Number
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[130px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Currency
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[200px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Bank Name
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[160px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Account Balance
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[140px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Expiry Date
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[130px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Status
                                            </div>
                                        </th>
                                        <th class="text-center !py-5" data-sortable="false">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/usa-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">999 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">USD</p>
                                                <span class="text-xs">US Dollar</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">Bank Of America</p>
                                                <span class="text-xs">US</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.121,212
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2027</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/cn-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">999 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">RUB</p>
                                                <span class="text-xs">Russian Rubble</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">VTB Bank</p>
                                                <span class="text-xs">RS</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.121,212
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2027</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/usa-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">999 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">USD</p>
                                                <span class="text-xs">US Dollar</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">Bank Of America</p>
                                                <span class="text-xs">US</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.121,212
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2027</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/usa-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">999 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">USD</p>
                                                <span class="text-xs">US Dollar</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">Bank Of America</p>
                                                <span class="text-xs">US</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.121,212
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2027</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/euro-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">919 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">EUR</p>
                                                <span class="text-xs">EURo</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">UniCredit</p>
                                                <span class="text-xs">Italy</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.821,222
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2028</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/usa-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">999 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">USD</p>
                                                <span class="text-xs">US Dollar</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">Bank Of America</p>
                                                <span class="text-xs">US</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.121,212
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2027</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/jp-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">999 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">JPY</p>
                                                <span class="text-xs">Japanese Yen</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">Shinsei Bank</p>
                                                <span class="text-xs">Japan</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.121,212
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2027</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/uk-sm.png" width="32" height="32"
                                                    class="rounded-full" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">988 *** *** 123</p>
                                                    <span class="text-xs">Account Number</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">GBP</p>
                                                <span class="text-xs">British Pound</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">Barclys Bank</p>
                                                <span class="text-xs">UK</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">
                                                    $1.121,212
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>11/05/2027</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex col-span-12 gap-4 sm:justify-between justify-center items-center flex-wrap">
                            <p>
                                Showing 1 to 8 of 18 entries
                            </p>

                            <ul class="flex gap-2 md:gap-3 flex-wrap md:font-semibold items-center">
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary rtl:rotate-180 hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        <i class="las la-angle-left text-lg"></i>
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-n0 bg-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        1
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        2
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        3
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 rtl:rotate-180 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        <i class="las la-angle-right text-lg"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Total Deposits -->
                    <div class="box col-span-12 lg:col-span-6">
                        <div
                            class="flex flex-wrap gap-4  justify-between items-center bb-dashed mb-4 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">Total Deposits</h4>
                            <div class="flex grow sm:justify-end items-center flex-wrap gap-4">
                                <button class="btn shrink-0 total-deposit-btn">
                                    Add Deposit
                                </button>
                                <form
                                    class="bg-primary/5 datatable-search dark:bg-bg3 border border-n30 dark:border-n500 flex gap-3 rounded-[30px] focus-within:border-primary p-1 items-center justify-between min-w-[200px] xxl:max-w-[319px] w-full">
                                    <input type="text" placeholder="Search"
                                        class="bg-transparent text-sm ltr:pl-4 rtl:pr-4 py-1 w-full border-none"
                                        id="deposit-search" />
                                    <button
                                        class="bg-primary shrink-0 rounded-full w-7 h-7 lg:w-8 lg:h-8 flex justify-center items-center text-n0">
                                        <i class="las la-search text-lg"></i>
                                    </button>
                                </form>
                                <div class="flex items-center gap-3 whitespace-nowrap">
                                    <span>Sort By : </span>
                                    <select name="sort" class="nc-select green">
                                        <option value="day">Last 15 Days</option>
                                        <option value="week">Last 1 Month</option>
                                        <option value="year">Last 6 Month</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto pb-4 lg:pb-6">
                            <table class="w-full whitespace-nowrap" id="deposit-table">
                                <thead>
                                    <tr class="bg-secondary1/5 dark:bg-bg3">
                                        <th class="text-start !py-5 px-6 min-w-[230px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Title
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[100px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Rate
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[200px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Account Balance
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[200px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Account Interest
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[130px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Expiry Date
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 cursor-pointer min-w-[100px]">
                                            <div class="flex items-center gap-1">
                                                Status
                                            </div>
                                        </th>
                                        <th class="text-center !py-5 " data-sortable="false">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        class="hover:bg-primary/5 dark:hover:bg-bg3 border-b border-n30 dark:border-n500 first:border-t">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <i class="las la-wallet text-primary"></i>
                                                <span class="font-medium">Fixed Deposit</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">14%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$52,584,854</p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$254.21</p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td>11/12/2028</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        class="hover:bg-primary/5 dark:hover:bg-bg3 border-b border-n30 dark:border-n500 first:border-t">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <i class="las la-wallet text-primary"></i>
                                                <span class="font-medium">Savings Deposit</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">14%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$52,584,854</p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$254.21</p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td>11/12/2028</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary3/10 dark:bg-bg3 text-secondary3">
                                                Pending
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        class="hover:bg-primary/5 dark:hover:bg-bg3 border-b border-n30 dark:border-n500 first:border-t">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <i class="las la-wallet text-primary"></i>
                                                <span class="font-medium">Fixed Deposit</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">14%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$52,584,854</p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$254.21</p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td>11/12/2028</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary3/10 dark:bg-bg3 text-secondary3">
                                                Pending
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        class="hover:bg-primary/5 dark:hover:bg-bg3 border-b border-n30 dark:border-n500 first:border-t">
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <i class="las la-wallet text-primary"></i>
                                                <span class="font-medium">Savings Deposit</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">14%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$52,584,854</p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div>
                                                <p class="font-medium">$254.21</p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td>11/12/2028</td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex col-span-12 gap-4 sm:justify-between justify-center items-center flex-wrap">
                            <p>
                                Showing 1 to 4 of 18 entries
                            </p>

                            <ul class="flex gap-2 md:gap-3 flex-wrap md:font-semibold items-center">
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary rtl:rotate-180 hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        <i class="las la-angle-left text-lg"></i>
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-n0 bg-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        1
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        2
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        3
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 rtl:rotate-180 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        <i class="las la-angle-right text-lg"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Your Credits -->
                    <div class="box col-span-12 lg:col-span-6">
                        <div
                            class="flex flex-wrap gap-4  justify-between items-center bb-dashed mb-4 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">Your Credits</h4>
                            <div class="flex flex-wrap items-center gap-4 grow sm:justify-end">
                                <form
                                    class="bg-primary/5 datatable-search dark:bg-bg3 border border-n30 dark:border-n500 flex gap-3 rounded-[30px] focus-within:border-primary p-1 items-center justify-between min-w-[200px] xxl:max-w-[319px] w-full">
                                    <input type="text" placeholder="Search"
                                        class="bg-transparent text-sm ltr:pl-4 rtl:pr-4 py-1 w-full border-none"
                                        id="credit-search" />
                                    <button
                                        class="bg-primary shrink-0 rounded-full w-7 h-7 lg:w-8 lg:h-8 flex justify-center items-center text-n0">
                                        <i class="las la-search text-lg"></i>
                                    </button>
                                </form>
                                <div class="flex items-center gap-3 whitespace-nowrap">
                                    <span>Sort By : </span>
                                    <select name="sort" class="nc-select green">
                                        <option value="day">Last 15 Days</option>
                                        <option value="week">Last 1 Month</option>
                                        <option value="year">Last 6 Month</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto pb-4 lg:pb-6">
                            <table class="w-full whitespace-nowrap select-all-table" id="transactionTable">
                                <thead>
                                    <tr class="bg-secondary1/5 dark:bg-bg3">
                                        <th class="text-start w-16 px-6 !py-5" data-sortable="false">
                                            <input name="select-all" type="checkbox" id="selectAllCheckbox"
                                                class="accent-secondary1 focus:border-none focus:shadow-none focus:outline-none" />
                                        </th>
                                        <th class="text-start !py-5 px-6 cursor-pointer min-w-[330px]">
                                            <div class="flex items-center gap-1">
                                                Title
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[80px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Rate
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[200px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Account Balance
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[200px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Account Interest
                                            </div>
                                        </th>
                                        <th class="text-start !py-5 min-w-[100px] cursor-pointer">
                                            <div class="flex items-center gap-1">
                                                Status
                                            </div>
                                        </th>
                                        <th class="text-center !py-5" data-sortable="false">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-t border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-4.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Daniel Trate - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">19%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-1.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Babul Beg - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">19%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-2.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Daniel Trate - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">14%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary3/10 dark:bg-bg3 text-secondary3">
                                                Pending
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-4.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Daniel Trate - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">12%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary3/10 dark:bg-bg3 text-secondary3">
                                                Pending
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-5.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Daniel Trate - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">39%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-6.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Daniel Trate - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">24%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-8.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Daniel Trate - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">49%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-secondary2/10 dark:bg-bg3 text-secondary2">
                                                Cancelled
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-n30 dark:border-n500">
                                        <td class="text-start px-6">
                                            <input type="checkbox" class="accent-bg3" />
                                        </td>
                                        <td class="py-2 px-6">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/card-sm-7.png" width="60" height="40"
                                                    class="rounded-sm" alt="payment medium icon" />
                                                <div>
                                                    <p class="font-medium mb-1">Daniel Trate - Metal</p>
                                                    <span class="text-xs">**4291 - Exp: 12/26</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">19%</p>
                                                <span class="text-xs">Rate</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $234,234,232
                                                </p>
                                                <span class="text-xs">Account Balance</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="font-medium mb-1">
                                                    $4,231
                                                </p>
                                                <span class="text-xs">Account Interest</span>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <span
                                                class="block text-xs w-28 xxl:w-36 text-center rounded-[30px] dark:border-n500 border border-n30 py-2 bg-primary/10 dark:bg-bg3 text-primary">
                                                Successful
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-center">
                                                <div class="relative">
                                                    <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                    <ul class="horiz-option hide">
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Edit
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Print
                                                            </span>
                                                        </li>
                                                        <li>
                                                            <span
                                                                class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                Share
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex col-span-12 gap-4 sm:justify-between justify-center items-center flex-wrap">
                            <p>
                                Showing 1 to 8 of 18 entries
                            </p>

                            <ul class="flex gap-2 md:gap-3 flex-wrap md:font-semibold items-center">
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary rtl:rotate-180 hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        <i class="las la-angle-left text-lg"></i>
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-n0 bg-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        1
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        2
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        3
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="hover:bg-primary text-primary hover:text-n0 rtl:rotate-180 border md:w-10 duration-300 md:h-10 w-8 h-8 flex items-center rounded-full justify-center border-primary">
                                        <i class="las la-angle-right text-lg"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div> --}}
        <!-- Graphe et statistiques -->
        <div class="content-separator" style="height:30px">

        </div>

        </div>
        </div>
        </div>
        </div>
    </main>
@endsection

@section('script_front_end')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartElement = document.querySelector("#fcp-vls-chart");
            if (chartElement) {
                const options = {
                    series: [
                        @foreach ($fcpProducts as $product)
                            {
                                name: "{{ $product->title }}",
                                data: [
                                    @foreach ($product->vl_history as $vl)
                                        {{ $vl->vl }},
                                    @endforeach
                                ]
                            },
                        @endforeach
                    ],
                    chart: {
                        height: 320,
                        type: 'line',
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        },
                        dropShadow: {
                            enabled: true,
                            top: 3,
                            left: 2,
                            blur: 4,
                            opacity: 0.1,
                        }
                    },
                    colors: ['#E5C646', '#10b981', '#3b82f6'],
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        width: 3,
                        curve: 'smooth'
                    },
                    markers: {
                        size: 4,
                        strokeWidth: 0,
                        hover: {
                            size: 6
                        }
                    },
                    xaxis: {
                        categories: [
                            @if (isset($fcpProducts[0]) && $fcpProducts[0]->vl_history->count() > 0)
                                @foreach ($fcpProducts[0]->vl_history as $vl)
                                    "{{ \Carbon\Carbon::parse($vl->created_at)->format('d/m') }}",
                                @endforeach
                            @endif
                        ],
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                return val.toLocaleString() + " XAF";
                            }
                        }
                    },
                    grid: {
                        borderColor: '#e7e7e7',
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                    }
                };

                const chart = new ApexCharts(chartElement, options);
                chart.render();
            }

            const distributionElement = document.querySelector("#portfolio-distribution-chart");
            if (distributionElement) {
                const distributionOptions = {
                    series: [{{ intval($totalFcpAum) }}, {{ intval($totalPmgAum) }}],
                    chart: {
                        type: 'donut',
                        height: 320
                    },
                    labels: ['FCP (Bourse)', 'PMG (Mandats)'],
                    colors: ['#E5C646', '#10b981'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total AUM',
                                        formatter: function(w) {
                                            return "{{ number_format($globalAum, 0, ' ', ' ') }} XAF";
                                        }
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val.toLocaleString() + " XAF";
                            }
                        }
                    }
                };

                const distChart = new ApexCharts(distributionElement, distributionOptions);
                distChart.render();
            }
        });
    </script>
@endsection
