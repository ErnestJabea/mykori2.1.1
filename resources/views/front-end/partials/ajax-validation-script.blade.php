<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Intercepter TOUS les formulaires qui pointent vers la validation de transaction
        document.addEventListener('submit', function(e) {
            const form = e.target;
            
            if (form.action && form.action.includes('validate-transaction')) {
                e.preventDefault();
                
                const btn = form.querySelector('button[type="submit"]');
                const originalContent = btn ? btn.innerHTML : '';
                const row = form.closest('tr');
                
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="las la-spinner la-spin"></i>';
                }

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Opération réussie',
                            text: data.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });

                        // Si on est dans le Modal, on ferme et on recharge
                        if (form.id === 'modalValidateForm') {
                            if (typeof closeTransactionModal === 'function') {
                                closeTransactionModal();
                            }
                            setTimeout(() => location.reload(), 1000);
                            return;
                        }

                        // Sinon (tableau), on anime la suppression de la ligne
                        if (row) {
                            row.style.transition = 'all 0.5s';
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(20px)';
                            setTimeout(() => {
                                row.remove();
                                if (document.querySelectorAll('tbody tr').length === 0) {
                                    location.reload();
                                }
                            }, 500);
                        }
                    } else {
                        throw new Error(data.message || 'Erreur lors de la validation');
                    }
                })
                .catch(error => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: error.message
                    });
                });
            }
        });
    });
</script>
