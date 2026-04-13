@extends('front-end/app/app-home-asset', ['title' => 'Architecture du Menu'])

@section('content')
    <main class="kad-main main-content has-sidebar">
        <div class="kad-inner">

            {{-- ── HEADER ─────────────────────────────────────────── --}}
            <div class="kad-topbar">
                <div class="kad-topbar-left">
                    <div class="kad-topbar-icon">
                        <i class="las la-layer-group"></i>
                    </div>
                    <div>
                        <h2 class="kad-topbar-title">Architecture des Menus</h2>
                        <p class="kad-topbar-sub">Gérez les points d'accès dynamiques pour chaque module métier.</p>
                    </div>
                </div>
                <button class="kad-btn-primary"
                    onclick="document.getElementById('addMenuModal').classList.remove('kad-hidden')">
                    <i class="las la-plus-circle"></i>
                    Nouveau Point d'Accès
                </button>
            </div>

            {{-- ── SPLIT LAYOUT ────────────────────────────────────── --}}
            <div class="kad-split">

                {{-- ── SIDEBAR ──────────────────────────────────────── --}}
                <aside class="kad-sidebar">
                    <div class="kad-sidebar-label">
                        <span class="kad-pulse"></span>
                        Rubriques actives
                    </div>

                    <nav id="sectionTabs" class="kad-nav">
                        @foreach ($sections as $key => $label)
                            <button onclick="switchSection('{{ $key }}')" id="tab-{{ $key }}"
                                class="kad-tab-item">
                                <div class="kad-tab-left">
                                    <i class="las la-folder-open kad-tab-icon"></i>
                                    <span class="kad-tab-label">{{ $label }}</span>
                                </div>
                                <span class="kad-tab-count">
                                    {{ $menus->where('section', $key)->count() }}
                                </span>
                            </button>
                        @endforeach
                    </nav>

                    <div class="kad-sidebar-hint mt-6">
                        Les changements effectués ici sont instantanés pour tous les utilisateurs.
                    </div>
                </aside>

                {{-- ── PANNEAU DROIT ────────────────────────────────── --}}
                <div class="kad-panel">

                    @foreach ($sections as $key => $label)
                        <div id="content-{{ $key }}" class="kad-section-content kad-hidden">

                            {{-- Section header --}}
                            <div class="kad-section-head">
                                <div>
                                    <h3 class="kad-section-title">{{ $label }}</h3>
                                    <p class="kad-section-sub">Configuration des menus pour la section {{ $label }}.</p>
                                </div>
                                <div class="kad-section-icon">
                                    <i class="las la-link"></i>
                                </div>
                            </div>

                            {{-- Menu cards --}}
                            <div class="kad-menu-grid">
                                @forelse($menus->where('section', $key)->sortBy('order') as $menu)
                                    <div class="kad-menu-card">

                                        {{-- Card top --}}
                                        <div class="kad-menu-card-top">
                                            <div class="kad-menu-card-left">
                                                <div class="kad-menu-icon" style="background:var(--kad-primary-10)">
                                                    <i class="{{ $menu->icon }}"></i>
                                                </div>
                                                <div>
                                                    <h4 class="kad-menu-title">{{ $menu->title }}</h4>
                                                    <span class="kad-menu-route">{{ $menu->route }}</span>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <button onclick="openEditModal({{ $menu }})" class="kad-menu-delete"
                                                    style="color:var(--kad-primary)">
                                                    <i class="las la-pen"></i>
                                                </button>
                                                <form action="{{ route('admin.front.menus.delete', $menu->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Supprimer ce lien dynamique ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kad-menu-delete">
                                                        <i class="las la-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Roles --}}
                                        <div class="kad-roles-block">
                                            <span class="kad-roles-label">Accès par rôles</span>
                                            <div class="kad-roles-list">
                                                @php $menuRoles = is_array($menu->roles_json) ? $menu->roles_json : []; @endphp
                                                @forelse($menuRoles as $roleId)
                                                    @php $role = $roles->where('id', $roleId)->first(); @endphp
                                                    <span class="kad-role-pill">
                                                        {{ $role->display_name ?? $roleId }}
                                                    </span>
                                                @empty
                                                    <span class="kad-role-public"
                                                        style="font-size: 9px; font-weight: 800; color: var(--kad-danger); text-transform: uppercase;">Public</span>
                                                @endforelse
                                            </div>
                                        </div>

                                        {{-- Footer --}}
                                        <div class="kad-menu-footer">
                                            <span class="kad-menu-order">Ordre : {{ $menu->order }}</span>
                                            <div class="kad-status-row">
                                                <span
                                                    class="kad-status-dot {{ $menu->is_active ? 'kad-dot-on' : 'kad-dot-off' }}"></span>
                                                <span
                                                    class="kad-status-label {{ $menu->is_active ? 'kad-status-on' : 'kad-status-off' }}">
                                                    {{ $menu->is_active ? 'Online' : 'Offline' }}
                                                </span>
                                            </div>
                                        </div>

                                    </div>
                                @empty
                                    <div class="kad-empty-state">
                                        <i class="las la-plus-circle kad-empty-icon text-n200"></i>
                                        <p class="kad-empty-text text-n400">Ajouter un premier lien</p>
                                    </div>
                                @endforelse
                            </div>

                        </div>
                    @endforeach

                </div>
            </div>
        </div>

        {{-- ── MODAL AJOUT MENU ────────────────────────────────────── --}}
        <div id="addMenuModal" class="kad-modal-overlay kad-hidden">
            <div class="kad-modal">

                <div class="kad-modal-header">
                    <div>
                        <h3 class="kad-modal-title">Nouveau Menu</h3>
                        <p class="kad-modal-sub">Renseignez les champs ci-dessous.</p>
                    </div>
                    <button class="kad-modal-close"
                        onclick="document.getElementById('addMenuModal').classList.add('kad-hidden')">
                        <i class="las la-times"></i>
                    </button>
                </div>

                <form action="{{ route('admin.front.menus.store') }}" method="POST" class="kad-modal-body">
                    @csrf

                    <div class="kad-field">
                        <label class="kad-field-label">Désignation</label>
                        <input type="text" name="title" placeholder="ex : Liste des Clients" required
                            class="kad-input">
                    </div>

                    <div class="kad-field-row">
                        <div class="kad-field">
                            <label class="kad-field-label">Route Name</label>
                            <input type="text" name="route" placeholder="customers.index" required
                                class="kad-input">
                        </div>
                        <div class="kad-field">
                            <label class="kad-field-label">Icône (LA)</label>
                            <input type="text" name="icon" value="las la-cube" required class="kad-input">
                        </div>
                    </div>

                    <div class="kad-field">
                        <label class="kad-field-label">Rubrique</label>
                        <select name="section" required class="kad-input">
                            @foreach ($sections as $k => $l)
                                <option value="{{ $k }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="kad-field">
                        <label class="kad-field-label">Accès Rôles</label>
                        <div class="kad-roles-check-grid">
                            @foreach ($roles as $role)
                                <label class="kad-check-item">
                                    <input type="checkbox" name="roles_json[]" value="{{ $role->id }}"
                                        class="kad-checkbox">
                                    <span class="kad-check-label">{{ $role->display_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="kad-field">
                        <label class="kad-field-label">Positionnement (Ordre)</label>
                        <input type="number" name="order" value="1" class="kad-input">
                    </div>

                    <button type="submit" class="kad-modal-submit">
                        Créer le Menu
                    </button>
                </form>
            </div>
        </div>

        {{-- ── MODAL EDIT PLACEHOLDER ────────────────────────────────── --}}
        <div id="editMenuModal" class="kad-modal-overlay kad-hidden">
            <div class="kad-modal" style="max-width:350px;">
                <div class="kad-modal-body text-center p-10">
                    <i class="las la-tools text-5xl text-primary mb-4 block"></i>
                    <h3 class="font-bold text-n900 text-xl italic mb-2">Smart Edition</h3>
                    <p class="text-sm text-n500 italic mb-6">La modification directe sera disponible après la validation de la structure. Pour le moment, recréez le lien souhaité.</p>
                    <button onclick="document.getElementById('editMenuModal').classList.add('kad-hidden')" 
                        class="kad-btn-primary w-full justify-center">Compris</button>
                </div>
            </div>
        </div>

    </main>

    {{-- Script pour la navigation --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.switchSection = function(key) {
                document.querySelectorAll('.kad-section-content').forEach(el => el.classList.add('kad-hidden'));
                document.querySelectorAll('.kad-tab-item').forEach(el => el.classList.remove('active'));

                const content = document.getElementById('content-' + key);
                const tab = document.getElementById('tab-' + key);

                if (content) content.classList.remove('kad-hidden');
                if (tab) tab.classList.add('active');
            };

            const firstKey = "{{ array_key_first($sections) }}";
            switchSection(firstKey);
        });

        function openEditModal(menu) {
            document.getElementById('editMenuModal').classList.remove('kad-hidden');
        }
    </script>

    {{-- On hérite des styles kad définis dans le dashboard si ils sont globaux, --}}
    {{-- sinon il faudrait les mettre dans un fichier CSS commun. --}}
    {{-- Pour le moment, je les laisse car ils sont probablement dans dashboard.blade.php Style tag. --}}
    {{-- Mais le dashboard est un autre fichier. Je vais ajouter les styles ici au cas où. --}}
    <style>
        {{-- Copie des styles kad- pour assurer le rendu --}}
        .kad-main {
            --kad-maroon: #6b1f0a;
            --kad-maroon-h: #8a2a10;
            --kad-primary: #1d6fb5;
            --kad-primary-h: #16568d;
            --kad-primary-10: rgba(29, 111, 181, 0.08);
            --kad-success: #16a34a;
            --kad-danger: #dc2626;
            --kad-page-bg: #f8fafc;
            --kad-bg: #ffffff;
            --kad-bg-soft: #f1f5f9;
            --kad-border: 1px solid #e2e8f0;
            --kad-border-dash: 1px dashed #cbd5e1;
            --kad-text: #1e293b;
            --kad-text-mid: #475569;
            --kad-text-soft: #64748b;
            --kad-text-muted: #94a3b8;
            --kad-radius-sm: 8px;
            --kad-radius-md: 12px;
            --kad-radius-lg: 16px;
            --kad-radius-xl: 20px;
        }

        .kad-main { min-height: 100vh; background: var(--kad-page-bg); font-family: 'Inter', sans-serif;}
        .kad-inner { max-width: 1280px; margin: 0 auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; }
        .kad-topbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .kad-topbar-left { display: flex; align-items: center; gap: 14px; }
        .kad-topbar-icon { width: 46px; height: 46px; background: var(--kad-bg); border-radius: var(--kad-radius-md); border: var(--kad-border); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--kad-primary); flex-shrink: 0; }
        .kad-topbar-title { font-size: 17px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--kad-text); margin: 0; }
        .kad-topbar-sub { font-size: 11px; color: var(--kad-text-soft); margin: 3px 0 0; font-style: italic; }
        .kad-btn-primary { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: var(--kad-primary); color: #ffffff; border: none; border-radius: var(--kad-radius-md); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; cursor: pointer; transition: all 0.2s; }
        .kad-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .kad-split { display: grid; grid-template-columns: 230px 1fr; gap: 1.25rem; align-items: start; }
        .kad-sidebar { background: var(--kad-bg); border-radius: var(--kad-radius-lg); border: var(--kad-border); padding: 16px; display: flex; flex-direction: column; gap: 4px; position: sticky; top: 1.5rem; }
        .kad-sidebar-label { display: flex; align-items: center; gap: 6px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.14em; color: var(--kad-text-muted); padding: 0 8px 10px; }
        .kad-pulse { width: 6px; height: 6px; border-radius: 50%; background: var(--kad-primary); animation: kadPulse 2s infinite; }
        @keyframes kadPulse { 0%, 100% { opacity: 1 } 50% { opacity: 0.25 } }
        .kad-nav { display: flex; flex-direction: column; gap: 3px; }
        .kad-tab-item { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid transparent; background: transparent; cursor: pointer; transition: all 0.15s; text-align: left; }
        .kad-tab-item:hover { background: var(--kad-bg-soft); }
        .kad-tab-item.active { background: var(--kad-maroon); border-color: var(--kad-maroon); }
        .kad-tab-item.active .kad-tab-icon, .kad-tab-item.active .kad-tab-label, .kad-tab-item.active .kad-tab-count { color: #ffffff; }
        .kad-tab-icon { color: var(--kad-text-muted); font-size: 16px; }
        .kad-tab-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--kad-text-soft); font-style: italic; }
        .kad-tab-count { font-size: 9px; font-weight: 700; background: #e2e8f0; color: var(--kad-text-soft); padding: 2px 7px; border-radius: 20px; }
        .kad-tab-item.active .kad-tab-count { background: rgba(255, 255, 255, 0.2); }
        .kad-panel { display: flex; flex-direction: column; gap: 1rem; }
        .kad-section-head { display: flex; justify-content: space-between; align-items: center; background: var(--kad-bg); border-radius: var(--kad-radius-lg); border: var(--kad-border); border-left: 5px solid var(--kad-primary); padding: 20px 24px; }
        .kad-section-title { font-size: 18px; font-weight: 700; text-transform: uppercase; color: var(--kad-text); font-style: italic; margin: 0; }
        .kad-section-sub { font-size: 11px; color: var(--kad-text-soft); font-style: italic; margin-top: 5px; }
        .kad-menu-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-top: 1rem; }
        .kad-menu-card { background: var(--kad-bg); border-radius: var(--kad-radius-lg); border: var(--kad-border); padding: 20px; display: flex; flex-direction: column; gap: 14px; transition: all 0.2s; }
        .kad-menu-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .kad-menu-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .kad-menu-card-left { display: flex; align-items: flex-start; gap: 12px; }
        .kad-menu-icon { width: 42px; height: 42px; border-radius: var(--kad-radius-md); background: var(--kad-bg-soft); border: var(--kad-border); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--kad-primary); }
        .kad-menu-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--kad-text); font-style: italic; margin: 0; }
        .kad-menu-route { font-size: 10px; font-weight: 600; color: var(--kad-text-soft); background: var(--kad-bg-soft); border: var(--kad-border); border-radius: 6px; padding: 2px 8px; margin-top:3px; display:inline-block; }
        .kad-menu-delete { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: var(--kad-bg-soft); border: var(--kad-border); border-radius: var(--kad-radius-sm); color: var(--kad-text-muted); cursor: pointer; transition: all 0.2s; }
        .kad-menu-delete:hover { background: #fef2f2; color: var(--kad-danger); }
        .kad-roles-block { background: var(--kad-bg-soft); border: var(--kad-border); border-radius: var(--kad-radius-md); padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }
        .kad-table-wrap { overflow-x: hidden; width: 100%; }
        .kad-table { width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed; }
        .kad-table thead th { padding: 16px 24px; background: #f8fafc; font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--kad-text-muted); border-bottom: var(--kad-border); letter-spacing: 0.1em; }
        .kad-table tbody tr { border-bottom: var(--kad-border); transition: all 0.15s; }
        .kad-table tbody tr:hover { background: #f8fafc; }
        .kad-table tbody td { padding: 14px 24px; font-size: 13px; font-weight: 600; font-style: italic; word-break: break-word; vertical-align: middle; }
        .kad-roles-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--kad-text-muted); font-style: italic; border-bottom: var(--kad-border); padding-bottom: 5px;}
        .kad-roles-list { display: flex; flex-wrap: wrap; gap: 5px; }
        .kad-role-pill { font-size: 9px; font-weight: 700; background: #fff; border: var(--kad-border); padding: 2px 7px; border-radius: 4px; color: var(--kad-text-mid); font-style: italic; }
        .kad-menu-footer { display: flex; justify-content: space-between; align-items: center; border-top: var(--kad-border); padding-top: 12px; margin-top:5px;}
        .kad-menu-order { font-size: 10px; font-weight: 700; color: var(--kad-text-soft); font-style: italic; }
        .kad-status-row { display: flex; align-items: center; gap: 6px; }
        .kad-status-dot { width: 7px; height: 7px; border-radius: 50%; }
        .kad-dot-on { background: var(--kad-success); } .kad-dot-off { background: var(--kad-danger); }
        .kad-status-label { font-size: 10px; font-weight: 700; font-style: italic; text-transform: uppercase; }
        .kad-status-on { color: var(--kad-success); } .kad-status-off { color: var(--kad-danger); }
        .kad-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 9999; display: flex; align-items: flex-start; justify-content: center; padding: 3rem 1rem; backdrop-filter: blur(3px); overflow-y: auto; }
        .kad-modal { background: var(--kad-bg); border-radius: var(--kad-radius-xl); border: var(--kad-border); width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); margin-bottom: 2rem; position: relative; }
        .kad-modal-header { padding: 20px 24px; border-bottom: var(--kad-border); background: var(--kad-bg-soft); display: flex; justify-content: space-between; align-items: center; }
        .kad-modal-title { font-size: 16px; font-weight: 700; text-transform: uppercase; color: var(--kad-text); font-style: italic; }
        .kad-modal-close { width: 34px; height: 34px; border-radius: var(--kad-radius-sm); background: #f1f5f9; border: var(--kad-border); cursor: pointer; }
        .kad-modal-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
        .kad-field { display: flex; flex-direction: column; gap: 5px; }
        .kad-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .kad-input { width: 100%; padding: 10px 14px; background: var(--kad-bg-soft); border: var(--kad-border); border-radius: var(--kad-radius-sm); font-size: 13px; font-weight: 600; font-style: italic; outline: none; transition: all 0.2s; }
        .kad-input:focus { border-color: var(--kad-primary); }
        .kad-roles-check-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; background: var(--kad-bg-soft); border: var(--kad-border); border-radius: var(--kad-radius-md); padding: 12px; max-height: 140px; overflow-y: auto; }
        .kad-check-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; cursor: pointer; }
        .kad-modal-submit { width: 100%; padding: 12px; background: var(--kad-primary); color: #fff; border: none; border-radius: var(--kad-radius-md); font-weight: 700; text-transform: uppercase; font-style: italic; cursor: pointer; }
        .kad-hidden { display: none !important; }
        .kad-empty-state { grid-column: 1 / -1; padding: 3rem; text-align: center; background: var(--kad-bg-soft); border-radius: var(--kad-radius-lg); border: 2px dashed #cbd5e1; }
    </style>
@endsection
