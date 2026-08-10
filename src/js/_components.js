import { initAnimations } from './components/animations.js';
import { initGallery } from './components/gallery.js';
import { initMaps } from './components/maps.js';
import { initModals } from './components/modal.js';
import { initNavigation } from './components/navigation.js';
import { initPageState } from './components/page-state.js';
import { initSliders } from './components/sliders.js';
import { initSmoothScroll } from './components/smooth-scroll.js';

initPageState();
initNavigation();
initModals();
initSmoothScroll();
initAnimations();
initGallery();
initSliders();
initMaps();
