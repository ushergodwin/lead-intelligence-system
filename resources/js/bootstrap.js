import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;  // Send session cookies to /api/* (Sanctum SPA auth)
window.axios.defaults.withXSRFToken   = true;  // Attach XSRF-TOKEN cookie as X-XSRF-TOKEN header
