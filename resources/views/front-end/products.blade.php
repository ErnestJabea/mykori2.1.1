@extends('front-end/app/app-home', ['Produits disponibles', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 hidden products-page'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6 pb-4">
            <!-- Liste des produits -->
            @foreach ($products_categories as $category)
                <div class="col-span-12">
                    <div class="box col-span-12 lg:col-span-6">
                        <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-4 pb-4 lg:mb-6 lg:pb-6">
                            <h4 class="h4">{{ $category->abreviation }} : {{ $category->title }}</h4>
                        </div>
                        @php
                            $produits = App\Product::where('products_category_id', $category->id)
                                ->where('nb_action', '>', 0)
                                ->get();
                        @endphp
                        <div class="overflow-x-auto">
                            <div class="mb-6 flex items-center justify-center gap-3 lg:mb-8 xxl:gap-4">
                                <button
                                    class="prev-wallet h-8 w-8 shrink-0 rounded-full border border-primary bg-n0 text-primary duration-300 hover:bg-primary hover:text-n0 dark:bg-bg4 dark:hover:bg-primary xxl:h-10 xxl:w-10">
                                    <i class="las la-angle-left text-lg rtl:rotate-180"></i>
                                </button>
                                <div class="swiper walletSwiper" dir="ltr">
                                    <div class="swiper-wrapper">
                                        @foreach ($produits as $product)
                                            @php
                                                $asset_value = App\Models\AssetValue::where('product_id', $product->id)
                                                    ->orderBy('date_vl', 'desc')
                                                    ->first();
                                            @endphp
                                            <div class="swiper-slide">
                                                <div class="flex justify-center">
                                                    <div class="content-product">
                                                        <div class="product-img">
                                                            <img src="{{ $product->logo }}"
                                                                class="rounded-xl" alt="{{ $product->title }}" />
                                                        </div>
                                                        <div class="title-product">
                                                            <h3>{{ $product->title }}</h3>
                                                        </div>
                                                        <div class="product-price">
                                                            @if ($product->products_category_id == 2)
                                                                <span class="price">{{ $product->vl }}%</span>
                                                            @else
                                                                <span
                                                                    class="price">{{ number_format($asset_value->vl, 2, ',', ' ') }}</span>
                                                                <sup>Fcfa</sup>
                                                            @endif
                                                        </div>
                                                        <div class="content-btn-action">
                                                            <button class="btn ac-modal-btn buy">
                                                                <a
                                                                    href="{{ route('product-detail', ['slug' => $product->slug]) }}">SOUSCRIRE</a>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <button
                                    class="next-wallet h-8 w-8 shrink-0 rounded-full border border-primary bg-n0 text-primary duration-300 hover:bg-primary hover:text-n0 dark:bg-bg4 dark:hover:bg-primary xxl:h-10 xxl:w-10">
                                    <i class="las la-angle-right text-lg rtl:rotate-180"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-separator" style="height:00px"></div>
            @endforeach



        </div>
        </div>
        </div>
        </div>
    </main>
@endsection
