<?php
/**
 * Informational cookie notice.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

if ( ! vityaz_field_is_enabled( 'cookie_notice_enabled', true, 'option' ) ) {
	return;
}

$title        = (string) vityaz_get_option( 'cookie_notice_title', 'Мы используем файлы cookie' );
$text         = (string) vityaz_get_option( 'cookie_notice_text', 'Это нужно, чтобы сайт работал корректно и помогал нам улучшать качество сервиса. Продолжая пользоваться сайтом, вы соглашаетесь с использованием файлов cookie.' );
$button_label = (string) vityaz_get_option( 'cookie_notice_button', 'Понятно' );
?>
<aside class="cookie-notice" data-cookie-notice role="region" aria-labelledby="cookie-notice-title" hidden>
	<button class="cookie-notice__close" type="button" data-cookie-accept aria-label="<?php echo esc_attr( $button_label ); ?>" title="<?php echo esc_attr( $button_label ); ?>"></button>
	<strong class="cookie-notice__title" id="cookie-notice-title"><?php echo esc_html( $title ); ?></strong>
	<p class="cookie-notice__text"><?php echo esc_html( $text ); ?></p>
</aside>
