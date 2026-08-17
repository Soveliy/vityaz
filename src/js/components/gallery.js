import { Fancybox } from '@fancyapps/ui/dist/fancybox/';

const GALLERY_SELECTOR = '[data-fancybox]';

export function initGallery() {
  if (!document.querySelector(GALLERY_SELECTOR)) return;

  Fancybox.bind(GALLERY_SELECTOR, {});
}
