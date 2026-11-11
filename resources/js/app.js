import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    // The public company page names itself; portal pages carry the أرشيف دال suffix.
    title: (title) => (!title ? 'أرشيف دال' : title === 'Archiving App' ? title : `${title} · أرشيف دال`),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
    progress: { color: '#03875A' },
});
