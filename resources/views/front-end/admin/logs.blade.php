@extends('front-end/app/app-home-asset', ['title' => 'Audit Trail - Admin Frontend'])

@section('content')
    <main class="kad-main main-content has-sidebar">
        <div class="kad-inner">

            {{-- ── HEADER ─────────────────────────────────────────── --}}
            <div class="kad-topbar">
                <div class="kad-topbar-left">
                    <div class="kad-topbar-icon">
                        <i class="las la-history"></i>
                    </div>
                    <div>
                        <h2 class="kad-topbar-title">Journal d'Audit Complet</h2>
                        <p class="kad-topbar-sub">Historique détaillé des opérations administratives.</p>
                    </div>
                </div>
                <a href="{{ route('admin.front.logs.export', request()->all()) }}" class="kad-btn-primary" style="background:var(--kad-success)">
                    <i class="las la-file-excel"></i>
                    Exporter les journaux Filtrés
                </a>
            </div>

            {{-- ── FILTERS ────────────────────────────────────────── --}}
            <div class="kad-panel" style="margin-top: 1rem;">
                <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm">
                    <form action="{{ route('admin.front.logs') }}" method="GET" class="flex flex-wrap items-end gap-6">
                        <div class="flex flex-col gap-2 grow min-w-[200px]">
                            <label class="kad-field-label">Collaborateur</label>
                            <select name="user_id" class="kad-input">
                                <option value="">Tous les utilisateurs</option>
                                @foreach($allUsers as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="kad-field-label">Du (Début)</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="kad-input">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="kad-field-label">Au (Fin)</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="kad-input">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="kad-btn-primary">
                                <i class="las la-filter"></i> Filtrer
                            </button>
                            <a href="{{ route('admin.front.logs') }}" class="w-10 h-10 flex items-center justify-center bg-n20 rounded-xl text-n500 hover:bg-n30 transition-all shadow-sm">
                                <i class="las la-times text-xl"></i>
                            </a>
                        </div>
                    </form>
                </div>

                {{-- ── TABLE ──────────────────────────────────────────── --}}
                <div class="kad-table-card mt-2">
                    <div class="kad-table-wrap">
                        <table class="kad-table">
                            <thead>
                                <tr>
                                    <th class="cursor-pointer hover:bg-n10 transition-all">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1">
                                            Horodatage 
                                            @if(request('sort', 'created_at') == 'created_at')
                                                <i class="las la-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} text-primary"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="cursor-pointer hover:bg-n10 transition-all">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'user_id', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1">
                                            Opérateur
                                            @if(request('sort') == 'user_id')
                                                <i class="las la-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} text-primary"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="cursor-pointer hover:bg-n10 transition-all">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'action', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 text-primary">
                                            Action
                                            @if(request('sort') == 'action')
                                                <i class="las la-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Détails de l'opération</th>
                                    <th>Cible (Désignation)</th>
                                    <th>IP Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr class="hover:bg-n10/50">
                                        <td class="kad-td-date">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td class="kad-td-name text-primary">{{ $log->user->name ?? 'Système' }}</td>
                                        <td>
                                            <span class="kad-action-badge">{{ $log->action }}</span>
                                        </td>
                                        <td class="kad-td-desc break-words max-w-xs">{{ $log->description }}</td>
                                        <td class="kad-td-desc break-words max-w-xs text-primary font-bold">
                                            @if($log->target_type)
                                                {{ class_basename($log->target_type) }} #{{ $log->target_id }}
                                                @php
                                                    $target = $log->target_type::find($log->target_id);
                                                    $name = null;
                                                    if($target) {
                                                        $name = $target->name ?? $target->title ?? $target->label ?? $target->display_name ?? null;
                                                    }
                                                @endphp
                                                @if($name)
                                                    <br><span class="text-[10px] text-n400 italic">({{ $name }})</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-[10px] font-bold text-n400 italic">{{ $log->ip_address }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ── PAGINATION ────────────────────────────────────── --}}
                    <div class="px-6 py-6 border-t border-n30 flex flex-col md:flex-row justify-between items-center gap-6 bg-n10/20">
                        <div class="text-[10px] text-n500 font-bold uppercase tracking-widest italic">
                            Page <span class="text-primary">{{ $logs->currentPage() }}</span> sur <span class="text-primary">{{ $logs->lastPage() }}</span>
                            <span class="mx-2 opacity-30">|</span>
                            Total : <span class="text-primary font-black">{{ $logs->total() }}</span> enregistrements
                        </div>
                        
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-2xl border border-n30 shadow-sm hover:border-primary transition-all">
                                <label class="text-[9px] font-extrabold uppercase text-n500 italic">Aller à</label>
                                <input type="number" id="jumpPage" min="1" max="{{ $logs->lastPage() }}" value="{{ $logs->currentPage() }}" 
                                    class="w-12 bg-transparent border-none outline-none font-black text-sm text-primary text-center">
                                <button onclick="window.location.href = '{{ request()->fullUrlWithQuery(['page' => '']) }}'.replace('page=', 'page=' + document.getElementById('jumpPage').value)"
                                    class="w-8 h-8 flex items-center justify-center bg-primary text-white rounded-xl hover:scale-110 transition-all shadow-md">
                                    <i class="las la-arrow-right"></i>
                                </button>
                            </div>
                            <div class="kad-pagination">
                                {{ $logs->links() }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

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

        .kad-main { min-height: 100vh; background: var(--kad-page-bg); font-family: 'Inter', sans-serif; }
        .kad-inner { max-width: 1280px; margin: 0 auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; }
        .kad-topbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .kad-topbar-left { display: flex; align-items: center; gap: 14px; }
        .kad-topbar-icon { width: 46px; height: 46px; background: var(--kad-bg); border-radius: var(--kad-radius-md); border: var(--kad-border); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--kad-primary); box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .kad-topbar-title { font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: var(--kad-maroon); margin: 0; font-style: italic; }
        .kad-topbar-sub { font-size: 11px; color: var(--kad-text-soft); margin-top: 2px; font-weight: 600; font-style: italic; }
        .kad-btn-primary { display: flex; align-items: center; gap: 8px; padding: 10px 20px; background: var(--kad-primary); color: #fff; border-radius: var(--kad-radius-md); font-size: 11px; font-weight: 800; text-transform: uppercase; font-style: italic; transition: all 0.2s; box-shadow: 0 4px 12px var(--kad-primary-10); }
        .kad-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        
        .kad-panel { display: flex; flex-direction: column; gap: 1.5rem; }
        .kad-field-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--kad-text-muted); font-style: italic; letter-spacing: 0.1em; padding-left: 2px; }
        .kad-input { width: 100%; padding: 10px 16px; background: var(--kad-bg-soft); border: var(--kad-border); border-radius: var(--kad-radius-md); font-size: 13px; font-weight: 700; font-style: italic; color: var(--kad-text); outline: none; transition: all 0.2s; }
        .kad-input:focus { border-color: var(--kad-primary); box-shadow: 0 0 0 3px var(--kad-primary-10); }
        
        .kad-table-card { background: var(--kad-bg); border-radius: var(--kad-radius-xl); border: var(--kad-border); shadow: 0 10px 30px rgba(0,0,0,0.04); overflow: hidden; }
        .kad-table-wrap { overflow-x: hidden; width: 100%; }
        .kad-table { width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed; }
        .kad-table thead th { padding: 16px 24px; background: #f8fafc; font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--kad-text-muted); border-bottom: var(--kad-border); letter-spacing: 0.1em; }
        .kad-table tbody tr { border-bottom: var(--kad-border); transition: all 0.15s; }
        .kad-table tbody tr:hover { background: #f8fafc; }
        .kad-table tbody td { padding: 14px 24px; font-size: 13px; font-weight: 600; font-style: italic; word-break: break-word; vertical-align: middle; }
        .kad-td-date { font-size: 11px; color: var(--kad-text-soft); }
        .kad-td-name { font-weight: 800; font-style: italic; }
        .kad-td-desc { font-size: 12px; color: var(--kad-text-soft); line-height: 1.5; }
        .kad-action-badge { display: inline-block; padding: 2px 10px; background: var(--kad-primary-10); color: var(--kad-primary); font-size: 9px; font-weight: 800; text-transform: uppercase; border-radius: 6px; }

        input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; filter: invert(0.2); }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.addEventListener('click', function() { this.showPicker(); });
            });
        });
    </script>
@endsection
