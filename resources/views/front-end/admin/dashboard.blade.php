@extends('front-end/app/app-home-asset', ['title' => 'Console d\'Administration'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-8">

                <!-- Header -->
                <div class="flex flex-wrap pb-10 md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-primary text-2xl border border-n30">
                            <i class="las la-server"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-n900 italic uppercase">Console Admin Front-End</h2>
                            <p class="text-n500 text-xs italic font-medium">Gestion centralisée des rubriques et nav-links
                                dynamiques.</p>
                        </div>
                    </div>

                    <button onclick="document.getElementById('addMenuModal').classList.remove('hidden')"
                        class="px-6 py-3 bg-primary text-white rounded-xl shadow-lg hover:scale-105 transition-all flex items-center gap-2 font-bold uppercase text-[10px] italic">
                        <i class="las la-plus-circle text-lg"></i>
                        Nouveau Lien Dynamique
                    </button>
                </div>

                <!-- Split Layout -->
                <div class="flex flex-col md:flex-row gap-8 min-h-[600px]">

                    <!-- Colonne de Gauche : Rubriques Dynamiques -->
                    <div class="w-full md:w-72 flex flex-col gap-4">
                        <div class="bg-white rounded-3xl border border-n30 p-5 shadow-sm">
                            <h3
                                class="text-[10px] font-bold uppercase text-n500 px-4 mb-5 tracking-widest italic flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-primary/40 animate-pulse"></span>
                                Rubriques Actives
                            </h3>
                            <nav class="flex flex-col gap-2" id="sectionTabs">
                                @foreach ($sections as $key => $label)
                                    <button onclick="switchSection('{{ $key }}')" id="tab-{{ $key }}"
                                        class="section-tab group w-full flex items-center justify-between px-5 py-4 rounded-xl transition-all font-bold italic text-xs uppercase text-left border border-transparent hover:bg-n10">
                                        <div class="flex items-center gap-3">
                                            <i
                                                class="{{ $key == 'supervision' ? 'las la-chart-pie' : 'las la-layer-group' }} text-xl text-n400 group-[.active]:text-primary"></i>
                                            <span class="text-n600 group-[.active]:text-primary">{{ $label }}</span>
                                        </div>
                                        @if ($key != 'supervision')
                                            <span
                                                class="text-[9px] bg-n20 px-2 py-0.5 rounded-lg text-n500 group-[.active]:bg-primary group-[.active]:text-white">{{ $menus->where('section', $key)->count() }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </nav>

                            <div class="mt-8 pt-8 border-t border-n30">
                                <div class="bg-n10 rounded-2xl p-4 border border-n30">
                                    <p class="text-[10px] text-n500 italic font-bold leading-relaxed text-center">
                                        Cliquez sur une rubrique pour administrer ses liens.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne de Droite : Panneau de Contrôle -->
                    <div class="flex-1">

                        <!-- TAB : supervision -->
                        <div id="content-supervision" class="section-content hidden flex flex-col gap-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm">
                                    <p class="text-[10px] font-bold text-n400 uppercase tracking-widest mb-1 italic">Total
                                        Utilisateurs</p>
                                    <h3 class="text-3xl font-bold text-n900 italic tracking-tighter">
                                        {{ $stats['total_users'] }}</h3>
                                </div>
                                @foreach ($stats['role_distribution'] as $role)
                                    <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm text-center">
                                        <p class="text-[10px] font-bold text-n400 uppercase tracking-widest mb-1 italic">
                                            {{ $role->display_name }}</p>
                                        <h3 class="text-3xl font-bold text-primary italic tracking-tighter">
                                            {{ $role->total }}</h3>
                                    </div>
                                @endforeach
                            </div>

                            <div class="bg-white rounded-3xl border border-n30 shadow-sm overflow-hidden">
                                <div class="p-6 border-b border-n30 bg-n10/30 flex justify-between items-center">
                                    <h4 class="font-bold text-n900 uppercase italic flex items-center gap-3">
                                        <i class="las la-history text-secondary text-2xl"></i>
                                        Piste d'Audit Récente
                                    </h4>
                                    <a href="{{ route('admin.front.export-logs') }}"
                                        class="px-4 py-2 bg-success text-white rounded-xl text-[10px] font-bold uppercase italic flex items-center gap-2 shadow-sm">
                                        <i class="las la-file-excel text-lg"></i> Exporter
                                    </a>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="text-n400 text-[10px] uppercase font-bold italic border-b border-n30">
                                            <tr>
                                                <th class="px-6 py-4">Date</th>
                                                <th class="px-6 py-4">Opérateur</th>
                                                <th class="px-6 py-4">Action</th>
                                                <th class="px-6 py-4">Détails</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-n30 italic">
                                            @forelse($stats['recent_actions'] as $log)
                                                <tr class="hover:bg-n10 transition-all">
                                                    <td class="px-6 py-4 text-[11px] font-bold text-n500">
                                                        {{ $log->created_at->format('d/m/Y H:i') }}</td>
                                                    <td class="px-6 py-4 text-sm font-bold text-n900">
                                                        {{ $log->user->name ?? 'Système' }}</td>
                                                    <td class="px-6 py-4">
                                                        <span
                                                            class="px-2 py-0.5 bg-primary/10 text-primary text-[9px] rounded font-bold uppercase">{{ $log->action }}</span>
                                                    </td>
                                                    <td class="px-6 py-4 text-xs text-n600 font-medium">
                                                        {{ $log->description }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4"
                                                        class="px-6 py-12 text-center text-n400 italic font-bold">Traceur
                                                        d'activité vide.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- TABs dynamiques pour les sections -->
                        @foreach ($sections as $key => $label)
                            @if ($key != 'supervision')
                                <div id="content-{{ $key }}" class="section-content hidden flex flex-col gap-6">
                                    <div
                                        class="bg-white rounded-3xl border border-n30 p-8 shadow-sm border-l-8 border-l-primary flex items-center justify-between">
                                        <div>
                                            <h3 class="text-2xl font-bold text-n900 italic uppercase">{{ $label }}
                                            </h3>
                                            <p class="text-xs text-n500 font-bold italic opacity-70">Configuration des
                                                points d'accès pour ce module.</p>
                                        </div>
                                        <div
                                            class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary border border-primary/20">
                                            <i class="las la-link text-2xl"></i>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        @forelse($menus->where('section', $key)->sortBy('order') as $menu)
                                            <div
                                                class="bg-white rounded-3xl border border-n30 p-6 shadow-sm hover:shadow-md transition-all group">
                                                <div class="flex items-start justify-between mb-6">
                                                    <div class="flex gap-4">
                                                        <div
                                                            class="w-12 h-12 rounded-xl bg-n10 flex items-center justify-center text-primary border border-n30">
                                                            <i class="{{ $menu->icon }} text-xl"></i>
                                                        </div>
                                                        <div>
                                                            <h4 class="font-bold text-n900 italic uppercase text-sm mb-1">
                                                                {{ $menu->title }}</h4>
                                                            <div
                                                                class="flex items-center gap-1.5 px-2 py-0.5 bg-n10 rounded w-fit">
                                                                <span
                                                                    class="text-[9px] text-n500 font-bold italic lowercase">{{ $menu->route }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                                        <form action="{{ route('admin.front.menus.delete', $menu->id) }}"
                                                            method="POST"
                                                            onsubmit="return confirm('Supprimer ce lien dynamique ?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="w-8 h-8 flex items-center justify-center bg-n10 text-n400 hover:text-danger rounded-lg transition-all shadow-sm"><i
                                                                    class="las la-trash-alt text-lg"></i></button>
                                                        </form>
                                                    </div>
                                                </div>

                                                <div
                                                    class="bg-n10/40 rounded-2xl p-4 flex flex-col gap-2 border border-n20">
                                                    <span
                                                        class="text-[9px] font-bold uppercase text-n500 italic border-b border-n30 pb-1">Accès
                                                        par rôles :</span>
                                                    <div class="flex flex-wrap gap-1">
                                                        @php $menuRoles = is_array($menu->roles_json) ? $menu->roles_json : []; @endphp
                                                        @forelse($menuRoles as $roleId)
                                                            @php $role = $roles->where('id', $roleId)->first(); @endphp
                                                            <span
                                                                class="px-2 py-0.5 bg-white border border-n30 rounded text-[9px] font-bold text-n600 uppercase italic">
                                                                {{ $role->display_name ?? $roleId }}
                                                            </span>
                                                        @empty
                                                            <span
                                                                class="text-[9px] text-danger font-bold uppercase">Public</span>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                <div
                                                    class="mt-4 pt-4 border-t border-n30 flex justify-between items-center">
                                                    <span class="text-[10px] font-bold text-n500 italic uppercase">Ordre :
                                                        {{ $menu->order }}</span>
                                                    <div class="flex items-center gap-1.5">
                                                        <span
                                                            class="w-2 h-2 rounded-full {{ $menu->is_active ? 'bg-success' : 'bg-danger' }}"></span>
                                                        <span
                                                            class="text-[9px] font-bold uppercase italic {{ $menu->is_active ? 'text-success' : 'text-danger' }}">
                                                            {{ $menu->is_active ? 'Online' : 'Offline' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div
                                                class="col-span-full py-12 text-center bg-n10 rounded-3xl border-2 border-dashed border-n30">
                                                <p class="text-n500 font-bold italic text-sm uppercase">Ajouter un premier
                                                    lien</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                        @endforeach

                    </div>
                </div>
            </div>
        </div>

        <!-- Add Menu Modal -->
        <div id="addMenuModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-n30">
                <div class="px-8 py-6 border-b border-n30 flex justify-between items-center bg-n10/30">
                    <div>
                        <h3 class="text-2xl font-bold text-n900 italic uppercase">Nouveau Menu</h3>
                    </div>
                    <button onclick="document.getElementById('addMenuModal').classList.add('hidden')"
                        class="w-10 h-10 flex items-center justify-center bg-n20 rounded-xl text-n500 hover:bg-danger hover:text-white transition-all"><i
                            class="las la-times text-xl"></i></button>
                </div>

                <form action="{{ route('admin.front.menus.store') }}" method="POST" class="p-8 flex flex-col gap-4">
                    @csrf
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-bold uppercase text-n500 italic px-2">Désignation</label>
                        <input type="text" name="title" placeholder="ex: Liste des Clients" required
                            class="w-full px-4 py-3 bg-n10 border border-n30 rounded-xl outline-none focus:border-primary font-bold italic text-sm text-n900 shadow-inner">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-bold uppercase text-n500 italic px-2">Route Name</label>
                            <input type="text" name="route" placeholder="customers.index" required
                                class="w-full px-4 py-3 bg-n10 border border-n30 rounded-xl outline-none focus:border-primary font-bold italic text-sm text-n900 shadow-inner">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-bold uppercase text-n500 italic px-2">Icône (LA)</label>
                            <input type="text" name="icon" value="las la-cube" required
                                class="w-full px-4 py-3 bg-n10 border border-n30 rounded-xl outline-none focus:border-primary font-bold italic text-sm text-n900 shadow-inner">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-bold uppercase text-n500 italic px-2">Rubrique</label>
                        <select name="section" required
                            class="w-full px-4 py-3 bg-n10 border border-n30 rounded-xl outline-none focus:border-primary font-bold italic text-sm text-n900 shadow-inner">
                            @foreach ($sections as $k => $l)
                                @if ($k != 'supervision')
                                    <option value="{{ $k }}">{{ $l }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-[9px] font-bold uppercase text-n500 italic px-2">Accès Rôles</label>
                        <div
                            class="grid grid-cols-2 gap-2 bg-n10 p-4 rounded-2xl border border-n30 shadow-inner max-h-32 overflow-y-auto">
                            @foreach ($roles as $role)
                                <label
                                    class="flex items-center gap-2 p-2 hover:bg-white rounded-lg transition-all cursor-pointer">
                                    <input type="checkbox" name="roles_json[]" value="{{ $role->id }}"
                                        class="w-4 h-4 rounded border-n30 text-primary">
                                    <span
                                        class="text-[10px] font-bold text-n600 uppercase italic">{{ $role->display_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-4 bg-primary text-white rounded-2xl font-bold uppercase italic shadow-lg mt-2">
                        Créer le Menu
                    </button>
                </form>
            </div>
        </div>

    </main>

    <style>
        .section-tab.active {
            background-color: #531d09 !important;
            /* using fallback color if primary variable not ready */
            color: white !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .section-tab.active i,
        .section-tab.active span {
            color: white !important;
        }

        .section-tab.active .group-\[.active\]\:bg-primary {
            background-color: white !important;
            color: #531d09 !important;
        }

        .hidden {
            display: none !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.switchSection = function(sectionKey) {
                // Masquer tous les contenus
                document.querySelectorAll('.section-content').forEach(function(c) {
                    c.classList.add('hidden');
                });
                // Réinitialiser les onglets
                document.querySelectorAll('.section-tab').forEach(function(t) {
                    t.classList.remove('active');
                });

                // Afficher le contenu actif
                const selectedContent = document.getElementById('content-' + sectionKey);
                const selectedTab = document.getElementById('tab-' + sectionKey);

                if (selectedContent) {
                    selectedContent.classList.remove('hidden');
                }
                if (selectedTab) {
                    selectedTab.classList.add('active');
                }
            };

            // Init au chargement
            switchSection('supervision');
        });
    </script>
@endsection
