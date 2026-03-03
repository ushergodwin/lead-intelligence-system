<script setup>
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const form = useForm({ password: '' });

const confirmDeletion = async () => {
    const result = await Swal.fire({
        title: 'Delete your account?',
        html: '<p class="text-muted small">Once deleted, all your data is permanently removed. This cannot be undone.</p>' +
              '<input type="password" id="swal-delete-password" class="form-control mt-2" placeholder="Enter your password to confirm" />',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash me-1"></i> Yes, Delete Account',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger px-4 me-2',
            cancelButton: 'btn btn-secondary px-4',
        },
        buttonsStyling: false,
        preConfirm: () => {
            const pwd = document.getElementById('swal-delete-password').value;
            if (!pwd) { Swal.showValidationMessage('Password is required'); return false; }
            return pwd;
        },
    });

    if (!result.isConfirmed) return;

    form.password = result.value;
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Incorrect Password', text: form.errors.password, customClass: { confirmButton: 'btn btn-primary px-4' }, buttonsStyling: false });
            form.reset();
        },
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <div>
        <p class="text-muted small mb-3">
            Once your account is deleted, all resources and data will be permanently removed.
            Please ensure you have saved anything you need before proceeding.
        </p>
        <button type="button" class="btn btn-outline-danger" @click="confirmDeletion" :disabled="form.processing">
            <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="fas fa-trash me-1"></i>
            Delete Account
        </button>
    </div>
</template>
