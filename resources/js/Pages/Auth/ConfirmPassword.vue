<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
const form = useForm({ password: '' });
const submit = () => { form.post(route('password.confirm'), { onFinish: () => form.reset() }); };
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />
        <div class="text-center mb-3">
            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle mb-2" style="width:52px;height:52px">
                <i class="fas fa-shield-alt text-warning fs-4"></i>
            </div>
            <p class="text-muted small mb-0">This is a secure area. Please confirm your password to continue.</p>
        </div>
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input id="password" v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" autocomplete="current-password" autofocus required />
                <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-check-circle me-2"></i>
                {{ form.processing ? 'Confirming...' : 'Confirm' }}
            </button>
        </form>
    </GuestLayout>
</template>
