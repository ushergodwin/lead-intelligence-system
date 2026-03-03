<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ status: { type: String } });
const form = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle mb-3" style="width:56px;height:56px">
                <i class="fas fa-envelope-open-text text-warning fs-4"></i>
            </div>
            <h5 class="fw-semibold mb-1">Verify your email</h5>
        </div>
        <p class="text-muted small mb-3">Thanks for signing up! Please verify your email address by clicking the link we just sent you. If you did not receive it, we can send another.</p>
        <div v-if="verificationLinkSent" class="alert alert-success py-2 small mb-3">
            <i class="fas fa-check-circle me-1"></i> A new verification link has been sent to your email address.
        </div>
        <form @submit.prevent="submit">
            <button type="submit" class="btn btn-primary w-100 mb-3" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-paper-plane me-2"></i>
                {{ form.processing ? 'Sending...' : 'Resend Verification Email' }}
            </button>
        </form>
        <div class="text-center">
            <Link :href="route('logout')" method="post" as="button" class="btn btn-link btn-sm text-muted p-0">
                <i class="fas fa-sign-out-alt me-1"></i>Log Out
            </Link>
        </div>
    </GuestLayout>
</template>
