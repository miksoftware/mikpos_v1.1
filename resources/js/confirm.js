import Swal from 'sweetalert2';

window.Swal = Swal;

function fireConfirm(trigger) {
    const message = trigger.dataset.confirm;
    const title = trigger.dataset.confirmTitle || '¿Estás seguro?';
    const icon = trigger.dataset.confirmIcon || 'warning';
    const confirmButtonColor = trigger.dataset.confirmColor || '#ff7261';
    const confirmButtonText = trigger.dataset.confirmButtonText || 'Sí, continuar';

    Swal.fire({
        title,
        text: message,
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
        confirmButtonColor,
        cancelButtonColor: '#94a3b8',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl',
            cancelButton: 'rounded-xl',
        },
    }).then((result) => {
        if (!result.isConfirmed) return;

        const method = trigger.dataset.confirmMethod;
        if (!method) return;

        const params = trigger.dataset.confirmParams ? JSON.parse(trigger.dataset.confirmParams) : [];

        // wire:id lives on the Livewire component root, which may not be the
        // immediate parent (e.g. buttons inside a modal rendered after an AJAX update).
        const root = trigger.closest('[wire\\:id]');
        const component = root && window.Livewire ? window.Livewire.find(root.getAttribute('wire:id')) : null;

        if (component && typeof component[method] === 'function') {
            component[method](...params);
        }
    });
}

/**
 * Global SweetAlert2 confirmation, delegated at document level so it keeps working
 * for buttons rendered after a Livewire AJAX update (no per-element Alpine binding needed).
 * Usage in blade:
 *   <button type="button"
 *       data-confirm="Mensaje de la pregunta."
 *       data-confirm-title="¿Título?"
 *       data-confirm-method="metodoDelComponente"
 *       data-confirm-params="[{{ $id }}]">
 */
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm]');
    if (!trigger) return;

    event.preventDefault();
    event.stopPropagation();
    fireConfirm(trigger);
});
