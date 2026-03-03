<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const role = computed(() => page.props.auth?.role);

const isSuperAdmin = computed(() => role.value === 'super_admin');
const canManage    = computed(() => role.value === 'super_admin' || role.value === 'manager');

const sidebarCollapsed  = ref(false);
const userMenuOpen      = ref(false);

const toggleUserMenu = () => { userMenuOpen.value = !userMenuOpen.value; };
const closeUserMenu  = () => { userMenuOpen.value = false; };

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const logout = () => {
    router.post(route('logout'));
};

const navItems = [
    { label: 'Dashboard',       icon: 'fas fa-tachometer-alt', route: 'dashboard',       roles: null },
    { label: 'Leads',           icon: 'fas fa-building',       route: 'leads.index',      roles: null },
    { label: 'Approved Leads',  icon: 'fas fa-check-circle',   route: 'leads.index',      query: { approved: 1 }, roles: null },
    { label: 'Contacted Leads', icon: 'fas fa-envelope-open',  route: 'leads.index',      query: { contacted: 1 }, roles: null },
    { label: 'Logs',            icon: 'fas fa-list-alt',       route: 'logs.index',       roles: null },
    { label: 'Users',           icon: 'fas fa-users-cog',      route: 'users.index',      roles: ['super_admin'] },
    { label: 'Settings',        icon: 'fas fa-cog',            route: 'settings.index',   roles: ['super_admin'] },
];

const visibleNavItems = computed(() =>
    navItems.filter(item => ! item.roles || item.roles.includes(role.value))
);

const isActive = (item) => {
    const currentRoute = page.component;
    if (item.route === 'dashboard')      return currentRoute === 'Dashboard/Index';
    if (item.route === 'leads.index')    return currentRoute === 'Leads/Index';
    if (item.route === 'logs.index')     return currentRoute === 'Logs/Index';
    if (item.route === 'settings.index') return currentRoute === 'Settings/Index';
    if (item.route === 'users.index')    return currentRoute === 'Users/Index';
    return false;
};

const getHref = (item) => {
    try {
        return item.query ? route(item.route, item.query) : route(item.route);
    } catch {
        return '#';
    }
};
</script>

<template>
    <div>
        <!-- Sidebar -->
        <nav class="sidebar" :class="{ collapsed: sidebarCollapsed }">
            <!-- Brand -->
            <a href="#" class="brand">
                <img src="/img/logo.png" alt="LeadIntel" style="width:28px;height:28px;object-fit:contain;flex-shrink:0">
                <span class="ms-2">LeadIntel</span>
            </a>

            <!-- Nav items -->
            <ul class="nav flex-column mt-2">
                <li v-for="item in visibleNavItems" :key="item.label" class="nav-item">
                    <Link
                        :href="getHref(item)"
                        class="nav-link"
                        :class="{ active: isActive(item) }"
                    >
                        <i :class="item.icon"></i>
                        <span>{{ item.label }}</span>
                    </Link>
                </li>
            </ul>

            <!-- Profile link (sidebar bottom) -->
            <div class="mt-auto p-2" style="position: absolute; bottom: 0; width: 100%;">
                <Link :href="route('profile.edit')" class="nav-link">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ user?.name }}</span>
                </Link>
            </div>
        </nav>

        <!-- Main wrapper -->
        <div class="main-wrapper" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
            <!-- Top Navbar -->
            <header class="top-navbar d-flex align-items-center px-3">
                <button class="btn btn-sm btn-outline-secondary me-3" @click="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h6 class="mb-0 fw-semibold text-muted">
                    <slot name="title">Dashboard</slot>
                </h6>
                <div class="ms-auto d-flex align-items-center">
                    <!-- User profile dropdown -->
                    <div class="dropdown" v-click-outside="closeUserMenu">
                        <button
                            class="btn btn-sm d-flex align-items-center gap-2 px-2 py-1 rounded-3"
                            style="background:rgba(0,0,0,.04); border:1px solid #e2e8f0"
                            @click="toggleUserMenu"
                        >
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white"
                                  style="width:28px;height:28px;font-size:.75rem;flex-shrink:0">
                                {{ user?.name?.charAt(0)?.toUpperCase() }}
                            </span>
                            <span class="fw-semibold small text-dark">{{ user?.name }}</span>
                            <i class="fas fa-chevron-down text-muted" style="font-size:.65rem"></i>
                        </button>

                        <ul v-if="userMenuOpen"
                            class="dropdown-menu show shadow border-0 mt-1"
                            style="min-width:170px; right:0; left:auto">
                            <li>
                                <Link :href="route('profile.edit')" class="dropdown-item py-2" @click="closeUserMenu">
                                    <i class="fas fa-user-circle me-2 text-muted"></i>Profile
                                </Link>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button class="dropdown-item py-2 text-danger" @click="logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="page-content">
                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ $page.props.flash.success }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ $page.props.flash.error }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
