<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name:                  '',
    email:                 '',
    password:              '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">

            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.name }"
                    autocomplete="name"
                    autofocus
                    required
                />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.email }"
                    autocomplete="username"
                    required
                />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.password }"
                    autocomplete="new-password"
                    required
                />
                <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.password_confirmation }"
                    autocomplete="new-password"
                    required
                />
                <div v-if="form.errors.password_confirmation" class="invalid-feedback">{{ form.errors.password_confirmation }}</div>
            </div>

            <button
                type="submit"
                class="btn btn-primary w-100 mb-3"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ form.processing ? 'Creating account...' : 'Create Account' }}
            </button>

            <p class="text-center small text-muted mb-0">
                Already have an account?
                <Link :href="route('login')" class="text-primary text-decoration-none">Sign in</Link>
            </p>

        </form>
    </GuestLayout>
</template>
