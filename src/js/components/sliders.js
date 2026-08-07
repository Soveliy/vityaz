import Swiper from 'swiper';
import { A11y, Keyboard, Navigation, Pagination } from 'swiper/modules';

export function initSliders() {
  const sliders = document.querySelectorAll('[data-slider]');

  sliders.forEach((slider) => {
    const root = slider.closest('[data-slider-root]') ?? slider;
    const nextEl = root.querySelector('[data-slider-next]');
    const paginationEl = root.querySelector('[data-slider-pagination]');
    const prevEl = root.querySelector('[data-slider-prev]');

    new Swiper(slider, {
      a11y: {
        enabled: true,
      },
      keyboard: {
        enabled: true,
        onlyInViewport: true,
      },
      modules: [A11y, Keyboard, Navigation, Pagination],
      navigation:
        nextEl && prevEl
          ? {
              nextEl,
              prevEl,
            }
          : undefined,
      pagination: paginationEl
        ? {
            clickable: true,
            el: paginationEl,
          }
        : undefined,
      slidesPerView: 'auto',
      spaceBetween: 16,
      speed: 650,
      watchOverflow: true,
    });
  });
}
