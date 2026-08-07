import Swal from 'sweetalert2';

export function confirmDestructive(options: {
    title: string;
    text?: string;
    confirmButtonText?: string;
}) {
    return Swal.fire({
        title: options.title,
        text: options.text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText ?? 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        background: '#151517',
        color: '#f5f5f5',
        confirmButtonColor: '#c9a24b',
        cancelButtonColor: '#3a3a3d',
        reverseButtons: true,
    });
}
