import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import './project';

import.meta.glob([
    '../images/**',
    '../fonts/**',
]);