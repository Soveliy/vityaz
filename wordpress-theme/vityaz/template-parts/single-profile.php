<?php
/**
 * Shared single template for students and trainers.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$post_type = (string) ( $args['post_type'] ?? get_post_type() );
$is_student = 'vityaz_student' === $post_type;
$config     = vityaz_archive_config( $post_type );

while ( have_posts() ) :
	the_post();

	$post_id    = get_the_ID();
	$person     = $is_student ? vityaz_student_card_from_post( get_post() ) : vityaz_trainer_card_from_post( get_post() );
	$gallery    = (array) vityaz_get_field( $is_student ? 'student_gallery' : 'trainer_gallery', array(), $post_id );
	$directions = get_the_terms( $post_id, 'vityaz_direction' );
	$sprite     = vityaz_asset_uri( 'img/sprite.svg' );
	?>
	<main class="main person-page person-page--<?php echo esc_attr( $is_student ? 'student' : 'trainer' ); ?>">
		<article class="section person-page__article">
			<div class="container">
				<nav class="breadcrumbs" aria-label="Хлебные крошки">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( vityaz_archive_url( $post_type ) ); ?>"><?php echo esc_html( $config['title'] ); ?></a>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php the_title(); ?></span>
				</nav>

				<div class="person-page__hero">
					<figure class="person-page__photo">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'vityaz-person', array( 'class' => 'media-cover', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
						<?php else : ?>
							<img class="person-page__placeholder" src="<?php echo esc_url( vityaz_asset_uri( 'img/logo.png' ) ); ?>" alt="" width="320" height="80">
						<?php endif; ?>
					</figure>

					<div class="person-page__summary">
						<div class="person-page__eyebrow"><?php echo esc_html( $is_student ? 'Лучшие воспитанники' : 'Тренеры Ассоциации Витязей' ); ?></div>
						<h1 class="person-page__title"><?php the_title(); ?></h1>
						<?php if ( ! empty( $person['subtitle'] ) ) : ?><p class="person-page__lead"><?php echo esc_html( $person['subtitle'] ); ?></p><?php endif; ?>

						<?php if ( $directions && ! is_wp_error( $directions ) ) : ?>
							<div class="person-page__directions"><?php foreach ( $directions as $direction ) : ?><span><?php echo esc_html( $direction->name ); ?></span><?php endforeach; ?></div>
						<?php endif; ?>

						<ul class="person-page__facts human-card__list">
							<?php if ( ! $is_student && ! empty( $person['experience'] ) ) : ?>
								<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-certificate_medal' ); ?>"></use></svg><div class="human-card__item-title">Стаж</div></div><div class="human-card__item-desc"><?php echo esc_html( $person['experience'] ); ?></div></li>
							<?php endif; ?>

							<?php if ( ! empty( $person['qualification'] ) ) : ?>
								<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-star' ); ?>"></use></svg><div class="human-card__item-title">Квалификация</div></div><div class="human-card__item-desc"><?php echo esc_html( $person['qualification'] ); ?></div></li>
							<?php endif; ?>

							<?php if ( $is_student && ! empty( $person['achievements'] ) ) : ?>
								<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-cup' ); ?>"></use></svg><div class="human-card__item-title">Достижения</div></div><div class="human-card__item-desc"><ul><?php foreach ( $person['achievements'] as $achievement ) : ?><li><?php echo esc_html( $achievement ); ?></li><?php endforeach; ?></ul></div></li>
							<?php endif; ?>

							<?php if ( $is_student && ! empty( $person['trainer_posts'] ) ) : ?>
								<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-trainer' ); ?>"></use></svg><div class="human-card__item-title">Тренер</div></div><div class="human-card__item-desc person-page__links"><?php foreach ( $person['trainer_posts'] as $trainer ) : ?><a href="<?php echo esc_url( get_permalink( $trainer ) ); ?>"><?php echo esc_html( get_the_title( $trainer ) ); ?></a><?php endforeach; ?></div></li>
							<?php endif; ?>

							<?php if ( ! $is_student && ! empty( $person['halls'] ) ) : ?>
								<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-pin' ); ?>"></use></svg><div class="human-card__item-title">Залы</div></div><div class="human-card__item-desc"><ul><?php foreach ( $person['halls'] as $hall ) : ?><li><?php echo esc_html( $hall ); ?></li><?php endforeach; ?></ul></div></li>
							<?php endif; ?>

							<?php if ( ! $is_student && ! empty( $person['achievements'] ) ) : ?>
								<li class="human-card__item"><div class="human-card__item-head"><svg class="human-card__item-icon" aria-hidden="true"><use href="<?php echo esc_url( $sprite . '#icon-cup' ); ?>"></use></svg><div class="human-card__item-title">Достижения и звания</div></div><div class="human-card__item-desc"><ul><?php foreach ( $person['achievements'] as $achievement ) : ?><li><?php echo esc_html( $achievement ); ?></li><?php endforeach; ?></ul></div></li>
							<?php endif; ?>
						</ul>
					</div>
				</div>

				<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
					<div class="person-page__content entry-page__content prose">
						<h2><?php echo esc_html( $is_student ? 'О спортсмене' : 'О тренере' ); ?></h2>
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php get_template_part( 'template-parts/entry-gallery', null, array( 'images' => $gallery, 'group' => $is_student ? 'student-gallery' : 'trainer-gallery' ) ); ?>

				<footer class="person-page__footer">
					<a class="entry-page__back" href="<?php echo esc_url( vityaz_archive_url( $post_type ) ); ?>">← Вернуться в раздел</a>
					<button class="btn" type="button" data-modal-open="request" data-type="<?php echo esc_attr( 'Запись к тренеру/спортсмену: ' . get_the_title() ); ?>">Записаться на тренировку</button>
				</footer>
			</div>
		</article>
	</main>
	<?php
endwhile;

