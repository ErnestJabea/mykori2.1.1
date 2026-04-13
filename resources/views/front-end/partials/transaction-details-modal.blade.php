{{-- ============================================================
     KORI Asset Management — Modal Détails Transaction (v2)
     CSS entièrement scopé sous #transactionDetailsModal
     Aucune classe Tailwind — zéro fuite de style
     ============================================================ --}}

{{-- ── MODAL OVERLAY ─────────────────────────────────────────── --}}
<div id="transactionDetailsModal">

    <div id="ktm-content">

        {{-- ── HEADER ──────────────────────────────────────────── --}}
        <div id="ktm-header">
            <div>
                <h3 id="ktm-header-title">Détails de l'opération</h3>
                <p id="modalRef">Réf : #0000</p>
            </div>
            <button id="ktm-close" onclick="closeTransactionModal()">✕</button>
        </div>

        {{-- ── BODY ────────────────────────────────────────────── --}}
        <div id="ktm-body">

            {{-- ── COLONNE GAUCHE ──────────────────────────────── --}}
            <div class="ktm-col">

                {{-- Client --}}
                <div class="ktm-section">
                    <p class="ktm-label">Investisseur / Client</p>
                    <div class="ktm-row-card">
                        <div id="clientInitial" class="ktm-avatar">?</div>
                        <div>
                            <p id="modalClientName" class="ktm-row-name">Chargement…</p>
                            <p class="ktm-row-sub">Client particulier</p>
                        </div>
                    </div>
                </div>

                {{-- Produit --}}
                <div class="ktm-section">
                    <p class="ktm-label">Produit de placement</p>
                    <div class="ktm-row-card">
                        <div class="ktm-product-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path
                                    d="M13 6.5C13 10 8 14 8 14C8 14 3 10 3 6.5C3 4.015 5.239 2 8 2C10.761 2 13 4.015 13 6.5Z"
                                    stroke="#6b1f0a" stroke-width="1.2" />
                                <circle cx="8" cy="6.5" r="1.5" fill="#6b1f0a" />
                            </svg>
                        </div>
                        <span id="modalProductName" class="ktm-product-name">Chargement…</span>
                    </div>
                </div>

                {{-- Métadonnées --}}
                <div class="ktm-section">
                    <p class="ktm-label">Informations complémentaires</p>
                    <div class="ktm-meta-list">
                        <div class="ktm-meta-row">
                            <span class="ktm-meta-key">Enregistré le</span>
                            <span id="modalDateEnreg" class="ktm-meta-val">—</span>
                        </div>
                        <div class="ktm-meta-row">
                            <span class="ktm-meta-key">Date souscription</span>
                            <span id="modalDateSouscr" class="ktm-meta-val">—</span>
                        </div>
                        <div class="ktm-meta-row">
                            <span class="ktm-meta-key">Statut</span>
                            <span id="modalStatus" class="ktm-meta-val ktm-status-val">EN ATTENTE</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── COLONNE DROITE ───────────────────────────────── --}}
            <div class="ktm-col">

                {{-- Carte montant --}}
                <div class="ktm-section">
                    <p class="ktm-label">Volume transactionnel</p>
                    <div class="ktm-amount-card">
                        <div class="ktm-amount-top">
                            <span class="ktm-amount-sublabel">Montant brut</span>
                            <span id="modalType" class="ktm-flux-badge">FLUX INITIAL</span>
                        </div>
                        <p id="modalAmount" class="ktm-amount-main">0 XAF</p>
                        <div class="ktm-amount-divider"></div>
                        <div class="ktm-amount-row">
                            <span class="ktm-amount-key">Montant net investi</span>
                            <span id="modalNetAmount" class="ktm-amount-val">0 XAF</span>
                        </div>
                        <div class="ktm-amount-row">
                            <span class="ktm-amount-key">Frais de souscription</span>
                            <span id="modalFeesDisplay" class="ktm-amount-val ktm-fees">0 XAF</span>
                        </div>
                        <svg class="ktm-deco-svg" width="80" height="80" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="#6b1f0a" stroke-width="1.5" />
                            <path
                                d="M12 6v2M12 16v2M8.5 9.5l1.5 1.5M14 14l1.5 1.5M6 12h2M16 12h2M8.5 14.5l1.5-1.5M14 10l1.5-1.5"
                                stroke="#6b1f0a" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </div>
                </div>

                {{-- Données techniques --}}
                <div class="ktm-section">
                    <p class="ktm-label">Données techniques</p>

                    {{-- FCP --}}
                    <div id="fcpInfoBlock" class="ktm-info-grid">
                        <div class="ktm-info-cell">
                            <span class="ktm-info-key">VL appliquée</span>
                            <span id="modalVlApplied" class="ktm-info-val ktm-maroon">0.00 XAF</span>
                        </div>
                        <div class="ktm-info-cell">
                            <span class="ktm-info-key">Mode de paiement</span>
                            <span id="modalPayment" class="ktm-info-val">N/A</span>
                        </div>
                    </div>

                    {{-- PMG --}}
                    <div id="pmgInfoBlock" class="ktm-info-grid" style="display:none;">
                        <div class="ktm-info-cell">
                            <span class="ktm-info-key">Taux d'intérêt</span>
                            <span id="modalRate" class="ktm-info-val ktm-maroon">0.00 %</span>
                        </div>
                        <div class="ktm-info-cell">
                            <span class="ktm-info-key">Échéance prévue</span>
                            <span id="modalMaturity" class="ktm-info-val ktm-maroon">—</span>
                        </div>
                        <div class="ktm-info-cell">
                            <span class="ktm-info-key">Durée</span>
                            <span id="modalDuration" class="ktm-info-val">0 mois</span>
                        </div>
                        <div class="ktm-info-cell">
                            <span class="ktm-info-key">Mode de paiement</span>
                            <span id="modalPaymentPmg" class="ktm-info-val">N/A</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── FOOTER ───────────────────────────────────────────── --}}
        <div id="ktm-footer">
            <button id="ktm-btn-cancel" onclick="closeTransactionModal()">Fermer</button>
            <form id="modalValidateForm" action="" method="POST">
                @csrf
                <button type="submit" id="ktm-btn-validate">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
                        <path d="M3 8l4 4 6-6" stroke="white" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Valider ce dossier
                </button>
            </form>
        </div>

    </div>
</div>


{{-- ── SCRIPTS ──────────────────────────────────────────────────── --}}
<script>
    function openTransactionModal(data, validateRoute) {
        const modal = document.getElementById('transactionDetailsModal');
        const content = document.getElementById('ktm-content');

        /* Référence */
        document.getElementById('modalRef').innerText =
            'Réf : ' + (data.ref || 'T-' + data.id);

        /* Client */
        const clientName = data.user ? data.user.name : 'Inconnu';
        document.getElementById('modalClientName').innerText = clientName;
        document.getElementById('clientInitial').innerText =
            clientName.substring(0, 2).toUpperCase();

        /* Produit */
        document.getElementById('modalProductName').innerText =
            data.product ? data.product.title : 'Produit inconnu';

        /* Montants */
        const fmt = v => new Intl.NumberFormat('fr-FR').format(v) + ' XAF';
        const grossAmount = parseFloat(data.amount) || 0;
        const feesAmount = parseFloat(data.fees) || 0;
        const netAmount = grossAmount - feesAmount;

        document.getElementById('modalAmount').innerText = fmt(grossAmount);
        document.getElementById('modalNetAmount').innerText = fmt(netAmount);
        document.getElementById('modalFeesDisplay').innerText = fmt(feesAmount);

        /* Badge flux */
        const title = (data.title || '').toLowerCase();
        let frequency = data.type_flux === 'main' ? 'FLUX INITIAL' : 'PONCTUELLE';
        if (title.includes('mensuelle')) frequency = 'MENSUELLE';
        if (title.includes('ponctuelle') && data.type_flux !== 'main') frequency = 'PONCTUELLE';
        document.getElementById('modalType').innerText = frequency;

        /* Dates */
        const fmtDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—';
        document.getElementById('modalDateEnreg').innerText = fmtDate(data.created_at);
        document.getElementById('modalDateSouscr').innerText = fmtDate(data.date_validation);

        /* Statut & paiement */
        document.getElementById('modalStatus').innerText = data.status || 'EN ATTENTE';
        document.getElementById('modalPayment').innerText = data.payment_mode || 'Virement';

        /* FCP vs PMG */
        const isPmg = data.product && data.product.products_category_id == 2;
        const fcpBlock = document.getElementById('fcpInfoBlock');
        const pmgBlock = document.getElementById('pmgInfoBlock');

        if (isPmg) {
            fcpBlock.style.display = 'none';
            pmgBlock.style.display = 'grid';
            document.getElementById('modalRate').innerText = (data.vl_buy || 0) + ' %';
            document.getElementById('modalDuration').innerText = (data.duree || 0) + ' mois';
            document.getElementById('modalMaturity').innerText = fmtDate(data.date_echeance) || 'Virement';
            document.getElementById('modalPaymentPmg').innerText = data.payment_mode || 'Virement';
        } else {
            fcpBlock.style.display = 'grid';
            pmgBlock.style.display = 'none';
            document.getElementById('modalVlApplied').innerText =
                new Intl.NumberFormat('fr-FR').format(data.vl_applied || data.vl_buy || 0) + ' XAF';
        }

        /* Formulaire */
        document.getElementById('modalValidateForm').action = validateRoute;

        /* Animation entrée */
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            content.classList.add('ktm-visible');
        });
    }

    function closeTransactionModal() {
        const modal = document.getElementById('transactionDetailsModal');
        const content = document.getElementById('ktm-content');

        content.classList.remove('ktm-visible');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
</script>


{{-- ── STYLES (100% scopés sous #transactionDetailsModal) ─────── --}}
<style>
    /* ── Variables locales ──────────────────────────────────────── */
    #transactionDetailsModal {
        --ktm-maroon: #6b1f0a;
        --ktm-maroon-hover: #7a2e0e;
        --ktm-maroon-50: #fdf3f0;
        --ktm-maroon-100: #f5d4c8;
        --ktm-maroon-dark: #2a0e05;
        --ktm-maroon-bd: #5a1a08;
        --ktm-gold: #c4890a;
        --ktm-radius-sm: 8px;
        --ktm-radius-md: 12px;
        --ktm-radius-lg: 20px;
        --ktm-border: 1px solid #e5e7eb;
        --ktm-bg: #ffffff;
        --ktm-bg-soft: #f9fafb;
        --ktm-text: #111827;
        --ktm-text-muted: #9ca3af;
        --ktm-text-soft: #6b7280;
    }

    /* dark mode */
    @media (prefers-color-scheme: dark) {
        #transactionDetailsModal {
            --ktm-bg: #111111;
            --ktm-bg-soft: #1c1c1c;
            --ktm-border: 1px solid #2d2d2d;
            --ktm-text: #f1f1f1;
            --ktm-text-muted: #6b7280;
            --ktm-text-soft: #9ca3af;
        }
    }

    /* ── Overlay ────────────────────────────────────────────────── */
    #transactionDetailsModal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    /* ── Conteneur modal ────────────────────────────────────────── */
    #transactionDetailsModal #ktm-content {
        background: var(--ktm-bg);
        width: 100%;
        max-width: 680px;
        border-radius: var(--ktm-radius-lg);
        overflow: hidden;
        border: var(--ktm-border);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        transform: scale(0.95);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    #transactionDetailsModal #ktm-content.ktm-visible {
        transform: scale(1);
        opacity: 1;
    }

    /* ── Header ─────────────────────────────────────────────────── */
    #transactionDetailsModal #ktm-header {
        background: var(--ktm-maroon);
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #transactionDetailsModal #ktm-header-title {
        font-size: 13px;
        font-weight: 500;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin: 0;
    }

    #transactionDetailsModal #modalRef {
        font-size: 11px;
        /*color: rgba(255, 255, 255, 0.45);*/
        margin: 3px 0 0;
        letter-spacing: 0.04em;
    }

    #transactionDetailsModal #ktm-close {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 14px;
        transition: background 0.2s;
    }

    #transactionDetailsModal #ktm-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    /* ── Body grid ──────────────────────────────────────────────── */
    #transactionDetailsModal #ktm-body {
        padding: 1.5rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        max-height: 72vh;
        overflow-y: auto;
    }

    #transactionDetailsModal #ktm-body::-webkit-scrollbar {
        width: 4px;
    }

    #transactionDetailsModal #ktm-body::-webkit-scrollbar-track {
        background: transparent;
    }

    #transactionDetailsModal #ktm-body::-webkit-scrollbar-thumb {
        background: rgba(107, 31, 10, 0.2);
        border-radius: 10px;
    }

    #transactionDetailsModal #ktm-body::-webkit-scrollbar-thumb:hover {
        background: rgba(107, 31, 10, 0.5);
    }

    @media (max-width: 560px) {
        #transactionDetailsModal #ktm-body {
            grid-template-columns: 1fr;
        }
    }

    /* ── Colonnes ───────────────────────────────────────────────── */
    #transactionDetailsModal .ktm-col {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    /* ── Section ────────────────────────────────────────────────── */
    #transactionDetailsModal .ktm-section {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    /* ── Label de section ───────────────────────────────────────── */
    #transactionDetailsModal .ktm-label {
        font-size: 10px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--ktm-text-muted);
        margin: 0;
    }

    /* ── Row card ───────────────────────────────────────────────── */
    #transactionDetailsModal .ktm-row-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: var(--ktm-bg-soft);
        border-radius: var(--ktm-radius-md);
        border: var(--ktm-border);
    }

    #transactionDetailsModal .ktm-avatar {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: var(--ktm-maroon-100);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 500;
        color: var(--ktm-maroon);
        flex-shrink: 0;
    }

    #transactionDetailsModal .ktm-row-name {
        font-size: 13px;
        font-weight: 500;
        color: var(--ktm-text);
        margin: 0;
    }

    #transactionDetailsModal .ktm-row-sub {
        font-size: 11px;
        color: var(--ktm-text-muted);
        margin: 2px 0 0;
    }

    #transactionDetailsModal .ktm-product-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--ktm-maroon-50);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    #transactionDetailsModal .ktm-product-name {
        font-size: 12px;
        font-weight: 500;
        color: var(--ktm-gold);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    /* ── Meta list ──────────────────────────────────────────────── */
    #transactionDetailsModal .ktm-meta-list {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    #transactionDetailsModal .ktm-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 10px;
        background: var(--ktm-bg-soft);
        border-radius: var(--ktm-radius-sm);
        border: var(--ktm-border);
    }

    #transactionDetailsModal .ktm-meta-key {
        font-size: 10px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--ktm-text-muted);
    }

    #transactionDetailsModal .ktm-meta-val {
        font-size: 11px;
        font-weight: 500;
        color: var(--ktm-text);
    }

    #transactionDetailsModal .ktm-status-val {
        color: #059669;
    }

    /* ── Carte montant ──────────────────────────────────────────── */
    #transactionDetailsModal .ktm-amount-card {
        position: relative;
        overflow: hidden;
        border-radius: var(--ktm-radius-md);
        padding: 1rem 1.25rem;
        background: var(--ktm-maroon-50);
        border: 1px solid var(--ktm-maroon-100);
    }

    #transactionDetailsModal .ktm-amount-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 4px;
    }

    #transactionDetailsModal .ktm-amount-sublabel {
        font-size: 10px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(107, 31, 10, 0.55);
    }

    #transactionDetailsModal .ktm-flux-badge {
        font-size: 9px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: var(--ktm-maroon-100);
        color: var(--ktm-maroon);
        border: 1px solid var(--ktm-maroon-100);
        border-radius: 20px;
        padding: 2px 8px;
    }

    #transactionDetailsModal .ktm-amount-main {
        font-size: 28px;
        font-weight: 500;
        color: var(--ktm-maroon);
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin: 4px 0 10px;
    }

    #transactionDetailsModal .ktm-amount-divider {
        border: none;
        border-top: 1px dashed var(--ktm-maroon-100);
        margin: 4px 0 8px;
    }

    #transactionDetailsModal .ktm-amount-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 3px 0;
    }

    #transactionDetailsModal .ktm-amount-key {
        font-size: 10px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--ktm-text-soft);
    }

    #transactionDetailsModal .ktm-amount-val {
        font-size: 11px;
        font-weight: 500;
        color: var(--ktm-text);
    }

    #transactionDetailsModal .ktm-fees {
        color: var(--ktm-gold);
    }

    #transactionDetailsModal .ktm-maroon {
        color: var(--ktm-maroon);
    }

    #transactionDetailsModal .ktm-deco-svg {
        position: absolute;
        right: -8px;
        bottom: -8px;
        opacity: 0.04;
        pointer-events: none;
    }

    /* ── Info grid ──────────────────────────────────────────────── */
    #transactionDetailsModal .ktm-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    #transactionDetailsModal .ktm-info-cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 10px 12px;
        background: var(--ktm-bg-soft);
        border-radius: var(--ktm-radius-md);
        border: var(--ktm-border);
    }

    #transactionDetailsModal .ktm-info-key {
        font-size: 9px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--ktm-text-muted);
    }

    #transactionDetailsModal .ktm-info-val {
        font-size: 12px;
        font-weight: 500;
        color: var(--ktm-text);
    }

    /* ── Footer ─────────────────────────────────────────────────── */
    #transactionDetailsModal #ktm-footer {
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        border-top: var(--ktm-border);
        background: var(--ktm-bg-soft);
    }

    #transactionDetailsModal #ktm-btn-cancel {
        padding: 8px 18px;
        border-radius: var(--ktm-radius-sm);
        background: var(--ktm-bg);
        border: var(--ktm-border);
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ktm-text-soft);
        cursor: pointer;
        transition: background 0.2s;
    }

    #transactionDetailsModal #ktm-btn-cancel:hover {
        background: var(--ktm-bg-soft);
    }

    #transactionDetailsModal #modalValidateForm {
        margin: 0;
        padding: 0;
    }

    #transactionDetailsModal #ktm-btn-validate {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 20px;
        border-radius: var(--ktm-radius-sm);
        background: var(--ktm-maroon);
        border: none;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #ffffff;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
    }

    #transactionDetailsModal #ktm-btn-validate:hover {
        background: var(--ktm-maroon-hover);
    }

    #transactionDetailsModal #ktm-btn-validate:active {
        transform: scale(0.97);
    }
</style>
