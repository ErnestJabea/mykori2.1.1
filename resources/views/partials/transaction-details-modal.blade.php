<!-- Transaction Details Modal -->
<div id="transactionDetailsModal"
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-n900/60 backdrop-blur-sm p-4 transition-all duration-300">
    <div class="bg-white dark:bg-bg3 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden transform transition-all scale-95 opacity-0 duration-300"
        id="modalContent">
        <!-- Header -->
        <div
            class="bg-marron p-6 text-white flex justify-between items-center bg-gradient-to-r from-marron to-[#7a2e0e]">
            <div>
                <h3 class="text-xl font-bold italic uppercase tracking-tighter">Détails de l'Opération</h3>
                <p class="text-[10px] opacity-70 italic font-bold" id="modalRef">Réf: #0000</p>
            </div>
            <button onclick="closeTransactionModal()"
                class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-all">
                <i class="las la-times text-xl"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Client & Product Section -->
                <div class="space-y-6">
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-n400 uppercase italic tracking-widest">Investisseur /
                            Client</span>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-marron/10 flex items-center justify-center text-marron font-black text-xs"
                                id="clientInitial">?</div>
                            <span class="text-base font-bold text-n900 italic" id="modalClientName">Chargement...</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-n400 uppercase italic tracking-widest">Produit de
                            Placement Target</span>
                        <div class="p-3 bg-n10 rounded-2xl border border-n30 flex items-center gap-3">
                            <i class="las la-piggy-bank text-2xl text-marron"></i>
                            <span class="text-sm font-bold text-primary uppercase italic"
                                id="modalProductName">Chargement...</span>
                        </div>
                    </div>
                </div>

                <!-- Financial Section -->
                <div class="space-y-6">
                    <div class="p-6 bg-slate-50 rounded-3xl border border-n30 relative overflow-hidden">
                        <span class="text-[10px] font-bold text-n400 uppercase italic tracking-widest mb-2 block">Volume Transactionnel (Brut)</span>
                        <h2 class="text-3xl font-black text-marron tracking-tight" id="modalAmount">0 XAF</h2>
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-[9px] text-success font-bold italic" id="modalType">FLUX INITIAL</p>
                            <span class="text-[10px] text-n400 font-bold italic" id="modalFeesDisplay">Frais: 0 XAF</span>
                        </div>
                        <i class="las la-coins absolute -bottom-4 -right-4 text-7xl opacity-5"></i>
                    </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-bold text-n400 uppercase italic">VL Appliquée</span>
                            <span class="text-xs font-bold text-marron/80 italic" id="modalVlApplied">0.00 XAF</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-bold text-n400 uppercase italic">Mode de Paiement</span>
                            <span class="text-xs font-bold text-n700 italic" id="modalPayment">N/A</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="mt-6 flex gap-4">
                <div class="px-3 py-1 bg-marron/5 rounded-lg border border-marron/10 flex items-center gap-2">
                    <span class="text-[8px] font-bold text-marron uppercase tracking-widest">Date:</span>
                    <span class="text-[10px] font-bold text-n700" id="modalDate">00/00/0000</span>
                </div>
                <div class="px-3 py-1 bg-marron/5 rounded-lg border border-marron/10 flex items-center gap-2">
                    <span class="text-[8px] font-bold text-marron uppercase tracking-widest">Status:</span>
                    <span class="text-[10px] font-bold text-success uppercase" id="modalStatus">EN ATTENTE</span>
                </div>
            </div>

        </div>

        <!-- Footer / Actions -->
        <div class="p-6 bg-n10 border-t border-n30 flex justify-end gap-3 text-sm">
            <button onclick="closeTransactionModal()"
                class="px-6 py-2.5 bg-n50 text-n700 font-bold rounded-2xl border border-n30 hover:bg-n10 transition-all italic">Fermer</button>
            <form id="modalValidateForm" action="" method="POST">
                @csrf
                <button type="submit"
                    class="px-6 py-2.5 bg-marron text-white font-bold rounded-2xl shadow-lg hover:scale-105 active:scale-95 transition-all flex items-center gap-2 uppercase tracking-widest text-[11px] italic">
                    <i class="las la-check-circle"></i> Valider ce Dossier
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openTransactionModal(data, validateRoute) {
        const modal = document.getElementById('transactionDetailsModal');
        const content = document.getElementById('modalContent');

        // Populating data
        document.getElementById('modalRef').innerText = 'Réf: ' + (data.ref || 'T-' + data.id);
        document.getElementById('modalClientName').innerText = data.user ? data.user.name : 'Inconnu';
        document.getElementById('clientInitial').innerText = data.user ? data.user.name.substring(0, 1) : '?';
        document.getElementById('modalProductName').innerText = data.product ? data.product.title : 'Produit inconnu';
        
        // Calcul du BRUT : Montant + Frais
        const grossAmount = (parseFloat(data.amount) || 0) + (parseFloat(data.fees) || 0);
        const feesAmount = (parseFloat(data.fees) || 0);
        
        document.getElementById('modalAmount').innerText = new Intl.NumberFormat('fr-FR').format(grossAmount) + ' XAF';
        document.getElementById('modalFeesDisplay').innerText = 'Frais inclus: ' + new Intl.NumberFormat('fr-FR').format(feesAmount) + ' XAF';
        
        document.getElementById('modalType').innerText = (data.type_flux === 'main' ? 'FLUX INITIAL' :
            'VERSEMENT COMPLÉMENTAIRE');
        document.getElementById('modalDate').innerText = data.created_at ? new Date(data.created_at)
        .toLocaleDateString() : '--/--/----';
        document.getElementById('modalPayment').innerText = data.payment_mode || 'Virement/Chèque';
        document.getElementById('modalStatus').innerText = data.status || 'EN ATTENTE';
        document.getElementById('modalVlApplied').innerText = (data.vl_applied || data.vl_buy || 0) + ' XAF';
        
        // Validation Form
        document.getElementById('modalValidateForm').action = validateRoute;

        // Validation Form
        document.getElementById('modalValidateForm').action = validateRoute;

        // Animation
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeTransactionModal() {
        const modal = document.getElementById('transactionDetailsModal');
        const content = document.getElementById('modalContent');

        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #531d0944;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #531d09;
    }
</style>
