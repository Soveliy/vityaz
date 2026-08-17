<?php
/**
 * Trainer card.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$trainer   = $args['trainer'] ?? array();
$classes   = array( 'human-card' );
$url       = (string) ( $trainer['url'] ?? '' );
$image     = $trainer['image'] ?? null;
$image_id  = is_array( $image ) ? (int) ( $image['ID'] ?? $image['id'] ?? 0 ) : ( is_numeric( $image ) ? (int) $image : 0 );
$show_more = ! array_key_exists( 'show_more', $args ) || (bool) $args['show_more'];

if ( ! empty( $args['class'] ) ) {
	$classes[] = sanitize_html_class( $args['class'] );
}
?>
<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php if ( $url ) : ?><a class="human-card__link" href="<?php echo esc_url( $url ); ?>"><?php endif; ?>
	<div class="human-card__row">
		<div class="human-card__image-container">
			<?php if ( $image_id ) : ?>
				<?php echo wp_get_attachment_image( $image_id, 'vityaz-person', false, array( 'class' => 'media-cover', 'loading' => 'lazy', 'alt' => vityaz_image_alt( $image, $trainer['name'] ?? 'Тренер Ассоциации Витязей' ) ) ); ?>
			<?php elseif ( vityaz_image_url( $image ) ) : ?>
				<img class="media-cover" src="<?php echo esc_url( vityaz_image_url( $image ) ); ?>" alt="<?php echo esc_attr( vityaz_image_alt( $image, $trainer['name'] ?? 'Тренер Ассоциации Витязей' ) ); ?>" width="319" height="398" loading="lazy">
			<?php else : ?>
				<img class="human-card__placeholder" src="<?php echo esc_url( vityaz_asset_uri( 'img/logo.png' ) ); ?>" alt="" width="320" height="80" loading="lazy">
			<?php endif; ?>
		</div>
		<div class="human-card__content">
			<div class="human-card__name human-card__name--center"><?php echo esc_html( $trainer['name'] ?? '' ); ?></div>
			<div class="human-card__desc human-card__desc--center"><?php echo esc_html( $trainer['subtitle'] ?? '' ); ?></div>
			<ul class="human-card__list">
				<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-certificate_medal' ) ); ?>"></use></svg><div class="human-card__item-title">Стаж</div></div><div class="human-card__item-desc"><?php echo esc_html( $trainer['experience'] ?? '' ); ?></div></li>
				<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-star' ) ); ?>"></use></svg><div class="human-card__item-title">Квалификация</div></div><div class="human-card__item-desc"><?php echo esc_html( $trainer['qualification'] ?? '' ); ?></div></li>
				<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( vityaz_asset_uri( 'img/sprite.svg#icon-pin' ) ); ?>"></use></svg><div class="human-card__item-title">Залы</div></div><div class="human-card__item-desc"><?php echo wp_kses( nl2br( esc_html( implode( "\n", vityaz_lines( $trainer['halls'] ?? '' ) ) ) ), array( 'br' => array() ) ); ?></div></li>
			</ul>
		</div>
	</div>
	<?php if ( $url && $show_more ) : ?><span class="human-card__more">Подробнее</span><?php endif; ?>
	<?php if ( $url ) : ?></a><?php endif; ?>
</article>
