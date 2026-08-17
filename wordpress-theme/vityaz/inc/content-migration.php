<?php
/**
 * Manual migration of legacy front-page ACF repeaters to public content types.
 *
 * The migration never runs automatically. Administrators can inspect a dry-run
 * report in Tools -> Vityaz migration and must explicitly confirm the import.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the legacy collections handled by the migration.
 */
function vityaz_migration_collections(): array {
	return array(
		'news'     => array(
			'label'        => 'Новости',
			'legacy_field' => 'news',
			'post_type'    => 'vityaz_news',
			'home_field'   => 'home_news',
			'home_limit'   => 5,
		),
		'events'   => array(
			'label'        => 'Мероприятия',
			'legacy_field' => 'events',
			'post_type'    => 'vityaz_event',
			'home_field'   => 'home_events',
			'home_limit'   => 4,
		),
		'students' => array(
			'label'        => 'Воспитанники',
			'legacy_field' => 'students',
			'post_type'    => 'vityaz_student',
			'home_field'   => 'home_students',
			'home_limit'   => 8,
		),
		'trainers' => array(
			'label'        => 'Тренеры',
			'legacy_field' => 'trainers',
			'post_type'    => 'vityaz_trainer',
			'home_field'   => 'home_trainers',
			'home_limit'   => 8,
		),
	);
}

/**
 * Register the protected migration screen.
 */
function vityaz_register_migration_page(): void {
	add_management_page(
		'Миграция материалов «Витязь»',
		'Миграция «Витязь»',
		'manage_options',
		'vityaz-content-migration',
		'vityaz_render_migration_page'
	);
}
add_action( 'admin_menu', 'vityaz_register_migration_page' );

/**
 * Process the explicit migration request before the page sends output.
 */
function vityaz_handle_migration_request(): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'vityaz-content-migration' !== $page ) {
		return;
	}

	check_admin_referer( 'vityaz_run_content_migration', 'vityaz_migration_nonce' );

	if ( empty( $_POST['vityaz_confirm_migration'] ) ) {
		vityaz_migration_store_result(
			array(
				'type'    => 'error',
				'message' => 'Миграция не запущена: подтвердите создание и публикацию материалов.',
			)
		);
	} else {
		$result = vityaz_run_legacy_migration();

		vityaz_migration_store_result(
			is_wp_error( $result )
				? array( 'type' => 'error', 'message' => $result->get_error_message() )
				: array( 'type' => 'success', 'message' => 'Миграция завершена.', 'report' => $result )
		);
	}

	wp_safe_redirect( admin_url( 'tools.php?page=vityaz-content-migration&migration-result=1' ) );
	exit;
}
add_action( 'admin_init', 'vityaz_handle_migration_request' );

/**
 * Store the post-redirect report for the current administrator.
 */
function vityaz_migration_store_result( array $result ): void {
	set_transient( 'vityaz_migration_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
}

/**
 * Read and delete the current administrator's migration report.
 */
function vityaz_migration_take_result(): array {
	$key    = 'vityaz_migration_result_' . get_current_user_id();
	$result = get_transient( $key );

	delete_transient( $key );

	return is_array( $result ) ? $result : array();
}

/**
 * Return actual ACF rows saved on the assigned front page.
 *
 * This deliberately calls get_field() directly and never reads defaults.php.
 */
function vityaz_migration_legacy_rows( string $field_name, int $front_page_id ): array {
	if ( ! function_exists( 'get_field' ) || ! $front_page_id ) {
		return array();
	}

	$rows = get_field( $field_name, $front_page_id );

	return is_array( $rows ) ? array_values( $rows ) : array();
}

/**
 * Create a stable fingerprint for detecting reordered legacy rows.
 */
function vityaz_migration_fingerprint( string $collection, array $row ): string {
	$normalized = $row;

	ksort( $normalized );

	return hash( 'sha256', $collection . '|' . wp_json_encode( $normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

/**
 * Normalize a person name for exact and initials-based comparison.
 */
function vityaz_migration_normalize_name( string $name ): string {
	$name = remove_accents( wp_strip_all_tags( $name ) );
	$name = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );
	$name = str_replace( 'ё', 'е', $name );
	$name = (string) preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $name );

	return trim( (string) preg_replace( '/\s+/u', ' ', $name ) );
}

/**
 * Build a surname and initials signature from a Russian full or abbreviated name.
 */
function vityaz_migration_name_signature( string $name ): array {
	$parts = preg_split( '/\s+/u', vityaz_migration_normalize_name( $name ), -1, PREG_SPLIT_NO_EMPTY ) ?: array();

	if ( ! $parts ) {
		return array( 'surname' => '', 'initials' => '' );
	}

	$surname  = (string) array_shift( $parts );
	$initials = '';

	foreach ( $parts as $part ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $part, 0, 1, 'UTF-8' ) : substr( $part, 0, 1 );
	}

	return compact( 'surname', 'initials' );
}

/**
 * Parse legacy ACF date and date-time formats in the WordPress timezone.
 */
function vityaz_migration_parse_datetime( mixed $value ): ?DateTimeImmutable {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return null;
	}

	$timezone = wp_timezone();
	$formats  = array( 'Y-m-d H:i:s', 'd.m.Y H:i', '!Ymd', '!Y-m-d', '!d.m.Y' );

	foreach ( $formats as $format ) {
		$date   = DateTimeImmutable::createFromFormat( $format, $value, $timezone );
		$errors = DateTimeImmutable::getLastErrors();

		if ( $date instanceof DateTimeImmutable && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
			return $date;
		}
	}

	return null;
}

/**
 * Resolve an ACF image value to an attachment ID without importing files.
 */
function vityaz_migration_attachment_id( mixed $image ): int {
	if ( is_array( $image ) ) {
		$attachment_id = (int) ( $image['ID'] ?? $image['id'] ?? 0 );
	} elseif ( is_numeric( $image ) ) {
		$attachment_id = (int) $image;
	} elseif ( is_string( $image ) && preg_match( '#^https?://#i', $image ) ) {
		$attachment_id = (int) attachment_url_to_postid( $image );
	} else {
		$attachment_id = 0;
	}

	return $attachment_id && wp_attachment_is_image( $attachment_id ) ? $attachment_id : 0;
}

/**
 * Find an entry imported from this exact legacy row.
 */
function vityaz_migration_find_imported_post( string $post_type, string $source_key, string $fingerprint ): int {
	$statuses = array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' );
	$args     = array(
		'post_type'      => $post_type,
		'post_status'    => $statuses,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	);

	$by_fingerprint = get_posts(
		$args + array(
			'meta_key'   => '_vityaz_legacy_fingerprint',
			'meta_value' => $fingerprint,
		)
	);

	if ( $by_fingerprint ) {
		return (int) $by_fingerprint[0];
	}

	$by_source = get_posts(
		$args + array(
			'meta_key'   => '_vityaz_legacy_source',
			'meta_value' => $source_key,
		)
	);

	if ( ! $by_source ) {
		return 0;
	}

	$post_id            = (int) $by_source[0];
	$stored_fingerprint = (string) get_post_meta( $post_id, '_vityaz_legacy_fingerprint', true );

	return '' === $stored_fingerprint || hash_equals( $stored_fingerprint, $fingerprint ) ? $post_id : 0;
}

/**
 * Find a manually created post that represents the same legacy row.
 */
function vityaz_migration_find_equivalent_post( string $collection, array $row, array $prepared ): int {
	$title = trim( (string) ( $prepared['post']['post_title'] ?? '' ) );

	if ( '' === $title ) {
		return 0;
	}

	$candidates = get_posts(
		array(
			'post_type'      => $prepared['post']['post_type'],
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'posts_per_page' => 10,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'title'          => $title,
		)
	);

	foreach ( $candidates as $candidate ) {
		if ( vityaz_migration_normalize_name( get_the_title( $candidate ) ) !== vityaz_migration_normalize_name( $title ) ) {
			continue;
		}

		if ( 'events' === $collection && ! empty( $prepared['fields']['event_start'] ) ) {
			$existing = vityaz_migration_parse_datetime( vityaz_get_field( 'event_start', '', $candidate->ID ) );
			$incoming = vityaz_migration_parse_datetime( $prepared['fields']['event_start'] );

			if ( ! $existing || ! $incoming || $existing->format( 'Y-m-d' ) !== $incoming->format( 'Y-m-d' ) ) {
				continue;
			}
		}

		if ( 'news' === $collection && ! empty( $prepared['post']['post_date'] ) ) {
			if ( get_the_date( 'Y-m-d', $candidate ) !== substr( $prepared['post']['post_date'], 0, 10 ) ) {
				continue;
			}
		}

		return (int) $candidate->ID;
	}

	return 0;
}

/**
 * Convert a legacy row to wp_insert_post and ACF values.
 */
function vityaz_migration_prepare_row( string $collection, array $row, int $index ): array|WP_Error {
	$title = trim( (string) ( $row['title'] ?? $row['name'] ?? '' ) );

	if ( '' === $title ) {
		return new WP_Error( 'missing_title', sprintf( 'Строка %d: не указан заголовок или имя.', $index + 1 ) );
	}

	$settings = vityaz_migration_collections()[ $collection ];
	$excerpt  = trim( (string) ( $row['excerpt'] ?? $row['subtitle'] ?? '' ) );
	$post     = array(
		'post_type'    => $settings['post_type'],
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_excerpt' => $excerpt,
		'menu_order'   => in_array( $collection, array( 'students', 'trainers' ), true ) ? $index : 0,
	);
	$fields   = array();

	if ( 'news' === $collection ) {
		$date = vityaz_migration_parse_datetime( $row['date'] ?? '' );

		$fields['news_lead'] = $excerpt;

		if ( ! empty( $row['url'] ) ) {
			$fields['news_source_url'] = esc_url_raw( (string) $row['url'] );
		}

		if ( $date ) {
			$post['post_date']     = $date->format( 'Y-m-d H:i:s' );
			$post['post_date_gmt'] = get_gmt_from_date( $post['post_date'] );
		}
	} elseif ( 'events' === $collection ) {
		$date = vityaz_migration_parse_datetime( $row['date'] ?? '' );

		if ( ! $date ) {
			return new WP_Error( 'missing_event_date', sprintf( 'Строка %d «%s»: не указана корректная дата мероприятия.', $index + 1, $title ) );
		}

		$fields['event_lead']  = $excerpt;
		$fields['event_start'] = $date->format( 'Y-m-d H:i:s' );

		if ( ! empty( $row['url'] ) ) {
			$fields['event_registration_url'] = esc_url_raw( (string) $row['url'] );
		}
	} elseif ( 'students' === $collection ) {
		$fields = array(
			'student_subtitle'      => trim( (string) ( $row['subtitle'] ?? '' ) ),
			'student_qualification' => trim( (string) ( $row['qualification'] ?? '' ) ),
			'student_achievements'  => array_map(
				static fn( string $achievement ): array => array(
					'achievement' => $achievement,
					'year'        => '',
					'result'      => '',
				),
				vityaz_lines( $row['achievements'] ?? '' )
			),
		);
	} elseif ( 'trainers' === $collection ) {
		$legacy_halls = vityaz_lines( $row['halls'] ?? '' );
		$hall_ids     = array();

		foreach ( $legacy_halls as $legacy_hall ) {
			$normalized_hall = vityaz_normalize_location_address( $legacy_hall );

			foreach ( vityaz_get_map_locations() as $location ) {
				$raw_address = (string) ( $location['address'] ?? '' );
				$address     = vityaz_normalize_location_address( $raw_address );
				$matches_address = $address && (
					str_contains( $normalized_hall, $address )
					|| str_contains( $address, $normalized_hall )
					|| vityaz_location_addresses_match( $legacy_hall, $raw_address )
				);

				if ( $normalized_hall && $matches_address ) {
					$hall_ids[] = $location['id'];
					break;
				}
			}
		}

		$fields = array(
			'trainer_position'      => trim( (string) ( $row['subtitle'] ?? '' ) ),
			'trainer_experience'    => trim( (string) ( $row['experience'] ?? '' ) ),
			'trainer_qualification' => trim( (string) ( $row['qualification'] ?? '' ) ),
			'trainer_hall_ids'      => array_values( array_unique( $hall_ids ) ),
			'trainer_halls'         => array_map(
				static fn( string $hall ): array => array( 'hall' => $hall ),
				$legacy_halls
			),
		);
	}

	return array(
		'post'               => $post,
		'fields'             => $fields,
		'image_id'           => vityaz_migration_attachment_id( $row['image'] ?? null ),
		'trainer_references' => 'students' === $collection ? vityaz_migration_split_trainer_references( (string) ( $row['trainer'] ?? '' ) ) : array(),
		'direction_text'     => implode( ' ', array_filter( array( $title, $excerpt, (string) ( $row['qualification'] ?? '' ) ) ) ),
	);
}

/**
 * Split the legacy free-text trainer field.
 */
function vityaz_migration_split_trainer_references( string $value ): array {
	$references = preg_split( '/[,;\r\n]+/u', $value, -1, PREG_SPLIT_NO_EMPTY ) ?: array();

	return array_values( array_filter( array_map( 'trim', $references ) ) );
}

/**
 * Update a local ACF field, falling back to post meta when ACF is unavailable.
 */
function vityaz_migration_update_field( string $context, string $name, mixed $value, int $post_id ): void {
	if ( function_exists( 'update_field' ) ) {
		$field_key = function_exists( 'vityaz_acf_key' ) ? vityaz_acf_key( $context, $name ) : $name;
		update_field( $field_key, $value, $post_id );

		return;
	}

	update_post_meta( $post_id, $name, $value );
}

/**
 * Fill missing values on an equivalent manually created post without overwrites.
 */
function vityaz_migration_enrich_equivalent_post( int $post_id, string $collection, array $prepared ): void {
	if ( ! has_post_thumbnail( $post_id ) && ! empty( $prepared['image_id'] ) ) {
		set_post_thumbnail( $post_id, (int) $prepared['image_id'] );
	}

	if ( '' === trim( (string) get_post_field( 'post_excerpt', $post_id ) ) && ! empty( $prepared['post']['post_excerpt'] ) ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $post_id,
					'post_excerpt' => $prepared['post']['post_excerpt'],
				)
			)
		);
	}

	$context = match ( $collection ) {
		'news'     => 'news_post',
		'events'   => 'event_post',
		'students' => 'student_post',
		default    => 'trainer_post',
	};

	foreach ( $prepared['fields'] as $field_name => $value ) {
		$current = function_exists( 'get_field' ) ? get_field( $field_name, $post_id, false ) : get_post_meta( $post_id, $field_name, true );

		if ( false === $current || null === $current || '' === $current || array() === $current ) {
			vityaz_migration_update_field( $context, $field_name, $value, $post_id );
		}
	}

	$directions = taxonomy_exists( 'vityaz_direction' ) ? wp_get_object_terms( $post_id, 'vityaz_direction', array( 'fields' => 'ids' ) ) : array();

	if ( ! $directions || is_wp_error( $directions ) ) {
		vityaz_migration_assign_directions( $post_id, $prepared['direction_text'] );
	}
}

/**
 * Infer the shared direction taxonomy from legacy text when possible.
 */
function vityaz_migration_assign_directions( int $post_id, string $text ): void {
	$text       = vityaz_migration_normalize_name( $text );
	$directions = array();

	if ( str_contains( $text, 'карат' ) ) {
		$directions[] = 'Каратэ';
	}

	if ( str_contains( $text, 'кудо' ) ) {
		$directions[] = 'Кудо';
	}

	if ( $directions && taxonomy_exists( 'vityaz_direction' ) ) {
		wp_set_object_terms( $post_id, $directions, 'vityaz_direction', false );
	}
}

/**
 * Return all published trainers keyed by ID for relationship matching.
 */
function vityaz_migration_trainer_index(): array {
	$trainers = get_posts(
		array(
			'post_type'      => 'vityaz_trainer',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$index    = array();

	foreach ( $trainers as $trainer ) {
		$index[ $trainer->ID ] = array(
			'title'     => get_the_title( $trainer ),
			'normalized' => vityaz_migration_normalize_name( get_the_title( $trainer ) ),
			'signature'  => vityaz_migration_name_signature( get_the_title( $trainer ) ),
		);
	}

	return $index;
}

/**
 * Match a legacy trainer reference to a unique trainer post.
 */
function vityaz_migration_match_trainer( string $reference, array $trainer_index ): int {
	$normalized = vityaz_migration_normalize_name( $reference );
	$signature  = vityaz_migration_name_signature( $reference );
	$matches    = array();

	foreach ( $trainer_index as $trainer_id => $trainer ) {
		if ( $normalized === $trainer['normalized'] ) {
			return (int) $trainer_id;
		}

		if ( ! $signature['surname'] || $signature['surname'] !== $trainer['signature']['surname'] ) {
			continue;
		}

		if ( $signature['initials'] && ! str_starts_with( $trainer['signature']['initials'], $signature['initials'] ) ) {
			continue;
		}

		$matches[] = (int) $trainer_id;
	}

	return 1 === count( $matches ) ? $matches[0] : 0;
}

/**
 * Merge imported IDs into a front-page Relationship without deleting choices.
 */
function vityaz_migration_update_home_relationship( string $field_name, array $imported_ids, int $front_page_id, int $limit ): array {
	$existing = function_exists( 'get_field' ) ? get_field( $field_name, $front_page_id, false ) : array();
	$existing = is_array( $existing ) ? $existing : array();
	$ids      = array();

	foreach ( array_merge( $existing, $imported_ids ) as $item ) {
		$post_id = $item instanceof WP_Post ? $item->ID : (int) $item;

		if ( $post_id && ! in_array( $post_id, $ids, true ) ) {
			$ids[] = $post_id;
		}
	}

	$ids = array_slice( $ids, 0, $limit );
	vityaz_migration_update_field( 'home_relation', $field_name, $ids, $front_page_id );

	return $ids;
}

/**
 * Import the saved legacy repeaters. This function must only be called manually.
 */
function vityaz_run_legacy_migration(): array|WP_Error {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', 'Недостаточно прав для запуска миграции.' );
	}

	if ( ! vityaz_has_acf_pro() || ! function_exists( 'get_field' ) ) {
		return new WP_Error( 'acf_missing', 'Для миграции требуется активный ACF Pro.' );
	}

	$front_page_id = (int) get_option( 'page_on_front' );

	if ( ! $front_page_id || 'page' !== get_post_type( $front_page_id ) ) {
		return new WP_Error( 'front_page_missing', 'Сначала назначьте статическую главную страницу в «Настройки → Чтение».' );
	}

	$collections = vityaz_migration_collections();
	$order       = array( 'trainers', 'students', 'news', 'events' );
	$report      = array(
		'front_page_id' => $front_page_id,
		'created'       => 0,
		'existing'      => 0,
		'matched'       => 0,
		'invalid'       => 0,
		'failed'        => 0,
		'collections'   => array(),
		'relationships' => array(),
		'warnings'      => array(),
	);
	$collection_ids = array();

	foreach ( $order as $collection ) {
		$settings = $collections[ $collection ];
		$rows     = vityaz_migration_legacy_rows( $settings['legacy_field'], $front_page_id );
		$ids      = array();
		$stats    = array( 'total' => count( $rows ), 'created' => 0, 'existing' => 0, 'matched' => 0, 'invalid' => 0, 'failed' => 0 );

		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				++$stats['invalid'];
				++$report['invalid'];
				continue;
			}

			$prepared = vityaz_migration_prepare_row( $collection, $row, $index );

			if ( is_wp_error( $prepared ) ) {
				++$stats['invalid'];
				++$report['invalid'];
				$report['warnings'][] = $settings['label'] . ': ' . $prepared->get_error_message();
				continue;
			}

			$source_key  = sprintf( '%d:%s:%d', $front_page_id, $settings['legacy_field'], $index );
			$fingerprint = vityaz_migration_fingerprint( $collection, $row );
			$post_id     = vityaz_migration_find_imported_post( $settings['post_type'], $source_key, $fingerprint );

			if ( $post_id ) {
				++$stats['existing'];
				++$report['existing'];

				if ( ! get_post_meta( $post_id, '_vityaz_legacy_migrated_complete', true ) ) {
					vityaz_migration_enrich_equivalent_post( $post_id, $collection, $prepared );
					update_post_meta( $post_id, '_vityaz_legacy_migrated_complete', '1' );
				}

				$ids[ $index ] = $post_id;
				continue;
			}

			$post_id = vityaz_migration_find_equivalent_post( $collection, $row, $prepared );

			if ( $post_id ) {
				update_post_meta( $post_id, '_vityaz_legacy_source', $source_key );
				update_post_meta( $post_id, '_vityaz_legacy_fingerprint', $fingerprint );
				vityaz_migration_enrich_equivalent_post( $post_id, $collection, $prepared );
				update_post_meta( $post_id, '_vityaz_legacy_migrated_complete', '1' );
				++$stats['matched'];
				++$report['matched'];
				$ids[ $index ] = $post_id;
				continue;
			}

			$post_id = wp_insert_post( wp_slash( $prepared['post'] ), true );

			if ( is_wp_error( $post_id ) ) {
				++$stats['failed'];
				++$report['failed'];
				$report['warnings'][] = sprintf( '%s, строка %d: %s', $settings['label'], $index + 1, $post_id->get_error_message() );
				continue;
			}

			$post_id = (int) $post_id;
			update_post_meta( $post_id, '_vityaz_legacy_source', $source_key );
			update_post_meta( $post_id, '_vityaz_legacy_fingerprint', $fingerprint );
			update_post_meta( $post_id, '_vityaz_legacy_front_page', $front_page_id );

			if ( $prepared['image_id'] ) {
				set_post_thumbnail( $post_id, $prepared['image_id'] );
			} elseif ( ! empty( $row['image'] ) ) {
				$report['warnings'][] = sprintf( '%s «%s»: изображение не является вложением WordPress и не перенесено.', $settings['label'], get_the_title( $post_id ) );
			}

			$context = match ( $collection ) {
				'news'     => 'news_post',
				'events'   => 'event_post',
				'students' => 'student_post',
				default    => 'trainer_post',
			};

			foreach ( $prepared['fields'] as $field_name => $value ) {
				vityaz_migration_update_field( $context, $field_name, $value, $post_id );
			}

			vityaz_migration_assign_directions( $post_id, $prepared['direction_text'] );
			update_post_meta( $post_id, '_vityaz_legacy_migrated_complete', '1' );

			++$stats['created'];
			++$report['created'];
			$ids[ $index ] = $post_id;
		}

		$collection_ids[ $collection ] = $ids;
		$report['collections'][ $collection ] = $stats;
	}

	$trainer_index = vityaz_migration_trainer_index();
	$student_rows   = vityaz_migration_legacy_rows( $collections['students']['legacy_field'], $front_page_id );

	foreach ( $student_rows as $index => $row ) {
		$student_id = (int) ( $collection_ids['students'][ $index ] ?? 0 );

		if ( ! $student_id || ! is_array( $row ) ) {
			continue;
		}

		$references = vityaz_migration_split_trainer_references( (string) ( $row['trainer'] ?? '' ) );
		$trainer_ids = array();

		foreach ( $references as $reference ) {
			$trainer_id = vityaz_migration_match_trainer( $reference, $trainer_index );

			if ( $trainer_id ) {
				$trainer_ids[] = $trainer_id;
			} else {
				$report['warnings'][] = sprintf( 'Воспитанник «%s»: тренер «%s» не сопоставлен однозначно.', get_the_title( $student_id ), $reference );
			}
		}

		if ( $trainer_ids ) {
			$existing_trainer_ids = function_exists( 'get_field' ) ? get_field( 'student_trainers', $student_id, false ) : array();
			$existing_trainer_ids = is_array( $existing_trainer_ids ) ? array_map( 'intval', $existing_trainer_ids ) : array();
			$trainer_ids          = array_values( array_unique( array_merge( $existing_trainer_ids, $trainer_ids ) ) );
			vityaz_migration_update_field( 'student_post', 'student_trainers', $trainer_ids, $student_id );
		}
	}

	foreach ( $collections as $collection => $settings ) {
		$report['relationships'][ $settings['home_field'] ] = vityaz_migration_update_home_relationship(
			$settings['home_field'],
			$collection_ids[ $collection ] ?? array(),
			$front_page_id,
			$settings['home_limit']
		);
	}

	update_option( 'vityaz_legacy_migration_last_run', current_time( 'mysql' ), false );

	return $report;
}

/**
 * Build a read-only migration preview.
 */
function vityaz_migration_preview(): array {
	$front_page_id = (int) get_option( 'page_on_front' );
	$preview       = array(
		'front_page_id' => $front_page_id,
		'acf_ready'     => vityaz_has_acf_pro() && function_exists( 'get_field' ),
		'collections'   => array(),
		'warnings'      => array(),
	);

	if ( ! $front_page_id || 'page' !== get_post_type( $front_page_id ) ) {
		$preview['warnings'][] = 'Статическая главная страница не назначена.';
		return $preview;
	}

	foreach ( vityaz_migration_collections() as $collection => $settings ) {
		$rows  = vityaz_migration_legacy_rows( $settings['legacy_field'], $front_page_id );
		$stats = array( 'label' => $settings['label'], 'total' => count( $rows ), 'to_create' => 0, 'existing' => 0, 'matched' => 0, 'invalid' => 0 );

		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				++$stats['invalid'];
				continue;
			}

			$prepared = vityaz_migration_prepare_row( $collection, $row, $index );

			if ( is_wp_error( $prepared ) ) {
				++$stats['invalid'];
				$preview['warnings'][] = $settings['label'] . ': ' . $prepared->get_error_message();
				continue;
			}

			$source_key  = sprintf( '%d:%s:%d', $front_page_id, $settings['legacy_field'], $index );
			$fingerprint = vityaz_migration_fingerprint( $collection, $row );

			if ( vityaz_migration_find_imported_post( $settings['post_type'], $source_key, $fingerprint ) ) {
				++$stats['existing'];
			} elseif ( vityaz_migration_find_equivalent_post( $collection, $row, $prepared ) ) {
				++$stats['matched'];
			} else {
				++$stats['to_create'];
			}
		}

		$preview['collections'][ $collection ] = $stats;
	}

	return $preview;
}

/**
 * Render the dry-run and explicit migration controls.
 */
function vityaz_render_migration_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'У вас нет прав для доступа к этой странице.', 'vityaz' ) );
	}

	$preview = vityaz_migration_preview();
	$result  = vityaz_migration_take_result();
	?>
	<div class="wrap">
		<h1>Миграция материалов «Витязь»</h1>
		<p>Инструмент переносит только фактически сохранённые ACF-повторители главной страницы. Данные из <code>defaults.php</code> не импортируются.</p>
		<p><strong>Перед запуском сделайте резервную копию базы данных.</strong> Новые материалы будут опубликованы сразу. Повторный запуск не создаёт дубли.</p>

		<?php if ( $result ) : ?>
			<div class="notice notice-<?php echo 'success' === ( $result['type'] ?? '' ) ? 'success' : 'error'; ?> inline"><p><?php echo esc_html( $result['message'] ?? '' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $preview['acf_ready'] ) : ?>
			<div class="notice notice-error inline"><p>ACF Pro не активирован: миграция недоступна.</p></div>
		<?php endif; ?>

		<?php foreach ( $preview['warnings'] as $warning ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $warning ); ?></p></div>
		<?php endforeach; ?>

		<h2>Предварительный просмотр (без изменений)</h2>
		<table class="widefat striped" style="max-width: 900px">
			<thead><tr><th>Раздел</th><th>Строк</th><th>Будет создано</th><th>Уже мигрировано</th><th>Совпало с CPT</th><th>Ошибки</th></tr></thead>
			<tbody>
			<?php foreach ( $preview['collections'] as $stats ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $stats['label'] ); ?></strong></td>
					<td><?php echo esc_html( (string) $stats['total'] ); ?></td>
					<td><?php echo esc_html( (string) $stats['to_create'] ); ?></td>
					<td><?php echo esc_html( (string) $stats['existing'] ); ?></td>
					<td><?php echo esc_html( (string) $stats['matched'] ); ?></td>
					<td><?php echo esc_html( (string) $stats['invalid'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( ! empty( $result['report'] ) ) : ?>
			<h2>Результат последнего запуска</h2>
			<p><?php echo esc_html( sprintf( 'Создано: %d; уже существовало: %d; сопоставлено: %d; пропущено: %d; ошибок: %d.', $result['report']['created'], $result['report']['existing'], $result['report']['matched'], $result['report']['invalid'], $result['report']['failed'] ) ); ?></p>
			<?php if ( $result['report']['warnings'] ) : ?><ul class="ul-disc"><?php foreach ( $result['report']['warnings'] as $warning ) : ?><li><?php echo esc_html( $warning ); ?></li><?php endforeach; ?></ul><?php endif; ?>
		<?php endif; ?>

		<?php if ( $preview['acf_ready'] && $preview['front_page_id'] ) : ?>
			<form method="post" style="margin-top: 28px; max-width: 900px">
				<?php wp_nonce_field( 'vityaz_run_content_migration', 'vityaz_migration_nonce' ); ?>
				<label><input type="checkbox" name="vityaz_confirm_migration" value="1" required> Подтверждаю создание и публикацию CPT-записей и обновление выборок на главной.</label>
				<?php submit_button( 'Запустить миграцию', 'primary', 'submit', false, array( 'onclick' => "return confirm('Запустить миграцию сохранённых ACF-данных?')" ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
