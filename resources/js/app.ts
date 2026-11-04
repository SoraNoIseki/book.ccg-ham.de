import './bootstrap';

import Alpine from 'alpinejs';
import 'flowbite';

import "@vueform/multiselect/themes/default.css";
import 'vue-toast-notification/dist/theme-sugar.css';

(window as any).Alpine = Alpine;

Alpine.start();

import './project';

import.meta.glob([
    '../images/**',
    '../fonts/**',
]);

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

const appTaskPlan = createApp(BookGroupApps.AppTaskPlan);
appTaskPlan.use(createPinia());
appTaskPlan.use(ToastPlugin);
appTaskPlan.mount("#app-task-plan");

const appPptGenerator = createApp(BookGroupApps.AppPptGenerator);
appPptGenerator.use(createPinia());
appPptGenerator.use(ToastPlugin);
appPptGenerator.mount("#app-ppt-generator");
