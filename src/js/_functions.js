export const isEscapeKey = (event) => event.key === 'Escape';

export const lockScroll = () => {
  document.documentElement.classList.add('is-scroll-locked');
};

export const unlockScroll = () => {
  document.documentElement.classList.remove('is-scroll-locked');
};

export const toggleScrollLock = (isLocked) => {
  document.documentElement.classList.toggle('is-scroll-locked', isLocked);
};
