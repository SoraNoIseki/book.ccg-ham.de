import './bootstrap';

import Alpine from 'alpinejs';
import 'flowbite';

import "@vueform/multiselect/themes/default.css";

(window as any).Alpine = Alpine;

Alpine.start();

import './project';
import '../../packages/soranoiseki/book-group/src/Resources/js/book';

import.meta.glob([
    '../images/**',
    '../fonts/**',
]);

import { createApp } from 'vue';
import { createPinia } from "pinia";
import VueDatePicker from '@vuepic/vue-datepicker';

import * as AppSongs from "../../packages/soranoiseki/book-group/src/Resources/js/songs";
import * as AppTaskPlan from "../../packages/soranoiseki/book-group/src/Resources/js/task-plan";
import * as AppAdmin from "./admin";

import '@vuepic/vue-datepicker/dist/main.css';

// App PPT Generator
const appSongs = createApp(AppSongs.App);
appSongs.mount("#app-songs");

const appAdmin = createApp(AppAdmin.App);
appAdmin.mount("#app-admin");

const taskPlan = createApp(AppTaskPlan.App);;
taskPlan.use(createPinia());
taskPlan.mount("#app-task-plan");