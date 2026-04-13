@extends('front-end/app/app-home-asset', ['title' => 'Habilitations - Admin Frontend'])

@section('content')
    <main class="kad-main main-content has-sidebar">
        <div class="kad-inner">

            {{-- ── HEADER ─────────────────────────────────────────── --}}
            <div class="kad-topbar">
                <div class="kad-topbar-left">
                    <div class="kad-topbar-icon">
                        <i class="las la-users-cog"></i>
                    </div>
                    <div>
                        <h2 class="kad-topbar-title">Habilitations Équipe</h2>
                        <p class="kad-topbar-sub">Gérez les accès et les responsabilités de vos collaborateurs.</p>
                    </div>
                </div>
                <button onclick="openCreateUserModal()" class="kad-btn-primary">
                    <i class="las la-user-plus"></i>
                    Nouveau Collaborateur
                </button>
            </div>

            {{-- ── ROLES DECO ────────────────────────────────────── --}}
            <div class="kad-stats-grid">
                <div class="kad-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background:var(--kad-danger)"></span>
                        <span class="text-[10px] font-black uppercase text-n700">Audit & Admin</span>
                    </div>
                    <p class="text-[10px] text-n500 italic leading-relaxed">Contrôle total et gestion des comptes.</p>
                </div>
                <div class="kad-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background:var(--kad-primary)"></span>
                        <span class="text-[10px] font-black uppercase text-n700">Gestion de Portefeuille</span>
                    </div>
                    <p class="text-[10px] text-n500 italic leading-relaxed">Asset Managers et opérations courantes.</p>
                </div>
                <div class="kad-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background:var(--kad-success)"></span>
                        <span class="text-[10px] font-black uppercase text-n700">Vérification Réglementaire</span>
                    </div>
                    <p class="text-[10px] text-n500 italic leading-relaxed">Compliance et conformité des dossiers.</p>
                </div>
                <div class="kad-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background:#eab308"></span>
                        <span class="text-[10px] font-black uppercase text-n700">Direction Générale</span>
                    </div>
                    <p class="text-[10px] text-n500 italic leading-relaxed">Pilotage stratégique et KPIs globaux.</p>
                </div>
            </div>

            {{-- ── FILTER & TABLE ────────────────────────────────── --}}
            <div class="kad-panel">
                <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm">
                    <form action="{{ route('admin.front.users') }}" method="GET" class="flex gap-4">
                        <div class="relative flex-1">
                            <i class="las la-search absolute left-4 top-1/2 -translate-y-1/2 text-n400 text-lg"></i>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Rechercher par nom ou email..." class="kad-input pl-12"
                                style="background: #fff; border: 1px solid #cbd5e1;">
                        </div>
                        <button type="submit" class="kad-btn-primary" style="background:var(--kad-maroon)">
                            Filtrer l'équipe
                        </button>
                    </form>
                </div>

                <div class="kad-table-card mt-2">
                    <div class="kad-table-wrap">
                        <table class="kad-table">
                            <thead>
                                <tr>
                                    <th class="px-8">Collaborateur</th>
                                    <th class="text-center">Profil d'Accès</th>
                                    <th class="text-center">Dernière activité</th>
                                    <th class="text-center">Modifier le rôle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="px-8 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="flex flex-col">
                                                    <span class="kad-td-name">{{ $user->name }}</span>
                                                    <small
                                                        class="text-ms text-[10px] text-n400 font-bold ">{{ $user->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $roleClass = match ((int) $user->role_id) {
                                                    1
                                                        => 'background: rgba(220, 38, 38, 0.1); color: var(--kad-danger);',
                                                    3
                                                        => 'background: rgba(29, 111, 181, 0.1); color: var(--kad-primary);',
                                                    4
                                                        => 'background: rgba(22, 163, 74, 0.1); color: var(--kad-success);',
                                                    5 => 'background: rgba(234, 179, 8, 0.1); color: #854d0e;',
                                                    default => 'background: #f1f5f9; color: #64748b;',
                                                };
                                            @endphp
                                            <span style="{{ $roleClass }}"
                                                class="px-3 py-1 text-[10px] rounded-lg font-black text-sm italic tracking-wider">
                                                {{ $user->role->display_name ?? 'Client' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex flex-col items-center">
                                                <span
                                                    class="text-[11px] text-n600 text-sm font-bold">{{ $user->updated_at->translatedFormat('d M Y') }}</span>
                                                <span
                                                    class="text-[9px] text-n400 text-sm  font-black">{{ $user->updated_at->format('H:i') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('admin.front.update-role', $user->id) }}" method="POST"
                                                class="flex items-center justify-center gap-2">
                                                @csrf
                                                <select name="role_id" onchange="this.form.submit()" class="kad-input"
                                                    style="width: auto; padding-top:5px; padding-bottom:5px; font-size:11px; background:#fff">
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}"
                                                            {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                                            {{ $role->display_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div
                                                    class="w-8 h-8 rounded-full bg-success/10 text-success flex items-center justify-center text-lg">
                                                    <i class="las la-check-circle"></i>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="py-4">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </main>

    <!-- Modal: Create User -->
    <div id="createUserModal" class="kad-modal-overlay kad-hidden">
        <div class="kad-modal">
            <div class="kad-modal-header">
                <h3 class="kad-modal-title">Nouveau Collaborateur</h3>
                <button onclick="closeCreateUserModal()" class="kad-modal-close"><i class="las la-times"></i></button>
            </div>

            <form action="{{ route('admin.front.store-user') }}" method="POST" class="kad-modal-body italic">
                @csrf
                <div class="kad-field">
                    <label class="kad-field-label">Nom Complet</label>
                    <input type="text" name="name" required placeholder="Ex: Jean Dupont" class="kad-input">
                </div>
                <div class="kad-field">
                    <label class="kad-field-label">Adresse Email Professionnelle</label>
                    <input type="email" name="email" required placeholder="jean.dupont@kori.com" class="kad-input">
                </div>
                <div class="kad-field">
                    <label class="kad-field-label">Rôle de départ</label>
                    <select name="role_id" class="kad-input uppercase">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCreateUserModal()" class="kad-btn-primary"
                        style="background:var(--kad-bg-soft); color:var(--kad-text-soft); border:none">Annuler</button>
                    <button type="submit" class="kad-btn-primary flex-1 justify-center" style="border:none">Créer le
                        compte</button>
                </div>

                <p class="text-[9px] text-n400 text-center font-bold uppercase">Le mot de passe par défaut est : Kori@2026
                </p>
            </form>
        </div>
    </div>

    <style>
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
            --kad-text: #1e293b;
            --kad-text-soft: #64748b;
            --kad-text-muted: #94a3b8;
            --kad-radius-sm: 8px;
            --kad-radius-md: 12px;
            --kad-radius-lg: 16px;
            --kad-radius-xl: 20px;
        }

        .kad-main {
            min-height: 100vh;
            background: var(--kad-page-bg);
            font-family: 'Inter', sans-serif;
        }

        .kad-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

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

        .kad-topbar-icon {
            width: 46px;
            height: 46px;
            background: var(--kad-bg);
            border-radius: var(--kad-radius-md);
            border: var(--kad-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--kad-primary);
        }

        .kad-topbar-title {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--kad-maroon);
            margin: 0;
            italic;
        }

        .kad-topbar-sub {
            font-size: 11px;
            color: var(--kad-text-soft);
            margin-top: 2px;
            font-weight: 600;
            font-style: italic;
        }

        .kad-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--kad-primary);
            color: #fff;
            border-radius: var(--kad-radius-md);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            font-style: italic;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .kad-btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .kad-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }

        .kad-stat-card {
            background: var(--kad-bg);
            border: var(--kad-border);
            border-radius: var(--kad-radius-lg);
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .kad-panel {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .kad-input {
            width: 100%;
            padding: 10px 16px;
            background: #f8fafc;
            border: var(--kad-border);
            border-radius: var(--kad-radius-md);
            font-size: 13px;
            font-weight: 700;
            font-style: italic;
            color: var(--kad-text);
            outline: none;
            transition: all 0.2s;
        }

        .kad-input:focus {
            border-color: var(--kad-primary);
        }

        .kad-table-card {
            background: var(--kad-bg);
            border-radius: var(--kad-radius-xl);
            border: var(--kad-border);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .kad-table-wrap {
            overflow-x: hidden;
            width: 100%;
        }

        .kad-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            table-layout: fixed;
        }

        .kad-table thead th {
            padding: 16px 24px;
            background: #f8fafc;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--kad-text-muted);
            border-bottom: var(--kad-border);
            letter-spacing: 0.1em;
        }

        .kad-table tbody tr {
            border-bottom: var(--kad-border);
            transition: all 0.15s;
        }

        .kad-table tbody tr:hover {
            background: #f8fafc;
        }

        .kad-table tbody td {
            padding: 14px 24px;
            font-size: 13px;
            font-weight: 600;
            font-style: italic;
            word-break: break-word;
            vertical-align: middle;
        }

        p.text-\[9px\] {
            font-size: 9px;
        }

        .kad-modal-overlay .flex.gap-3.pt-2 button {
            background: #531d09;
            margin-right: 5px;
            display: block;
        }

        .kad-modal-overlay .flex.gap-3.pt-2 button:first-child {
            border: 1px solid #ebb009 !important;
            color: #ebb009 !important;
        }

        .kad-td-name {
            font-weight: 800;
            color: var(--kad-text);
            font-style: italic;
        }

        .kad-modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999; display: flex; align-items: flex-start; justify-content: center; padding: 3rem 1rem; backdrop-filter: blur(4px); overflow-y: auto; }
        .kad-modal { background: var(--kad-bg); border-radius: var(--kad-radius-xl); border: var(--kad-border); width: 100%; max-width: 450px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); margin-bottom: 2rem; position: relative; }

        .kad-modal-header {
            padding: 20px 24px;
            border-bottom: var(--kad-border);
            background: var(--kad-bg-soft);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kad-modal-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--kad-maroon);
            font-style: italic;
        }

        .kad-modal-close {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: var(--kad-border);
            cursor: pointer;
            background: #fff;
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
            gap: 6px;
        }

        .kad-field-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--kad-text-muted);
            italic;
            padding-left: 2px;
        }

        .kad-hidden {
            display: none !important;
        }
    </style>

    <script>
        function openCreateUserModal() {
            document.getElementById('createUserModal').classList.remove('kad-hidden');
        }

        function closeCreateUserModal() {
            document.getElementById('createUserModal').classList.add('kad-hidden');
        }
    </script>
@endsection
