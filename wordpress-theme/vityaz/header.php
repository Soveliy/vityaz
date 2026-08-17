<?php
/**
 * Site header.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

$phone              = (string) vityaz_get_option( 'phone', '+7 (920) 265-73-65' );
$header_logo        = vityaz_get_option( 'header_logo' );
$header_logo_mobile = vityaz_get_option( 'header_logo_mobile' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#0335d6">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> id="top">
<?php wp_body_open(); ?>

<header class="header" data-header>
	<div class="container">
		<div class="header__row">
			<a class="header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — на главную">
				<picture>
					<source srcset="<?php echo esc_url( vityaz_image_url( $header_logo_mobile, 'img/mob_logo.png' ) ); ?>" media="(max-width: 767px)" width="163" height="40">
					<img src="<?php echo esc_url( vityaz_image_url( $header_logo, 'img/logo.png' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="320" height="80">
				</picture>
			</a>

			<div class="header__right" id="header-panel">
				<nav class="menu header__menu" id="header-menu" data-header-menu aria-label="Основная навигация">
					<ul class="menu__list">
						<li class="menu__item"><a class="menu__link" href="<?php echo esc_url( home_url( '/#why' ) ); ?>">О нас</a></li>
						<li class="menu__item">
							<details class="menu__dropdown">
								<summary class="menu__link">Залы в Курске
									<svg aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-cvervron_down' ) ); ?>"></use></svg>
								</summary>
								<div class="menu__submenu menu__submenu--city">
									<div class="menu__submenu-column">
										<strong class="menu__submenu-title">Каратэ</strong>
										<a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Центральный округ</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Северо-Западный мкр-н</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Северный мкр-н</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Железнодорожный округ</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Сеймский округ</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Волокно</a>
									</div>
									<div class="menu__submenu-column">
										<strong class="menu__submenu-title">Кудо</strong>
										<a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Центральный округ</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Северо-Западный мкр-н</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Северный мкр-н</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Железнодорожный округ</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">Сеймский округ</a>
									</div>
								</div>
							</details>
						</li>
						<li class="menu__item">
							<details class="menu__dropdown">
								<summary class="menu__link">Залы в Курской области
									<svg aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-cvervron_down' ) ); ?>"></use></svg>
								</summary>
								<div class="menu__submenu menu__submenu--region">
									<div class="menu__submenu-column"><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">г. Дмитриев</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">г. Курчатов</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">г. Обоянь</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">г. Фатеж</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">г. Щигры</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">с. Верхний Любаж</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">пос. Искра</a></div>
									<div class="menu__submenu-column"><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">пос. им. Карла Либкнехта</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">пос. Мантурово</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">пос. Медвенка</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">пос. Черемисиново</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">пос. Черницыно</a><a href="<?php echo esc_url( home_url( '/#places' ) ); ?>">пос. им. Маршала Жукова</a></div>
								</div>
							</details>
						</li>
						<li class="menu__item"><a class="menu__link" href="<?php echo esc_url( vityaz_archive_url( 'vityaz_news' ) ); ?>">Новости</a></li>
						<li class="menu__item"><a class="menu__link" href="<?php echo esc_url( vityaz_archive_url( 'vityaz_event' ) ); ?>">Мероприятия</a></li>
						<li class="menu__item"><a class="menu__link" href="<?php echo esc_url( home_url( '/#contacts' ) ); ?>">Контакты</a></li>
					</ul>
				</nav>

				<div class="header__contacts">
					<a class="header__phone" href="tel:<?php echo esc_attr( vityaz_phone_href( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					<button class="btn header__btn" type="button" data-modal-open="request" data-type="Запись из шапки">Записаться</button>
				</div>
			</div>

			<button class="burger header__burger" type="button" data-header-toggle aria-expanded="false" aria-controls="header-panel" aria-label="Открыть меню">
				<span class="burger__line" aria-hidden="true"></span>
			</button>
		</div>
	</div>
</header>
