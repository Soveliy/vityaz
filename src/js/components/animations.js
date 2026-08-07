import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

export function initAnimations() {
  if (motionQuery.matches) {
    return;
  }

  const revealItems = document.querySelectorAll('[data-reveal]');

  revealItems.forEach((item) => {
    gsap.fromTo(
      item,
      {
        autoAlpha: 0,
        y: 32,
      },
      {
        autoAlpha: 1,
        duration: 0.8,
        ease: 'power3.out',
        scrollTrigger: {
          once: true,
          start: 'top 85%',
          trigger: item,
        },
        y: 0,
      },
    );
  });
}
