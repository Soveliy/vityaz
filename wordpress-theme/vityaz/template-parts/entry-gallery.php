<?php
/**
 * Fancybox gallery for a single entry.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$images = is_array( $args['images'] ?? null ) ? $args['images'] : array();
$title  = (string) ( $args['title'] ?? 'Фотогалерея' );
$group  = sanitize_html_class( $args['group'] ?? 'entry-gallery' );

if ( ! $images ) {
	return;
}
?>
<section class="entry-page__gallery-section" aria-labelledby="<?php echo esc_attr( $group ); ?>-title">
	<h2 class="entry-page__subtitle" id="<?php echo esc_attr( $group ); ?>-title"><?php echo esc_html( $title ); ?></h2>
	<div class="entry-page__gallery">
		<?php foreach ( $images as $image ) : ?>
			<?php
			$image_id  = is_array( $image ) ? (int) ( $image['ID'] ?? $image['id'] ?? 0 ) : ( is_numeric( $image ) ? (int) $image : 0 );
			$image_url = vityaz_image_url( $image );

			if ( ! $image_url ) {
				continue;
			}
			?>
			<a class="entry-page__gallery-item" href="<?php echo esc_url( $image_url ); ?>" data-fancybox="<?php echo esc_attr( $group ); ?>">
				<?php if ( $image_id ) : ?>
					<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'media-cover', 'loading' => 'lazy' ) ); ?>
				<?php else : ?>
					<img class="media-cover" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( vityaz_image_alt( $image, $title ) ); ?>" width="480" height="320" loading="lazy">
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>

