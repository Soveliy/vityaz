<?php
/**
 * Request form.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$prefix       = sanitize_html_class( $args['id_prefix'] ?? wp_unique_id( 'request-' ) );
$class        = $args['class'] ?? 'form__offer form';
$request_type = $args['request_type'] ?? 'Пробная тренировка';
$show_email  = ! empty( $args['show_email'] );
$privacy_url = (string) vityaz_get_option( 'legal_privacy_url', get_privacy_policy_url() );

if (
	function_exists( 'vityaz_render_cf7_form' )
	&& vityaz_render_cf7_form(
		array(
			'id_prefix'   => $prefix,
			'class'       => $class,
			'title'       => 'Заявка на тренировку',
			'request_type' => $request_type,
		)
	)
) {
	return;
}
?>
<form class="<?php echo esc_attr( $class ); ?>" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" data-request-form>
	<div class="form__item">
		<label for="<?php echo esc_attr( $prefix ); ?>-name" class="form__label">Имя</label>
		<input id="<?php echo esc_attr( $prefix ); ?>-name" type="text" class="form__input" name="name" placeholder="Ваше имя" autocomplete="name" required>
	</div>
	<div class="modal__contact-row">
		<div class="form__item">
			<label for="<?php echo esc_attr( $prefix ); ?>-phone" class="form__label">Телефон</label>
			<input id="<?php echo esc_attr( $prefix ); ?>-phone" type="tel" class="form__input" name="phone" placeholder="Ваш номер телефона" autocomplete="tel" required>
		</div>
		<?php if ( $show_email ) : ?>
			<div class="form__item" data-schedule-field hidden>
				<label for="<?php echo esc_attr( $prefix ); ?>-email" class="form__label">Почта</label>
				<input id="<?php echo esc_attr( $prefix ); ?>-email" type="email" class="form__input" name="email" placeholder="Ваш e-mail" autocomplete="email" disabled>
			</div>
		<?php endif; ?>
	</div>
	<div class="form__footer">
		<button class="btn form__btn" type="submit">Отправить</button>
		<label class="form__checkbox">
			<input type="checkbox" name="consent" value="1" required>
			<span>Я согласен(а) на <?php if ( $privacy_url ) : ?><a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener">обработку персональных данных</a><?php else : ?>обработку персональных данных<?php endif; ?></span>
		</label>
	</div>
	<input type="hidden" name="request_type" value="<?php echo esc_attr( $request_type ); ?>" data-request-type>
	<input class="visually-hidden" type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
</form>
