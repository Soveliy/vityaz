<?php
/**
 * Shared custom post type archive content.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$post_type   = (string) ( $args['post_type'] ?? get_query_var( 'post_type' ) );
$config      = vityaz_archive_config( $post_type );
$kind        = (string) $config['kind'];
$archive_url = vityaz_archive_url( $post_type );
$period      = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : 'all';
$period      = in_array( $period, array( 'all', 'upcoming', 'past' ), true ) ? $period : 'all';
?>
<main class="main archive-page archive-page--<?php echo esc_attr( $kind ); ?>">
	<section class="section archive-page__hero">
		<div class="container">
			<nav class="breadcrumbs" aria-label="Хлебные крошки">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
				<span aria-hidden="true">/</span>
				<span aria-current="page"><?php echo esc_html( $config['title'] ); ?></span>
			</nav>
			<h1 class="archive-page__title"><?php echo esc_html( $config['title'] ); ?></h1>
			<?php if ( $config['intro'] ) : ?><p class="archive-page__intro"><?php echo esc_html( $config['intro'] ); ?></p><?php endif; ?>

			<?php if ( 'vityaz_event' === $post_type ) : ?>
				<nav class="archive-page__filters" aria-label="Фильтр мероприятий">
					<a class="archive-page__filter<?php echo 'all' === $period ? ' is-active' : ''; ?>" href="<?php echo esc_url( $archive_url ); ?>">Все</a>
					<a class="archive-page__filter<?php echo 'upcoming' === $period ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'period', 'upcoming', $archive_url ) ); ?>">Предстоящие</a>
					<a class="archive-page__filter<?php echo 'past' === $period ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'period', 'past', $archive_url ) ); ?>">Прошедшие</a>
				</nav>
			<?php endif; ?>
		</div>
	</section>

	<section class="section archive-page__listing" aria-label="<?php echo esc_attr( $config['title'] ); ?>">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="archive-page__grid archive-page__grid--<?php echo esc_attr( $kind ); ?>">
					<?php
					while ( have_posts() ) :
						the_post();

						if ( 'students' === $kind ) {
							get_template_part( 'template-parts/student-card', null, array( 'student' => vityaz_student_card_from_post( get_post() ) ) );
						} elseif ( 'trainers' === $kind ) {
							get_template_part( 'template-parts/trainer-card', null, array( 'trainer' => vityaz_trainer_card_from_post( get_post() ) ) );
						} else {
							get_template_part(
								'template-parts/content-card',
								null,
								array(
									'card'         => vityaz_content_card_from_post( get_post() ),
									'fallback_url' => $archive_url,
								)
							);
						}
					endwhile;
					?>
				</div>

				<div class="archive-page__pagination">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 1,
							'prev_text' => 'Назад',
							'next_text' => 'Далее',
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="archive-page__empty">
					<p><?php echo esc_html( $config['empty'] ); ?></p>
					<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">На главную</a>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php get_template_part( 'template-parts/offer', null, array( 'id_prefix' => 'archive-trial-' . $kind ) ); ?>
</main>
