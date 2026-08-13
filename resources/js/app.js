import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'InventPro';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render: () =>
                h('div', { class: 'contents' }, [
                    h(App, props),
                    h(Toaster, {
                        position: 'top-right',
                        richColors: true,
                        closeButton: true,
                        duration: 4000,
                    }),
                ]),
        });

        vueApp.use(plugin);
        vueApp.use(ZiggyVue);
        vueApp.mount(el);

        return vueApp;
    },
    progress: {
        color: '#0D9488',
    },
});
