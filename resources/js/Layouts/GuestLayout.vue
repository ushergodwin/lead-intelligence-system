<template>
    <div class="auth-page min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="auth-card card border-0 shadow-lg w-100">
            <div class="card-body p-4 p-md-5">

                <!-- Brand -->
                <div class="text-center mb-4">
                    <div class="auth-brand-icon d-inline-flex align-items-center justify-content-center rounded-3 mb-3">
                        <i class="fas fa-brain text-white fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark">Lead Intelligence</h4>
                    <p class="text-muted small mb-0">B2B Outreach Management</p>
                </div>

                <slot />

            </div>
        </div>
    </div>
</template>
