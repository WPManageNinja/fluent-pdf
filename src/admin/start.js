import routes from './routes';
import {createWebHashHistory, createRouter} from 'vue-router'
import WPPluginVueTailwind from './Bits/WPPluginVueTailwind';

const router = createRouter({
    history: createWebHashHistory(),
    routes
});


const framework = new WPPluginVueTailwind();

framework.app.config.globalProperties.appVars = window.WPPluginVueTailwindAdmin;

window.WPPluginVueTailwindApp = framework.app.use(router).mount('#fluent-pdf_app');

router.afterEach((to, from) => {
    jQuery('.fluent_pdf_menu_item').removeClass('active');
    let active = to.meta.active;
    if (active) {
        jQuery('.fluent_pdf_main-menu-items').find('li[data-key=' + active + ']').addClass('active');
    }
});
