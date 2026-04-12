@extends('front-end/app/app-home-asset', ['title' => 'Gestion des VL - Asset Manager'])

@section('content')
    <main class="kad-main main-content has-sidebar">
        <div class="kad-inner">

            {{-- ── HEADER ─────────────────────────────────────────── --}}
            <div class="kad-topbar">
                <div class="kad-topbar-left">
                    <div class="kad-topbar-icon" style="background: var(--kad-primary-10);">
                        <i class="las la-chart-area" style="color: var(--kad-primary)"></i>
                    </div>
                    <div>
                        <h2 class="kad-topbar-title">Valeurs Liquidatives</h2>
                        <p class="kad-topbar-sub">Pilotage et mise à jour des performances FCP.</p>
                    </div>
                </div>
                <button onclick="openVlModal()" class="kad-btn-primary">
                    <i class="las la-plus-circle"></i>
                    Nouvelle VL
                </button>
            </div>

            {{-- ── FILTERS & SELECTION ────────────────────────────── --}}
            <div class="kad-panel" style="margin-top: 1rem;">
                <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm">
                    <form action="{{ route('asset-manager.vls') }}" method="GET" class="flex flex-wrap items-end gap-6">
                        <div class="flex flex-col gap-2 grow min-w-[250px]">
                            <label class="kad-field-label">Produit / Fonds</label>
                            <select name="product_id" class="kad-input" onchange="this.form.submit()">
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}"
                                        {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                        {{ $p->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <p class="text-[11px] text-n400 font-bold italic mb-2">Sélectionnez un produit pour voir son
                                historique complet.</p>
                        </div>
                    </form>
                </div>

                {{-- ── TABLE ──────────────────────────────────────────── --}}
                <div class="kad-table-card mt-2">
                    <div class="kad-table-wrap">
                        <table class="kad-table">
                            <thead>
                                <tr>
                                    <th>Date de la VL</th>
                                    <th>Produit</th>
                                    <th class="text-right">Valeur Unitaire (XAF)</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vls as $vl)
                                    <tr class="hover:bg-n10/50">
                                        <td class="kad-td-date">
                                            <div class="flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                                                {{ \Carbon\Carbon::parse($vl->date_vl)->translatedFormat('d F Y') }}
                                            </div>
                                        </td>
                                        <td class="kad-td-name text-primary uppercase text-[11px]">
                                            {{ $vl->product->title ?? 'N/A' }}</td>
                                        <td class="text-right font-black text-n800">
                                            {{ number_format($vl->vl, 2, ',', ' ') }}
                                        </td>
                                        <td class="text-center">
                                            <span class="kad-action-badge"
                                                style="background: rgba(22, 163, 74, 0.1); color: #16a34a;">Validé</span>
                                        </td>
                                        <td class="text-right">
                                            <button
                                                onclick="editVl({{ json_encode(['id' => $vl->id, 'product_id' => $vl->product_id, 'date_vl' => $vl->date_vl, 'vl' => $vl->vl]) }})"
                                                class="w-8 h-8 rounded-lg bg-bg10 text-n500 hover:bg-primary/10 hover:text-primary transition-all">
                                                <i class="las la-pen"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ── PAGINATION ────────────────────────────────────── --}}
                    <div
                        class="px-6 py-6 border-t border-n30 flex flex-col md:flex-row justify-between items-center gap-6 bg-n10/20">
                        <div class="text-[10px] text-n500 font-bold uppercase tracking-widest italic">
                            Page <span class="text-primary">{{ $vls->currentPage() }}</span> sur <span
                                class="text-primary">{{ $vls->lastPage() }}</span>
                        </div>
                        <div class="kad-pagination">
                            {{ $vls->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- ── MODAL: NOUVELLE VL (Design Premium) ────────────────── --}}
    <div id="vlModal" class="vlm-overlay vlm-hidden">
        <div class="vlm-modal">
            <div class="vlm-header">
                <div class="vlm-header-left">
                    <div class="vlm-header-icon"><i class="las la-chart-line"></i></div>
                    <div>
                        <h3 class="vlm-header-title">Valeur Liquidative</h3>
                        <p class="vlm-header-sub">Mise à jour du cours</p>
                    </div>
                </div>
                <button type="button" onclick="closeVlModal()" class="vlm-close"><i class="las la-times"></i></button>
            </div>

            <form action="{{ route('asset-manager.vls.store') }}" method="POST" class="vlm-body">
                @csrf
                <div class="vlm-field">
                    <label class="vlm-label">Produit</label>
                    <select name="product_id" required class="vlm-input">
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                {{ $p->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="vlm-field">
                    <label class="vlm-label">Date effective</label>
                    <input type="date" name="date_vl" value="{{ date('Y-m-d') }}" required class="vlm-input">
                </div>

                <div class="vlm-divider"></div>

                <div class="vlm-amount-wrap">
                    <span class="vlm-amount-lbl">Valeur liquidative (VL)</span>
                    <div class="vlm-amount-row">
                        <input type="number" step="0.000001" name="vl" required placeholder="0.000000" class="vlm-amount-input">
                        <span class="vlm-amount-unit">XAF</span>
                    </div>
                    <span class="vlm-amount-hint">6 décimales autorisées · ex : 11 293,512400</span>
                </div>

                <div class="vlm-actions">
                    <button type="button" onclick="closeVlModal()" class="vlm-btn-cancel">Annuler</button>
                    <button type="submit" class="vlm-btn-submit">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="none"><path d="M3 8l4 4 6-6" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── SCRIPTS ──────────────────────────────────────────────── --}}
    <script>
        function openVlModal() {
            var overlay = document.getElementById('vlModal');
            var modal = overlay.querySelector('.vlm-modal');
            overlay.classList.remove('vlm-hidden');
            overlay.style.display = 'flex';
            requestAnimationFrame(function() {
                modal.classList.add('vlm-modal-visible');
            });
        }

        function closeVlModal() {
            var overlay = document.getElementById('vlModal');
            var modal = overlay.querySelector('.vlm-modal');
            modal.classList.remove('vlm-modal-visible');
            setTimeout(function() {
                overlay.style.display = 'none';
                overlay.classList.add('vlm-hidden');
                overlay.querySelector('form').reset();
            }, 260);
        }

        function editVl(data) {
            const modal = document.getElementById('vlModal');
            const form = modal.querySelector('form');
            form.querySelector('[name="product_id"]').value = data.product_id;
            form.querySelector('[name="date_vl"]').value = data.date_vl;
            form.querySelector('[name="vl"]').value = data.vl;
            openVlModal();
        }

        document.getElementById('vlModal').addEventListener('click', function(e) {
            if (e.target === this) closeVlModal();
        });

        // Force l'ouverture du calendrier au clic
        document.addEventListener('click', function(e) {
            if (e.target.matches('input[type="date"]')) {
                try {
                    e.target.showPicker();
                } catch (err) {
                    console.log('Sélecteur natif non supporté, focus simple.');
                }
            }
        });
    </script>

    {{-- ── STYLES ─────────────────────────────────────────────── --}}
    <style>
        #vlModal {
            --vlm-maroon: #6b1f0a; 
            --vlm-maroon-h: #7a2e0e; 
            --vlm-maroon-50: #fdf3f0; 
            --vlm-maroon-100: #f5d4c8;
            --kad-primary: #1d6fb5; 
            --kad-text-muted: #94a3b8;
        }

        /* Modal Specific Styles */
        #vlModal { 
            position: fixed; 
            inset: 0; 
            z-index: 9999; 
            display: none; 
            align-items: center; 
            justify-content: center; 
            padding: 1rem; 
            background: rgba(15, 23, 42, 0.7); 
            backdrop-filter: blur(8px); 
        }
        
        #vlModal.vlm-hidden { display: none !important; }
        
        .vlm-modal { 
            background: #fff; 
            border-radius: 18px; 
            width: 100%; 
            max-width: 440px; 
            overflow: hidden; 
            box-shadow: 0 24px 60px rgba(0,0,0,0.3); 
            transform: scale(0.95); 
            opacity: 0; 
            transition: transform 0.26s ease, opacity 0.26s ease; 
            display: flex;
            flex-direction: column;
        }
        
        .vlm-modal-visible { transform: scale(1) !important; opacity: 1 !important; }
        
        .vlm-header { 
            background: var(--vlm-maroon) !important; 
            padding: 20px 24px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            color: white;
        }
        
        .vlm-header-left { display: flex; align-items: center; gap: 12px; }
        .vlm-header-icon { width: 36px; height: 36px; border-radius: 8px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
        .vlm-header-title { font-size: 14px; font-weight: 800; text-transform: uppercase; color: #fff; margin: 0; letter-spacing: 0.5px; }
        .vlm-header-sub { font-size: 10px; color: rgba(255,255,255,0.6); font-style: italic; margin: 2px 0 0; }
        .vlm-close { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.1); border: none; cursor: pointer; color: #fff; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .vlm-close:hover { background: rgba(255,255,255,0.2); }
        
        .vlm-body { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
        .vlm-field { display: flex; flex-direction: column; gap: 6px; }
        .vlm-label { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; font-style: italic; }
        .vlm-input { 
            width: 100%; 
            padding: 12px 16px; 
            background: #f8fafc; 
            border: 1px solid #e2e8f0 !important; 
            border-radius: 10px; 
            font-size: 14px; 
            font-weight: 600; 
            outline: none; 
            transition: all 0.2s; 
            color: #1e293b;
        }
        
        .vlm-input:focus { border-color: var(--vlm-maroon) !important; background: white; box-shadow: 0 0 0 3px var(--vlm-maroon-50); }
        
        .vlm-input[type="date"] {
            cursor: pointer;
        }

        /* Assure que l'icône calendrier est visible sur Chrome/Edge/Safari */
        .vlm-input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            border-radius: 4px;
            padding: 2px;
            filter: invert(0.2); /* Pour qu'il soit bien visible */
        }
        
        .vlm-divider { border-top: 1px dashed #e2e8f0; margin: 4px 0; }
        
        .vlm-amount-wrap { background: var(--vlm-maroon-50); border: 1px solid var(--vlm-maroon-100); border-radius: 14px; padding: 18px; }
        .vlm-amount-lbl { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--vlm-maroon); opacity: 0.6; font-style: italic; }
        .vlm-amount-row { display: flex; align-items: baseline; gap: 6px; margin-top: 4px; }
        .vlm-amount-input { flex: 1; background: transparent; border: none; outline: none; font-size: 32px; font-weight: 900; color: var(--vlm-maroon); font-style: italic; width: 100%; }
        .vlm-amount-unit { font-size: 14px; font-weight: 800; color: var(--vlm-maroon); opacity: 0.5; }
        .vlm-amount-hint { font-size: 10px; color: var(--vlm-maroon); opacity: 0.4; font-style: italic; margin-top: 4px; display: block; }
        
        .vlm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 8px; }
        .vlm-btn-cancel { padding: 12px; border-radius: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; text-transform: uppercase; cursor: pointer; color: #64748b; transition: all 0.2s; }
        .vlm-btn-cancel:hover { background: #e2e8f0; color: #1e293b; }
        
        .vlm-btn-submit { padding: 12px; border-radius: 10px; background: var(--vlm-maroon); border: none; font-size: 12px; font-weight: 800; text-transform: uppercase; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; }
        .vlm-btn-submit:hover { background: var(--vlm-maroon-h); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(107,31,10,0.2); }

        .kad-main {
            --kad-maroon: #6b1f0a; --kad-primary: #1d6fb5; --kad-primary-10: rgba(29, 111, 181, 0.08);
            --kad-page-bg: #f8fafc; --kad-bg: #ffffff; --kad-bg-soft: #f1f5f9; --kad-border: 1px solid #e2e8f0;
            --kad-text: #1e293b; --kad-text-soft: #64748b; --kad-text-muted: #94a3b8;
            --kad-radius-md: 12px; --kad-radius-xl: 20px;
        }

        .kad-main { min-height: 100vh; background: var(--kad-page-bg); font-family: 'Inter', sans-serif; }
        .kad-inner { max-width: 1280px; margin: 0 auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; }
        .kad-topbar { display: flex; justify-content: space-between; align-items: center; }
        .kad-topbar-left { display: flex; align-items: center; gap: 14px; }
        .kad-topbar-icon { width: 46px; height: 46px; background: var(--kad-bg); border-radius: var(--kad-radius-md); border: var(--kad-border); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .kad-topbar-title { font-size: 18px; font-weight: 800; text-transform: uppercase; color: var(--kad-maroon); margin: 0; font-style: italic; }
        .kad-topbar-sub { font-size: 11px; color: var(--kad-text-soft); font-weight: 600; font-style: italic; }
        .kad-btn-primary { display: flex; align-items: center; gap: 8px; padding: 10px 24px; background: var(--kad-primary); color: #fff; border-radius: var(--kad-radius-md); font-size: 11px; font-weight: 800; text-transform: uppercase; font-style: italic; border:none; cursor: pointer; }
        .kad-input { width: 100%; padding: 10px 16px; background: var(--kad-bg-soft); border: var(--kad-border); border-radius: var(--kad-radius-md); font-size: 13px; font-weight: 700; font-style: italic; outline: none; }
        .kad-field-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--kad-text-muted); font-style: italic; }
        .kad-table-card { background: var(--kad-bg); border-radius: var(--kad-radius-xl); border: var(--kad-border); overflow: hidden; }
        .kad-table { width: 100%; border-collapse: collapse; }
        .kad-table thead th { padding: 16px 24px; background: #f8fafc; font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--kad-text-muted); border-bottom: var(--kad-border); text-align: left; }
        .kad-table tbody td { padding: 14px 24px; font-size: 13px; font-weight: 600; border-bottom: var(--kad-border); }
        .kad-action-badge { padding: 2px 10px; font-size: 9px; font-weight: 800; text-transform: uppercase; border-radius: 6px; }
    </style>
@endsection
