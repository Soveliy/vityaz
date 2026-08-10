export function initNavigation() {
  const header = document.querySelector('[data-header]');
  const toggle = header?.querySelector('[data-header-toggle]');
  const menu = header?.querySelector('[data-header-menu]');

  if (!header || !toggle || !menu) {
    return;
  }

  const closeMenu = () => {
    header.classList.remove('is-menu-open');
    toggle.classList.remove('is-active');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Открыть меню');
    menu.querySelectorAll('details[open]').forEach((dropdown) => {
      dropdown.removeAttribute('open');
    });
  };

  toggle.addEventListener('click', () => {
    const shouldOpen = !header.classList.contains('is-menu-open');

    header.classList.toggle('is-menu-open', shouldOpen);
    toggle.classList.toggle('is-active', shouldOpen);
    toggle.setAttribute('aria-expanded', String(shouldOpen));
    toggle.setAttribute('aria-label', shouldOpen ? 'Закрыть меню' : 'Открыть меню');
  });

  menu.addEventListener('click', (event) => {
    if (event.target.closest('a')) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
      toggle.focus();
    }
  });

  document.addEventListener('click', (event) => {
    if (!header.contains(event.target)) {
      closeMenu();
    }
  });
}
