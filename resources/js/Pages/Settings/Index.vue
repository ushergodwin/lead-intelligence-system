<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    settings: { type: Object, required: true },
});

const toast = Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false,
    timer: 3000, timerProgressBar: true,
    customClass: { popup: 'shadow-sm' },
});

const form = reactive({
    daily_leads_limit:            props.settings.daily_leads_limit,
    daily_email_limit:            props.settings.daily_email_limit,
    min_ai_score:                 props.settings.min_ai_score,
    search_categories:            [...(props.settings.search_categories ?? [])],
    company_name:                 props.settings.company_name        ?? '',
    sender_name:                  props.settings.sender_name         ?? '',
    sender_position:              props.settings.sender_position     ?? '',
    company_email:                props.settings.company_email       ?? '',
    company_phone:                props.settings.company_phone       ?? '',
    company_whatsapp:             props.settings.company_whatsapp    ?? '',
    follow_up_days:               props.settings.follow_up_days      ?? 4,
    follow_up_notification_email: props.settings.follow_up_notification_email ?? '',
});

const errors     = reactive({});
const processing = ref(false);
const newCategory = ref('');

const addCategory = () => {
    const cat = newCategory.value.trim();
    if (cat && ! form.search_categories.includes(cat)) {
        form.search_categories.push(cat);
        newCategory.value = '';
    }
};

const removeCategory = (index) => {
    form.search_categories.splice(index, 1);
};

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach(k => delete errors[k]);
    try {
        const res = await axios.post(route('settings.update'), { ...form });
        toast.fire({ icon: 'success', title: res.data.message ?? 'Settings saved.' });
    } catch (err) {
        if (err.response?.status === 422) {
            Object.assign(errors, err.response.data.errors ?? {});
        } else {
            toast.fire({ icon: 'error', title: err.response?.data?.message ?? 'Failed to save settings.' });
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <Head title="Settings" />
    <AdminLayout>
        <template #title>Settings</template>

        <div class="mb-4">
            <h4 class="fw-bold mb-1">System Settings</h4>
            <p class="text-muted mb-0">Configure lead collection, outreach, and company details.</p>
        </div>

        <form @submit.prevent="submit">
            <div class="row g-4">

                <!-- ---- Lead Collection Limit ---- -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-start border-4 border-warning">
                        <div class="card-body py-3">
                            <div class="row align-items-center g-3">
                                <div class="col-auto">
                                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center"
                                         style="width:48px;height:48px">
                                        <i class="fas fa-coins text-warning fs-5"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="fw-semibold">Daily Leads Collection Limit</div>
                                    <div class="text-muted small">
                                        Controls how many <strong>new</strong> leads are stored per day.
                                        Each new lead triggers one Google Places Details API call
                                        (~$0.017 each). Updates to existing leads are free.
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="input-group">
                                        <input
                                            v-model.number="form.daily_leads_limit"
                                            type="number"
                                            min="1"
                                            max="5000"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.daily_leads_limit }"
                                        />
                                        <span class="input-group-text text-muted small">leads/day</span>
                                    </div>
                                    <div v-if="errors.daily_leads_limit" class="text-danger small mt-1">
                                        {{ errors.daily_leads_limit }}
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-normal">
                                            {{ form.daily_leads_limit }} leads/day
                                            ≈ ${{ (form.daily_leads_limit * 0.017).toFixed(2) }}/day
                                        </span>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 fw-normal">
                                            ≈ ${{ (form.daily_leads_limit * 0.017 * 30).toFixed(0) }}/month
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---- Outreach Settings ---- -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent fw-semibold">
                            <i class="fas fa-envelope me-2 text-primary"></i>Outreach Settings
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <label class="form-label">Daily Email Limit</label>
                                <input
                                    v-model.number="form.daily_email_limit"
                                    type="number"
                                    min="1"
                                    max="500"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.daily_email_limit }"
                                />
                                <div v-if="errors.daily_email_limit" class="invalid-feedback">
                                    {{ errors.daily_email_limit }}
                                </div>
                                <div class="form-text">Maximum emails to send per day.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Minimum AI Score Threshold</label>
                                <input
                                    v-model.number="form.min_ai_score"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.min_ai_score }"
                                />
                                <div v-if="errors.min_ai_score" class="invalid-feedback">
                                    {{ errors.min_ai_score }}
                                </div>
                                <div class="form-text">Leads below this score will not be sent emails (1–10).</div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ---- Search Categories ---- -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent fw-semibold">
                            <i class="fas fa-search me-2 text-warning"></i>Search Categories
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Active Categories</label>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span
                                        v-for="(cat, i) in form.search_categories"
                                        :key="i"
                                        class="badge bg-primary d-flex align-items-center gap-1 fs-6 fw-normal"
                                        style="cursor: default"
                                    >
                                        {{ cat }}
                                        <button
                                            type="button"
                                            @click="removeCategory(i)"
                                            class="btn-close btn-close-white ms-1"
                                            style="font-size:0.6rem"
                                        ></button>
                                    </span>
                                    <span v-if="!form.search_categories.length" class="text-muted small">
                                        No categories added.
                                    </span>
                                </div>
                                <div v-if="errors.search_categories" class="text-danger small mb-2">
                                    {{ errors.search_categories }}
                                </div>
                            </div>
                            <div class="input-group input-group-sm">
                                <input
                                    v-model="newCategory"
                                    type="text"
                                    class="form-control"
                                    placeholder="e.g. pharmacies Kampala"
                                    @keyup.enter.prevent="addCategory"
                                />
                                <button type="button" @click="addCategory" class="btn btn-outline-primary">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                            <div class="form-text">These are used by the CollectLeads command.</div>
                        </div>
                    </div>
                </div>

                <!-- ---- Company / Sender Signature ---- -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-semibold">
                            <i class="fas fa-id-card me-2 text-success"></i>Company & Sender Details
                            <span class="text-muted fw-normal small ms-1">(used in outreach email signature)</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.sender_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.sender_name }"
                                        placeholder="John Doe"
                                    />
                                    <div v-if="errors.sender_name" class="invalid-feedback">{{ errors.sender_name }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Position / Title <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.sender_position"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.sender_position }"
                                        placeholder="Sales Manager"
                                    />
                                    <div v-if="errors.sender_position" class="invalid-feedback">{{ errors.sender_position }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.company_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.company_name }"
                                        placeholder="Acme Web Solutions Ltd"
                                    />
                                    <div v-if="errors.company_name" class="invalid-feedback">{{ errors.company_name }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Official Email <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.company_email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.company_email }"
                                        placeholder="info@acmeweb.co.ug"
                                    />
                                    <div v-if="errors.company_email" class="invalid-feedback">{{ errors.company_email }}</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Phone / Tel</label>
                                    <input
                                        v-model="form.company_phone"
                                        type="text"
                                        class="form-control"
                                        placeholder="+256 700 000000"
                                    />
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <input
                                        v-model="form.company_whatsapp"
                                        type="text"
                                        class="form-control"
                                        placeholder="+256 700 000000"
                                    />
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---- Follow-Up Settings ---- -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-semibold">
                            <i class="fas fa-clock me-2 text-info"></i>Follow-Up Reminder Settings
                            <span class="text-muted fw-normal small ms-1">(sent to admin after outreach)</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-12 col-md-6">
                                    <label class="form-label">
                                        Follow-Up Delay (days) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            v-model.number="form.follow_up_days"
                                            type="number"
                                            min="1"
                                            max="90"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.follow_up_days }"
                                        />
                                        <span class="input-group-text text-muted small">days after outreach</span>
                                    </div>
                                    <div v-if="errors.follow_up_days" class="text-danger small mt-1">
                                        {{ errors.follow_up_days }}
                                    </div>
                                    <div class="form-text">
                                        How many days after sending an outreach email before a follow-up reminder is triggered.
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">
                                        Reminder Notification Email <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        v-model="form.follow_up_notification_email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.follow_up_notification_email }"
                                        placeholder="admin@yourcompany.com"
                                    />
                                    <div v-if="errors.follow_up_notification_email" class="invalid-feedback">
                                        {{ errors.follow_up_notification_email }}
                                    </div>
                                    <div class="form-text">
                                        Digest reminder emails listing leads due for follow-up will be sent here daily at 08:00.
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---- Save Button ---- -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4" :disabled="processing">
                        <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="fas fa-save me-2"></i>
                        {{ processing ? 'Saving...' : 'Save Settings' }}
                    </button>
                </div>

            </div>
        </form>
    </AdminLayout>
</template>
