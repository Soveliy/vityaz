import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger);

const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

let smoothScroll = null;

function getAnchorTarget(link) {
  const href = link.getAttribute('href');

  if (!href || href === '#' || !href.startsWith('#')) {
    return null;
  }

  return document.querySelector(href);
}

function initAnchorLinks() {
  const links = document.querySelectorAll('a[href^="#"]');

  links.forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = getAnchorTarget(link);

      if (!target) {
        return;
      }

      event.preventDefault();

      if (smoothScroll) {
        smoothScroll.scrollTo(target, {
          offset: Number(link.dataset.scrollOffset ?? 0),
        });

        return;
      }

      target.scrollIntoView({
        behavior: motionQuery.matches ? 'auto' : 'smooth',
        block: 'start',
      });
    });
  });
}

export function getSmoothScroll() {
  return smoothScroll;
}

export function initSmoothScroll() {
  initAnchorLinks();

  if (motionQuery.matches) {
    return null;
  }

  smoothScroll = new Lenis({
    lerp: 0.08,
    smoothWheel: true,
    touchMultiplier: 1.4,
    wheelMultiplier: 0.9,
  });

  smoothScroll.on('scroll', ScrollTrigger.update);

  gsap.ticker.add((time) => {
    smoothScroll.raf(time * 1000);
  });

  gsap.ticker.lagSmoothing(0);

  return smoothScroll;
}
