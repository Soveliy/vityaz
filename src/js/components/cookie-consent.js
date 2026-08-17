const CONSENT_KEY = 'vityaz_cookie_consent_v1';
const CONSENT_VALUE = 'accepted';
const CONSENT_MAX_AGE = 60 * 60 * 24 * 365;

function hasConsent() {
  try {
    if (window.localStorage.getItem(CONSENT_KEY) === CONSENT_VALUE) {
      return true;
    }
  } catch {
    // Storage can be unavailable in strict privacy modes.
  }

  return document.cookie
    .split(';')
    .some((item) => item.trim() === `${CONSENT_KEY}=${CONSENT_VALUE}`);
}

function rememberConsent() {
  try {
    window.localStorage.setItem(CONSENT_KEY, CONSENT_VALUE);
  } catch {
    // The first-party cookie below remains as a fallback.
  }

  const secure = window.location.protocol === 'https:' ? '; Secure' : '';

  document.cookie = `${CONSENT_KEY}=${CONSENT_VALUE}; Max-Age=${CONSENT_MAX_AGE}; Path=/; SameSite=Lax${secure}`;
}

export function initCookieConsent() {
  const notice = document.querySelector('[data-cookie-notice]');

  if (!notice || hasConsent()) {
    return;
  }

  const root = document.documentElement;
  const acceptButtons = notice.querySelectorAll('[data-cookie-accept]');
  let hideTimer;

  const updateNoticeHeight = () => {
    root.style.setProperty('--cookie-notice-height', `${Math.ceil(notice.offsetHeight)}px`);
  };

  const hideNotice = () => {
    window.clearTimeout(hideTimer);
    rememberConsent();
    notice.classList.remove('is-shown');

    hideTimer = window.setTimeout(() => {
      notice.hidden = true;
      root.classList.remove('has-cookie-notice');
      root.style.removeProperty('--cookie-notice-height');
      window.removeEventListener('resize', updateNoticeHeight);
    }, 250);
  };

  acceptButtons.forEach((button) => button.addEventListener('click', hideNotice));
  notice.hidden = false;
  root.classList.add('has-cookie-notice');
  window.addEventListener('resize', updateNoticeHeight, { passive: true });

  requestAnimationFrame(() => {
    updateNoticeHeight();
    notice.classList.add('is-shown');
  });
}
