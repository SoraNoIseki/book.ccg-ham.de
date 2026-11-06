import './bootstrap';

import Alpine from 'alpinejs';

import "@vueform/multiselect/themes/default.css";
import 'vue-toast-notification/dist/theme-sugar.css';

(window as any).Alpine = Alpine;

Alpine.start();

// Register static assets in the Vite manifest so Vite::asset() can resolve them.
// `eager` is required: Vite 8 (Rolldown) drops an unused lazy glob and the assets never get emitted.
import.meta.glob([
    '../images/**',
    '../fonts/**',
], { eager: true });

import { createApp } from 'vue';
import { createPinia } from "pinia";
import ToastPlugin from 'vue-toast-notification';

import * as BookGroupApps from '@book/index';
import * as AppAdmin from "@/admin";

import '@vuepic/vue-datepicker/dist/main.css';
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'reverb',
});

const appAdmin = createApp(AppAdmin.App);
appAdmin.mount("#app-admin");

const appSongs = createApp(BookGroupApps.AppSongs);
appSongs.mount("#app-songs");

const appSongAdmin = createApp(BookGroupApps.AppSongAdmin);
appSongAdmin.mount("#app-song-admin");

const appTaskPlan = createApp(BookGroupApps.AppTaskPlan);
appTaskPlan.use(createPinia());
appTaskPlan.use(ToastPlugin);
appTaskPlan.mount("#app-task-plan");

const appPptGenerator = createApp(BookGroupApps.AppPptGenerator);
appPptGenerator.use(createPinia());
appPptGenerator.use(ToastPlugin);
appPptGenerator.mount("#app-ppt-generator");

const appCalendar = createApp(BookGroupApps.AppCalendar);
appCalendar.use(createPinia());
appCalendar.mount("#app-calendar");

const appLibrary = createApp(BookGroupApps.AppLibrary);
appLibrary.use(createPinia());
appLibrary.mount("#app-library");
