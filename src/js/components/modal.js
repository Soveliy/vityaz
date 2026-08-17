import { lockScroll, unlockScroll } from '../_functions.js';
import { MAP_LOCATIONS } from './map-locations.js';

const themeConfig = globalThis.vityazTheme ?? {};
const mapLocations = Array.isArray(themeConfig.mapLocations)
  ? themeConfig.mapLocations
  : MAP_LOCATIONS;
const FOCUSABLE_SELECTOR =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
const DEFAULT_REQUEST_TITLE = 'Оставьте заявку — мы вам перезвоним';
const MODAL_SCROLL_LOCK = 'modal';

function getLocationGroup(location) {
  if (location.group) {
    return location.group;
  }

  if (location.scope === 'region') {
    return 'region';
  }

  const district = location.district?.toLocaleLowerCase('ru-RU') ?? '';

  if (district.includes('северо-запад')) return 'northwest';
  if (district.includes('сха') || district.includes('побед') || district.includes('дериглаз')) {
    return 'north';
  }
  if (district.includes('железнодорож')) return 'railway';
  if (district.includes('сейм')) return 'seym';
  if (district.includes('волокно')) return 'volokno';
  if (district.includes('центр')) return 'center';

  return '';
}

function getLocationLabel(location) {
  return `${location.address}, ${location.name}`;
}

export function initModals() {
  const requestModal = document.querySelector('[data-modal="request"]');
  const locationsModal = document.querySelector('[data-modal="locations"]');
  const requestTitle = requestModal?.querySelector('[data-request-modal-title]');
  const nameInput = requestModal?.querySelector('input[name="name"], input[name="your-name"]');
  const emailInput = requestModal?.querySelector('input[name="email"], input[name="your-email"]');
  const scheduleField = requestModal?.querySelector('[data-schedule-field]');
  const requestTypeInput = requestModal?.querySelector(
    '[data-request-type], input[name="request-type"]',
  );
  const locationTitle = locationsModal?.querySelector('[data-location-modal-title]');
  const locationList = locationsModal?.querySelector('[data-location-modal-list]');
  const locationSignupButton = locationsModal?.querySelector('[data-location-signup]');
  const notice = document.querySelector('[data-success-notice]');
  const openButtons = document.querySelectorAll('[data-modal-open="request"]');
  const locationButtons = document.querySelectorAll('[data-location-group]');
  const requestForms = document.querySelectorAll('[data-request-form]');

  if (!requestModal) {
    return;
  }

  let activeLocationTrigger;
  let activeModal;
  let closeTimer;
  let lastFocusedElement;
  let noticeHideTimer;
  let noticeTimer;

  const hideModalImmediately = (modal) => {
    if (!modal) {
      return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    modal.hidden = true;
  };

  const openModal = (modal, trigger, focusTarget) => {
    window.clearTimeout(closeTimer);

    if (activeModal && activeModal !== modal) {
      hideModalImmediately(activeModal);
    }

    activeModal = modal;
    lastFocusedElement = trigger;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    lockScroll(MODAL_SCROLL_LOCK);

    requestAnimationFrame(() => {
      modal.classList.add('is-open');
      focusTarget?.focus();
    });
  };

  const closeModal = (modal = activeModal) => {
    if (!modal || modal.hidden) {
      return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    activeModal = null;
    unlockScroll(MODAL_SCROLL_LOCK);

    closeTimer = window.setTimeout(() => {
      modal.hidden = true;
    }, 250);

    lastFocusedElement?.focus();
  };

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

  const setRequestVariant = (trigger) => {
    const isSchedule = trigger.dataset.modalVariant === 'schedule';

    requestModal.classList.toggle('modal--schedule', isSchedule);

    if (requestTitle) {
      requestTitle.textContent = trigger.dataset.modalTitle || DEFAULT_REQUEST_TITLE;
    }

    if (scheduleField && emailInput) {
      scheduleField.hidden = !isSchedule;
      emailInput.disabled = !isSchedule;
      emailInput.required = isSchedule;
    }

    if (requestTypeInput) {
      requestTypeInput.value = trigger.dataset.type || 'Заявка с сайта';
    }
  };

  const openRequestModal = (trigger) => {
    setRequestVariant(trigger);
    openModal(requestModal, trigger, nameInput);
  };

  const openLocationsModal = (trigger) => {
    if (!locationsModal || !locationTitle || !locationList) {
      return;
    }

    const selectedGroup = trigger.dataset.locationGroup;
    const locations = mapLocations.filter(
      (location) => getLocationGroup(location) === selectedGroup,
    );

    activeLocationTrigger = trigger;
    locationTitle.textContent = trigger.dataset.locationTitle || 'Ближайшие залы';
    locationList.replaceChildren(
      ...locations.map((location) => {
        const item = document.createElement('li');

        item.className = 'modal__location';
        item.textContent = getLocationLabel(location);

        return item;
      }),
    );

    openModal(locationsModal, trigger, locationsModal.querySelector('[data-modal-close]'));
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      openRequestModal(button);
    });
  });

  locationButtons.forEach((button) => {
    button.addEventListener('click', () => openLocationsModal(button));
  });

  locationSignupButton?.addEventListener('click', () => {
    if (!activeLocationTrigger) {
      return;
    }

    activeLocationTrigger.dataset.type = `Выбран зал: ${activeLocationTrigger.dataset.locationTitle}`;
    openRequestModal(activeLocationTrigger);
  });

  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => closeModal(button.closest('[data-modal]')));
  });

  document.querySelectorAll('[data-modal]').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal(modal);
      }
    });
  });

  document.addEventListener('keydown', (event) => {
    if (!activeModal) {
      return;
    }

    if (event.key === 'Escape') {
      closeModal();
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    const focusableElements = [...activeModal.querySelectorAll(FOCUSABLE_SELECTOR)].filter(
      (element) => !element.closest('[hidden]'),
    );
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
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const submitButton = form.querySelector('[type="submit"]');

      if (themeConfig.ajaxUrl) {
        const formData = new FormData(form);

        formData.set('action', 'vityaz_submit_request');
        formData.set('nonce', themeConfig.requestNonce || '');
        submitButton?.setAttribute('disabled', '');

        try {
          const response = await fetch(themeConfig.ajaxUrl, {
            body: formData,
            credentials: 'same-origin',
            method: 'POST',
          });
          const result = await response.json();

          if (!response.ok || !result.success) {
            throw new Error(result.data?.message || 'Не удалось отправить заявку');
          }
        } catch (error) {
          window.alert(error.message || 'Не удалось отправить заявку. Попробуйте ещё раз.');
          return;
        } finally {
          submitButton?.removeAttribute('disabled');
        }
      }

      if (requestModal.contains(form)) {
        closeModal(requestModal);
      }

      form.reset();
      showNotice();
    });
  });

  document.addEventListener('wpcf7mailsent', (event) => {
    const eventTarget = event.target;
    const contactForm =
      eventTarget instanceof Element && eventTarget.matches('.vityaz-cf7-form')
        ? eventTarget
        : eventTarget instanceof Element
          ? eventTarget.querySelector('.vityaz-cf7-form')
          : null;
    const configuredFormId = Number(themeConfig.contactFormId) || 0;
    const submittedFormId = Number(event.detail?.contactFormId) || 0;

    if (
      !contactForm ||
      (configuredFormId && submittedFormId && submittedFormId !== configuredFormId)
    ) {
      return;
    }

    if (requestModal.contains(contactForm)) {
      closeModal(requestModal);
    }

    showNotice();
  });
}
