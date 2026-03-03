<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token:                 props.token,
    email:                 props.email,
    password:              '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

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
                    required
                />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.password }"
                    autocomplete="new-password"
                    autofocus
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

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-lock me-2"></i>
                {{ form.processing ? 'Resetting...' : 'Reset Password' }}
            </button>

        </form>
    </GuestLayout>
</template>
