import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Finisher Legacy';

const PUBLIC_PAGES = new Set([
    'Home',
    'HowItWorks',
    'Privacy',
    'Terms',
    'Contact',
]);
const PUBLIC_PREFIXES = ['events/', 'legacy-code/', 'profile/', 'errors/'];

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case PUBLIC_PAGES.has(name):
            case PUBLIC_PREFIXES.some((prefix) => name.startsWith(prefix)):
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('operator/'):
            case name.startsWith('production/'):
                return OperatorLayout;
            case name.startsWith('admin/'):
            case name.startsWith('imports/'):
                return AdminLayout;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
