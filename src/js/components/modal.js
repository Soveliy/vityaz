import { lockScroll, unlockScroll } from '../_functions.js';

const FOCUSABLE_SELECTOR =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function initModals() {
  const modal = document.querySelector('[data-modal="request"]');
  const closeButton = modal?.querySelector('[data-modal-close]');
  const nameInput = modal?.querySelector('input[name="name"]');
  const requestTypeInput = modal?.querySelector('[data-request-type]');
  const notice = document.querySelector('[data-success-notice]');
  const openButtons = document.querySelectorAll('[data-modal-open="request"]');
  const requestForms = document.querySelectorAll('[data-request-form], .form__offer');

  if (!modal || !closeButton) {
    return;
  }

  let closeTimer;
  let lastFocusedElement;
  let noticeHideTimer;
  let noticeTimer;

  const showNotice = () => {
    if (!notice) {
      return;
    }

    window.clearTimeout(noticeHideTimer);
    window.clearTimeout(noticeTimer);
    notice.hidden = false;

    requestAnimationFrame(() => {
      notice.classList.add('is-shown');
    });

    noticeTimer = window.setTimeout(() => {
      notice.classList.remove('is-shown');

      noticeHideTimer = window.setTimeout(() => {
        notice.hidden = true;
      }, 250);
    }, 4000);
  };

  const openModal = (trigger) => {
    window.clearTimeout(closeTimer);
    lastFocusedElement = trigger;

    if (requestTypeInput) {
      requestTypeInput.value = trigger.dataset.type || 'Заявка с сайта';
    }

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    lockScroll();

    requestAnimationFrame(() => {
      modal.classList.add('is-open');
      nameInput?.focus();
    });
  };

  const closeModal = () => {
    if (modal.hidden) {
      return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    unlockScroll();

    closeTimer = window.setTimeout(() => {
      modal.hidden = true;
    }, 250);

    lastFocusedElement?.focus();
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      openModal(button);
    });
  });

  closeButton.addEventListener('click', closeModal);

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (modal.hidden) {
      return;
    }

    if (event.key === 'Escape') {
      closeModal();
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    const focusableElements = [...modal.querySelectorAll(FOCUSABLE_SELECTOR)];
    const firstElement = focusableElements[0];
    const lastElement = focusableElements.at(-1);

    if (event.shiftKey && document.activeElement === firstElement) {
      event.preventDefault();
      lastElement?.focus();
    } else if (!event.shiftKey && document.activeElement === lastElement) {
      event.preventDefault();
      firstElement?.focus();
    }
  });

  requestForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      if (modal.contains(form)) {
        closeModal();
      }

      form.reset();
      showNotice();
    });
  });
}
