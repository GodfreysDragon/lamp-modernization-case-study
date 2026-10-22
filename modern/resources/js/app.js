import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import AppLayout from './layouts/AppLayout.vue';

createInertiaApp({
    title: (title) => (title ? `${title} · HelpDesk` : 'HelpDesk'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
        return pages[`./pages/${name}.vue`];
    },
    // Every page gets the app shell except the login screen.
    layout: (name) => (name.startsWith('auth/') ? null : AppLayout),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#4f46e5' },
});
