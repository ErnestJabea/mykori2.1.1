@extends('front-end/app/app-home-asset', ['Créer un nouveau client', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <div class="col-span-12">
                <div class="box bg-n0 dark:bg-bg4 p-6 rounded-2xl shadow-sm border border-n30 dark:border-n500">
                    <div class="bb-dashed mb-6 flex items-center justify-between pb-4 border-b border-dashed border-n40">
                        <h3 class="h3 flex items-center gap-3">
                            <i class="las la-user-plus text-primary"></i> CRÉER UN NOUVEAU CLIENT
                        </h3>
                        <a href="{{ route('customer') }}"
                            class="btn border border-primary text-primary px-4 py-2 rounded-xl hover:bg-primary hover:text-white duration-300">
                            <i class="las la-arrow-left"></i> Retour
                        </a>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-secondary2/10 text-secondary2 rounded-xl border border-secondary2/20">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('asset-manager.store-customer') }}" method="POST"
                        class="grid grid-cols-12 gap-6" id="create-customer-form">
                        @csrf

                        <div class="col-span-12 lg:col-span-4">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Civilité (Genre)</label>
                            <div class="relative">
                                <i
                                    class="las la-id-card-alt absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <select name="genre" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300">
                                    <option value="0">Monsieur</option>
                                    <option value="1">Madame</option>
                                    <option value="2">Entreprise</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-8">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Nom complet</label>
                            <div class="relative">
                                <i class="las la-user absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="Ex: Jean Paul">
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-12">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Adresse Email</label>
                            <div class="relative">
                                <i
                                    class="las la-envelope absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="exemple@kori.cm">
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-6">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Localisation</label>
                            <div class="relative">
                                <i
                                    class="las la-map-marker absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="text" name="localisation" value="{{ old('localisation') }}" required
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="Ex: Douala, Akwa">
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-6">
                            <label class="text-[11px] font-bold opacity-60 mb-2 block uppercase">Boite Postale (BP)</label>
                            <div class="relative">
                                <i
                                    class="las la-mail-bulk absolute left-4 top-1/2 -translate-y-1/2 text-primary opacity-60"></i>
                                <input type="text" name="bp" value="{{ old('bp') }}"
                                    class="w-full bg-n10 dark:bg-bg3 border border-n30 dark:border-n500 rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 duration-300"
                                    placeholder="Ex: BP 1234">
                            </div>
                        </div>

                        <div class="col-span-12 mt-4">
                            <button type="submit"
                                class="btn bg-primary text-white w-full lg:w-auto px-10 py-4 rounded-xl font-bold shadow-lg hover:opacity-90 duration-300 flex items-center justify-center gap-3">
                                <i class="las la-save text-xl"></i> CRÉER LE COMPTE CLIENT
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
@endsection
