<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String } });

const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <p class="text-muted small mb-3">
            Forgot your password? Enter your email and we'll send you a reset link.
        </p>

        <div v-if="status" class="alert alert-success py-2 small mb-3">{{ status }}</div>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.email }"
                    autofocus
                    required
                />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-envelope me-2"></i>
                {{ form.processing ? 'Sending...' : 'Send Reset Link' }}
            </button>
        </form>
    </GuestLayout>
</template>
