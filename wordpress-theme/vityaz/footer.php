<?php
/**
 * Site footer.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

$phone       = (string) vityaz_get_option( 'phone', '+7 (920) 265-73-65' );
$email       = (string) vityaz_get_option( 'email', 'vityazi-kursk@yandex.ru' );
$address     = (string) vityaz_get_option( 'address', 'г. Курск, ул. Краснознамённая, 20А' );
$vk_url      = (string) vityaz_get_option( 'vk_url', 'https://vk.com/vityazikursk' );
$footer_logo = vityaz_get_option( 'footer_logo' );
$privacy_url = (string) vityaz_get_option( 'legal_privacy_url', get_privacy_policy_url() );
$terms_url   = (string) vityaz_get_option( 'legal_terms_url', '' );
?>
<footer class="footer" id="contacts">
	<div class="container">
		<div class="footer__grid">
			<div class="footer__brand">
				<a class="footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — на главную">
					<img src="<?php echo esc_url( vityaz_image_url( $footer_logo, 'img/logo_white.png' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="320" height="80" loading="lazy">
				</a>
				<div class="footer__legal">
					<?php if ( $privacy_url ) : ?><a href="<?php echo esc_url( $privacy_url ); ?>">Политика обработки персональных данных</a><?php endif; ?>
					<?php if ( $terms_url ) : ?><a href="<?php echo esc_url( $terms_url ); ?>">Пользовательское соглашение</a><?php endif; ?>
					<span>Copyright © <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				</div>
			</div>

			<nav class="footer__nav footer__nav--federation" aria-label="О федерации">
				<div class="footer__title">О федерации</div>
				<a href="<?php echo esc_url( home_url( '/#why' ) ); ?>">О нас</a><a href="<?php echo esc_url( vityaz_archive_url( 'vityaz_student' ) ); ?>">Воспитанники</a><a href="<?php echo esc_url( vityaz_archive_url( 'vityaz_trainer' ) ); ?>">Тренеры</a><a href="<?php echo esc_url( home_url( '/#gallery' ) ); ?>">Фотогалерея</a><a href="<?php echo esc_url( vityaz_archive_url( 'vityaz_news' ) ); ?>">Новости</a><a href="<?php echo esc_url( vityaz_archive_url( 'vityaz_event' ) ); ?>">Мероприятия</a>
				<a class="footer__social footer__social--mobile" href="<?php echo esc_url( $vk_url ); ?>" target="_blank" rel="me noopener noreferrer" aria-label="Ассоциация Витязей во ВКонтакте">
					<svg aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-vk' ) ); ?>"></use></svg>
				</a>
			</nav>

			<nav class="footer__nav footer__nav--sports" aria-label="Единоборства для детей">
				<div class="footer__title">Единоборства для детей</div>
				<a href="<?php echo esc_url( home_url( '/#groups' ) ); ?>">Каратэ</a><a href="<?php echo esc_url( home_url( '/#groups' ) ); ?>">Кудо</a><a href="<?php echo esc_url( home_url( '/#price' ) ); ?>">Персональные тренировки</a>
			</nav>

			<div class="footer__contacts">
				<div class="footer__title">Контакты</div>
				<span><?php echo esc_html( $address ); ?></span>
				<a href="tel:<?php echo esc_attr( vityaz_phone_href( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
				<a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a>
				<a class="footer__social footer__social--desktop" href="<?php echo esc_url( $vk_url ); ?>" target="_blank" rel="me noopener noreferrer" aria-label="Ассоциация Витязей во ВКонтакте">
					<svg aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-vk' ) ); ?>"></use></svg>
				</a>
			</div>
		</div>
	</div>
</footer>

<?php get_template_part( 'template-parts/cookie-notice' ); ?>
<?php get_template_part( 'template-parts/modals' ); ?>
<?php wp_footer(); ?>
</body>
</html>
