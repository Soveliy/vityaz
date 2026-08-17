<?php
/**
 * Fallback template.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main class="main">
	<section class="section">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<article <?php post_class( 'prose' ); ?>>
						<h1 class="section__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
						<?php the_content(); ?>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'Материалы не найдены.', 'vityaz' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
