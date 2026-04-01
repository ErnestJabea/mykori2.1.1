@extends('front-end/app/app-home-asset', ['title' => 'Audit Clients Compliance', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 my-products-page other-page compliance-profil'])

@section('content')
    <main class="main-content has-sidebar my-products-page other-page">
        <div class="grid grid-cols-12 gap-4 xxl:gap-6">
            <!-- Header Section -->
            <div class="col-span-12 flex flex-col gap-4 md:col-span-12 xxl:gap-6">
                <div
                    class="flex justify-between items-center bg-white dark:bg-bg3 p-6 rounded-2xl shadow-sm border border-n30">
                    <div>
                        <h3 class="h3 uppercase text-primary">AUDIT DES CLIENTS</h3>
                        <p class="text-sm opacity-70 font-medium">Accès intégral aux dossiers et historiques</p>
                    </div>
                    <form action="{{ route('compliance.clients') }}" method="GET" class="flex items-center gap-2">
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email..."
                                class="bg-n10 dark:bg-bg4 border border-n30 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-primary min-w-[250px]">
                            <button type="submit"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-n500 hover:text-primary duration-200">
                                <i class="las la-search text-lg"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-span-12">
                <div class="box bg-white dark:bg-bg3 rounded-2xl border border-n30 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead>
                                <tr class="bg-n20 dark:bg-bg3 text-n500 uppercase text-[11px] font-bold">
                                    <th class="py-4 px-6 text-start">ID</th>
                                    <th class="py-4 px-6 text-start">Nom Complet</th>
                                    {{-- <th class="py-4 px-6 text-start">Email</th> --}}
                                    <th class="py-4 px-6 text-start">Date de Création</th>
                                    <th class="py-4 px-6 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-n30">
                                @foreach ($clients as $c)
                                    <tr class="hover:bg-primary/5 duration-200">
                                        <td class="py-4 px-6 text-sm font-bold text-n500">#{{ $c->id }}</td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-secondary1 text-white flex items-center justify-center font-bold text-xs uppercase">
                                                    {{ substr($c->name, 0, 2) }}
                                                </div>
                                                <span class="text-sm font-semibold">{{ $c->name }}</span>
                                            </div>
                                        </td>
                                        {{-- <td class="py-4 px-6 text-sm text-n600">{{ $c->email }}</td> --}}
                                        <td class="py-4 px-6 text-sm">{{ $c->created_at->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6 text-center">
                                            <a href="{{ route('compliance.client-history', $c->id) }}"
                                                class="btn-outline border border-primary text-primary px-4 py-1.5 rounded-full text-xs font-bold hover:bg-primary hover:text-white duration-300">
                                                Voir plus
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($clients->isEmpty())
                                    <tr>
                                        <td colspan="5" class="py-20 text-center opacity-50 italic">Aucun client trouvé
                                            pour cette recherche.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @if ($clients->hasPages())
                        <div class="p-4 border-t border-n30 flex justify-center">
                            {{ $clients->appends(request()->input())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
