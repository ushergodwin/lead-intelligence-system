<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    logs:    { type: Object, required: true },
    filters: { type: Object, required: true },
});

const filters = reactive({
    status: props.filters.status ?? '',
});

const applyFilters = () => {
    const params = Object.fromEntries(
        Object.entries(filters).filter(([, v]) => v !== '')
    );
    router.get(route('logs.index'), params, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.status = '';
    router.get(route('logs.index'));
};

const statusBadge = (status) => {
    if (status === 'sent')   return 'badge bg-success';
    if (status === 'failed') return 'badge bg-danger';
    return 'badge bg-secondary';
};

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString();
};
</script>

<template>
    <Head title="Outreach Logs" />
    <AdminLayout>
        <template #title>Outreach Logs</template>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0">Outreach Logs</h4>
            <span class="badge bg-secondary">{{ logs.total }} total</span>
        </div>

        <!-- Filter -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <select v-model="filters.status" class="form-select form-select-sm">
                            <option value="">All statuses</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button @click="applyFilters" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <button @click="resetFilters" class="btn btn-outline-secondary btn-sm ms-1">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Business</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Response</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id">
                                <td class="ps-3">{{ log.lead?.business_name ?? '—' }}</td>
                                <td>{{ log.email }}</td>
                                <td><span :class="statusBadge(log.status)">{{ log.status }}</span></td>
                                <td>
                                    <span class="text-muted small text-truncate d-inline-block" style="max-width:200px">
                                        {{ log.response ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ formatDate(log.sent_at) }}</td>
                            </tr>
                            <tr v-if="!logs.data.length">
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                                    No log entries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ disabled: !logs.prev_page_url }">
                            <a class="page-link" :href="logs.prev_page_url ?? '#'">‹</a>
                        </li>
                        <li
                            v-for="link in logs.links.slice(1, -1)"
                            :key="link.label"
                            class="page-item"
                            :class="{ active: link.active }"
                        >
                            <a class="page-link" :href="link.url ?? '#'" v-html="link.label"></a>
                        </li>
                        <li class="page-item" :class="{ disabled: !logs.next_page_url }">
                            <a class="page-link" :href="logs.next_page_url ?? '#'">›</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>
