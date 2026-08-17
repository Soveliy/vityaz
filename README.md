# Витязь Frontend Build

Классическая сборка для верстки на Vite: HTML, SCSS, JS, SVG-спрайт и статические ассеты.

## Команды

- `npm run dev` - Vite dev-сервер с watch.
- `npm run build` - production-сборка в `dist`.
- `npm run backend` - сборка в `backend` без минификации и с sourcemap.
- `npm run wordpress` - собрать frontend в режиме WordPress и перенести ассеты в тему `wordpress-theme/vityaz`; ключ карты берётся в WordPress во время выполнения и в bundle не встраивается.
- `npm run wordpress:zip` - собрать готовый к установке архив `archive/vityaz-wordpress.zip`.
- `npm run sprite` - собрать SVG-спрайт в `public/img/sprite.svg`.
- `npm run zip` - собрать production и архив `archive/vityaz.zip`.
- `npm run clean` - удалить каталоги сборки и сгенерированный `public`.
- `npm run lint` - проверить SCSS и форматирование.
- `npm run lint:styles` - проверить SCSS через Stylelint.
- `npm run format` - отформатировать проект через Prettier.
- `npm run format:check` - проверить форматирование.

## Структура

```text
src/
  index.html
  partials/        HTML-части для @include
  scss/
    base/          базовая доступность, медиа и reset-поведение
    components/    проектные компоненты и UI kit
    pages/         стили конкретных страниц
    sections/      переиспользуемые секции
    ui/            базовые UI-примитивы
    utilities/     утилиты и состояния
    main.scss      основной порядок импортов
    vendor.scss    стили сторонних библиотек
  js/
    main.js        точка входа Vite
    components/    JS-компоненты
    vendor/        локальные сторонние скрипты без npm
  img/
    svg/           исходники для SVG-спрайта
  resources/       fonts, video, favicon, php и прочие файлы
```

`public/` генерируется автоматически из `src/img`, `src/resources`, `src/js/vendor` и SVG-спрайта.

## Conventions

- Классы пишем в БЭМ-подходе: `.block`, `.block__element`, `.block--modifier`.
- JS-хуки не привязываем к CSS-классам. Используем `data-*`, например `data-menu-button`.
- Состояния пишем через `is-*`: `is-active`, `is-open`, `is-hidden`, `is-disabled`.
- Размеры из макета можно писать в `px` только как аргументы `rem()` и `fluid()`. В итоговом CSS они уходят в `rem`.
- Для картинок используем `picture`, `width`, `height`, `loading="lazy"` вне первого экрана и классы `.media-cover` / `.media-contain`.
- Для интерактива не убираем фокус: базовый `:focus-visible` уже настроен.

## HTML

В HTML можно подключать части страницы:

```html
@include('partials/header.html')
```

## VS Code Snippets

В проекте есть переносимые frontend-сниппеты в `.vscode/frontend.code-snippets`.

- `fe-sprite` - декоративная SVG-иконка из спрайта.
- `fe-sprite-a11y` - доступная SVG-иконка с `aria-label`.
- `fe-img` / `fe-img-lazy` - картинка с `width`, `height`, `alt`.
- `fe-picture` / `fe-ratio-picture` - `picture` с `avif`, `webp`, fallback-изображением.
- `fe-include` - HTML partial.
- `fe-section` - секция с container.
- `fe-btn`, `fe-link`, `fe-field`, `fe-textarea`, `fe-modal`, `fe-burger`, `fe-nav` - базовые UI-заготовки.
- `fe-rem`, `fe-fluid`, `fe-fluid-prop`, `fe-media`, `fe-scss-component` - SCSS helpers.
- `fe-js-init` - JS init-функция компонента.

## SCSS

SCSS подключается в `src/js/main.js`:

```js
import '../scss/vendor.scss';
import '../scss/main.scss';
```

Порядок импортов в `main.scss`:

```scss
fluid / vars / mixins
fonts
settings
base
components
ui
sections
pages
utilities
```

### Fluid sizes

Для размеров есть helper `src/scss/_fluid.scss`. В Sass можно писать значения из макета в `px`, но на выходе они компилируются в `rem`:

```scss
$radius-card: rem(12px);
$font-size-h1: fluid(38px, 58px);

.section {
  @include fluid-prop(padding-top, 48px, 96px);
}
```

`fluid()` возвращает `clamp()` с `rem`-границами. По умолчанию размер меняется в диапазоне viewport `375px -> 1520`.

## JavaScript

JS собирается Vite из `src/js/main.js`.

Сторонние библиотеки из npm подключаются обычным импортом:

```js
import Swiper from 'swiper';
```

Локальные vendor-скрипты можно положить в `src/js/vendor`. Они копируются в `js/vendor` и могут быть подключены отдельным тегом `<script>`.

В `src/js/_functions.js` уже есть базовые helpers:

```js
isEscapeKey(event);
lockScroll();
unlockScroll();
toggleScrollLock(isLocked);
```

### Libraries

В проект уже подключены:

- `swiper` - слайдеры.
- `@fancyapps/ui` - Fancybox для фотогалереи.

Swiper инициализируется по `data-slider`:

```html
<div data-slider-root>
  <div class="swiper" data-slider>
    <div class="swiper-wrapper">
      <div class="swiper-slide">Slide</div>
    </div>
  </div>

  <button type="button" data-slider-prev>Prev</button>
  <div data-slider-pagination></div>
  <button type="button" data-slider-next>Next</button>
</div>
```

## WordPress + ACF Pro

Готовая классическая тема находится в `wordpress-theme/vityaz`. Поля главной страницы, глобальные контакты, адреса карты и API-настройки регистрируются программно через ACF Pro. Инструкция по установке находится в `wordpress-theme/vityaz/README.md`.

Отдельная клиентская инструкция по наполнению и ежедневной работе находится в `wordpress-theme/vityaz/CLIENT-GUIDE.md`.

Тема также регистрирует отдельные типы материалов и готовые архивы/детальные страницы:

- новости — `/news/`;
- мероприятия — `/events/`;
- лучшие воспитанники — `/students/`;
- тренеры — `/trainers/`.

Карточки главной можно выбирать через ACF Relationship; при пустом выборе выводятся опубликованные материалы автоматически. Для заявок используется Contact Form 7: тема создаёт форму и подключает её к существующим модальным и встроенным блокам, не изменяя SMTP. Расписание остаётся картинкой: для десктопа и телефона используются отдельные изображения.

Старые ACF-повторители новостей, мероприятий, воспитанников и тренеров переносятся в новые типы записей вручную через «Инструменты → Миграция „Витязь“». Перед переносом сделайте резервную копию базы данных; предварительный просмотр не меняет данные, повторный запуск не создаёт дубли.

Для чистого сайта доступно ручное начальное наполнение через «Инструменты → Начальное наполнение „Витязь“». Оно создаёт материалы из локального снимка публичных данных официального сайта, загружает фотографии в медиатеку и не перезаписывает уже сохранённые редактором значения. Информационные страницы создаются черновиками; подтверждённых новостей и мероприятий в снимке нет, поэтому вымышленные публикации не добавляются. Тема также безопасно обрабатывает неполную TLS-цепочку старого сайта: SSL-проверка остаётся включённой, а официальный сертификат GlobalSign применяется только к разрешённым URL источника.

В WordPress-версии успешная отправка Contact Form 7 закрывает модальное окно и показывает уведомление в правом нижнем углу. Cookie-плашка с сохранением закрытия на один год настраивается через ACF Pro в «Настройки „Витязь“ → Cookie».

## SVG-спрайт

SVG-файлы из `src/img/svg` собираются в `img/sprite.svg`. Символы получают id по имени файла:

```html
<svg aria-hidden="true">
  <use href="/img/sprite.svg#icon-check"></use>
</svg>
```

`fill` и `stroke` нормализуются в `currentColor`, чтобы иконки было удобно красить через CSS.
