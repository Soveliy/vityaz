import { MAP_LOCATIONS } from './map-locations.js';

const YANDEX_MAPS_API_URL = 'https://api-maps.yandex.ru/v3/';
const MARKER_ICON_URL = `${import.meta.env.BASE_URL}img/mark.svg`;
const MAP_CENTER = [36.192647, 51.730361];
const DEFAULT_ZOOM = 11;
const SELECTED_MARKER_ZOOM = 15;

let mapsApiPromise;

function loadYandexMapsApi(apiKey) {
  if (globalThis.ymaps3) {
    return Promise.resolve(globalThis.ymaps3);
  }

  if (mapsApiPromise) {
    return mapsApiPromise;
  }

  mapsApiPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    const parameters = new URLSearchParams({
      apikey: apiKey,
      lang: 'ru_RU',
    });

    script.async = true;
    script.src = `${YANDEX_MAPS_API_URL}?${parameters}`;
    script.addEventListener(
      'load',
      () => {
        if (globalThis.ymaps3) {
          resolve(globalThis.ymaps3);
          return;
        }

        reject(new Error('API Яндекс Карт загрузился без объекта ymaps3'));
      },
      { once: true },
    );
    script.addEventListener(
      'error',
      () => reject(new Error('Не удалось загрузить API Яндекс Карт')),
      { once: true },
    );
    document.head.append(script);
  });

  return mapsApiPromise;
}

async function loadCustomization() {
  const customizationUrl = `${import.meta.env.BASE_URL}resources/customization.json`;
  const response = await fetch(customizationUrl);

  if (!response.ok) {
    throw new Error(`Не удалось загрузить стили карты: ${response.status}`);
  }

  return response.json();
}

function createTextElement(className, text) {
  const element = document.createElement('div');

  element.className = className;
  element.textContent = text;

  return element;
}

function createPopupElement(location) {
  const popup = document.createElement('div');

  popup.className = 'map-marker__balloon';
  popup.append(createTextElement('map-marker__address', location.address));

  return popup;
}

function createMarkerElement(location, onOpen) {
  const marker = document.createElement('div');
  const button = document.createElement('button');
  const icon = document.createElement('img');
  const balloon = createPopupElement(location);

  marker.className = 'map-marker';

  button.className = 'map-marker__button';
  button.type = 'button';
  button.setAttribute('aria-expanded', 'false');
  button.setAttribute('aria-label', `Показать адрес: ${location.address}`);

  icon.classList.add('map-marker__icon');
  icon.src = MARKER_ICON_URL;
  icon.alt = '';
  icon.width = 24;
  icon.height = 32;

  balloon.hidden = true;
  balloon.addEventListener('click', (event) => event.stopPropagation());
  button.append(icon);
  marker.append(balloon, button);

  const closeBalloon = () => {
    balloon.hidden = true;
    marker.classList.remove('is-open');
    button.setAttribute('aria-expanded', 'false');
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();

    const shouldOpen = balloon.hidden;

    document.dispatchEvent(new CustomEvent('map-marker-open', { detail: marker }));

    if (shouldOpen) {
      balloon.hidden = false;
      marker.classList.add('is-open');
      button.setAttribute('aria-expanded', 'true');
      onOpen(location);
    } else {
      closeBalloon();
    }
  });

  document.addEventListener('click', closeBalloon);
  document.addEventListener('map-marker-open', (event) => {
    if (event.detail !== marker) {
      closeBalloon();
    }
  });

  return marker;
}

function getBounds(locations) {
  if (!locations.length) {
    return null;
  }

  const longitudes = locations.map(({ coordinates }) => coordinates[0]);
  const latitudes = locations.map(({ coordinates }) => coordinates[1]);

  return [
    [Math.min(...longitudes), Math.min(...latitudes)],
    [Math.max(...longitudes), Math.max(...latitudes)],
  ];
}

function fitMapToLocations(map, locations) {
  if (!locations.length) {
    return;
  }

  if (locations.length === 1) {
    map.setLocation({
      center: locations[0].coordinates,
      duration: 500,
      zoom: 15,
    });
    return;
  }

  map.setLocation({
    bounds: getBounds(locations),
    duration: 500,
  });
}

function getLocationsByScope(records, scope) {
  if (scope === 'all') {
    return records;
  }

  return records.filter(({ location }) => location.scope === scope);
}

function connectScopeControls(container, map, records) {
  const controls = container.closest('.map')?.querySelectorAll('[data-map-scope]') ?? [];
  const attachedMarkers = new Set();

  const showScope = (scope) => {
    const visibleRecords = getLocationsByScope(records, scope);

    attachedMarkers.forEach((marker) => map.removeChild(marker));
    attachedMarkers.clear();

    visibleRecords.forEach(({ marker }) => {
      map.addChild(marker);
      attachedMarkers.add(marker);
    });

    controls.forEach((control) => {
      const isActive = control.dataset.mapScope === scope;

      control.classList.toggle('is-active', isActive);
      control.setAttribute('aria-pressed', String(isActive));
    });

    fitMapToLocations(
      map,
      visibleRecords.map(({ location }) => location),
    );
  };

  controls.forEach((control) => {
    control.addEventListener('click', () => showScope(control.dataset.mapScope));
  });

  showScope('city');
}

function updateMapStatus(status, text, state) {
  if (!status) {
    return;
  }

  status.textContent = text;
  status.dataset.state = state;
}

async function createMap(container, apiKey) {
  const status = container.querySelector('[data-map-status]');
  const [ymaps3, customization] = await Promise.all([
    loadYandexMapsApi(apiKey),
    loadCustomization(),
  ]);

  await ymaps3.ready;

  const { YMap, YMapDefaultFeaturesLayer, YMapDefaultSchemeLayer, YMapMarker } = ymaps3;
  const map = new YMap(
    container,
    {
      behaviors: ['drag', 'scrollZoom', 'pinchZoom', 'dblClick'],
      location: {
        center: MAP_CENTER,
        zoom: DEFAULT_ZOOM,
      },
      mode: 'vector',
    },
    [new YMapDefaultSchemeLayer({ customization }), new YMapDefaultFeaturesLayer({ zIndex: 1800 })],
  );
  container.classList.add('is-loaded');

  const locations = MAP_LOCATIONS.filter(({ coordinates }) => coordinates?.length === 2);
  const records = locations.map((location) => {
    const marker = new YMapMarker(
      {
        blockEvents: true,
        coordinates: location.coordinates,
        zIndex: 2000,
      },
      createMarkerElement(location, (selectedLocation) => {
        map.setLocation({
          center: selectedLocation.coordinates,
          duration: 500,
          zoom: SELECTED_MARKER_ZOOM,
        });
      }),
    );

    return { location, marker };
  });

  connectScopeControls(container, map, records);
  container.dataset.markerCount = String(records.length);

  if (locations.length === MAP_LOCATIONS.length) {
    updateMapStatus(status, `${locations.length} площадок`, 'success');
  } else {
    updateMapStatus(
      status,
      `Показано ${locations.length} из ${MAP_LOCATIONS.length} площадок`,
      'warning',
    );
  }
}

export function initMaps() {
  const containers = document.querySelectorAll('[data-map]');
  const apiKey = import.meta.env.VITE_YANDEX_MAPS_API_KEY?.trim();

  if (!containers.length) {
    return;
  }

  if (!apiKey) {
    console.warn('Для карты не задан VITE_YANDEX_MAPS_API_KEY');
    return;
  }

  containers.forEach((container) => {
    createMap(container, apiKey).catch((error) => {
      updateMapStatus(
        container.querySelector('[data-map-status]'),
        'Не удалось загрузить интерактивную карту',
        'error',
      );
      console.error(error);
    });
  });
}
