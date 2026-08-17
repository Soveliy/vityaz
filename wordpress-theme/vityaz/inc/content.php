<?php
/**
 * Content mapping helpers shared by the front page and archives.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve legacy event fields from the current shared location reference.
 *
 * Existing text remains the fallback when no valid dictionary location is selected.
 */
function vityaz_resolve_linked_field_value( string $name, mixed $value, mixed $post_id ): mixed {
	$field_map = array(
		'event_location_name' => 'name',
		'event_address'       => 'address',
	);

	if ( ! isset( $field_map[ $name ] ) || ! function_exists( 'get_field' ) ) {
		return $value;
	}

	$location_id = get_field( 'event_location_id', $post_id );

	if ( is_array( $location_id ) ) {
		$location_id = reset( $location_id );
	}

	$location = is_scalar( $location_id )
		? vityaz_get_map_location( (string) $location_id )
		: null;
	$resolved = $location ? trim( (string) ( $location[ $field_map[ $name ] ] ?? '' ) ) : '';

	return '' !== $resolved ? $resolved : $value;
}

/**
 * Return the archive URL with a safe home-page fallback.
 */
function vityaz_archive_url( string $post_type, string $fallback = '' ): string {
	$url = get_post_type_archive_link( $post_type );

	return $url ?: home_url( $fallback ?: '/' );
}

/**
 * Format an ACF date value for display.
 */
function vityaz_format_date( mixed $value, string $format = 'd.m.Y' ): string {
	if ( ! $value ) {
		return '';
	}

	$timezone = wp_timezone();

	if ( $value instanceof DateTimeInterface ) {
		$timestamp = $value->getTimestamp();
	} elseif ( is_numeric( $value ) && (int) $value >= 100000000 ) {
		$timestamp = (int) $value;
	} else {
		try {
			$date      = new DateTimeImmutable( (string) $value, $timezone );
			$timestamp = $date->getTimestamp();
		} catch ( Exception ) {
			return (string) $value;
		}
	}

	return $timestamp ? wp_date( $format, $timestamp, $timezone ) : (string) $value;
}

/**
 * Convert a repeater or textarea into plain text lines.
 */
function vityaz_repeater_lines( mixed $value, string $key = 'text' ): array {
	if ( ! is_array( $value ) ) {
		return vityaz_lines( $value );
	}

	$lines = array();

	foreach ( $value as $row ) {
		if ( is_array( $row ) && ! empty( $row[ $key ] ) ) {
			$lines[] = trim( (string) $row[ $key ] );
		} elseif ( is_string( $row ) && '' !== trim( $row ) ) {
			$lines[] = trim( $row );
		}
	}

	return $lines;
}

/**
 * Convert structured achievement rows to concise readable lines.
 */
function vityaz_achievement_lines( mixed $value ): array {
	if ( ! is_array( $value ) ) {
		return vityaz_lines( $value );
	}

	$lines = array();

	foreach ( $value as $row ) {
		if ( ! is_array( $row ) || empty( $row['achievement'] ) ) {
			continue;
		}

		$details = array_filter(
			array(
				trim( (string) ( $row['result'] ?? '' ) ),
				trim( (string) ( $row['year'] ?? '' ) ),
			)
		);
		$line    = trim( (string) $row['achievement'] );

		if ( $details ) {
			$line .= ' — ' . implode( ', ', $details );
		}

		$lines[] = $line;
	}

	return $lines;
}

/**
 * Resolve a relationship field to post objects.
 */
function vityaz_resolve_posts( mixed $items ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$posts = array();

	foreach ( $items as $item ) {
		$post = $item instanceof WP_Post ? $item : get_post( (int) $item );

		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			$posts[] = $post;
		}
	}

	return $posts;
}

/**
 * Prefer manually selected home-page posts, then use latest published posts.
 */
function vityaz_home_posts( string $field_name, string $post_type, int $limit ): array {
	$selected = vityaz_resolve_posts( vityaz_get_field( $field_name, array() ) );

	if ( $selected ) {
		return array_slice( $selected, 0, $limit );
	}

	$query_args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'orderby'             => in_array( $post_type, array( 'vityaz_student', 'vityaz_trainer' ), true ) ? 'menu_order title' : 'date',
			'order'               => in_array( $post_type, array( 'vityaz_student', 'vityaz_trainer' ), true ) ? 'ASC' : 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

	if ( 'vityaz_event' === $post_type ) {
		$query_args['meta_key']   = 'event_start';
		$query_args['meta_query'] = array(
			array(
				'key'     => 'event_start',
				'value'   => current_time( 'mysql' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			),
		);
		$query_args['orderby'] = 'meta_value';
		$query_args['order']   = 'ASC';
	}

	$posts = get_posts( $query_args );

	if ( ! $posts && 'vityaz_event' === $post_type ) {
		unset( $query_args['meta_query'] );
		$query_args['order'] = 'DESC';
		$posts               = get_posts( $query_args );
	}

	return $posts;
}

/**
 * Get a concise excerpt for cards and metadata.
 */
function vityaz_post_excerpt( int $post_id, string $lead_field = '' ): string {
	$lead = $lead_field ? (string) vityaz_get_field( $lead_field, '', $post_id ) : '';

	if ( $lead ) {
		return $lead;
	}

	$excerpt = get_the_excerpt( $post_id );

	if ( $excerpt ) {
		return $excerpt;
	}

	return wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 24 );
}

/**
 * Map a news or event post to the existing content card.
 */
function vityaz_content_card_from_post( WP_Post|int $post ): array {
	$post    = $post instanceof WP_Post ? $post : get_post( $post );
	$post_id = $post instanceof WP_Post ? $post->ID : 0;

	if ( ! $post_id ) {
		return array();
	}

	$is_event  = 'vityaz_event' === $post->post_type;
	$date      = $is_event ? vityaz_get_field( 'event_start', '', $post_id ) : get_the_date( 'd.m.Y', $post_id );
	$date_iso  = $is_event ? vityaz_format_date( $date, 'Y-m-d' ) : get_the_date( 'Y-m-d', $post_id );
	$lead_name = $is_event ? 'event_lead' : 'news_lead';

	return array(
		'image'   => get_post_thumbnail_id( $post_id ),
		'date'    => $is_event ? vityaz_format_date( $date ) : (string) $date,
		'date_iso' => $date_iso,
		'title'   => get_the_title( $post_id ),
		'excerpt' => vityaz_post_excerpt( $post_id, $lead_name ),
		'url'     => get_permalink( $post_id ),
	);
}

/**
 * Map a student post to the existing person card.
 */
function vityaz_student_card_from_post( WP_Post|int $post ): array {
	$post    = $post instanceof WP_Post ? $post : get_post( $post );
	$post_id = $post instanceof WP_Post ? $post->ID : 0;

	if ( ! $post_id ) {
		return array();
	}

	$trainers     = vityaz_resolve_posts( vityaz_get_field( 'student_trainers', array(), $post_id ) );
	$trainer_name = implode( ', ', array_map( static fn( WP_Post $trainer ): string => get_the_title( $trainer ), $trainers ) );

	return array(
		'image'          => get_post_thumbnail_id( $post_id ),
		'name'           => get_the_title( $post_id ),
		'subtitle'       => (string) vityaz_get_field( 'student_subtitle', '', $post_id ),
		'qualification'  => (string) vityaz_get_field( 'student_qualification', '', $post_id ),
		'achievements'   => vityaz_achievement_lines( vityaz_get_field( 'student_achievements', array(), $post_id ) ),
		'trainer'        => $trainer_name,
		'trainer_posts'  => $trainers,
		'url'            => get_permalink( $post_id ),
		'post_id'        => $post_id,
	);
}

/**
 * Map a trainer post to the existing person card.
 */
function vityaz_trainer_card_from_post( WP_Post|int $post ): array {
	$post    = $post instanceof WP_Post ? $post : get_post( $post );
	$post_id = $post instanceof WP_Post ? $post->ID : 0;

	if ( ! $post_id ) {
		return array();
	}

	$linked_halls = vityaz_get_map_locations_by_id(
		vityaz_get_field( 'trainer_hall_ids', array(), $post_id )
	);
	$halls        = $linked_halls
		? array_map( 'vityaz_map_location_label', $linked_halls )
		: vityaz_repeater_lines( vityaz_get_field( 'trainer_halls', array(), $post_id ), 'hall' );

	return array(
		'image'          => get_post_thumbnail_id( $post_id ),
		'name'           => get_the_title( $post_id ),
		'subtitle'       => (string) vityaz_get_field( 'trainer_position', '', $post_id ),
		'experience'     => (string) vityaz_get_field( 'trainer_experience', '', $post_id ),
		'qualification'  => (string) vityaz_get_field( 'trainer_qualification', '', $post_id ),
		'halls'          => $halls,
		'achievements'   => vityaz_achievement_lines( vityaz_get_field( 'trainer_achievements', array(), $post_id ) ),
		'url'            => get_permalink( $post_id ),
		'post_id'        => $post_id,
	);
}

/**
 * Return normalized home-page items or the legacy ACF/demo collection.
 */
function vityaz_home_collection(
	string $selection_field,
	string $legacy_field,
	string $post_type,
	array $fallback,
	int $limit
): array {
	$posts = vityaz_home_posts( $selection_field, $post_type, $limit );

	if ( $posts ) {
		$mapper = match ( $post_type ) {
			'vityaz_student' => 'vityaz_student_card_from_post',
			'vityaz_trainer' => 'vityaz_trainer_card_from_post',
			default           => 'vityaz_content_card_from_post',
		};

		return array_values( array_filter( array_map( $mapper, $posts ) ) );
	}

	return array_slice( (array) vityaz_get_field( $legacy_field, $fallback ), 0, $limit );
}

/**
 * Configuration shared by archive templates and navigation.
 */
function vityaz_archive_config( string $post_type ): array {
	$config = array(
		'vityaz_news'    => array(
			'title'       => 'Новости',
			'intro'       => 'Новости Ассоциации Витязей, результаты соревнований и жизнь нашей команды.',
			'option_key'  => 'news_archive_intro',
			'kind'        => 'content',
			'empty'       => 'Новости скоро появятся.',
		),
		'vityaz_event'   => array(
			'title'       => 'Мероприятия',
			'intro'       => 'Соревнования, аттестации, сборы и открытые тренировки Ассоциации Витязей.',
			'option_key'  => 'events_archive_intro',
			'kind'        => 'content',
			'empty'       => 'Новые мероприятия скоро появятся.',
		),
		'vityaz_student' => array(
			'title'       => 'Лучшие воспитанники',
			'intro'       => 'Спортсмены Ассоциации Витязей, их квалификация, достижения и путь в единоборствах.',
			'option_key'  => 'students_archive_intro',
			'kind'        => 'students',
			'empty'       => 'Информация о воспитанниках скоро появится.',
		),
		'vityaz_trainer' => array(
			'title'       => 'Тренеры',
			'intro'       => 'Опытные тренеры по каратэ и кудо в Курске и Курской области.',
			'option_key'  => 'trainers_archive_intro',
			'kind'        => 'trainers',
			'empty'       => 'Информация о тренерах скоро появится.',
		),
	);

	$item          = $config[ $post_type ] ?? $config['vityaz_news'];
	$item['intro'] = (string) vityaz_get_option( $item['option_key'], $item['intro'] );

	return $item;
}
