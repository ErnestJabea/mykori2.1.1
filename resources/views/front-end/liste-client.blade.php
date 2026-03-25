@extends('front-end/app/app-home-asset', ['Clients ', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3
my-products-page'])


@section("inner-head")
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/magnific-popup.css">
@endsection

@section('content')
@php
use Carbon\Carbon;
Carbon::setLocale('fr_FR');
$periode = Carbon::now()->subMonth()->translatedFormat('F Y');
@endphp

@php
//dd( $clients );
@endphp

<main class="main-content has-sidebar">
    <div class="grid grid-cols-12 gap-4 xxl:gap-6">
        <div class="col-span-12 flex flex-col gap-4 md:col-span-7 lg:col-span-8 xxl:gap-6">
            <h3>Relevés clients – {{ ucfirst($periode) }}</h3>
        </div>
        <div class="col-span-12 md:col-span-5 lg:col-span-4">
            <div class="row flex items-center justify-end gap-3">
                <p style="text-align: right">{{ date('d-m-Y') }}</p>
                <div class="content-right">
                    <button class="btn ac-modal-btn buy">
                        <a href="">Validation des relev&eacute;s</a>
                    </button>
                </div>
            </div>
        </div>

    </div>
    <div class="content-separator" style="height:30px">

    </div>
    <!-- Tableau -->
    <div class="card">
        <div class="card-body">
            <p class="text-muted mt-2">
                <span id="selectedCount">0</span> client(s) sélectionné(s)
            </p>
            <table class="table table-hover align-middle" border="1">
                <thead class="table-light">
                    <tr>
                        <th width="40" align="left">
                            <input type="checkbox" id="checkAllClients" title="Tout sélectionner">
                        </th>
                        <th align="left" style="padding: 10px;">Client</th>
                        <th style="padding: 10px;">Portefeuille</th>
                        <th style="padding: 10px;">Relevé FCP</th>
                        <th style="padding: 10px;">Relevé PMG</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($clients as $client)
                    <tr>
                        <td>
                            <input type="checkbox" class="client-checkbox" onchange="updateSelectedCount()"
                                value="{{ $client->id }}">
                        </td>

                        <td>
                            <strong>{{ $client->name }}</strong><br>
                            <small class="text-muted">{{ $client->email }}</small>
                        </td>

                        <td align="right" style="font-weight: bold;">
                            {{ number_format($client->portefeuille_total, 0, ',', ' ') }} FCFA
                        </td>

                        <td align="center">
                            @if ($client->has_fcp)
                            <button class="btn btn-sm btn-outline-info btn-preview preview-link-fcp" data-id="{{ $client->id }}"
                                data-type="fcp">
                                 Prévisualiser 
                            </button>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td align="center">
                            @if ($client->has_pmg)
                             <button class="btn btn-sm btn-outline-primary   btn-preview preview-link-pmg"
                                    data-client-id="{{ $client->id }}">Prévisualiser </button>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            Aucun client disponible.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>

    <!-- Bouton futur (désactivé volontairement) -->
    <div class="mt-4 text-end">
        <button type="button" id="btnSendSelected" class="btn btn-primary" disabled>
            Générer et envoyer les relevés sélectionnés
        </button>
    </div>

    </div>

</main>



{{-- <div class="ac-modal-overlay fixed inset-0 z-[99] modalhide overflow-y-auto bg-n900/80 duration-500">
    <div class="overflow-y-auto">
        <div
            class="modal box modal-inner absolute left-1/2 my-10 max-h-[90vh] w-[95%] max-w-[710px] duration-300 -translate-x-1/2 overflow-y-auto xl:p-8">
            <!-- { "translate-y-0 scale-100 opacity-100 my-10": open } -->
            <div class="relative">
                <button class="ac-modal-close-btn absolute top-0 ltr:right-0 rtl:left-0">
                    <i class="las la-times"></i>
                </button>
                <div class="bb-dashed mb-4 flex items-center justify-between pb-4 lg:mb-6 lg:pb-6">
                    <h4 class="h4">Acheter - VL : XAF </h4>
                </div>
                <div class="alert alert-success" id="response"></div>
                <form action="achat-ok.php" method="post">
                    <div class="mt-6 grid grid-cols-2 gap-4 xl:mt-8 xxxl:gap-6">
                        <div class="col-span-2">
                            <label for="name" class="mb-4 block font-medium md:text-lg">
                                Montant (en XAF)
                            </label>
                            <input type="number"
                                class="w-full rounded-3xl border border-n30 bg-secondary1/5 px-6 py-2.5 dark:border-n500 dark:bg-bg3 md:py-3"
                                placeholder="Indiquez le montant" min="0" id="montantInput" name="montantInput"
                                required />
                        </div>
                        <div class="col-span-2">
                            <label for="number" class="mb-4 block font-medium md:text-lg" id="valeur-liquidative">
                                Nombre de parts : 0
                            </label>
                        </div>
                        <div class="col-span-2">
                            <label for="number" class="mb-4 block font-medium md:text-lg" id="frais-de-gestion">
                                Frais de souscription : XAF 0
                            </label>
                        </div>
                        <div class="col-span-2">
                            <label for="number" class="mb-4 block font-medium md:text-lg" id="montantTotal">
                                Montant total : XAF 0
                            </label>
                        </div>
                        <div class="col-span-2 mt-4">
                            <button class="btn flex w-full justify-center" id="submitButton" type="button">
                                Souscrire maintenant
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        </di v>--}}
    </div>


    {{-- <div class="modal fade" id="confirmSendModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Confirmation d’envoi des relevés
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <iframe id="previewIframe" src="" width="100%" height="600" style="border:0;"></iframe>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <button class="btn btn-success" id="btnConfirmSend">
                        Confirmer l’envoi
                    </button>
                </div>

            </div>
        </div>
    </div> --}}
    <div id="confirmSendPopup" class="mfp-hide white-popup-block">
        <h4>Confirmation d’envoi des relevés</h4>

        <p>
            Vous êtes sur le point d’envoyer les relevés de
            <strong><span id="confirmClientCount">0</span> client(s)</strong>.
        </p>

        <p class="text-danger">
            Cette action est irréversible.
        </p>

        <div class="text-end mt-4">
            <button type="button" class="btn btn-secondary me-2" id="btnCancelSend">
                Annuler
            </button>

            <button type="button" class="btn btn-success" id="btnConfirmSend">
                Confirmer l’envoi
            </button>
        </div>
    </div>

    @endsection

    @section('script_front_end')
    <script src="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/jquery.magnific-popup.min.js"></script>

    {{-- <script>
        document.addEventListener('DOMContentLoaded', function () {

    const sendButton = document.getElementById('btnSendSelected');
    const confirmModal = new bootstrap.Modal(
        document.getElementById('confirmSendModal')
    );

    sendButton.addEventListener('click', function () {

        const selectedClients = getSelectedClients();

        if (selectedClients.length === 0) {
            alert('Veuillez sélectionner au moins un client.');
            return;
        }

        document.getElementById('selectedCount').innerText =
            selectedClients.length;

        confirmModal.show();
    });

    document.getElementById('btnConfirmSend')
        .addEventListener('click', function () {

            fetch(`/backoffice/releves/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    clients: getSelectedClients()
                })
            })
            .then(res => res.json())
            .then(response => {
                location.reload();
            });
        });

    function getSelectedClients() {
        return Array.from(
            document.querySelectorAll('.client-checkbox:checked')
        ).map(cb => cb.value);
    }

});
     
            if (document.querySelector(".ac-modal-btn")) {
                setupModal(".ac-modal-btn", ".ac-modal-overlay", ".ac-modal-close-btn");
            }
    </script> --}}
    <script>
        /*    document.addEventListener('DOMContentLoaded', function () {

    let selectedClientId = null;

     document.querySelectorAll('.btn-preview').forEach(button => {
        button.addEventListener('click', function () {
              const clientId = this.dataset.clientId;

        alert(clientId);
        document.getElementById('previewIframe').src = `./asset-manager/releves/preview/${clientId}`;
        });
    }); */

    /* 
function updateSelectedCount() {
    document.getElementById('selectedCount').innerText =
        document.querySelectorAll('.client-checkbox:checked').length;
}

    document.getElementById('btnConfirmSend').addEventListener('click', function () {

        fetch(`/backoffice/releves/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                clients: getSelectedClients()
            })
        })
        .then(res => res.json())
        .then(response => {
            location.reload();
        });
    });


    

    const checkAll = document.getElementById('checkAllClients');
    const clientCheckboxes = () =>
        document.querySelectorAll('.client-checkbox');

    // ✔️ TOUT COCHER / TOUT DÉCOCHER
    checkAll.addEventListener('change', function () {
        clientCheckboxes().forEach(cb => {
            cb.checked = checkAll.checked;
        });
    });

    // ✔️ MISE À JOUR AUTO DU "TOUT COCHER"
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('client-checkbox')) return;

        const allChecked = Array.from(clientCheckboxes())
            .every(cb => cb.checked);

        checkAll.checked = allChecked;
    });


    function getSelectedClients() {
        return Array.from(
            document.querySelectorAll('.client-checkbox:checked')
        ).map(cb => cb.value);
    }

});

 */







$(document).ready(function () {

    $('.preview-link-pmg').on('click', function () {

        const clientId = $(this).data('client-id');


        const previewUrl = "{{ route('asset-manager.releves.preview', ':id') }}"
            .replace(':id', clientId);

        $.magnificPopup.open({
            items: {
                src: previewUrl
            },
            type: 'iframe',
            iframe: {
                patterns: {
                    laravel: {
                        index: previewUrl,
                        src: previewUrl
                    }
                }
            },
            closeBtnInside: true,
            preloader: true,
            mainClass: 'mfp-fade',
            removalDelay: 160,
        });

    });

    $('.preview-link-fcp').on('click', function () {

        const clientId = $(this).data('id');


        const previewUrl = "{{ route('asset-manager.releves.preview-fcp', ':id') }}"
            .replace(':id', clientId);

        $.magnificPopup.open({
            items: {
                src: previewUrl
            },
            type: 'iframe',
            iframe: {
                patterns: {
                    laravel: {
                        index: previewUrl,
                        src: previewUrl
                    }
                }
            },
            closeBtnInside: true,
            preloader: true,
            mainClass: 'mfp-fade',
            removalDelay: 160,
        });

    });

});
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

    const checkAll = document.getElementById('checkAllClients');
    const sendBtn  = document.getElementById('btnSendSelected');

    function getSelectedClients() {
        return document.querySelectorAll('.client-checkbox:checked');
    }

    function updateButtonState() {
        sendBtn.disabled = getSelectedClients().length === 0;
    }

    // 🔹 Tout cocher / décocher
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.client-checkbox').forEach(cb => {
            cb.checked = checkAll.checked;
        });
        updateSelectedCount();   
        updateButtonState();
    });

    // 🔹 Sélection individuelle
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('client-checkbox')) return;

        const allChecked = document.querySelectorAll('.client-checkbox').length ===
                           getSelectedClients().length;

        checkAll.checked = allChecked;
        updateButtonState();
    });

    

});

function updateSelectedCount() {
    document.getElementById('selectedCount').innerText =
        document.querySelectorAll('.client-checkbox:checked').length;
}

    </script>

    <script>
        $(document).ready(function () {

    function getSelectedClients() {
        return $('.client-checkbox:checked')
            .map(function () {
                return $(this).val();
            }).get();
    }

    // 🔹 Bouton principal
    $('#btnSendSelected').on('click', function () {

        const selectedClients = getSelectedClients();

        if (selectedClients.length === 0) {
            alert('Veuillez sélectionner au moins un client.');
            return;
        }

        // Mise à jour du compteur dans la popup
        $('#confirmClientCount').text(selectedClients.length);

        // Ouverture Magnific Popup
        $.magnificPopup.open({
            items: {
                src: '#confirmSendPopup',
                type: 'inline'
            },
            closeOnBgClick: false,
            enableEscapeKey: false
        });
    });

    // ❌ Annuler
    $('#btnCancelSend').on('click', function () {
        $.magnificPopup.close();
    });


    
$('#btnConfirmSend').on('click', function () {
    const selectedClients = $('.client-checkbox:checked')
        .map(function () {
            return $(this).val();
        }).get();

    if (selectedClients.length === 0) {
        alert('Aucun client sélectionné');
        return;
    }

    console.log('Clients sélectionnés :', selectedClients);

    // ✅ Sauvegarder le bouton et son texte original
    const $btn = $(this);
    const originalText = $btn.html();
    
    // ✅ Désactiver le bouton et afficher le loader
    $btn.prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Envoi en cours...');

    // Créer un FormData
    const formData = new FormData();
    formData.append('_token', "{{ csrf_token() }}");
    
    // Ajouter chaque client individuellement
    selectedClients.forEach(function(clientId) {
        formData.append('clients[]', clientId);
    });

    $.ajax({
        url: "{{ route('releves.send') }}",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (response) {
            // ✅ Afficher succès sur le bouton
            $btn.html('<i class="fas fa-check me-2"></i>Envoyé avec succès !');
            
            setTimeout(function() {
                alert(response.message);
                location.reload();
            }, 500);
        },
        error: function (xhr) {
            console.error('AJAX ERROR', xhr.responseText);
            
            // ✅ Réactiver le bouton en cas d'erreur
            $btn.prop('disabled', false)
                .html(originalText);
            
            alert('Erreur lors de l\'envoi des relevés');
        }
    });
});
});




    </script>

    @endsection