<?php
/**
 * Trial request section.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$title  = $args['title'] ?? vityaz_get_option( 'global_offer_title', 'Запишитесь на бесплатную пробную тренировку' );
$image  = $args['image'] ?? vityaz_get_option( 'global_offer_image' );
$prefix = $args['id_prefix'] ?? 'trial';
$class  = $args['class'] ?? '';
?>
<section class="section offer <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $prefix ); ?>">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( $title ); ?></h2>
		<div class="offer__row">
			<img class="offer__image" src="<?php echo esc_url( vityaz_image_url( $image, 'img/offer.jpg' ) ); ?>" alt="<?php echo esc_attr( vityaz_image_alt( $image, 'Детская тренировка по каратэ' ) ); ?>" width="730" height="333" loading="lazy">
			<?php
			get_template_part(
				'template-parts/request-form',
				null,
				array(
					'id_prefix'   => $prefix,
					'class'       => 'form__offer form',
					'request_type' => 'Пробная тренировка',
				)
			);
			?>
		</div>
	</div>
</section>
