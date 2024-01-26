import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'
import liveReload from 'vite-plugin-live-reload';
import { viteStaticCopy } from "vite-plugin-static-copy";
import path from "path";
import AutoImport from 'unplugin-auto-import/vite'

const {ElementPlusResolver} = require("unplugin-vue-components/resolvers");
const Components = require("unplugin-vue-components/vite");
// https://vitejs.dev/config/

//Add All css and js here
//Important: Key must be output filepath without extension, and value will be the file source
const inputs = [
    'src/admin/start.js',
    'src/admin/FontManager/FontManager.js',
    'src/scss/admin/app.scss',
];
export default defineConfig({
    plugins:
        [
            vue(),
            viteStaticCopy({
                targets: [
                    { src: "src/images", dest: "" },
                ],
            }),
            liveReload([
                `${__dirname}/**/*\.php`,
            ]),
            AutoImport({
                resolvers: [ElementPlusResolver()],
            }),
            Components({
                resolvers: [ElementPlusResolver()],
                directives: false
            }),
        ],

    build: {
        manifest: true,
        outDir: 'assets',
        //assetsDir: '',
        publicDir: 'assets',
        //root: '/',
        emptyOutDir: true, // delete the contents of the output directory before each build

        // https://rollupjs.org/guide/en/#big-list-of-options
        rollupOptions: {
            input: inputs,
            output: {
                chunkFileNames: '[name].js',
                entryFileNames: '[name].js',
            },
        },
    },

    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
            '@': path.resolve(__dirname, 'resources/admin'),
        },
    },

    server: {
        port: 2222,
        strictPort: true,
        hmr: {
            port: 2222,
            host: 'localhost',
            protocol: 'ws',
        }
    }
})
