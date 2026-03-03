<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status:           { type: String },
});

const form = useForm({
    email:    '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign In" />

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
                    autocomplete="username"
                    autofocus
                    required
                />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password" class="form-label mb-0">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="small text-primary text-decoration-none"
                    >
                        Forgot password?
                    </Link>
                </div>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="form-control mt-1"
                    :class="{ 'is-invalid': form.errors.password }"
                    autocomplete="current-password"
                    required
                />
                <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
            </div>

            <div class="mb-3 form-check">
                <input
                    id="remember"
                    v-model="form.remember"
                    type="checkbox"
                    class="form-check-input"
                />
                <label for="remember" class="form-check-label small">Remember me</label>
            </div>

            <button
                type="submit"
                class="btn btn-primary w-100"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-sign-in-alt me-2"></i>
                {{ form.processing ? 'Signing in...' : 'Sign In' }}
            </button>

        </form>
    </GuestLayout>
</template>
