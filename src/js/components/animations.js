import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

export function initAnimations() {
  if (motionQuery.matches) {
    return;
  }

  const hero = document.querySelector('[data-hero]');

  if (hero) {
    const contentItems = hero.querySelectorAll('[data-hero-content] > *');
    const photos = hero.querySelectorAll('[data-hero-photo]');
    const fighters = hero.querySelector('[data-hero-fighters]');
    const heroTimeline = gsap.timeline({
      defaults: {
        duration: 0.8,
        ease: 'power3.out',
      },
    });

    heroTimeline
      .from(contentItems, {
        autoAlpha: 0,
        stagger: 0.09,
        y: 36,
      })
      .from(
        photos,
        {
          autoAlpha: 0,
          scale: 0.94,
          stagger: 0.12,
          y: 28,
        },
        '-=0.6',
      );

    if (fighters) {
      heroTimeline.from(
        fighters,
        {
          autoAlpha: 0,
          duration: 0.95,
          scale: 0.9,
          y: 40,
        },
        '-=0.65',
      );
    }
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
