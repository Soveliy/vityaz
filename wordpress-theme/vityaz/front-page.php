<?php
/**
 * Front page template.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

get_header();

$defaults              = vityaz_home_defaults();
$hero_facts            = vityaz_get_field( 'hero_facts', $defaults['hero_facts'] );
$place_groups          = vityaz_get_field( 'place_groups', $defaults['place_groups'] );
$tizers                = vityaz_get_field( 'tizers', $defaults['tizers'] );
$groups                = vityaz_get_field( 'groups', $defaults['groups'] );
$why_items             = vityaz_get_field( 'why_items', $defaults['why_items'] );
$prices                = vityaz_get_field( 'prices', $defaults['prices'] );
$students              = vityaz_home_collection( 'home_students', 'students', 'vityaz_student', $defaults['students'], 8 );
$trainers              = vityaz_home_collection( 'home_trainers', 'trainers', 'vityaz_trainer', $defaults['trainers'], 4 );
$news                  = vityaz_home_collection( 'home_news', 'news', 'vityaz_news', $defaults['news'], 5 );
$gallery               = array_slice( (array) vityaz_get_field( 'gallery', $defaults['gallery'] ), 0, 12 );
$events                = vityaz_home_collection( 'home_events', 'events', 'vityaz_event', $defaults['events'], 4 );
$reviews               = vityaz_get_field( 'reviews', $defaults['reviews'] );
$faq                   = vityaz_get_field( 'faq', $defaults['faq'] );
$offer_title           = vityaz_get_option( 'global_offer_title', vityaz_get_field( 'offer_title', 'Запишитесь на бесплатную пробную тренировку' ) );
$offer_image           = vityaz_get_option( 'global_offer_image', vityaz_get_field( 'offer_image' ) );
$schedule_image        = vityaz_get_field( 'schedule_image' );
$schedule_image_mobile = vityaz_get_field( 'schedule_image_mobile' );
$sprite                = vityaz_asset_uri( 'img/sprite.svg' );
$location_titles       = array(
	'center'    => 'Курск — Центральный округ',
	'northwest' => 'Курск — Северо-Западный район',
	'north'     => 'Курск — Северный микрорайон',
	'railway'   => 'Курск — Железнодорожный округ',
	'seym'      => 'Курск — Сеймский округ',
	'region'    => 'Курская область',
);
$show_places    = vityaz_field_is_enabled( 'show_places' );
$show_map       = vityaz_field_is_enabled( 'show_map' );
$show_tizers    = vityaz_field_is_enabled( 'show_tizers' );
$show_groups    = vityaz_field_is_enabled( 'show_groups' );
$show_why       = vityaz_field_is_enabled( 'show_why' );
$show_schedule  = vityaz_field_is_enabled( 'show_schedule' );
$show_price     = vityaz_field_is_enabled( 'show_price' );
$show_offers    = vityaz_field_is_enabled( 'show_offers' );
$show_students  = vityaz_field_is_enabled( 'show_students' );
$show_trainers  = vityaz_field_is_enabled( 'show_trainers' );
$show_news      = vityaz_field_is_enabled( 'show_news' );
$show_gallery   = vityaz_field_is_enabled( 'show_gallery' );
$show_events    = vityaz_field_is_enabled( 'show_events' );
$show_reviews   = vityaz_field_is_enabled( 'show_reviews' );
$show_faq       = vityaz_field_is_enabled( 'show_faq' );
?>
<main class="main">
	<div class="first-screen">
		<div class="hero">
			<div class="container hero__container">
				<div class="hero__content">
					<h1 class="hero__title"><?php echo esc_html( vityaz_get_field( 'hero_title', 'Каратэ для детей в Курске и области' ) ); ?></h1>
					<p class="hero__text"><?php echo esc_html( vityaz_get_field( 'hero_text', 'Обучаем детей от 5 до 18 лет. Развиваем силу, дисциплину, уверенность в себе и спортивные навыки под руководством опытных тренеров.' ) ); ?></p>
					<div class="hero__facts">
						<?php foreach ( $hero_facts as $fact ) : ?><span><?php echo esc_html( $fact['text'] ?? '' ); ?></span><?php endforeach; ?>
					</div>
					<button class="btn hero__btn" type="button" data-modal-open="request" data-type="Пробная тренировка">Записаться</button>
				</div>

				<div class="hero__visual" aria-hidden="true">
					<div class="hero__photo hero__photo--first">
						<picture>
							<source srcset="<?php echo esc_url( vityaz_image_url( vityaz_get_field( 'hero_image_mobile' ), 'img/hero_mob.jpg' ) ); ?>" media="(max-width: 767px)">
							<img src="<?php echo esc_url( vityaz_image_url( vityaz_get_field( 'hero_image_first' ), 'img/hero/1.jpg' ) ); ?>" alt="" width="281" height="503">
						</picture>
					</div>
					<div class="hero__photo hero__photo--second"><img src="<?php echo esc_url( vityaz_image_url( vityaz_get_field( 'hero_image_second' ), 'img/hero/2.jpg' ) ); ?>" alt="" width="371" height="502"></div>
					<img class="hero__fighters" src="<?php echo esc_url( vityaz_image_url( vityaz_get_field( 'hero_image_fighters' ), 'img/hero/3.png' ) ); ?>" alt="" width="568" height="369" fetchpriority="high">
				</div>
			</div>

			<?php if ( $show_places ) : ?><section class="section place" id="places">
				<div class="container">
					<h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'places_title', 'Выберите ближайший зал' ) ); ?></h2>
					<div class="place__grid">
						<?php foreach ( $place_groups as $place ) : ?>
							<?php $location_group = (string) ( $place['group_key'] ?? '' ); ?>
							<button class="place-item" type="button" data-location-group="<?php echo esc_attr( $location_group ); ?>" data-location-title="<?php echo esc_attr( $location_titles[ $location_group ] ?? ( $place['title'] ?? '' ) ); ?>">
								<div class="place-item__content">
									<h3 class="place-item__name"><?php echo esc_html( $place['title'] ?? '' ); ?></h3>
									<div class="place-item__adress"><?php echo esc_html( $place['subtitle'] ?? '' ); ?></div>
								</div>
								<svg class="place-item__icon" aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-arrow_right' ); ?>"></use></svg>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</section><?php endif; ?>
		</div>
	</div>

	<?php if ( $show_map ) : ?><section class="section map" id="map">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'map_title', 'Наши адреса' ) ); ?></h2>
			<div class="map__wrap" data-map role="region" aria-label="Адреса Ассоциации Витязей в Курске и Курской области">
				<a class="map__fallback" href="<?php echo esc_url( 'https://yandex.ru/maps/?text=' . rawurlencode( get_bloginfo( 'name' ) . ' Курск' ) ); ?>" target="_blank" rel="noopener noreferrer">Открыть адреса в Яндекс Картах</a>
				<span class="map__status" data-map-status data-state="loading" aria-live="polite">Загружаем карту…</span>
			</div>
		</div>
	</section><?php endif; ?>

	<?php if ( $show_tizers ) : ?><section class="section tizers">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'tizers_title', 'Что дают ребёнку занятия каратэ и кудо' ) ); ?></h2>
			<div class="tizers__grid">
				<?php foreach ( $tizers as $tizer ) : ?>
					<div class="tizers-item"><div class="tizers-item__image-container"><img src="<?php echo esc_url( vityaz_image_url( $tizer['image'] ?? null ) ); ?>" alt="<?php echo esc_attr( vityaz_image_alt( $tizer['image'] ?? null, $tizer['title'] ?? '' ) ); ?>" width="493" height="220" loading="lazy"></div><div class="tizers-item__body"><h3 class="tizers-item__title"><?php echo esc_html( $tizer['title'] ?? '' ); ?></h3><div class="tizers-item__desc"><?php echo esc_html( $tizer['text'] ?? '' ); ?></div></div></div>
				<?php endforeach; ?>
			</div>
		</div>
	</section><?php endif; ?>

	<?php if ( $show_offers ) { get_template_part( 'template-parts/offer', null, array( 'title' => $offer_title, 'image' => $offer_image, 'id_prefix' => 'trial-primary' ) ); } ?>

	<?php if ( $show_groups ) : ?><section class="section group-inv" id="groups">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'groups_title', 'Набор в группы по каратэ' ) ); ?></h2><div class="group-inv__row">
			<?php foreach ( $groups as $group ) : ?><div class="group-inv-item"><div class="group-inv-item__icon"><img src="<?php echo esc_url( vityaz_image_url( $group['icon'] ?? null ) ); ?>" alt="" width="120" height="120" loading="lazy"></div><h3 class="group-inv-item__name"><?php echo esc_html( $group['title'] ?? '' ); ?></h3><div class="group-inv-item__params"><?php echo esc_html( $group['description'] ?? '' ); ?></div></div><?php endforeach; ?>
		</div></div>
	</section><?php endif; ?>

	<?php if ( $show_why ) : ?><section class="section why" id="why">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'why_title', 'Почему родители выбирают каратэ' ) ); ?></h2><div class="why__grid">
			<?php foreach ( $why_items as $index => $item ) : ?>
				<?php if ( 2 === $index ) : ?><div class="why-item__image"><img src="<?php echo esc_url( vityaz_image_url( vityaz_get_field( 'why_image' ), 'img/why-img.jpg' ) ); ?>" alt="" width="538" height="396" loading="lazy"></div><?php endif; ?>
				<div class="why-item"><h3 class="why-item__title"><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div class="why-item__desc"><?php echo esc_html( $item['description'] ?? '' ); ?></div></div>
			<?php endforeach; ?>
		</div></div>
	</section><?php endif; ?>

	<?php if ( $show_schedule ) : ?><section class="section schedule" id="schedule">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'schedule_title', 'Расписание' ) ); ?></h2><div class="schedule__container">
			<picture>
				<source srcset="<?php echo esc_url( vityaz_image_url( $schedule_image_mobile, 'img/schedule_mob.png' ) ); ?>" media="(max-width: 767px)" width="350" height="349">
				<img src="<?php echo esc_url( vityaz_image_url( $schedule_image, 'img/schedule.png' ) ); ?>" alt="Расписание групповых тренировок Ассоциации Витязей" width="1520" height="546" loading="lazy">
			</picture>
		</div><button class="schedule__btn btn" type="button" data-modal-open="request" data-type="Подробное расписание" data-modal-variant="schedule" data-modal-title="Оставьте заявку — мы отправим вам актуальное расписание"><?php echo esc_html( vityaz_get_field( 'schedule_button', 'Получить подробное расписание' ) ); ?></button></div>
	</section><?php endif; ?>

	<?php if ( $show_price ) : ?><section class="section price" id="price">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'price_title', 'Сколько стоит обучение каратэ' ) ); ?></h2><div class="price__grid">
			<?php foreach ( $prices as $price ) : ?><div class="price-item"><h3 class="price-item__title"><?php echo esc_html( $price['title'] ?? '' ); ?></h3><div class="price-item__value"><?php echo esc_html( $price['value'] ?? '' ); ?></div><div class="price-item__desc"><?php echo esc_html( $price['description'] ?? '' ); ?></div><ul class="price-item__list"><?php foreach ( vityaz_lines( $price['features'] ?? '' ) as $feature ) : ?><li class="price-item__list-item"><?php echo esc_html( $feature ); ?></li><?php endforeach; ?></ul><button class="btn price-item__btn" type="button" data-modal-open="request" data-type="<?php echo esc_attr( $price['title'] ?? 'Тариф' ); ?>"><?php echo esc_html( $price['button'] ?? 'Записаться' ); ?></button></div><?php endforeach; ?>
		</div></div>
	</section><?php endif; ?>

	<?php if ( $show_offers ) { get_template_part( 'template-parts/offer', null, array( 'title' => $offer_title, 'image' => $offer_image, 'id_prefix' => 'trial-middle' ) ); } ?>

	<?php if ( $show_students ) : ?><section class="section students">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'students_title', 'Лучшие воспитанники' ) ); ?></h2><div class="students__slider-wrap" data-slider-root><div class="students__slider swiper" data-slider="students"><div class="swiper-wrapper">
			<?php foreach ( $students as $student ) : ?><?php get_template_part( 'template-parts/student-card', null, array( 'student' => $student, 'is_slider' => true, 'show_more' => false ) ); ?><?php endforeach; ?>
		</div></div><button class="students__nav students__nav--prev" type="button" data-slider-prev aria-label="Предыдущие воспитанники"><svg aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-arrow_left' ); ?>"></use></svg></button><button class="students__nav students__nav--next" type="button" data-slider-next aria-label="Следующие воспитанники"><svg aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-arrow_right' ); ?>"></use></svg></button></div><a class="students__more btn" href="<?php echo esc_url( vityaz_archive_url( 'vityaz_student' ) ); ?>">Смотреть всех</a></div>
	</section><?php endif; ?>

	<?php if ( $show_trainers ) : ?><section class="section trainers" id="trainers">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'trainers_title', 'Тренеры' ) ); ?></h2><div class="trainers__grid">
			<?php $trainer_columns = array( array(), array() ); foreach ( $trainers as $index => $trainer ) { $trainer_columns[ $index % 2 ][] = $trainer; } ?>
			<?php foreach ( $trainer_columns as $column ) : ?><div class="trainers__col"><?php foreach ( $column as $trainer ) : ?><?php get_template_part( 'template-parts/trainer-card', null, array( 'trainer' => $trainer, 'show_more' => false ) ); ?><?php endforeach; ?></div><?php endforeach; ?>
		</div><a href="<?php echo esc_url( vityaz_archive_url( 'vityaz_trainer' ) ); ?>" class="trainers__btn btn">Смотреть всех</a></div>
	</section><?php endif; ?>

	<?php if ( $show_offers ) { get_template_part( 'template-parts/offer', null, array( 'title' => $offer_title, 'image' => $offer_image, 'id_prefix' => 'trial-final', 'class' => 'offer--repeat' ) ); } ?>

	<?php if ( $show_news ) : ?><section class="section news" id="news">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'news_title', 'Новости' ) ); ?></h2><div class="news__slider-wrap" data-slider-root><div class="news__slider swiper" data-slider="news"><div class="swiper-wrapper"><?php foreach ( $news as $card ) : ?><?php get_template_part( 'template-parts/content-card', null, array( 'card' => $card, 'class' => 'swiper-slide', 'fallback_url' => vityaz_archive_url( 'vityaz_news' ), 'show_more' => false ) ); ?><?php endforeach; ?></div></div><button class="news__nav news__nav--prev" type="button" data-slider-prev aria-label="Предыдущие новости"><svg aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-arrow_left' ); ?>"></use></svg></button><button class="news__nav news__nav--next" type="button" data-slider-next aria-label="Следующие новости"><svg aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-arrow_right' ); ?>"></use></svg></button></div><a class="btn news__more" href="<?php echo esc_url( vityaz_archive_url( 'vityaz_news' ) ); ?>">Смотреть все</a></div>
	</section><?php endif; ?>

	<?php if ( $show_gallery ) : ?><section class="section gallery" id="gallery">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'gallery_title', 'Фотогалерея' ) ); ?></h2><div class="gallery__grid">
			<?php foreach ( $gallery as $index => $image ) : $image_url = vityaz_image_url( $image ); $modifier = in_array( $index, array( 1, 7 ), true ) ? ' gallery__item--wide' : ( in_array( $index, array( 2, 6 ), true ) ? ' gallery__item--tall' : '' ); ?><a class="gallery__item<?php echo esc_attr( $modifier ); ?>" href="<?php echo esc_url( $image_url ); ?>" data-fancybox="gallery"><img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( vityaz_image_alt( $image, 'Фотография с соревнований Ассоциации Витязей' ) ); ?>" width="365" height="325" loading="lazy"></a><?php endforeach; ?>
		</div></div>
	</section><?php endif; ?>

	<?php if ( $show_events ) : ?><section class="section events" id="events">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'events_title', 'Мероприятия' ) ); ?></h2><div class="events__grid"><?php foreach ( $events as $card ) : ?><?php get_template_part( 'template-parts/content-card', null, array( 'card' => $card, 'fallback_url' => vityaz_archive_url( 'vityaz_event' ), 'show_more' => false ) ); ?><?php endforeach; ?></div></div>
	</section><?php endif; ?>

	<?php if ( $show_reviews ) : ?><section class="section reviews" id="reviews">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'reviews_title', 'Отзывы' ) ); ?></h2><div class="reviews__grid"><div class="reviews__widget"><iframe src="<?php echo esc_url( vityaz_get_option( 'review_widget_url', 'https://yandex.ru/maps-reviews-widget/235430791173?comments' ) ); ?>" title="Отзывы об Ассоциации Витязей на Яндекс Картах" loading="lazy"></iframe></div><div class="reviews__cards"><?php foreach ( $reviews as $review ) : ?><article class="reviews__card"><h3><?php echo esc_html( $review['author'] ?? '' ); ?></h3><p><?php echo esc_html( $review['text'] ?? '' ); ?></p></article><?php endforeach; ?></div></div></div>
	</section><?php endif; ?>

	<?php if ( $show_faq ) : ?><section class="section faq" id="faq">
		<div class="container"><h2 class="section__title"><?php echo esc_html( vityaz_get_field( 'faq_title', 'Частые вопросы' ) ); ?></h2><div class="faq__list"><?php foreach ( $faq as $item ) : ?><details class="faq__item"><summary class="faq__question"><?php echo esc_html( $item['question'] ?? '' ); ?><svg aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-arrow_link' ); ?>"></use></svg></summary><div class="faq__answer"><?php echo wp_kses_post( wpautop( $item['answer'] ?? '' ) ); ?></div></details><?php endforeach; ?></div></div>
	</section><?php endif; ?>
</main>

<?php get_footer(); ?>
