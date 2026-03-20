@extends('front-end/app/app-home', ['Mon historique', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden'])
@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Latest Transactions -->
            <div class="col-span-12">
                <div class="box col-span-12 lg:col-span-6">
                    <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                        <h4 class="h4">Transactions récentes</h4>
                        <div class="flex items-center gap-4">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap select-all-table" id="transactionTable">
                            <thead>
                                <tr class="bg-secondary1/5 dark:bg-bg3">
                                    <th class="min-w-[220px] cursor-pointer px-6 py-5 text-start">
                                        <div class="flex items-center gap-1">Libellé</div>
                                    </th>
                                    <th class="min-w-[120px] py-5 text-center">Référence</th>
                                    <th class="min-w-[120px] cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1">Moyen de paiement
                                        </div>
                                    </th>
                                    <th class="min-w-[120px] cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1">Montant (Fcfa)
                                        </div>
                                    </th>
                                    <th align="center" class="cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1" style="justify-content: center;">Status</div>
                                    </th>
                                    <th align="center" class="cursor-pointer py-5 text-center">
                                        <div class="flex items-center gap-1" style="justify-content: center;">Actions</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($transactions->count() > 0)
                                    @foreach ($transactions as $transaction)
                                        @php
                                            $product = App\Product::where('id',$transaction->product_id)->first();
                                        @endphp
                                        <tr class="even:bg-secondary1/5 dark:even:bg-bg3">
                                            <td class="px-6 py-2">
                                                <div class="flex items-center gap-3">
                                                    <div>
                                                        <p class="mb-1 font-medium">{{ $transaction->title }}
                                                            </p>
                                                        <span class="text-xs">{{ $transaction->created_at }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td align="center" class="py-2">{{ $transaction->ref }}</td>
                                            <td align="center" class="py-2">{{ $transaction->payment_mode }}</td>
                                            <td align="center" class="py-2">
                                                {{ number_format($transaction->amount, 0, ' ', ' ') }}</td>
                                            <td align="center" class="py-2">
                                                @if ($transaction->status == 'Refusé')
                                                    <span
                                                        class="block w-28 rounded-[30px] border border-n30 bg-secondary2/10 py-2 text-center text-xs text-secondary2 dark:border-n500 dark:bg-bg3 xxl:w-36">
                                                        {{ $transaction->status }}
                                                    </span>
                                                @elseif ($transaction->status == 'En attente')
                                                    <span
                                                        class="block w-28 rounded-[30px] border border-n30 bg-secondary3/10 py-2 text-center text-xs text-secondary3 dark:border-n500 dark:bg-bg3 xxl:w-36">
                                                        {{ $transaction->status }}
                                                    </span>
                                                @elseif ($transaction->status == 'Succès')
                                                    <span
                                                        class="block w-28 rounded-[30px] border border-n30 bg-primary/10 py-2 text-center text-xs text-primary dark:border-n500 dark:bg-bg3 xxl:w-36">
                                                        {{ $transaction->status }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="py-2">
                                                <div class="flex justify-center">
                                                    <!-- Add your action elements here -->
                                                    <div class="relative">
                                                        <i class="las la-ellipsis-v horiz-option-btn cursor-pointer"></i>
                                                        <ul class="horiz-option hide">
                                                            <li>
                                                                <a
                                                                    href="{{ route('transaction-detail', ['reference' => $transaction->ref]) }}">
                                                                    <span
                                                                        class="block cursor-pointer rounded px-3 py-1 text-sm leading-normal duration-300 hover:bg-primary/10 dark:hover:bg-bg4">
                                                                        Voir le détail
                                                                    </span>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <p style="text-align: center"> Pas de transaction pour le moment</p>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </main>
@endsection
