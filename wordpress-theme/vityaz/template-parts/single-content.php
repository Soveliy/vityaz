<?php
/**
 * Shared single template for news and events.
 *
 * @package Vityaz
 * @var array $args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$post_type = (string) ( $args['post_type'] ?? get_post_type() );
$config    = vityaz_archive_config( $post_type );

while ( have_posts() ) :
	the_post();

	$post_id          = get_the_ID();
	$is_event         = 'vityaz_event' === $post_type;
	$lead             = vityaz_post_excerpt( $post_id, $is_event ? 'event_lead' : 'news_lead' );
	$start            = $is_event ? vityaz_get_field( 'event_start', '', $post_id ) : get_the_date( 'Y-m-d H:i:s', $post_id );
	$end              = $is_event ? vityaz_get_field( 'event_end', '', $post_id ) : '';
	$date_display     = $is_event ? vityaz_format_date( $start, 'd.m.Y, H:i' ) : get_the_date( 'd.m.Y', $post_id );
	$date_iso         = vityaz_format_date( $start, 'c' );
	$location_name    = $is_event ? (string) vityaz_get_field( 'event_location_name', '', $post_id ) : '';
	$address          = $is_event ? (string) vityaz_get_field( 'event_address', '', $post_id ) : '';
	$registration_url = $is_event ? (string) vityaz_get_field( 'event_registration_url', '', $post_id ) : '';
	$gallery          = (array) vityaz_get_field( $is_event ? 'event_gallery' : 'news_gallery', array(), $post_id );
	$directions       = get_the_terms( $post_id, 'vityaz_direction' );
	?>
	<main class="main entry-page entry-page--<?php echo esc_attr( $is_event ? 'event' : 'news' ); ?>">
		<article class="section entry-page__article">
			<div class="container">
				<nav class="breadcrumbs" aria-label="Хлебные крошки">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( vityaz_archive_url( $post_type ) ); ?>"><?php echo esc_html( $config['title'] ); ?></a>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php the_title(); ?></span>
				</nav>

				<header class="entry-page__header">
					<div class="entry-page__overline">
						<?php if ( $date_display ) : ?><time datetime="<?php echo esc_attr( $date_iso ); ?>"><?php echo esc_html( $date_display ); ?></time><?php endif; ?>
						<?php if ( $directions && ! is_wp_error( $directions ) ) : ?>
							<span aria-hidden="true">•</span>
							<span><?php echo esc_html( implode( ', ', wp_list_pluck( $directions, 'name' ) ) ); ?></span>
						<?php endif; ?>
					</div>
					<h1 class="entry-page__title"><?php the_title(); ?></h1>
					<?php if ( $lead ) : ?><p class="entry-page__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="entry-page__media">
						<?php the_post_thumbnail( 'full', array( 'class' => 'entry-page__image', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
					</figure>
				<?php endif; ?>

				<div class="entry-page__layout<?php echo $is_event ? ' entry-page__layout--event' : ''; ?>">
					<div class="entry-page__content prose">
						<?php the_content(); ?>

						<?php if ( ! $is_event ) : ?>
							<?php $video_url = (string) vityaz_get_field( 'news_video_url', '', $post_id ); ?>
							<?php if ( $video_url ) : ?>
								<div class="entry-page__video"><?php echo wp_kses_post( wp_oembed_get( $video_url ) ?: sprintf( '<a href="%s">Смотреть видео</a>', esc_url( $video_url ) ) ); ?></div>
							<?php endif; ?>
						<?php endif; ?>
					</div>

					<?php if ( $is_event ) : ?>
						<aside class="entry-page__aside" aria-label="Информация о мероприятии">
							<dl class="entry-page__meta">
								<?php if ( $date_display ) : ?><div><dt>Начало</dt><dd><?php echo esc_html( $date_display ); ?></dd></div><?php endif; ?>
								<?php if ( $end ) : ?><div><dt>Окончание</dt><dd><?php echo esc_html( vityaz_format_date( $end, 'd.m.Y, H:i' ) ); ?></dd></div><?php endif; ?>
								<?php if ( $location_name ) : ?><div><dt>Площадка</dt><dd><?php echo esc_html( $location_name ); ?></dd></div><?php endif; ?>
								<?php if ( $address ) : ?><div><dt>Адрес</dt><dd><?php echo esc_html( $address ); ?></dd></div><?php endif; ?>
							</dl>
							<?php if ( $registration_url ) : ?><a class="btn entry-page__registration" href="<?php echo esc_url( $registration_url ); ?>" target="_blank" rel="noopener noreferrer">Зарегистрироваться</a><?php endif; ?>
						</aside>
					<?php endif; ?>
				</div>

				<?php
				get_template_part(
					'template-parts/entry-gallery',
					null,
					array(
						'images' => $gallery,
						'group'  => $is_event ? 'event-gallery' : 'news-gallery',
					)
				);
				?>

				<footer class="entry-page__footer">
					<a class="entry-page__back" href="<?php echo esc_url( vityaz_archive_url( $post_type ) ); ?>">← Вернуться в раздел</a>
					<?php if ( ! $is_event ) : ?>
						<?php $source_url = (string) vityaz_get_field( 'news_source_url', '', $post_id ); ?>
						<?php if ( $source_url ) : ?><a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer">Источник публикации ↗</a><?php endif; ?>
					<?php endif; ?>
				</footer>

				<div class="entry-page__navigation">
					<?php the_post_navigation( array( 'prev_text' => '<span>Предыдущий материал</span><strong>%title</strong>', 'next_text' => '<span>Следующий материал</span><strong>%title</strong>' ) ); ?>
				</div>
			</div>
		</article>

		<?php get_template_part( 'template-parts/offer', null, array( 'id_prefix' => 'entry-trial-' . $post_id ) ); ?>
	</main>
	<?php
endwhile;
