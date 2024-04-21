import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import './project';
import '../../packages/soranoiseki/book-group/src/Resources/js/book';

import.meta.glob([
    '../images/**',
    '../fonts/**',
]);