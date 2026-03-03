<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const kpiCards = (stats) => [
    {
        title:  'Total Leads',
        value:  stats.total_leads,
        icon:   'fas fa-building',
        color:  'primary',
        bg:     'bg-primary bg-opacity-10',
        text:   'text-primary',
    },
    {
        title:  'High Score Leads',
        value:  stats.high_score_leads,
        icon:   'fas fa-star',
        color:  'warning',
        bg:     'bg-warning bg-opacity-10',
        text:   'text-warning',
    },
    {
        title:  'Approved Leads',
        value:  stats.approved_leads,
        icon:   'fas fa-check-circle',
        color:  'success',
        bg:     'bg-success bg-opacity-10',
        text:   'text-success',
    },
    {
        title:  'Contacted Leads',
        value:  stats.contacted_leads,
        icon:   'fas fa-envelope',
        color:  'info',
        bg:     'bg-info bg-opacity-10',
        text:   'text-info',
    },
    {
        title:  'Emails Sent Today',
        value:  stats.emails_sent_today,
        icon:   'fas fa-paper-plane',
        color:  'secondary',
        bg:     'bg-secondary bg-opacity-10',
        text:   'text-secondary',
    },
];
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <template #title>Dashboard</template>

        <div class="mb-4">
            <h4 class="fw-bold mb-1">Overview</h4>
            <p class="text-muted mb-0">Lead Intelligence System — at a glance</p>
        </div>

        <div class="row g-3">
            <div
                v-for="card in kpiCards(stats)"
                :key="card.title"
                class="col-12 col-sm-6 col-xl-4"
            >
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div :class="['kpi-icon', card.bg, card.text]">
                            <i :class="card.icon"></i>
                        </div>
                        <div>
                            <div class="text-muted small">{{ card.title }}</div>
                            <div class="fs-3 fw-bold">{{ card.value.toLocaleString() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title fw-semibold mb-3">
                            <i class="fas fa-bolt text-warning me-2"></i>Quick Actions
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a :href="route('leads.index')" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-1"></i>View All Leads
                            </a>
                            <a :href="route('leads.index', { approved: 1 })" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-check me-1"></i>Approved Leads
                            </a>
                            <a :href="route('logs.index')" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-clipboard-list me-1"></i>Outreach Logs
                            </a>
                            <a :href="route('settings.index')" class="btn btn-outline-dark btn-sm">
                                <i class="fas fa-cog me-1"></i>Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
