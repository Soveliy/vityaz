<?php
/**
 * Page template.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main class="main">
	<section class="section">
		<div class="container">
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'prose' ); ?>><h1 class="section__title"><?php the_title(); ?></h1><?php the_content(); ?></article>
			<?php endwhile; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
