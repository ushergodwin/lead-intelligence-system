<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    users: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const users = ref([...props.users]);

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
    timer: 3000, timerProgressBar: true,
    customClass: { popup: 'shadow-sm' },
});

// ---- Modal state ----
const showModal  = ref(false);
const editMode   = ref(false);
const submitting = ref(false);

const blankForm = () => ({ id: null, name: '', email: '', role: 'viewer', password: '' });
const form = reactive(blankForm());
const errors = reactive({});

const openCreate = () => {
    Object.assign(form, blankForm());
    Object.keys(errors).forEach(k => delete errors[k]);
    editMode.value  = false;
    showModal.value = true;
};

const openEdit = (user) => {
    Object.assign(form, { id: user.id, name: user.name, email: user.email, role: user.role, password: '' });
    Object.keys(errors).forEach(k => delete errors[k]);
    editMode.value  = true;
    showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

// ---- CRUD ----
const saveUser = async () => {
    submitting.value = true;
    Object.keys(errors).forEach(k => delete errors[k]);
    try {
        const url    = editMode.value ? route('users.update', form.id) : route('users.store');
        const method = editMode.value ? 'put' : 'post';
        const res    = await axios[method](url, { ...form });
        const saved  = res.data.user;

        if (editMode.value) {
            const idx = users.value.findIndex(u => u.id === saved.id);
            if (idx !== -1) users.value[idx] = saved;
        } else {
            users.value.push(saved);
        }

        closeModal();
        toast.fire({ icon: 'success', title: res.data.message });
    } catch (err) {
        if (err.response?.status === 422) {
            Object.assign(errors, err.response.data.errors ?? {});
        } else {
            toast.fire({ icon: 'error', title: err.response?.data?.message ?? 'Something went wrong.' });
        }
    } finally {
        submitting.value = false;
    }
};

const deleteUser = async (user) => {
    const result = await dialog.fire({
        title: `Remove ${user.name}?`,
        text:  'This user will lose access immediately.',
        icon:  'warning',
        showCancelButton:  true,
        confirmButtonText: 'Yes, remove',
        denyButtonText:    'Cancel',
    });
    if (! result.isConfirmed) return;

    try {
        const res = await axios.delete(route('users.destroy', user.id));
        users.value = users.value.filter(u => u.id !== user.id);
        toast.fire({ icon: 'success', title: res.data.message });
    } catch (err) {
        toast.fire({ icon: 'error', title: err.response?.data?.message ?? 'Delete failed.' });
    }
};

const roleBadge = (role) => ({
    'super_admin': 'bg-danger',
    'manager':     'bg-warning text-dark',
    'viewer':      'bg-secondary',
}[role] ?? 'bg-secondary');

const roleLabel = (role) => ({
    'super_admin': 'Super Admin',
    'manager':     'Manager',
    'viewer':      'Viewer',
}[role] ?? role);
</script>

<template>
    <Head title="User Management" />
    <AdminLayout>
        <template #title>User Management</template>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">Users</h4>
                <p class="text-muted mb-0">Manage team members and their access levels.</p>
            </div>
            <button class="btn btn-primary" @click="openCreate">
                <i class="fas fa-user-plus me-2"></i>Add User
            </button>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;background:#f8fafc">Name</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;background:#f8fafc">Email</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;background:#f8fafc">Role</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;background:#f8fafc">Joined</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;background:#f8fafc;width:120px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id">
                                <td class="ps-4 fw-semibold">
                                    <i class="fas fa-user-circle text-muted me-2"></i>{{ user.name }}
                                </td>
                                <td class="text-muted">{{ user.email }}</td>
                                <td>
                                    <span class="badge rounded-pill" :class="roleBadge(user.role)">
                                        {{ roleLabel(user.role) }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ user.created_at }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary me-1" title="Edit" @click="openEdit(user)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Remove" @click="deleteUser(user)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-2x mb-2 d-block opacity-25"></i>
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,.45)">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-semibold">
                                <i class="fas fa-user-cog me-2 text-primary"></i>
                                {{ editMode ? 'Edit User' : 'Add New User' }}
                            </h5>
                            <button type="button" class="btn-close" @click="closeModal"></button>
                        </div>
                        <div class="modal-body">
                            <form @submit.prevent="saveUser">
                                <div class="row g-3">

                                    <div class="col-12">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': errors.name }" required />
                                        <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': errors.email }" required />
                                        <div v-if="errors.email" class="invalid-feedback">{{ errors.email[0] }}</div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Role <span class="text-danger">*</span></label>
                                        <select v-model="form.role" class="form-select" :class="{ 'is-invalid': errors.role }">
                                            <option value="viewer">Viewer — read-only access</option>
                                            <option value="manager">Manager — approve &amp; send outreach</option>
                                            <option value="super_admin">Super Admin — full access</option>
                                        </select>
                                        <div v-if="errors.role" class="invalid-feedback">{{ errors.role[0] }}</div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Password
                                            <span v-if="!editMode" class="text-danger">*</span>
                                            <span v-else class="text-muted small ms-1">(leave blank to keep current)</span>
                                        </label>
                                        <input v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': errors.password }" :required="!editMode" autocomplete="new-password" />
                                        <div v-if="errors.password" class="invalid-feedback">{{ errors.password[0] }}</div>
                                    </div>

                                </div>
                            </form>
                        </div>
                        <div class="modal-footer border-top">
                            <button class="btn btn-secondary" @click="closeModal">Cancel</button>
                            <button class="btn btn-primary px-4" :disabled="submitting" @click="saveUser">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="fas fa-save me-1"></i>
                                {{ submitting ? 'Saving...' : (editMode ? 'Save Changes' : 'Create User') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>
