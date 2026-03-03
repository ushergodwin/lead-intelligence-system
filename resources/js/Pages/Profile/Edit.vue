<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
});

// ---- SweetAlert2 mixins ----
const dialog = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-primary px-4 me-2',
        cancelButton:  'btn btn-secondary px-4',
        denyButton:    'btn btn-danger px-4 me-2',
        popup:         'shadow',
    },
    buttonsStyling: false,
});
const toast = Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false,
    timer: 3500, timerProgressBar: true,
    customClass: { popup: 'shadow-sm' },
});

// ---- API Token management ----
const tokens      = ref([]);
const tokenErrors = reactive({});
const newTokenName = ref('');
const creatingToken = ref(false);
const loadingTokens = ref(false);
const newlyCreated  = ref(null); // plain-text token shown once after creation

const loadTokens = async () => {
    loadingTokens.value = true;
    try {
        const res = await axios.get(route('api.auth.tokens.index'));
        tokens.value = res.data;
    } catch {
        // silently ignore
    } finally {
        loadingTokens.value = false;
    }
};

loadTokens();

const createToken = async () => {
    if (! newTokenName.value.trim()) return;
    creatingToken.value = true;
    delete tokenErrors.name;
    try {
        const res = await axios.post(route('api.auth.tokens.store'), { name: newTokenName.value.trim() });
        newlyCreated.value = res.data.token;
        newTokenName.value = '';
        tokens.value.unshift({
            id:         res.data.id,
            name:       res.data.name,
            last_used:  'Never',
            created_at: new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),
        });
    } catch (err) {
        if (err.response?.status === 422) {
            Object.assign(tokenErrors, err.response.data.errors ?? {});
        } else {
            toast.fire({ icon: 'error', title: 'Could not create token.' });
        }
    } finally {
        creatingToken.value = false;
    }
};

const revokeToken = async (token) => {
    const result = await dialog.fire({
        title: `Revoke "${token.name}"?`,
        text:  'Any app using this token will lose access immediately.',
        icon:  'warning',
        showCancelButton:  true,
        confirmButtonText: 'Revoke',
    });
    if (! result.isConfirmed) return;

    try {
        await axios.delete(route('api.auth.tokens.destroy', token.id));
        tokens.value = tokens.value.filter(t => t.id !== token.id);
        toast.fire({ icon: 'success', title: 'Token revoked.' });
    } catch {
        toast.fire({ icon: 'error', title: 'Could not revoke token.' });
    }
};

const copyToken = () => {
    navigator.clipboard.writeText(newlyCreated.value);
    toast.fire({ icon: 'success', title: 'Token copied to clipboard.' });
};
</script>

<template>
    <Head title="My Profile" />
    <AdminLayout>
        <template #title>My Profile</template>

        <div class="mb-4">
            <h4 class="fw-bold mb-1">Account Settings</h4>
            <p class="text-muted mb-0">Manage your profile information, password, API tokens, and account.</p>
        </div>

        <div class="row g-4">

            <!-- Profile Information -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent fw-semibold">
                        <i class="fas fa-user me-2 text-primary"></i>Profile Information
                    </div>
                    <div class="card-body">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent fw-semibold">
                        <i class="fas fa-lock me-2 text-warning"></i>Change Password
                    </div>
                    <div class="card-body">
                        <UpdatePasswordForm />
                    </div>
                </div>
            </div>

            <!-- API Tokens -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent fw-semibold">
                        <i class="fas fa-key me-2 text-info"></i>API Tokens
                        <span class="text-muted fw-normal small ms-1">(for mobile app &amp; external integrations)</span>
                    </div>
                    <div class="card-body">

                        <!-- New token revealed once -->
                        <div v-if="newlyCreated" class="alert alert-success border-0 mb-4">
                            <div class="fw-semibold mb-1"><i class="fas fa-check-circle me-1"></i>Token created — copy it now, it won't be shown again.</div>
                            <div class="d-flex gap-2 align-items-center mt-2">
                                <code class="flex-grow-1 bg-white border rounded px-2 py-1 small" style="word-break:break-all">{{ newlyCreated }}</code>
                                <button class="btn btn-sm btn-success flex-shrink-0" @click="copyToken">
                                    <i class="fas fa-copy me-1"></i>Copy
                                </button>
                                <button class="btn btn-sm btn-outline-secondary flex-shrink-0" @click="newlyCreated = null">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Create new token -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Create New Token</label>
                            <div class="input-group">
                                <input
                                    v-model="newTokenName"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': tokenErrors.name }"
                                    placeholder="e.g. Android App, Zapier"
                                    @keyup.enter="createToken"
                                />
                                <button class="btn btn-primary" :disabled="creatingToken || !newTokenName.trim()" @click="createToken">
                                    <span v-if="creatingToken" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="fas fa-plus me-1"></i>
                                    Generate
                                </button>
                            </div>
                            <div v-if="tokenErrors.name" class="text-danger small mt-1">{{ tokenErrors.name[0] }}</div>
                            <div class="form-text">Use Bearer tokens in mobile/API clients: <code>Authorization: Bearer &lt;token&gt;</code></div>
                        </div>

                        <!-- Token list -->
                        <div v-if="loadingTokens" class="text-muted small">
                            <span class="spinner-border spinner-border-sm me-1"></span>Loading tokens...
                        </div>
                        <div v-else-if="tokens.length === 0" class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>No tokens yet.
                        </div>
                        <ul v-else class="list-group list-group-flush">
                            <li v-for="token in tokens" :key="token.id"
                                class="list-group-item px-0 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-semibold small">{{ token.name }}</div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        Created {{ token.created_at }} &bull; Last used: {{ token.last_used }}
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-danger" @click="revokeToken(token)">
                                    <i class="fas fa-trash-alt me-1"></i>Revoke
                                </button>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm border-danger border-opacity-25">
                    <div class="card-header bg-transparent fw-semibold text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                    </div>
                    <div class="card-body">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
