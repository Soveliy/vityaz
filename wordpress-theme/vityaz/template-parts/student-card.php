<?php
/**
 * Student card.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$student   = $args['student'] ?? array();
$classes   = array( 'human-card' );
$url       = (string) ( $student['url'] ?? '' );
$image     = $student['image'] ?? null;
$image_id  = is_array( $image ) ? (int) ( $image['ID'] ?? $image['id'] ?? 0 ) : ( is_numeric( $image ) ? (int) $image : 0 );
$show_more = ! array_key_exists( 'show_more', $args ) || (bool) $args['show_more'];

if ( ! empty( $args['is_slider'] ) ) {
	$classes[] = 'swiper-slide';
}

if ( ! empty( $args['class'] ) ) {
	$classes[] = sanitize_html_class( $args['class'] );
}
?>
<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php if ( $url ) : ?><a class="human-card__link" href="<?php echo esc_url( $url ); ?>"><?php endif; ?>
	<div class="human-card__image-container">
		<?php if ( $image_id ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'vityaz-person', false, array( 'class' => 'media-cover', 'loading' => 'lazy', 'alt' => vityaz_image_alt( $image, ( $student['name'] ?? '' ) . ' — воспитанник Ассоциации Витязей' ) ) ); ?>
		<?php elseif ( vityaz_image_url( $image ) ) : ?>
			<img class="media-cover" src="<?php echo esc_url( vityaz_image_url( $image ) ); ?>" alt="<?php echo esc_attr( vityaz_image_alt( $image, ( $student['name'] ?? '' ) . ' — воспитанник Ассоциации Витязей' ) ); ?>" width="285" height="398" loading="lazy">
		<?php else : ?>
			<img class="human-card__placeholder" src="<?php echo esc_url( vityaz_asset_uri( 'img/logo.png' ) ); ?>" alt="" width="320" height="80" loading="lazy">
		<?php endif; ?>
	</div>
	<div class="human-card__name"><?php echo esc_html( $student['name'] ?? '' ); ?></div>
	<div class="human-card__desc"><?php echo esc_html( $student['subtitle'] ?? '' ); ?></div>
	<ul class="human-card__list">
		<li class="human-card__item">
			<div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-star' ) ); ?>"></use></svg><div class="human-card__item-title">Квалификация</div></div>
			<div class="human-card__item-desc"><?php echo esc_html( $student['qualification'] ?? '' ); ?></div>
		</li>
		<li class="human-card__item">
			<div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-cup' ) ); ?>"></use></svg><div class="human-card__item-title">Достижения</div></div>
			<div class="human-card__item-desc"><ul><?php foreach ( vityaz_lines( $student['achievements'] ?? '' ) as $achievement ) : ?><li><?php echo esc_html( $achievement ); ?></li><?php endforeach; ?></ul></div>
		</li>
		<li class="human-card__item">
			<div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-trainer' ) ); ?>"></use></svg><div class="human-card__item-title">Тренер</div></div>
			<div class="human-card__item-desc"><?php echo esc_html( $student['trainer'] ?? '' ); ?></div>
		</li>
	</ul>
	<?php if ( $url && $show_more ) : ?><span class="human-card__more">Подробнее</span><?php endif; ?>
	<?php if ( $url ) : ?></a><?php endif; ?>
</article>
