export const isEscapeKey = (event) => event.key === 'Escape';

const scrollLocks = new Set();

const syncScrollLock = () => {
  const isLocked = scrollLocks.size > 0;

  document.documentElement.classList.toggle('is-scroll-locked', isLocked);
};

export const lockScroll = (source = 'default') => {
  scrollLocks.add(source);
  syncScrollLock();
};

export const unlockScroll = (source = 'default') => {
  scrollLocks.delete(source);
  syncScrollLock();
};

export const toggleScrollLock = (isLocked, source = 'default') => {
  if (isLocked) {
    lockScroll(source);
  } else {
    unlockScroll(source);
  }
};
