import Swiper from 'swiper';
import { A11y, Keyboard, Navigation, Pagination } from 'swiper/modules';

function createSlider(slider) {
  const isCardSlider = ['news', 'students'].includes(slider.dataset.slider);
  const root = slider.closest('[data-slider-root]') ?? slider;
  const nextEl = root.querySelector('[data-slider-next]');
  const paginationEl = root.querySelector('[data-slider-pagination]');
  const prevEl = root.querySelector('[data-slider-prev]');

  return new Swiper(slider, {
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
        : false,
    pagination: paginationEl
      ? {
          clickable: true,
          el: paginationEl,
        }
      : false,
    breakpoints: isCardSlider
      ? {
          768: {
            slidesPerView: 2,
          },
          1024: {
            slidesPerView: 4,
          },
        }
      : undefined,
    slidesPerView: 'auto',
    spaceBetween: isCardSlider ? 20 : 16,
    speed: 650,
    watchOverflow: true,
  });
}

export function initSliders() {
  const sliders = document.querySelectorAll('[data-slider]');
  const mobileMedia = window.matchMedia('(max-width: 767px)');

  sliders.forEach((slider) => {
    if (slider.dataset.slider !== 'news') {
      createSlider(slider);
      return;
    }

    let instance;

    const syncNewsSlider = () => {
      if (mobileMedia.matches) {
        instance?.destroy(true, true);
        instance = undefined;
        return;
      }

      instance ??= createSlider(slider);
    };

    syncNewsSlider();
    mobileMedia.addEventListener('change', syncNewsSlider);
  });
}
