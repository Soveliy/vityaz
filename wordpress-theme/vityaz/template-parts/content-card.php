<?php
/**
 * News/event card.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$card         = $args['card'] ?? array();
$class        = $args['class'] ?? '';
$url          = $card['url'] ?? '';
$fallback_url = $args['fallback_url'] ?? vityaz_archive_url( 'vityaz_news' );
$image        = $card['image'] ?? null;
$image_id     = is_array( $image ) ? (int) ( $image['ID'] ?? $image['id'] ?? 0 ) : ( is_numeric( $image ) ? (int) $image : 0 );
$show_more    = ! array_key_exists( 'show_more', $args ) || (bool) $args['show_more'];
?>
<article class="content-card <?php echo esc_attr( $class ); ?>">
	<a class="content-card__link" href="<?php echo esc_url( $url ?: $fallback_url ); ?>">
		<?php if ( $image_id ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'vityaz-card', false, array( 'class' => 'content-card__image', 'loading' => 'lazy', 'alt' => vityaz_image_alt( $image, $card['title'] ?? '' ) ) ); ?>
		<?php elseif ( vityaz_image_url( $image ) ) : ?>
			<img class="content-card__image" src="<?php echo esc_url( vityaz_image_url( $image ) ); ?>" alt="<?php echo esc_attr( vityaz_image_alt( $image, $card['title'] ?? '' ) ); ?>" width="285" height="179" loading="lazy">
		<?php else : ?>
			<span class="content-card__image content-card__image--placeholder" aria-hidden="true"><img src="<?php echo esc_url( vityaz_asset_uri( 'img/logo.png' ) ); ?>" alt=""></span>
		<?php endif; ?>
		<div class="content-card__body">
			<time class="content-card__date"<?php if ( ! empty( $card['date_iso'] ) ) : ?> datetime="<?php echo esc_attr( $card['date_iso'] ); ?>"<?php endif; ?>><?php echo esc_html( $card['date'] ?? '' ); ?></time>
			<h3 class="content-card__title"><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
			<p class="content-card__text"><?php echo esc_html( $card['excerpt'] ?? '' ); ?></p>
			<?php if ( $show_more ) : ?><span class="content-card__more">Подробнее...</span><?php endif; ?>
		</div>
	</a>
</article>
