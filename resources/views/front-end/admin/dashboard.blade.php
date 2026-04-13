@extends('front-end/app/app-home-asset', ['title' => 'Console d\'Administration'])

@section('content')
    <main class="kad-main main-content has-sidebar">
        <div class="kad-inner">

            {{-- ── HEADER ─────────────────────────────────────────── --}}
            <div class="kad-topbar">
                <div class="kad-topbar-left">
                    <div class="kad-topbar-icon">
                        <i class="las la-server"></i>
                    </div>
                    <div>
                        <h2 class="kad-topbar-title">Console Admin Front-End</h2>
                        <p class="kad-topbar-sub">Gestion centralisée des rubriques et nav-links dynamiques.</p>
                    </div>
                </div>
                <button class="kad-btn-primary"
                    onclick="document.getElementById('addMenuModal').classList.remove('kad-hidden')">
                    <i class="las la-plus-circle"></i>
                    Nouveau Lien Dynamique
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
                                    <i
                                        class="{{ $key === 'supervision' ? 'las la-chart-pie' : 'las la-layer-group' }} kad-tab-icon"></i>
                                    <span class="kad-tab-label">{{ $label }}</span>
                                </div>
                                @if ($key !== 'supervision')
                                    <span class="kad-tab-count">
                                        {{ $menus->where('section', $key)->count() }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </nav>

                    <div class="kad-sidebar-hint">
                        Cliquez sur une rubrique pour administrer ses liens.
                    </div>
                </aside>

                {{-- ── PANNEAU DROIT ────────────────────────────────── --}}
                <div class="kad-panel">

                    {{-- TAB : Supervision --}}
                    <div id="content-supervision" class="kad-section-content kad-hidden">

                        {{-- Stats --}}
                        <div class="kad-stats-grid">
                            <div class="kad-stat-card">
                                <span class="kad-stat-label">Total utilisateurs</span>
                                <span class="kad-stat-value">{{ $stats['total_users'] }}</span>
                            </div>

                            <div class="kad-stat-card kad-stat-featured"
                                onclick="window.location.href='{{ route('admin.front.users') }}'">
                                <span class="kad-stat-label kad-stat-label-white">Gestion équipe</span>
                                <span class="kad-stat-label-big">Gérer les Habilitations</span>
                                <i class="las la-users-cog kad-stat-deco"></i>
                            </div>

                            @foreach ($stats['role_distribution'] as $role)
                                <div class="kad-stat-card">
                                    <span class="kad-stat-label">{{ $role->display_name }}</span>
                                    <span class="kad-stat-value kad-stat-value-primary">{{ $role->total }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Audit Table --}}
                        <div class="kad-table-card">
                            <div class="kad-table-header">
                                <span class="kad-table-title">
                                    <i class="las la-history"></i>
                                    Piste d'Audit Récente
                                </span>
                                <a href="{{ route('admin.front.logs.export') }}" class="kad-btn-export">
                                    <i class="las la-file-excel"></i> Exporter
                                </a>
                            </div>
                            <div class="kad-table-wrap">
                                <table class="kad-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Opérateur</th>
                                            <th>Action</th>
                                            <th>Détails</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($stats['recent_actions'] as $log)
                                            <tr>
                                                <td class="kad-td-date">
                                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                                </td>
                                                <td class="kad-td-name">
                                                    {{ $log->user->name ?? 'Système' }}
                                                </td>
                                                <td>
                                                    <span class="kad-action-badge">{{ $log->action }}</span>
                                                </td>
                                                <td class="kad-td-desc">{{ $log->description }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="kad-table-empty">
                                                    Traceur d'activité vide.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="kad-table-footer">
                                <div class="kad-pagination-info">
                                    Page <span class="kad-text-primary">{{ $stats['recent_actions']->currentPage() }}</span> sur <span class="kad-text-primary">{{ $stats['recent_actions']->lastPage() }}</span>
                                    <span class="mx-2 opacity-30">|</span>
                                    Total : <span class="kad-text-primary">{{ $stats['recent_actions']->total() }}</span> actions
                                </div>
                                <div class="kad-pagination-links">
                                    {{ $stats['recent_actions']->links() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TABs dynamiques --}}
                    @foreach ($sections as $key => $label)
                        @if ($key !== 'supervision')
                            <div id="content-{{ $key }}" class="kad-section-content kad-hidden">

                                {{-- Section header --}}
                                <div class="kad-section-head">
                                    <div>
                                        <h3 class="kad-section-title">{{ $label }}</h3>
                                        <p class="kad-section-sub">Configuration des points d'accès pour ce module.</p>
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
                                                    <div class="kad-menu-icon">
                                                        <i class="{{ $menu->icon }}"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="kad-menu-title">{{ $menu->title }}</h4>
                                                        <span class="kad-menu-route">{{ $menu->route }}</span>
                                                    </div>
                                                </div>
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
                                                        <span class="kad-role-public">Public</span>
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
                                            <i class="las la-plus-circle kad-empty-icon"></i>
                                            <p class="kad-empty-text">Ajouter un premier lien</p>
                                        </div>
                                    @endforelse
                                </div>

                            </div>
                        @endif
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
                                @if ($k !== 'supervision')
                                    <option value="{{ $k }}">{{ $l }}</option>
                                @endif
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

                    <button type="submit" class="kad-modal-submit">
                        Créer le Menu
                    </button>
                </form>
            </div>
        </div>

    </main>


    {{-- ── STYLES ───────────────────────────────────────────────────── --}}
    <style>
        /* Thème institutionnel Kori (Light par défaut) */
        .kad-main {
            --kad-maroon: #6b1f0a;
            --kad-maroon-h: #8a2a10;
            --kad-primary: #1d6fb5;
            --kad-primary-h: #16568d;
            --kad-primary-10: rgba(29, 111, 181, 0.08);
            --kad-success: #16a34a;
            --kad-danger: #dc2626;
            --kad-page-bg: #f8fafc; /* Fond institutionnel léger */
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

        /* ── Layout ─────────────────────────────────────────────────── */
        .kad-main {
            min-height: 100vh;
        }

        .kad-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* ── Top bar ────────────────────────────────────────────────── */
        .kad-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .kad-topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .kad-btn-export {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--kad-maroon);
            color: #ffffff;
            border-radius: var(--kad-radius-md);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(107, 31, 10, 0.2);
        }

        .kad-btn-export:hover {
            background: var(--kad-maroon-h);
            transform: translateY(-1px);
        }

        .kad-topbar-icon {
            width: 46px;
            height: 46px;
            background: var(--kad-bg);
            border-radius: var(--kad-radius-md);
            border: var(--kad-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--kad-primary);
            flex-shrink: 0;
        }

        .kad-topbar-title {
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--kad-text);
            margin: 0;
        }

        .kad-topbar-sub {
            font-size: 11px;
            color: var(--kad-text-soft);
            margin: 3px 0 0;
            font-style: italic;
        }

        .kad-btn-primary {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: var(--kad-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--kad-radius-md);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
        }

        .kad-btn-primary:hover {
            opacity: 0.9;
        }

        .kad-btn-primary:active {
            transform: scale(0.97);
        }

        /* ── Split ──────────────────────────────────────────────────── */
        .kad-split {
            display: grid;
            grid-template-columns: 230px 1fr;
            gap: 1.25rem;
            align-items: start;
        }

        @media (max-width: 768px) {
            .kad-split {
                grid-template-columns: 1fr;
            }
        }

        /* ── Sidebar ────────────────────────────────────────────────── */
        .kad-sidebar {
            background: var(--kad-bg);
            border-radius: var(--kad-radius-lg);
            border: var(--kad-border);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: sticky;
            top: 1.5rem;
        }

        .kad-sidebar-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--kad-text-muted);
            padding: 0 8px 10px;
        }

        .kad-pulse {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(29, 111, 181, 0.5);
            flex-shrink: 0;
            animation: kadPulse 2s infinite;
        }

        @keyframes kadPulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.25
            }
        }

        .kad-nav {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .kad-tab-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            text-align: left;
        }

        .kad-tab-item:hover {
            background: var(--kad-bg-soft);
        }

        .kad-tab-item.active {
            background: var(--kad-maroon);
            border-color: var(--kad-maroon);
        }

        .kad-tab-item.active .kad-tab-icon,
        .kad-tab-item.active .kad-tab-label,
        .kad-tab-item.active .kad-tab-count {
            color: #ffffff;
        }

        .kad-tab-icon {
            font-size: 16px;
            color: var(--kad-text-muted);
            transition: color 0.15s;
        }

        .kad-tab-item.active .kad-tab-icon {
            color: rgba(255, 255, 255, 0.8);
        }

        .kad-tab-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--kad-text-soft);
            font-style: italic;
            transition: color 0.15s;
        }

        .kad-tab-item.active .kad-tab-label {
            color: #ffffff;
        }

        .kad-tab-count {
            font-size: 9px;
            font-weight: 700;
            background: #e2e8f0;
            color: var(--kad-text-soft);
            padding: 2px 7px;
            border-radius: 20px;
            transition: background 0.15s, color 0.15s;
        }

        .kad-tab-item.active .kad-tab-count {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .kad-sidebar-hint {
            margin-top: 12px;
            padding: 10px;
            background: var(--kad-bg-soft);
            border-radius: var(--kad-radius-sm);
            border: var(--kad-border);
            font-size: 10px;
            color: var(--kad-text-muted);
            font-style: italic;
            text-align: center;
            line-height: 1.5;
        }

        /* ── Panel droit ────────────────────────────────────────────── */
        .kad-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-height: 400px;
        }

        .kad-section-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-left: 1rem;
        }

        .kad-hidden {
            display: none !important;
        }

        /* ── Stats ──────────────────────────────────────────────────── */
        .kad-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        @media (max-width: 900px) {
            .kad-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .kad-stat-card {
            background: var(--kad-bg);
            border-radius: var(--kad-radius-lg);
            border: var(--kad-border);
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .kad-stat-card.kad-stat-featured {
            background: linear-gradient(135deg, var(--kad-maroon) 0%, var(--kad-maroon-h) 100%);
            cursor: pointer;
            border-color: var(--kad-maroon);
            position: relative;
            overflow: hidden;
            transition: opacity 0.2s;
        }

        .kad-stat-card.kad-stat-featured:hover {
            opacity: 0.92;
        }

        .kad-stat-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--kad-text-muted);
            font-style: italic;
        }

        .kad-stat-label-white {
            color: rgba(255, 255, 255, 0.65) !important;
        }

        .kad-stat-label-big {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            font-style: italic;
            line-height: 1.3;
            margin-top: 2px;
        }

        .kad-stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--kad-text);
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .kad-stat-value-primary {
            color: var(--kad-primary);
        }

        .kad-stat-deco {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 64px;
            opacity: 0.1;
            color: #ffffff;
            transition: transform 0.3s;
        }

        .kad-stat-featured:hover .kad-stat-deco {
            transform: rotate(12deg);
        }

        /* ── Table audit ────────────────────────────────────────────── */
        .kad-table-card {
            background: var(--kad-bg);
            border-radius: var(--kad-radius-lg);
            border: var(--kad-border);
            overflow: hidden;
        }

        .kad-table-header {
            padding: 14px 20px;
            border-bottom: var(--kad-border);
            background: var(--kad-bg-soft);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kad-table-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--kad-text-mid);
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kad-table-title i {
            font-size: 18px;
            color: var(--kad-primary);
        }

        .kad-btn-export {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            background: var(--kad-success);
            color: #ffffff;
            border: none;
            border-radius: var(--kad-radius-sm);
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .kad-btn-export:hover {
            opacity: 0.88;
        }

        .kad-table-wrap { overflow-x: hidden; width: 100%; }

        .kad-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kad-table thead th {
            padding: 10px 18px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--kad-text-muted);
            font-style: italic;
            border-bottom: var(--kad-border);
            text-align: left;
        }

        .kad-table tbody tr {
            border-bottom: var(--kad-border);
            transition: background 0.15s;
        }

        .kad-table tbody tr:last-child {
            border-bottom: none;
        }

        .kad-table tbody tr:hover {
            background: var(--kad-bg-soft);
        }

        .kad-table tbody td {
            padding: 11px 18px;
        }

        .kad-td-date {
            font-size: 11px;
            font-weight: 600;
            color: var(--kad-text-soft);
            font-style: italic;
        }

        .kad-td-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--kad-text);
        }

        .kad-td-desc {
            font-size: 12px;
            color: var(--kad-text-soft);
        }

        .kad-action-badge {
            display: inline-block;
            padding: 2px 8px;
            background: var(--kad-primary-10);
            color: var(--kad-primary);
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
        }

        .kad-table-empty {
            text-align: center;
            padding: 3rem;
            font-size: 13px;
            font-weight: 700;
            font-style: italic;
            color: var(--kad-text-muted);
        }

        /* ── Section header dynamique ───────────────────────────────── */
        .kad-section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--kad-bg);
            border-radius: var(--kad-radius-lg);
            border: var(--kad-border);
            border-left: 5px solid var(--kad-primary);
            padding: 20px 24px;
        }

        .kad-section-title {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--kad-text);
            font-style: italic;
            margin: 0 0 4px;
        }

        .kad-section-sub {
            font-size: 11px;
            color: var(--kad-text-soft);
            font-style: italic;
            font-weight: 600;
            margin: 0;
        }

        .kad-section-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--kad-radius-md);
            background: var(--kad-primary-10);
            border: 1px solid rgba(29, 111, 181, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--kad-primary);
            flex-shrink: 0;
        }

        .kad-table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            background: var(--kad-bg-soft);
            border-top: var(--kad-border);
            gap: 1rem;
            flex-wrap: wrap;
        }

        .kad-pagination-info {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            font-style: italic;
            color: var(--kad-text-soft);
        }

        .kad-pagination-links nav div {
            flex-wrap: nowrap;
        }

        .kad-pagination-links svg {
            width: 16px;
            height: 16px;
        }

        .kad-pagination-links span[aria-current="page"] span {
            background: var(--kad-maroon) !important;
            border-color: var(--kad-maroon) !important;
            color: white !important;
        }

        .kad-pagination-links a:hover {
            color: var(--kad-maroon) !important;
        }

        /* ── Menu cards grid ────────────────────────────────────────── */
        .kad-menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        @media (max-width: 700px) {
            .kad-menu-grid {
                grid-template-columns: 1fr;
            }
        }

        .kad-menu-card {
            background: var(--kad-bg);
            border-radius: var(--kad-radius-lg);
            border: var(--kad-border);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: box-shadow 0.2s;
        }

        .kad-menu-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .kad-menu-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .kad-menu-card-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .kad-menu-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--kad-radius-md);
            background: var(--kad-bg-soft);
            border: var(--kad-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--kad-primary);
            flex-shrink: 0;
        }

        .kad-menu-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--kad-text);
            font-style: italic;
            margin: 0 0 5px;
        }

        .kad-menu-route {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            color: var(--kad-text-soft);
            background: var(--kad-bg-soft);
            border: var(--kad-border);
            border-radius: 6px;
            padding: 2px 8px;
            font-style: italic;
        }

        .kad-menu-delete {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--kad-bg-soft);
            border: var(--kad-border);
            border-radius: var(--kad-radius-sm);
            color: var(--kad-text-muted);
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .kad-menu-delete:hover {
            background: #fef2f2;
            color: var(--kad-danger);
            border-color: #fecaca;
        }

        /* Roles */
        .kad-roles-block {
            background: var(--kad-bg-soft);
            border: var(--kad-border);
            border-radius: var(--kad-radius-md);
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .kad-roles-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--kad-text-muted);
            font-style: italic;
            padding-bottom: 6px;
            border-bottom: var(--kad-border);
        }

        .kad-roles-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .kad-role-pill {
            display: inline-block;
            padding: 2px 8px;
            background: var(--kad-bg);
            border: var(--kad-border);
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--kad-text-mid);
            font-style: italic;
        }

        .kad-role-public {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--kad-danger);
        }

        /* Card footer */
        .kad-menu-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: var(--kad-border);
        }

        .kad-menu-order {
            font-size: 10px;
            font-weight: 700;
            color: var(--kad-text-soft);
            font-style: italic;
            text-transform: uppercase;
        }

        .kad-status-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .kad-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .kad-dot-on {
            background: var(--kad-success);
        }

        .kad-dot-off {
            background: var(--kad-danger);
        }

        .kad-status-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            font-style: italic;
        }

        .kad-status-on {
            color: var(--kad-success);
        }

        .kad-status-off {
            color: var(--kad-danger);
        }

        /* Empty state */
        .kad-empty-state {
            grid-column: 1 / -1;
            padding: 3rem;
            text-align: center;
            background: var(--kad-bg-soft);
            border-radius: var(--kad-radius-lg);
            border: 2px dashed #cbd5e1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .kad-empty-icon {
            font-size: 32px;
            color: var(--kad-text-muted);
        }

        .kad-empty-text {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            font-style: italic;
            color: var(--kad-text-muted);
        }

        /* ── Modal ──────────────────────────────────────────────────── */
        .kad-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(3px);
        }

        .kad-modal {
            background: var(--kad-bg);
            border-radius: var(--kad-radius-xl);
            border: var(--kad-border);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .kad-modal-header {
            padding: 20px 24px;
            border-bottom: var(--kad-border);
            background: var(--kad-bg-soft);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .kad-modal-title {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--kad-text);
            font-style: italic;
            margin: 0 0 3px;
        }

        .kad-modal-sub {
            font-size: 11px;
            color: var(--kad-text-soft);
            font-style: italic;
            margin: 0;
        }

        .kad-modal-close {
            width: 34px;
            height: 34px;
            border-radius: var(--kad-radius-sm);
            background: #f1f5f9;
            border: var(--kad-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--kad-text-soft);
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            flex-shrink: 0;
        }

        .kad-modal-close:hover {
            background: #fef2f2;
            color: var(--kad-danger);
        }

        .kad-modal-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .kad-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .kad-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .kad-field-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--kad-text-soft);
            font-style: italic;
            padding-left: 2px;
        }

        .kad-input {
            width: 100%;
            padding: 10px 14px;
            background: var(--kad-bg-soft);
            border: var(--kad-border);
            border-radius: var(--kad-radius-sm);
            font-size: 13px;
            font-weight: 600;
            font-style: italic;
            color: var(--kad-text);
            outline: none;
            transition: border-color 0.2s;
            -webkit-appearance: none;
        }

        .kad-input:focus {
            border-color: var(--kad-primary);
        }

        .kad-roles-check-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            background: var(--kad-bg-soft);
            border: var(--kad-border);
            border-radius: var(--kad-radius-md);
            padding: 12px;
            max-height: 140px;
            overflow-y: auto;
        }

        .kad-check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: var(--kad-radius-sm);
            cursor: pointer;
            transition: background 0.15s;
        }

        .kad-check-item:hover {
            background: var(--kad-bg);
        }

        .kad-checkbox {
            width: 15px;
            height: 15px;
            accent-color: var(--kad-primary);
            cursor: pointer;
        }

        .kad-check-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            font-style: italic;
            color: var(--kad-text-mid);
        }

        .kad-modal-submit {
            width: 100%;
            padding: 12px;
            background: var(--kad-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--kad-radius-md);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-style: italic;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 4px;
        }

        .kad-modal-submit:hover {
            opacity: 0.9;
        }
    </style>


    {{-- ── SCRIPT ───────────────────────────────────────────────────── --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            window.switchSection = function(key) {
                document.querySelectorAll('.kad-section-content').forEach(function(el) {
                    el.classList.add('kad-hidden');
                });
                document.querySelectorAll('.kad-tab-item').forEach(function(el) {
                    el.classList.remove('active');
                });

                var content = document.getElementById('content-' + key);
                var tab = document.getElementById('tab-' + key);

                if (content) content.classList.remove('kad-hidden');
                if (tab) tab.classList.add('active');
            };

            switchSection('supervision');
        });
    </script>
@endsection
