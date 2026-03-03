<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) { form.reset('password', 'password_confirmation'); passwordInput.value?.focus(); }
            if (form.errors.current_password) { form.reset('current_password'); currentPasswordInput.value?.focus(); }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword">
        <div class="row g-3">

            <div class="col-12 col-md-6">
                <label for="current_password" class="form-label">Current Password</label>
                <input id="current_password" ref="currentPasswordInput" v-model="form.current_password" type="password" class="form-control" :class="{ 'is-invalid': form.errors.current_password }" autocomplete="current-password" />
                <div v-if="form.errors.current_password" class="invalid-feedback">{{ form.errors.current_password }}</div>
            </div>

            <div class="col-12 col-md-6">
                <label for="new_password" class="form-label">New Password</label>
                <input id="new_password" ref="passwordInput" v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" autocomplete="new-password" />
                <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
            </div>

            <div class="col-12 col-md-6">
                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="form-control" :class="{ 'is-invalid': form.errors.password_confirmation }" autocomplete="new-password" />
                <div v-if="form.errors.password_confirmation" class="invalid-feedback">{{ form.errors.password_confirmation }}</div>
            </div>

            <div class="col-12 d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-warning text-white" :disabled="form.processing">
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="fas fa-key me-1"></i>
                    {{ form.processing ? 'Updating...' : 'Update Password' }}
                </button>
                <Transition enter-active-class="transition-opacity" enter-from-class="opacity-0" leave-active-class="transition-opacity" leave-to-class="opacity-0">
                    <span v-if="form.recentlySuccessful" class="text-success small"><i class="fas fa-check me-1"></i>Password updated.</span>
                </Transition>
            </div>

        </div>
    </form>
</template>
