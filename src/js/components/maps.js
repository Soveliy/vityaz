const YANDEX_MAPS_API_URL = 'https://api-maps.yandex.ru/v3/';
const MAP_CENTER = [36.2400211, 51.7462];

const locations = [
  {
    address: 'г. Курск, ул. Краснознаменная, 20А, детский центр «Добрыня»',
    coordinates: [36.2400211, 51.7445976],
  },
];

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
    script.addEventListener('load', () => resolve(globalThis.ymaps3), { once: true });
    script.addEventListener(
      'error',
      () => reject(new Error('Не удалось загрузить API Яндекс Карт')),
      {
        once: true,
      },
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

function createMarkerElement(location) {
  const marker = document.createElement('div');
  const balloon = document.createElement('div');
  const button = document.createElement('button');
  const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');

  marker.className = 'map-marker';
  balloon.className = 'map-marker__balloon';
  balloon.hidden = true;
  balloon.textContent = location.address;

  button.className = 'map-marker__button';
  button.type = 'button';
  button.setAttribute('aria-expanded', 'false');
  button.setAttribute('aria-label', `Показать адрес: ${location.address}`);

  icon.classList.add('map-marker__icon');
  icon.setAttribute('aria-hidden', 'true');
  use.setAttribute('href', `${import.meta.env.BASE_URL}img/sprite.svg#icon-mark`);
  icon.append(use);
  button.append(icon);
  marker.append(balloon, button);

  const closeBalloon = () => {
    balloon.hidden = true;
    button.setAttribute('aria-expanded', 'false');
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();

    const shouldOpen = balloon.hidden;

    document.dispatchEvent(new CustomEvent('map-balloon-open', { detail: marker }));
    balloon.hidden = !shouldOpen;
    button.setAttribute('aria-expanded', String(shouldOpen));
  });

  document.addEventListener('click', closeBalloon);
  document.addEventListener('map-balloon-open', (event) => {
    if (event.detail !== marker) {
      closeBalloon();
    }
  });

  return marker;
}

async function createMap(container, apiKey) {
  const [ymaps3, customization] = await Promise.all([
    loadYandexMapsApi(apiKey),
    loadCustomization(),
  ]);

  await ymaps3.ready;

  const { YMap, YMapDefaultFeaturesLayer, YMapDefaultSchemeLayer, YMapMarker } = ymaps3;
  const map = new YMap(container, {
    behaviors: ['drag', 'pinchZoom', 'dblClick'],
    location: {
      center: MAP_CENTER,
      zoom: 15,
    },
    mode: 'vector',
  });

  map.addChild(new YMapDefaultSchemeLayer({ customization }));
  map.addChild(new YMapDefaultFeaturesLayer({ zIndex: 1800 }));

  locations.forEach((location) => {
    map.addChild(
      new YMapMarker(
        {
          blockEvents: true,
          coordinates: location.coordinates,
        },
        createMarkerElement(location),
      ),
    );
  });

  container.classList.add('is-loaded');
}

export function initMaps() {
  const containers = document.querySelectorAll('[data-map]');
  const apiKey = import.meta.env.VITE_YANDEX_MAPS_API_KEY;

  if (!containers.length || !apiKey) {
    return;
  }

  containers.forEach((container) => {
    createMap(container, apiKey).catch((error) => {
      console.error(error);
    });
  });
}
