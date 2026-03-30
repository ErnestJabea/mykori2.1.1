@extends('front-end/app/app-home-asset', ['title' => 'Gestion des Menus Dynamiques'])

@section('content')
    <main class="main-content has-sidebar">
        <div class="main-inner p-4 md:p-8">
            <div class="flex flex-col gap-6">

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.front.dashboard') }}" class="p-2 bg-white rounded-xl shadow-sm hover:text-primary transition-all">
                            <i class="las la-arrow-left text-xl"></i>
                        </a>
                        <h2 class="text-2xl font-bold text-n900">Architecture du Menu</h2>
                    </div>
                    
                    <button onclick="document.getElementById('addMenuModal').classList.remove('hidden')" class="px-5 py-3 bg-primary text-white rounded-2xl shadow-lg hover:scale-105 transition-all flex items-center gap-2 font-bold uppercase text-xs tracking-wider italic">
                        <i class="las la-plus-circle text-lg"></i>
                        Nouveau Lien
                    </button>
                </div>

                <div class="flex flex-col lg:flex-row gap-8 min-h-[600px]">
                    
                    <!-- Colonne de Gauche : Rubriques Dynamiques -->
                    <div class="w-full lg:w-72 flex flex-col gap-3">
                        <div class="bg-white rounded-3xl border border-n30 p-4 shadow-sm">
                            <h3 class="text-[10px] font-bold uppercase text-n400 px-4 mb-4 tracking-widest italic">Rubriques Actives</h3>
                            <nav class="flex flex-col gap-1" id="sectionTabs">
                                @foreach($sections as $key => $label)
                                    <button onclick="switchSection('{{ $key }}')" id="tab-{{ $key }}" class="section-tab w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all font-bold italic text-sm text-left group">
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 rounded-full bg-n100 group-[.active]:bg-primary transition-all"></div>
                                            <span class="text-n600 group-[.active]:text-primary">{{ $label }}</span>
                                        </div>
                                        <span class="text-[10px] bg-n20 px-2 py-0.5 rounded-full text-n500 group-[.active]:bg-primary/10 group-[.active]:text-primary">{{ $menus->where('section', $key)->count() }}</span>
                                    </button>
                                @endforeach
                            </nav>
                        </div>
                        
                        <div class="bg-primary/5 rounded-3xl border border-primary/20 p-6 italic">
                            <p class="text-xs text-primary/80 leading-relaxed font-medium">
                                <i class="las la-info-circle mr-1"></i>
                                Les changements effectués ici sont instantanés pour tous les utilisateurs concernés.
                            </p>
                        </div>
                    </div>

                    <!-- Colonne de Droite : Gestionnaire de Liens -->
                    <div class="flex-1">
                        @foreach($sections as $key => $label)
                            <div id="content-{{ $key }}" class="section-content hidden animate-fade-in flex flex-col gap-6">
                                <div class="bg-white rounded-3xl border border-n30 p-6 flex items-center justify-between shadow-sm">
                                    <div>
                                        <h3 class="text-xl font-bold text-n900 italic">{{ $label }}</h3>
                                        <p class="text-xs text-n500 font-medium">Gestion des liens pour la section {{ $label }}</p>
                                    </div>
                                    <div class="w-12 h-12 bg-n20 rounded-2xl flex items-center justify-center text-n400">
                                        <i class="las la-layer-group text-2xl"></i>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @forelse($menus->where('section', $key)->sortBy('order') as $menu)
                                        <div class="bg-white rounded-3xl border border-n30 p-5 shadow-lg hover:shadow-primary/5 transition-all group border-l-4 border-l-primary/30">
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex gap-4">
                                                    <div class="w-12 h-12 rounded-2xl bg-n10 flex items-center justify-center text-primary shadow-inner border border-n20">
                                                        <i class="{{ $menu->icon }} text-2xl"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-n900 italic tracking-tight">{{ $menu->title }}</h4>
                                                        <p class="text-[10px] text-n400 font-bold uppercase tracking-tighter">{{ $menu->route }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex gap-1">
                                                     <button onclick="openEditModal({{ $menu }})" class="p-2 text-n300 hover:text-primary transition-all"><i class="las la-pen text-lg"></i></button>
                                                     <form action="{{ route('admin.front.menus.delete', $menu->id) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-2 text-n300 hover:text-danger transition-all"><i class="las la-trash text-lg"></i></button>
                                                     </form>
                                                </div>
                                            </div>

                                            <div class="bg-n10/50 rounded-2xl p-3 flex flex-col gap-2">
                                                <span class="text-[9px] font-bold uppercase text-n400 italic">Accès autorisés :</span>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @php $menuRoles = is_array($menu->roles_json) ? $menu->roles_json : []; @endphp
                                                    @foreach($menuRoles as $roleId)
                                                        @php $role = $roles->where('id', $roleId)->first(); @endphp
                                                        <span class="px-2 py-0.5 bg-white border border-n30 rounded-lg text-[9px] font-extrabold text-n600 uppercase italic">
                                                            {{ $role->name ?? $roleId }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="mt-4 pt-4 border-t border-n20 flex justify-between items-center">
                                                <div class="flex items-center gap-1.5">
                                                    <i class="las la-sort text-n400"></i>
                                                    <span class="text-[10px] font-bold text-n500 italic">Ordre : {{ $menu->order }}</span>
                                                </div>
                                                <span class="px-2 py-0.5 {{ $menu->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }} rounded text-[9px] font-bold uppercase italic shadow-sm">
                                                    {{ $menu->is_active ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-span-full py-12 text-center bg-white rounded-3xl border border-n30 border-dashed">
                                            <i class="las la-folder-open text-4xl text-n200 mb-2"></i>
                                            <p class="text-n400 font-bold italic text-sm">Aucun lien dynamisé dans cette section.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>

            </div>
        </div>

        <!-- Create Modal -->
        <div id="addMenuModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[100] flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-[40px] shadow-2xl w-full max-w-lg overflow-hidden animate-modal-in border border-white/20">
                <div class="px-10 py-8 border-b border-n30 flex justify-between items-center bg-n10/30">
                    <div>
                        <h3 class="text-2xl font-bold text-n900 italic tracking-tight">Configuration Link</h3>
                        <p class="text-xs text-n500 font-medium italic">Paramétrez un nouveau point d'entrée dynamique.</p>
                    </div>
                    <button onclick="document.getElementById('addMenuModal').classList.add('hidden')" class="w-10 h-10 flex items-center justify-center bg-n20 rounded-2xl text-n500 hover:bg-danger hover:text-white transition-all"><i class="las la-times text-xl"></i></button>
                </div>
                
                <form action="{{ route('admin.front.menus.store') }}" method="POST" class="p-10 flex flex-col gap-6">
                    @csrf
                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-bold uppercase text-n400 italic tracking-widest px-1">Intitulé du Menu</label>
                            <input type="text" name="title" required class="w-full px-5 py-3.5 bg-n10 border border-n30 rounded-2xl outline-none focus:border-primary transition-all font-bold italic text-sm text-n900 shadow-inner">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-bold uppercase text-n400 italic tracking-widest px-1">Icône Awesome</label>
                            <input type="text" name="icon" value="las la-link" required class="w-full px-5 py-3.5 bg-n10 border border-n30 rounded-2xl outline-none focus:border-primary transition-all font-bold italic text-sm text-n900 shadow-inner">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-bold uppercase text-n400 italic tracking-widest px-1">Route (Named)</label>
                            <input type="text" name="route" required class="w-full px-5 py-3.5 bg-n10 border border-n30 rounded-2xl outline-none focus:border-primary transition-all font-bold italic text-sm text-n900 shadow-inner">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-bold uppercase text-n400 italic tracking-widest px-1">Section Cible</label>
                            <select name="section" required class="w-full px-5 py-3.5 bg-n10 border border-n30 rounded-2xl outline-none focus:border-primary transition-all font-bold italic text-sm text-n900 shadow-inner appearance-none cursor-pointer">
                                @foreach($sections as $k => $l)
                                    <option value="{{ $k }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <label class="text-[10px] font-bold uppercase text-n400 italic tracking-widest px-1">Distribution des Rôles</label>
                        <div class="grid grid-cols-2 gap-2 bg-n10 p-4 rounded-3xl border border-n30 shadow-inner max-h-40 overflow-y-auto">
                            @foreach($roles as $role)
                                <label class="flex items-center gap-3 p-2 hover:bg-white rounded-xl transition-all cursor-pointer border border-transparent hover:border-n30 group">
                                    <input type="checkbox" name="roles_json[]" value="{{ $role->id }}" class="w-4 h-4 rounded border-n30 text-primary focus:ring-primary transition-all cursor-pointer">
                                    <span class="text-[10px] font-bold text-n600 uppercase italic group-hover:text-primary">{{ $role->display_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-bold uppercase text-n400 italic tracking-widest px-1">Positionnement (Ordre)</label>
                        <input type="number" name="order" value="1" class="w-full px-5 py-3.5 bg-n10 border border-n30 rounded-2xl outline-none focus:border-primary transition-all font-bold italic text-sm text-n900 shadow-inner">
                    </div>

                    <div class="flex gap-4 mt-4">
                         <button type="submit" class="flex-1 py-4 bg-primary text-white rounded-3xl font-bold uppercase italic tracking-widest shadow-xl hover:shadow-primary/30 transition-all transform active:scale-95">
                            Valider le Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editMenuModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[100] flex items-center justify-center hidden p-4">
             <!-- Modal de modification rapide (implémentation simplifiée) -->
             <div class="bg-white rounded-[40px] shadow-2xl p-10 text-center max-w-sm">
                <i class="las la-tools text-5xl text-primary mb-4 block"></i>
                <h3 class="font-bold text-n900 text-xl italic mb-2">Smart Edition</h3>
                <p class="text-sm text-n500 italic mb-6">La modification directe sera disponible après la validation de la structure. Pour le moment, recréez le lien souhaité.</p>
                <button onclick="document.getElementById('editMenuModal').classList.add('hidden')" class="px-8 py-3 bg-n100 text-white rounded-2xl font-bold uppercase text-xs italic">Compris</button>
             </div>
        </div>

    </main>

    <style>
        @keyframes modal-in {
            from { transform: translateY(30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-modal-in { animation: modal-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .animate-fade-in { animation: fade-in 0.3s ease-out; }
        
        .section-tab.active {
            background-color: white;
            border-color: #f1f5f9; /* n30-ish */
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
    </style>
    
    <script>
        function switchSection(sectionKey) {
            // Hide all contents
            document.querySelectorAll('.section-content').forEach(c => c.classList.add('hidden'));
            // Remove active from all tabs
            document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
            
            // Show selected
            document.getElementById('content-' + sectionKey).classList.remove('hidden');
            document.getElementById('tab-' + sectionKey).classList.add('active');
        }

        // Initialize first section
        window.onload = () => {
             const firstKey = "{{ array_key_first($sections) }}";
             switchSection(firstKey);
        };

        function openEditModal(menu) {
            document.getElementById('editMenuModal').classList.remove('hidden');
        }
    </script>
@endsection
