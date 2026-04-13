@extends('front-end/app/app-home-asset', ['title' => 'Audit des VL - Compliance'])

@section('content')
    <main class="kad-main main-content has-sidebar">
        <div class="kad-inner">

            {{-- ── HEADER ─────────────────────────────────────────── --}}
            <div class="kad-topbar">
                <div class="kad-topbar-left">
                    <div class="kad-topbar-icon" style="background: rgba(234, 179, 8, 0.1);">
                        <i class="las la-shield-alt" style="color: #854d0e;"></i>
                    </div>
                    <div>
                        <h2 class="kad-topbar-title">Audit des Valeurs Liquidatives</h2>
                        <p class="kad-topbar-sub">Supervision et traçabilité des performances fonds.</p>
                    </div>
                </div>
            </div>

            {{-- ── EXPORT PANEL ──────────────────────────────────── --}}
            <div class="kad-panel" style="margin-top: 1rem;">
                <div class="bg-white p-6 rounded-3xl border border-n30 shadow-sm">
                    <h4 class="text-[10px] font-black uppercase text-n500 mb-4 tracking-widest italic">
                        <i class="las la-file-export text-lg text-success"></i> Exportation des données d'audit
                    </h4>
                    <form action="{{ route('compliance.export') }}" method="GET" class="flex flex-wrap items-end gap-6">
                        <input type="hidden" name="type" value="vls">
                        
                        <div class="flex flex-col gap-2 grow min-w-[300px]">
                            <label class="kad-field-label">Produits à inclure</label>
                            <div class="flex flex-wrap gap-2 p-3 bg-n10 rounded-xl border border-n30">
                                @foreach($products as $p)
                                    <label class="flex items-center gap-2 px-3 py-1 bg-white border border-n30 rounded-lg cursor-pointer hover:border-primary transition-all">
                                        <input type="checkbox" name="product_ids[]" value="{{ $p->id }}" checked class="accent-primary">
                                        <span class="text-[10px] font-bold">{{ $p->title }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kad-field-label">Période du</label>
                            <input type="date" name="start_date" class="kad-input">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kad-field-label">au</label>
                            <input type="date" name="end_date" value="{{ date('Y-m-d') }}" class="kad-input">
                        </div>

                        <button type="submit" class="kad-btn-primary" style="background: var(--kad-success)">
                            <i class="las la-download"></i> Exporter
                        </button>
                    </form>
                </div>

                {{-- ── TABLE ──────────────────────────────────────────── --}}
                <div class="kad-table-card mt-2">
                    <div class="p-6 border-b border-n30 bg-n10/30 flex justify-between items-center">
                        <form action="{{ route('compliance.vl-history') }}" method="GET" class="flex items-center gap-4">
                            <label class="text-[10px] font-black uppercase text-n500 italic">Filtrer par Produit :</label>
                            <select name="product_id" class="kad-input" style="width: auto; min-width: 220px;" onchange="this.form.submit()">
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                        {{ $p->title }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="kad-table-wrap">
                        <table class="kad-table">
                            <thead>
                                <tr>
                                    <th>Date de la Valeur</th>
                                    <th>Produit</th>
                                    <th class="text-right">Valeur Liquidative (XAF)</th>
                                    <th class="text-right">Enregistrement Système</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vls as $v)
                                    <tr class="hover:bg-n10/50">
                                        <td class="kad-td-date">
                                            <div class="flex items-center gap-3">
                                                <i class="las la-calendar text-primary"></i>
                                                {{ \Carbon\Carbon::parse($v->date_vl)->translatedFormat('d F Y') }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="px-2 py-0.5 rounded bg-n20 text-n700 text-[9px] font-black uppercase tracking-widest">
                                                {{ $v->product->title ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-right font-black text-primary text-lg">
                                            {{ number_format($v->vl, 2, ',', ' ') }}
                                        </td>
                                        <td class="text-right text-[10px] text-n400 font-bold italic">
                                            MAJ le {{ $v->created_at->format('d/m/Y à H:i') }}
                                        </td>
                                        <td class="text-right">
                                            <form action="{{ route('compliance.vl.delete', $v->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette valeur liquidative ? Cette action est irréversible.')" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-danger hover:text-danger/70 transition-all text-xl" title="Supprimer">
                                                    <i class="las la-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ── PAGINATION ────────────────────────────────────── --}}
                    @if ($vls->hasPages())
                        <div class="px-6 py-6 border-t border-n30 flex justify-center bg-n10/20">
                            {{ $vls->appends(request()->input())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <style>
        .kad-main {
            --kad-maroon: #6b1f0a;
            --kad-primary: #1d6fb5;
            --kad-primary-10: rgba(29, 111, 181, 0.08);
            --kad-success: #16a34a;
            --kad-page-bg: #f8fafc;
            --kad-bg: #ffffff;
            --kad-bg-soft: #f1f5f9;
            --kad-border: 1px solid #e2e8f0;
            --kad-text: #1e293b;
            --kad-text-soft: #64748b;
            --kad-text-muted: #94a3b8;
            --kad-radius-md: 12px;
            --kad-radius-xl: 20px;
        }

        .kad-main { min-height: 100vh; background: var(--kad-page-bg); font-family: 'Inter', sans-serif; }
        .kad-inner { max-width: 1280px; margin: 0 auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; }
        .kad-topbar { display: flex; justify-content: space-between; align-items: center; }
        .kad-topbar-left { display: flex; align-items: center; gap: 14px; }
        .kad-topbar-icon { width: 46px; height: 46px; background: var(--kad-bg); border-radius: var(--kad-radius-md); border: var(--kad-border); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .kad-topbar-title { font-size: 18px; font-weight: 800; text-transform: uppercase; color: var(--kad-maroon); margin: 0; font-style: italic; }
        .kad-topbar-sub { font-size: 11px; color: var(--kad-text-soft); font-weight: 600; font-style: italic; }
        
        .kad-btn-primary { display: flex; align-items: center; gap: 8px; padding: 10px 24px; background: var(--kad-primary); color: #fff; border-radius: var(--kad-radius-md); font-size: 11px; font-weight: 800; text-transform: uppercase; font-style: italic; transition: all 0.2s; border:none; cursor: pointer;}
        
        .kad-field-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--kad-text-muted); font-style: italic; padding-left: 2px; }
        .kad-input { width: 100%; padding: 10px 16px; background: var(--kad-bg-soft); border: var(--kad-border); border-radius: var(--kad-radius-md); font-size: 13px; font-weight: 700; font-style: italic; color: var(--kad-text); outline: none; }

        .kad-table-card { background: var(--kad-bg); border-radius: var(--kad-radius-xl); border: var(--kad-border); overflow: hidden; }
        .kad-table { width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed; }
        .kad-table thead th { padding: 16px 24px; background: #f8fafc; font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--kad-text-muted); border-bottom: var(--kad-border); }
        .kad-table tbody td { padding: 14px 24px; font-size: 13px; font-weight: 600; border-bottom: var(--kad-border); vertical-align: middle; word-break: break-all; }
    </style>
@endsection
