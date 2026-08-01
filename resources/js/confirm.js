import Swal from 'sweetalert2';

window.Swal = Swal;

/**
 * Global SweetAlert2-based confirmation used across the app instead of the
 * native browser confirm() dialog (wire:confirm). Usage from blade:
 *   x-on:click="confirmAction('¿Mensaje?', () => $wire.someMethod(id))"
 */
window.confirmAction = function (message, onConfirm, options = {}) {
    Swal.fire({
        title: options.title || '¿Estás seguro?',
        text: message,
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText || 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
        confirmButtonColor: options.confirmButtonColor || '#ff7261',
        cancelButtonColor: '#94a3b8',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl',
            cancelButton: 'rounded-xl',
        },
    }).then((result) => {
        if (result.isConfirmed && typeof onConfirm === 'function') {
            onConfirm();
        }
    });
};
