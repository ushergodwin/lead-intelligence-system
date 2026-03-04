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
    daily_sms_limit:              props.settings.daily_sms_limit      ?? 30,
    min_ai_score:                 props.settings.min_ai_score,
    min_review_year:              props.settings.min_review_year      ?? 0,
    search_categories:            [...(props.settings.search_categories ?? [])],
    company_name:                 props.settings.company_name         ?? '',
    sender_name:                  props.settings.sender_name          ?? '',
    sender_position:              props.settings.sender_position      ?? '',
    company_email:                props.settings.company_email        ?? '',
    company_phone:                props.settings.company_phone        ?? '',
    company_whatsapp:             props.settings.company_whatsapp     ?? '',
    follow_up_days:               props.settings.follow_up_days       ?? 4,
    sms_follow_up_days:           props.settings.sms_follow_up_days   ?? 3,
    follow_up_notification_email: props.settings.follow_up_notification_email ?? '',
    email_subject_template:       props.settings.email_subject_template  ?? '',
    email_body_template:          props.settings.email_body_template     ?? '',
    sms_body_template:            props.settings.sms_body_template       ?? '',
    sms_follow_up_template:       props.settings.sms_follow_up_template  ?? '',
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
                                <label class="form-label">Daily SMS Limit</label>
                                <input
                                    v-model.number="form.daily_sms_limit"
                                    type="number"
                                    min="1"
                                    max="500"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.daily_sms_limit }"
                                />
                                <div v-if="errors.daily_sms_limit" class="invalid-feedback">
                                    {{ errors.daily_sms_limit }}
                                </div>
                                <div class="form-text">
                                    Maximum SMS to send per day.
                                    <span class="text-muted">≈ {{ (form.daily_sms_limit * 32).toLocaleString() }} UGX/day</span>
                                </div>
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
                                <div class="form-text">Leads below this score will not be sent outreach (1–10).</div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Min Review Year Filter</label>
                                <input
                                    v-model.number="form.min_review_year"
                                    type="number"
                                    min="0"
                                    max="2099"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.min_review_year }"
                                    placeholder="0"
                                />
                                <div v-if="errors.min_review_year" class="invalid-feedback">
                                    {{ errors.min_review_year }}
                                </div>
                                <div class="form-text">
                                    Only collect leads with a Google review in or after this year.
                                    Set to <code>0</code> to disable the filter.
                                </div>
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
                                        Email Follow-Up Delay (days) <span class="text-danger">*</span>
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
                                        <span class="input-group-text text-muted small">days after email</span>
                                    </div>
                                    <div v-if="errors.follow_up_days" class="text-danger small mt-1">
                                        {{ errors.follow_up_days }}
                                    </div>
                                    <div class="form-text">
                                        Days after sending an outreach email before a follow-up reminder is triggered.
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">
                                        SMS Follow-Up Delay (days) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            v-model.number="form.sms_follow_up_days"
                                            type="number"
                                            min="1"
                                            max="90"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.sms_follow_up_days }"
                                        />
                                        <span class="input-group-text text-muted small">days after SMS</span>
                                    </div>
                                    <div v-if="errors.sms_follow_up_days" class="text-danger small mt-1">
                                        {{ errors.sms_follow_up_days }}
                                    </div>
                                    <div class="form-text">
                                        Days after the initial SMS before a follow-up SMS is sent automatically.
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

                <!-- ---- Message Templates ---- -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-semibold">
                            <i class="fas fa-file-alt me-2 text-purple" style="color:#6f42c1"></i>Message Templates
                            <span class="text-muted fw-normal small ms-1">
                                (used in outreach emails and SMS)
                            </span>
                        </div>
                        <div class="card-body">

                            <div class="alert alert-light border small mb-3 py-2">
                                <strong>Available placeholders:</strong>
                                <code class="ms-1">{business_name}</code>
                                <code class="ms-1">{reviews_count}</code>
                                <code class="ms-1">{rating}</code>
                                <span class="text-muted ms-1">(email only)</span>
                                &nbsp;|&nbsp;
                                <code>{reviews_label}</code>
                                <code class="ms-1">{signature}</code>
                                <span class="text-muted ms-1">(SMS only)</span>
                            </div>

                            <!-- Email Subject -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Email Subject <span class="text-danger">*</span>
                                </label>
                                <input
                                    v-model="form.email_subject_template"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.email_subject_template }"
                                    placeholder="We noticed {business_name} doesn't have a website"
                                />
                                <div v-if="errors.email_subject_template" class="invalid-feedback">
                                    {{ errors.email_subject_template }}
                                </div>
                                <div class="form-text">
                                    Supports: <code>{business_name}</code>
                                </div>
                            </div>

                            <!-- Email Body -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Email Body <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    v-model="form.email_body_template"
                                    rows="10"
                                    class="form-control font-monospace"
                                    :class="{ 'is-invalid': errors.email_body_template }"
                                    placeholder="One paragraph per line. Each line becomes a separate paragraph in the email."
                                ></textarea>
                                <div v-if="errors.email_body_template" class="invalid-feedback">
                                    {{ errors.email_body_template }}
                                </div>
                                <div class="form-text">
                                    One paragraph per line. Supports:
                                    <code>{business_name}</code>
                                    <code>{reviews_count}</code>
                                    <code>{rating}</code>
                                    — max 5000 chars.
                                </div>
                            </div>

                            <!-- SMS Initial Body -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    SMS — Initial Outreach <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    v-model="form.sms_body_template"
                                    rows="3"
                                    class="form-control font-monospace"
                                    :class="{ 'is-invalid': errors.sms_body_template }"
                                    placeholder="Hello {business_name}, ..."
                                ></textarea>
                                <div v-if="errors.sms_body_template" class="invalid-feedback">
                                    {{ errors.sms_body_template }}
                                </div>
                                <div class="d-flex justify-content-between align-items-start mt-1">
                                    <div class="form-text">
                                        Supports:
                                        <code>{business_name}</code>
                                        <code>{reviews_label}</code>
                                        <code>{signature}</code>
                                        — max 500 chars.
                                    </div>
                                    <span
                                        class="badge ms-2 flex-shrink-0"
                                        :class="form.sms_body_template.length > 480
                                            ? 'bg-danger'
                                            : form.sms_body_template.length > 160
                                                ? 'bg-warning text-dark'
                                                : 'bg-secondary'"
                                    >
                                        {{ form.sms_body_template.length }}/500
                                    </span>
                                </div>
                            </div>

                            <!-- SMS Follow-Up Body -->
                            <div class="mb-2">
                                <label class="form-label fw-semibold">
                                    SMS — Follow-Up Message <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    v-model="form.sms_follow_up_template"
                                    rows="3"
                                    class="form-control font-monospace"
                                    :class="{ 'is-invalid': errors.sms_follow_up_template }"
                                    placeholder="Hi {business_name}, just following up ..."
                                ></textarea>
                                <div v-if="errors.sms_follow_up_template" class="invalid-feedback">
                                    {{ errors.sms_follow_up_template }}
                                </div>
                                <div class="d-flex justify-content-between align-items-start mt-1">
                                    <div class="form-text">
                                        Sent automatically after the SMS follow-up delay. Same placeholders as above.
                                    </div>
                                    <span
                                        class="badge ms-2 flex-shrink-0"
                                        :class="form.sms_follow_up_template.length > 480
                                            ? 'bg-danger'
                                            : form.sms_follow_up_template.length > 160
                                                ? 'bg-warning text-dark'
                                                : 'bg-secondary'"
                                    >
                                        {{ form.sms_follow_up_template.length }}/500
                                    </span>
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
