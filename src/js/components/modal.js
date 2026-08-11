import { lockScroll, unlockScroll } from '../_functions.js';
import { MAP_LOCATIONS } from './map-locations.js';

const FOCUSABLE_SELECTOR =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
const DEFAULT_REQUEST_TITLE = 'Оставьте заявку — мы вам перезвоним';
const MODAL_SCROLL_LOCK = 'modal';
const LOCATION_FILTERS = {
  center: ({ district }) => district === 'Центр',
  north: ({ district }) => district === 'СХА, Победа, Дериглазова',
  northwest: ({ district }) => district === 'Северо-Западный район',
  railway: ({ district }) => district === 'Железнодорожный округ',
  region: ({ scope }) => scope === 'region',
  seym: ({ district }) => district === 'Сеймский округ',
};

function getLocationLabel(location) {
  return `${location.address}, ${location.name}`;
}

export function initModals() {
  const requestModal = document.querySelector('[data-modal="request"]');
  const locationsModal = document.querySelector('[data-modal="locations"]');
  const requestTitle = requestModal?.querySelector('[data-request-modal-title]');
  const nameInput = requestModal?.querySelector('input[name="name"]');
  const emailInput = requestModal?.querySelector('input[name="email"]');
  const scheduleField = requestModal?.querySelector('[data-schedule-field]');
  const requestTypeInput = requestModal?.querySelector('[data-request-type]');
  const locationTitle = locationsModal?.querySelector('[data-location-modal-title]');
  const locationList = locationsModal?.querySelector('[data-location-modal-list]');
  const locationSignupButton = locationsModal?.querySelector('[data-location-signup]');
  const notice = document.querySelector('[data-success-notice]');
  const openButtons = document.querySelectorAll('[data-modal-open="request"]');
  const locationButtons = document.querySelectorAll('[data-location-group]');
  const requestForms = document.querySelectorAll('[data-request-form], .form__offer');

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

    const filter = LOCATION_FILTERS[trigger.dataset.locationGroup];
    const locations = MAP_LOCATIONS.filter(
      (location) => location.disciplines.includes('Каратэ') && filter?.(location),
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
    form.addEventListener('submit', (event) => {
      event.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      if (requestModal.contains(form)) {
        closeModal(requestModal);
      }

      form.reset();
      showNotice();
    });
  });
}
