<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({ mustVerifyEmail: { type: Boolean }, status: { type: String } });

const user = usePage().props.auth.user;
const form = useForm({ name: user.name, email: user.email });
</script>

<template>
    <form @submit.prevent="form.patch(route('profile.update'))">
        <div class="row g-3">

            <div class="col-12 col-md-6">
                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input id="name" v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }" autocomplete="name" required />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>

            <div class="col-12 col-md-6">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input id="email" v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" autocomplete="username" required />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <div v-if="mustVerifyEmail && !$page.props.auth.user.email_verified_at" class="col-12">
                <div class="alert alert-warning py-2 small mb-0">
                    Your email address is unverified.
                    <Link :href="route('verification.send')" method="post" as="button" class="btn btn-link btn-sm p-0 ms-1">Resend verification email</Link>
                </div>
                <div v-if="status === 'verification-link-sent'" class="alert alert-success py-2 small mt-2 mb-0">A new verification link has been sent to your email.</div>
            </div>

            <div class="col-12 d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="fas fa-save me-1"></i>
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
                <Transition enter-active-class="transition-opacity" enter-from-class="opacity-0" leave-active-class="transition-opacity" leave-to-class="opacity-0">
                    <span v-if="form.recentlySuccessful" class="text-success small"><i class="fas fa-check me-1"></i>Saved.</span>
                </Transition>
            </div>

        </div>
    </form>
</template>
