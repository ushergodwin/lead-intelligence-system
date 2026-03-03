<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// ---- SweetAlert2 mixins with Bootstrap styling ----
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
    toast:              true,
    position:           'top-end',
    showConfirmButton:  false,
    timer:              3000,
    timerProgressBar:   true,
    customClass: { popup: 'shadow-sm' },
});

const props = defineProps({
    leads:      { type: Object, required: true },
    categories: { type: Array,  required: true },
    filters:    { type: Object, required: true },
});

// ---- Phone helper (mirrors PhoneHelper.php) ----
const normalizeMobile = (phone) => {
    if (!phone) return '';
    let n = phone.replace(/[^0-9+]/g, '');
    if (n.startsWith('+256')) return n.slice(1);       // +256... → 256...
    if (n.startsWith('0') && n.length === 10) return '256' + n.slice(1); // 07X → 256X
    return n;
};

const isMobile = (phone) => {
    if (!phone) return false;
    const n = normalizeMobile(phone);
    const digits = n.startsWith('256') ? n.slice(3) : n;
    return ['77', '78', '76', '70', '75', '74'].some(p => digits.startsWith(p));
};

// ---- Filters form ----
const filters = reactive({
    search:     props.filters.search     ?? '',
    high_score: props.filters.high_score ?? '',
    approved:   props.filters.approved   ?? '',
    contacted:  props.filters.contacted  ?? '',
    has_mobile: props.filters.has_mobile ?? '',
    category:   props.filters.category   ?? '',
    sort:       props.filters.sort       ?? 'created_at',
    direction:  props.filters.direction  ?? 'desc',
});

const applyFilters = () => {
    const params = Object.fromEntries(
        Object.entries(filters).filter(([, v]) => v !== '' && v !== false && v !== null)
    );
    router.get(route('leads.index'), params, { preserveState: true, replace: true });
};

const resetFilters = () => {
    Object.assign(filters, {
        search: '', high_score: '', approved: '',
        contacted: '', has_mobile: '', category: '', sort: 'created_at', direction: 'desc',
    });
    router.get(route('leads.index'));
};

const sortBy = (field) => {
    if (filters.sort === field) {
        filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
    } else {
        filters.sort = field;
        filters.direction = 'desc';
    }
    applyFilters();
};

// ---- Detail modal ----
const selectedLead   = ref(null);
const loadingLead    = ref(false);
const showLeadModal  = ref(false);

const viewLead = async (lead) => {
    loadingLead.value = true;
    showLeadModal.value = true;
    try {
        const res = await axios.get(route('leads.show', lead.id));
        selectedLead.value = res.data;
    } finally {
        loadingLead.value = false;
    }
};

const closeModal = () => {
    showLeadModal.value = false;
    selectedLead.value  = null;
};

// ---- Approve toggle ----
const toggling = ref(null);
const toggleApprove = async (lead) => {
    toggling.value = lead.id;
    try {
        const res = await axios.patch(route('leads.approve', lead.id));
        lead.approved_for_outreach = res.data.approved_for_outreach;
        toast.fire({ icon: 'success', title: res.data.message });
    } catch {
        toast.fire({ icon: 'error', title: 'Could not update approval.' });
    } finally {
        toggling.value = null;
    }
};

// ---- Send email ----
const emailModal   = ref(false);
const emailLead    = ref(null);
const emailAddress = ref('');
const sendingEmail = ref(false);

const openEmailModal = (lead) => {
    emailLead.value    = lead;
    emailAddress.value = '';
    emailModal.value   = true;
};

const sendEmail = async () => {
    sendingEmail.value = true;
    try {
        const res = await axios.post(route('leads.send-email', emailLead.value.id), { email: emailAddress.value });
        emailModal.value = false;
        toast.fire({ icon: 'success', title: res.data.message });
    } catch (e) {
        toast.fire({ icon: 'error', title: e.response?.data?.message ?? 'Failed to queue email.' });
    } finally {
        sendingEmail.value = false;
    }
};

// ---- Send SMS ----
const smsModal   = ref(false);
const smsLead    = ref(null);
const sendingSms = ref(false);

const smsPreview = (lead) => {
    if (!lead) return '';
    const reviews = lead.reviews_count ?? 0;
    const label   = reviews === 1 ? '1 Google review' : `${reviews} Google reviews`;
    return `Hello ${lead.business_name}, We saw your ${label} - that's impressive. A simple website could help convert more search traffic into sales. Can we share a quick idea with you? - [Company Name] | [Company Phone]`;
};

const openSmsModal = (lead) => {
    smsLead.value  = lead;
    smsModal.value = true;
};

const sendSms = async () => {
    sendingSms.value = true;
    try {
        const res = await axios.post(route('leads.send-sms', smsLead.value.id));
        if (res.data.sms_sent_at) {
            smsLead.value.sms_sent_at = res.data.sms_sent_at;
        }
        smsModal.value = false;
        toast.fire({ icon: 'success', title: res.data.message });
    } catch (e) {
        toast.fire({ icon: 'error', title: e.response?.data?.message ?? 'Failed to send SMS.' });
    } finally {
        sendingSms.value = false;
    }
};

// ---- Delete ----
const deleteLead = async (lead) => {
    const result = await dialog.fire({
        title:             'Delete Lead?',
        html:              `<strong>${lead.business_name}</strong> will be permanently removed.`,
        icon:              'warning',
        showCancelButton:  true,
        confirmButtonText: '<i class="fas fa-trash me-1"></i> Yes, Delete',
        cancelButtonText:  'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger px-4 me-2',
            cancelButton:  'btn btn-secondary px-4',
        },
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(route('leads.destroy', lead.id));
        toast.fire({ icon: 'success', title: 'Lead deleted.' });
        router.reload({ only: ['leads'] });
    } catch {
        toast.fire({ icon: 'error', title: 'Could not delete lead.' });
    }
};

// ---- Score badge ----
const scoreBadgeClass = (score) => {
    if (!score) return 'badge badge-score-none';
    if (score >= 8) return 'badge badge-score-high';
    if (score >= 5) return 'badge badge-score-medium';
    return 'badge badge-score-low';
};
</script>

<template>
    <Head title="Leads" />
    <AdminLayout>
        <template #title>Leads Management</template>

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0">Leads</h4>
            <span class="badge bg-secondary">{{ leads.total }} total</span>
        </div>

        <!-- Filters card -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <input
                            v-model="filters.search"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Search business name..."
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <div class="col-6 col-md-2">
                        <select v-model="filters.category" class="form-select form-select-sm">
                            <option value="">All categories</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select v-model="filters.sort" class="form-select form-select-sm">
                            <option value="created_at">Date Added</option>
                            <option value="ai_score">AI Score</option>
                            <option value="rating">Rating</option>
                            <option value="reviews_count">Reviews</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" v-model="filters.high_score" value="1" id="fHigh">
                            <label class="form-check-label small" for="fHigh">High Score</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" v-model="filters.approved" value="1" id="fApproved">
                            <label class="form-check-label small" for="fApproved">Approved</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" v-model="filters.contacted" value="1" id="fContacted">
                            <label class="form-check-label small" for="fContacted">Contacted</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" v-model="filters.has_mobile" value="1" id="fMobile">
                            <label class="form-check-label small" for="fMobile">
                                <i class="fas fa-mobile-alt me-1 text-success"></i>Has Mobile
                            </label>
                        </div>
                    </div>
                    <div class="col-auto ms-md-auto d-flex gap-2">
                        <button @click="applyFilters" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <button @click="resetFilters" class="btn btn-outline-secondary btn-sm">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-leads table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Business Name</th>
                                <th>Category</th>
                                <th class="cursor-pointer" @click="sortBy('rating')">
                                    Rating <i class="fas fa-sort ms-1 opacity-50"></i>
                                </th>
                                <th class="cursor-pointer" @click="sortBy('reviews_count')">
                                    Reviews <i class="fas fa-sort ms-1 opacity-50"></i>
                                </th>
                                <th class="cursor-pointer" @click="sortBy('ai_score')">
                                    AI Score <i class="fas fa-sort ms-1 opacity-50"></i>
                                </th>
                                <th>Approved</th>
                                <th>Contacted</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="lead in leads.data" :key="lead.id">
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ lead.business_name }}</div>
                                    <div class="text-muted small text-truncate" style="max-width:200px">{{ lead.address }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ lead.category }}</span></td>
                                <td>
                                    <span v-if="lead.rating">
                                        <i class="fas fa-star text-warning me-1"></i>{{ lead.rating }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>{{ lead.reviews_count ?? '—' }}</td>
                                <td>
                                    <span :class="scoreBadgeClass(lead.ai_score)">
                                        {{ lead.ai_score ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="lead.approved_for_outreach ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ lead.approved_for_outreach ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="lead.contacted ? 'badge bg-info' : 'badge bg-light text-dark border'">
                                        {{ lead.contacted ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button @click="viewLead(lead)" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button
                                            @click="toggleApprove(lead)"
                                            :disabled="toggling === lead.id"
                                            :class="lead.approved_for_outreach ? 'btn btn-sm btn-success' : 'btn btn-sm btn-outline-success'"
                                            title="Toggle Approve"
                                        >
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button @click="openEmailModal(lead)" class="btn btn-sm btn-outline-info" title="Send Email">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button
                                            v-if="isMobile(lead.phone)"
                                            @click="openSmsModal(lead)"
                                            :class="lead.sms_sent_at ? 'btn btn-sm btn-success' : 'btn btn-sm btn-outline-success'"
                                            :title="lead.sms_sent_at ? 'SMS already sent' : 'Send SMS'"
                                        >
                                            <i class="fas fa-sms"></i>
                                        </button>
                                        <button @click="deleteLead(lead)" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!leads.data.length">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                                    No leads found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="leads.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ leads.from }}–{{ leads.to }} of {{ leads.total }}
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ disabled: !leads.prev_page_url }">
                            <a class="page-link" :href="leads.prev_page_url ?? '#'">‹</a>
                        </li>
                        <li
                            v-for="link in leads.links.slice(1, -1)"
                            :key="link.label"
                            class="page-item"
                            :class="{ active: link.active }"
                        >
                            <a class="page-link" :href="link.url ?? '#'" v-html="link.label"></a>
                        </li>
                        <li class="page-item" :class="{ disabled: !leads.next_page_url }">
                            <a class="page-link" :href="leads.next_page_url ?? '#'">›</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- ===================== LEAD DETAIL MODAL ===================== -->
        <div v-if="showLeadModal" class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-building me-2 text-primary"></i>Lead Details
                        </h5>
                        <button class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loadingLead" class="text-center py-4">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div v-else-if="selectedLead">
                            <dl class="row">
                                <dt class="col-sm-4">Business Name</dt>
                                <dd class="col-sm-8 fw-semibold">{{ selectedLead.business_name }}</dd>

                                <dt class="col-sm-4">Category</dt>
                                <dd class="col-sm-8">{{ selectedLead.category }}</dd>

                                <dt class="col-sm-4">Address</dt>
                                <dd class="col-sm-8">{{ selectedLead.address }}</dd>

                                <dt class="col-sm-4">Phone</dt>
                                <dd class="col-sm-8">{{ selectedLead.phone ?? '—' }}</dd>

                                <dt class="col-sm-4">Rating</dt>
                                <dd class="col-sm-8">
                                    <i class="fas fa-star text-warning me-1"></i>
                                    {{ selectedLead.rating ?? '—' }}
                                    ({{ selectedLead.reviews_count ?? 0 }} reviews)
                                </dd>

                                <dt class="col-sm-4">AI Score</dt>
                                <dd class="col-sm-8">
                                    <span :class="scoreBadgeClass(selectedLead.ai_score)">
                                        {{ selectedLead.ai_score ?? 'Not scored' }}
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Google Maps</dt>
                                <dd class="col-sm-8">
                                    <a :href="selectedLead.google_maps_url" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-map-marker-alt me-1"></i>Open in Maps
                                    </a>
                                </dd>

                                <dt class="col-sm-4">Approved</dt>
                                <dd class="col-sm-8">
                                    <span :class="selectedLead.approved_for_outreach ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ selectedLead.approved_for_outreach ? 'Yes' : 'No' }}
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Contacted</dt>
                                <dd class="col-sm-8">
                                    <span :class="selectedLead.contacted ? 'badge bg-info' : 'badge bg-secondary'">
                                        {{ selectedLead.contacted ? 'Yes' : 'No' }}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            v-if="selectedLead"
                            @click="toggleApprove(selectedLead); selectedLead.approved_for_outreach = !selectedLead.approved_for_outreach"
                            :class="selectedLead?.approved_for_outreach ? 'btn btn-success' : 'btn btn-outline-success'"
                        >
                            <i class="fas fa-check me-1"></i>
                            {{ selectedLead?.approved_for_outreach ? 'Revoke Approval' : 'Approve' }}
                        </button>
                        <button v-if="selectedLead" @click="openEmailModal(selectedLead); closeModal()" class="btn btn-info">
                            <i class="fas fa-envelope me-1"></i>Send Email
                        </button>
                        <button
                            v-if="selectedLead && isMobile(selectedLead.phone)"
                            @click="openSmsModal(selectedLead); closeModal()"
                            :class="selectedLead.sms_sent_at ? 'btn btn-success' : 'btn btn-outline-success'"
                        >
                            <i class="fas fa-sms me-1"></i>
                            {{ selectedLead.sms_sent_at ? 'SMS Sent' : 'Send SMS' }}
                        </button>
                        <button class="btn btn-secondary" @click="closeModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showLeadModal" class="modal-backdrop fade show"></div>

        <!-- ===================== SEND EMAIL MODAL ===================== -->
        <div v-if="emailModal" class="modal fade show d-block" tabindex="-1" @click.self="emailModal = false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-envelope me-2 text-info"></i>Send Email</h5>
                        <button class="btn-close" @click="emailModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Send outreach email to <strong>{{ emailLead?.business_name }}</strong>
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Recipient Email Address</label>
                            <input
                                v-model="emailAddress"
                                type="email"
                                class="form-control"
                                placeholder="recipient@example.com"
                                :disabled="sendingEmail"
                            />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="sendEmail" :disabled="sendingEmail || !emailAddress" class="btn btn-info">
                            <span v-if="sendingEmail" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="fas fa-paper-plane me-1"></i>
                            {{ sendingEmail ? 'Sending...' : 'Queue Email' }}
                        </button>
                        <button class="btn btn-secondary" @click="emailModal = false">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="emailModal" class="modal-backdrop fade show"></div>

        <!-- ===================== SEND SMS MODAL ===================== -->
        <div v-if="smsModal" class="modal fade show d-block" tabindex="-1" @click.self="smsModal = false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-sms me-2 text-success"></i>Send SMS
                        </h5>
                        <button class="btn-close" @click="smsModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width:40px;height:40px;flex-shrink:0">
                                <i class="fas fa-mobile-alt text-success"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ smsLead?.business_name }}</div>
                                <div class="text-muted small">
                                    <i class="fas fa-phone me-1"></i>{{ smsLead?.phone }}
                                    <span v-if="smsLead?.sms_sent_at" class="badge bg-success ms-2 fw-normal">
                                        <i class="fas fa-check me-1"></i>Already sent
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold text-uppercase">Message Preview</label>
                            <div class="p-3 rounded border bg-light font-monospace small" style="white-space:pre-line">{{ smsPreview(smsLead) }}</div>
                            <div class="form-text">Company name will be filled from your Settings.</div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button
                            @click="sendSms"
                            :disabled="sendingSms"
                            class="btn btn-success"
                        >
                            <span v-if="sendingSms" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="fas fa-paper-plane me-1"></i>
                            {{ sendingSms ? 'Sending...' : 'Send SMS' }}
                        </button>
                        <button class="btn btn-secondary" @click="smsModal = false">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="smsModal" class="modal-backdrop fade show"></div>
    </AdminLayout>
</template>
