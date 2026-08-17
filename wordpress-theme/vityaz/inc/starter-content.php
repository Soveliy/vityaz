<?php
/**
 * Explicit, idempotent starter-content import from a bundled snapshot.
 *
 * The importer is intentionally separate from the legacy ACF migration. It
 * never runs on theme activation, never matches existing content by title and
 * never updates a record that has already completed this import.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

const VITYAZ_STARTER_SOURCE_META   = '_vityaz_source_id';
const VITYAZ_STARTER_COMPLETE_META = '_vityaz_starter_import_complete';
const VITYAZ_STARTER_FAILED_META   = '_vityaz_starter_import_failed';
const VITYAZ_STARTER_MEDIA_MAX     = 12582912; // 12 MiB.
const VITYAZ_STARTER_LOCK_OPTION   = 'vityaz_starter_content_lock';
const VITYAZ_STARTER_LOCK_TTL      = 3600;
const VITYAZ_STARTER_CA_SHA256     = 'd0d0e65409c5d583357ec05a6b70cab858334437de8b2fb12388f35dd0fbbc51';

/**
 * Return the bundled snapshot path.
 */
function vityaz_starter_seed_path(): string {
	return VITYAZ_THEME_DIR . '/data/content-seed-v1.json';
}

/**
 * Read and validate the bundled JSON without performing network requests.
 */
function vityaz_starter_load_seed(): array|WP_Error {
	$file = vityaz_starter_seed_path();

	if ( ! is_readable( $file ) ) {
		return new WP_Error( 'starter_seed_missing', 'Файл начального наполнения data/content-seed-v1.json не найден.' );
	}

	$size = filesize( $file );

	if ( false !== $size && $size > 5 * MB_IN_BYTES ) {
		return new WP_Error( 'starter_seed_too_large', 'Файл начального наполнения превышает допустимый размер 5 МБ.' );
	}

	$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( false === $raw || '' === trim( $raw ) ) {
		return new WP_Error( 'starter_seed_empty', 'Файл начального наполнения пуст или недоступен для чтения.' );
	}

	$raw = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );

	try {
		$decoded = json_decode( (string) $raw, true, 512, JSON_THROW_ON_ERROR );
	} catch ( JsonException $exception ) {
		return new WP_Error( 'starter_seed_json', 'Некорректный JSON начального наполнения: ' . $exception->getMessage() );
	}

	if ( ! is_array( $decoded ) ) {
		return new WP_Error( 'starter_seed_shape', 'Корневой элемент начального наполнения должен быть объектом.' );
	}

	foreach ( array( 'starter_content', 'content' ) as $wrapper ) {
		if ( isset( $decoded[ $wrapper ] ) && is_array( $decoded[ $wrapper ] ) ) {
			$decoded = array_merge(
				array_intersect_key( $decoded, array_flip( array( 'version', 'schema_version', 'generated_at', 'source' ) ) ),
				$decoded[ $wrapper ]
			);
			break;
		}
	}

	$snapshot = is_array( $decoded['snapshot'] ?? null ) ? $decoded['snapshot'] : array();
	$seed     = array(
		'version'         => sanitize_text_field( (string) ( $decoded['version'] ?? $snapshot['id'] ?? $decoded['schema_version'] ?? '1' ) ),
		'trainers'        => vityaz_starter_list( $decoded['trainers'] ?? $decoded['coaches'] ?? array() ),
		'students'        => vityaz_starter_list( $decoded['students'] ?? $decoded['athletes'] ?? array() ),
		'pages'           => vityaz_starter_list( $decoded['pages'] ?? array() ),
		'gallery'         => vityaz_starter_list( $decoded['gallery'] ?? $decoded['photos'] ?? array() ),
		'contacts'        => is_array( $decoded['contacts'] ?? null ) ? $decoded['contacts'] : array(),
		'home'            => is_array( $decoded['home'] ?? $decoded['home_relationships'] ?? null ) ? ( $decoded['home'] ?? $decoded['home_relationships'] ) : array(),
		'known_conflicts' => vityaz_starter_list( $decoded['known_source_conflicts'] ?? array() ),
	);

	foreach ( array( 'projects' => 'project', 'articles' => 'project', 'combat' => 'combat', 'combat_pages' => 'combat' ) as $collection => $kind ) {
		$items = vityaz_starter_list( $decoded[ $collection ] ?? array() );

		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				$item['kind'] = $item['kind'] ?? $kind;
				$seed['pages'][] = $item;
			}
		}
	}

	if ( isset( $decoded['history'] ) && is_array( $decoded['history'] ) && ! array_is_list( $decoded['history'] ) ) {
		$history         = $decoded['history'];
		$history['kind'] = $history['kind'] ?? 'history';
		$seed['pages'][] = $history;
	}

	return $seed;
}

/**
 * Normalize a possibly associative JSON collection to a list of rows.
 */
function vityaz_starter_list( mixed $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	if ( array_is_list( $value ) ) {
		return array_values( $value );
	}

	$rows = array();

	foreach ( $value as $key => $row ) {
		if ( is_array( $row ) ) {
			if ( empty( $row['id'] ) && empty( $row['source_id'] ) ) {
				$row['id'] = (string) $key;
			}
			$rows[] = $row;
		}
	}

	return $rows;
}

/**
 * Generate a stable, collection-scoped source ID.
 */
function vityaz_starter_source_id( string $collection, array $row ): string {
	$identity = trim( (string) ( $row['source_id'] ?? $row['id'] ?? $row['slug'] ?? '' ) );

	if ( '' === $identity ) {
		$identity = trim( (string) ( $row['source_url'] ?? $row['url'] ?? '' ) );
	}

	if ( '' === $identity ) {
		return '';
	}

	$readable = strtolower( (string) preg_replace( '/[^a-zA-Z0-9._:-]+/', '-', $identity ) );
	$readable = trim( $readable, '-:.' );

	if ( '' === $readable || strlen( $readable ) > 100 ) {
		$readable = hash( 'sha256', $identity );
	}

	return 'official-site:' . sanitize_key( $collection ) . ':' . $readable;
}

/**
 * Return the title/name supplied by a starter row.
 */
function vityaz_starter_title( array $row ): string {
	return sanitize_text_field( (string) ( $row['title'] ?? $row['name'] ?? '' ) );
}

/**
 * Normalize one source image declaration.
 */
function vityaz_starter_image( mixed $image ): array {
	if ( is_string( $image ) ) {
		return array( 'url' => trim( $image ), 'alt' => '', 'caption' => '' );
	}

	if ( ! is_array( $image ) ) {
		return array( 'url' => '', 'alt' => '', 'caption' => '' );
	}

	return array(
		'url'     => trim( (string) ( $image['image_url'] ?? $image['url'] ?? $image['source_url'] ?? $image['src'] ?? '' ) ),
		'alt'     => sanitize_text_field( (string) ( $image['alt'] ?? $image['title'] ?? '' ) ),
		'caption' => sanitize_textarea_field( (string) ( $image['caption'] ?? $image['description'] ?? '' ) ),
	);
}

/**
 * Resolve the image field used by a content row.
 */
function vityaz_starter_record_image( array $row ): array {
	return vityaz_starter_image( $row['image'] ?? $row['image_url'] ?? $row['featured_image'] ?? $row['photo'] ?? '' );
}

/**
 * Build conservative page content from the snapshot's verified summary/facts.
 */
function vityaz_starter_record_content( array $row ): string {
	$content = trim( (string) ( $row['content'] ?? $row['body_html'] ?? $row['body'] ?? '' ) );

	if ( '' !== $content ) {
		return wp_kses_post( $content );
	}

	$html    = '';
	$summary = trim( (string) ( $row['summary'] ?? '' ) );

	if ( '' !== $summary ) {
		$html .= wpautop( esc_html( $summary ) );
	}

	$facts = array_merge(
		vityaz_starter_fact_lines( $row['facts_from_source'] ?? array() ),
		vityaz_starter_fact_lines( $row['facts_from_homepage'] ?? array() )
	);

	if ( $facts ) {
		$html .= '<ul>';

		foreach ( $facts as $fact ) {
			$html .= '<li>' . esc_html( $fact ) . '</li>';
		}

		$html .= '</ul>';
	}

	return wp_kses_post( $html );
}

/**
 * Turn structured verified facts into readable draft-page list items.
 */
function vityaz_starter_fact_lines( mixed $facts ): array {
	if ( ! is_array( $facts ) ) {
		return vityaz_starter_scalar_list( $facts );
	}

	if ( array_is_list( $facts ) ) {
		return vityaz_starter_scalar_list( $facts );
	}

	$labels = array(
		'group_size'                => 'Размер группы',
		'minimum_age'               => 'Минимальный возраст',
		'duration'                  => 'Продолжительность занятий',
		'cost'                      => 'Стоимость',
		'regional_branches'         => 'Региональных отделений',
		'kursk_start_year'          => 'Работа в Курской области с',
		'districts_in_kursk_region' => 'Районов Курской области',
		'pupils'                    => 'Воспитанников',
		'instructors'               => 'Тренеров и инструкторов',
	);
	$lines  = array();

	foreach ( $facts as $key => $value ) {
		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
			continue;
		}

		$label   = $labels[ (string) $key ] ?? ucfirst( str_replace( '_', ' ', (string) $key ) );
		$lines[] = sanitize_text_field( $label . ': ' . $value );
	}

	return $lines;
}

/**
 * Find content imported from one exact source ID, including trashed records.
 */
function vityaz_starter_find_source_post( string $source_id, string|array $post_type ): int {
	if ( '' === $source_id ) {
		return 0;
	}

	$is_attachment = 'attachment' === $post_type;
	$posts         = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => $is_attachment ? array( 'inherit', 'private', 'trash' ) : array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_key'         => VITYAZ_STARTER_SOURCE_META,
			'meta_value'       => $source_id,
		)
	);

	return $posts ? (int) $posts[0] : 0;
}

/**
 * Detect a same-title record without treating it as an imported identity.
 */
function vityaz_starter_find_title_conflict( string $title, string $post_type ): int {
	if ( '' === trim( $title ) ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page'   => 10,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'title'            => $title,
		)
	);

	foreach ( $posts as $post ) {
		if ( vityaz_starter_alias_key( get_the_title( $post ) ) === vityaz_starter_alias_key( $title ) ) {
			return (int) $post->ID;
		}
	}

	return 0;
}

/**
 * Determine whether a post ACF value was ever explicitly saved.
 */
function vityaz_starter_post_field_is_set( int $post_id, string $field_name ): bool {
	return metadata_exists( 'post', $post_id, $field_name ) || metadata_exists( 'post', $post_id, '_' . $field_name );
}

/**
 * Update one ACF value only when it has never been stored.
 */
function vityaz_starter_update_post_field_if_unset( string $context, string $field_name, mixed $value, int $post_id ): bool {
	if ( vityaz_starter_post_field_is_set( $post_id, $field_name ) || ! function_exists( 'update_field' ) ) {
		return false;
	}

	return (bool) update_field( vityaz_acf_key( $context, $field_name ), $value, $post_id );
}

/**
 * Determine whether an ACF option was explicitly stored, including empty text.
 */
function vityaz_starter_option_is_set( string $field_name ): bool {
	return false !== get_option( 'options_' . $field_name, false ) || false !== get_option( '_options_' . $field_name, false );
}

/**
 * Validate an external image URL against the one approved source host.
 */
function vityaz_starter_validate_image_url( string $url ): bool|WP_Error {
	$url   = esc_url_raw( trim( $url ), array( 'https' ) );
	$parts = $url ? wp_parse_url( $url ) : false;

	if ( ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) ) {
		return new WP_Error( 'starter_image_scheme', 'Разрешены только изображения по HTTPS.' );
	}

	if ( 'www.vityazi-kursk.ru' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
		return new WP_Error( 'starter_image_host', 'Изображение находится за пределами разрешённого домена www.vityazi-kursk.ru.' );
	}

	if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) || ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) ) {
		return new WP_Error( 'starter_image_authority', 'Недопустимый адрес изображения.' );
	}

	$path = rawurldecode( (string) ( $parts['path'] ?? '' ) );

	if ( ! preg_match( '/\.(?:jpe?g|png|gif|webp)$/i', $path ) ) {
		return new WP_Error( 'starter_image_extension', 'Адрес изображения не содержит разрешённое расширение JPG, PNG, GIF или WebP.' );
	}

	return true;
}

/**
 * Return the verified CA chain used only when the legacy source omits its ICA.
 */
function vityaz_starter_ca_bundle_path(): string|WP_Error {
	static $result = null;

	if ( null !== $result ) {
		return $result;
	}

	$file = VITYAZ_THEME_DIR . '/data/certificates/vityazi-kursk-ca-chain.pem';

	if ( ! is_readable( $file ) ) {
		$result = new WP_Error( 'starter_ca_missing', 'Не найден файл проверочной цепочки TLS для официального сайта.' );
		return $result;
	}

	$size = filesize( $file );

	if ( false === $size || $size < 1024 || $size > 64 * KB_IN_BYTES ) {
		$result = new WP_Error( 'starter_ca_size', 'Файл проверочной цепочки TLS имеет недопустимый размер.' );
		return $result;
	}

	$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$hash     = false === $contents
		? false
		: hash( 'sha256', str_replace( array( "\r\n", "\r" ), "\n", $contents ) );

	if ( false === $hash || ! hash_equals( VITYAZ_STARTER_CA_SHA256, strtolower( $hash ) ) ) {
		$result = new WP_Error( 'starter_ca_integrity', 'Файл проверочной цепочки TLS повреждён или был изменён.' );
		return $result;
	}

	$result = $file;
	return $result;
}

/**
 * Detect the incomplete-chain error currently returned by the legacy source.
 */
function vityaz_starter_is_ca_chain_error( WP_Error $error ): bool {
	$message = strtolower( implode( ' ', $error->get_error_messages() ) );

	return str_contains( $message, 'curl error 60' )
		|| str_contains( $message, 'unable to get local issuer certificate' )
		|| str_contains( $message, 'unable_to_verify_leaf_signature' );
}

/**
 * Check remote headers before WordPress downloads an image.
 *
 * The source currently omits its intermediate certificate. WordPress first
 * uses its normal CA store and retries with the bundled official GlobalSign
 * chain only for that exact, allowlisted URL and only after a chain error.
 *
 * @return true|string|WP_Error True for the normal CA store, a CA path for the
 *                              scoped fallback, or an error.
 */
function vityaz_starter_preflight_image( string $url ): bool|string|WP_Error {
	$validation = vityaz_starter_validate_image_url( $url );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$args = array(
		'timeout'     => 10,
		'redirection' => 0,
		'user-agent'  => 'Vityaz starter content/' . VITYAZ_THEME_VERSION . '; ' . home_url( '/' ),
		'sslverify'   => true,
	);

	$response  = wp_safe_remote_head( $url, $args );
	$ca_bundle = '';

	if ( is_wp_error( $response ) && vityaz_starter_is_ca_chain_error( $response ) ) {
		$ca_bundle = vityaz_starter_ca_bundle_path();

		if ( is_wp_error( $ca_bundle ) ) {
			return $ca_bundle;
		}

		$args['sslcertificates'] = $ca_bundle;

		$ssl_filter = static function ( mixed $verify, string $request_url ) use ( $url, $ca_bundle ): mixed {
			return $url === $request_url ? $ca_bundle : $verify;
		};

		add_filter( 'https_ssl_verify', $ssl_filter, PHP_INT_MAX, 2 );

		try {
			$response = wp_safe_remote_head( $url, $args );
		} finally {
			remove_filter( 'https_ssl_verify', $ssl_filter, PHP_INT_MAX );
		}
	}

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'starter_image_head', 'Не удалось проверить изображение: ' . $response->get_error_message() );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code ) {
		return new WP_Error( 'starter_image_status', sprintf( 'Источник изображения вернул HTTP %d.', $code ) );
	}

	$content_type = strtolower( trim( (string) wp_remote_retrieve_header( $response, 'content-type' ) ) );
	$content_type = trim( (string) strtok( $content_type, ';' ) );

	if ( ! in_array( $content_type, array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ), true ) ) {
		return new WP_Error( 'starter_image_mime', 'Источник вернул неподдерживаемый MIME-тип изображения.' );
	}

	$content_length = (int) wp_remote_retrieve_header( $response, 'content-length' );

	if ( $content_length > VITYAZ_STARTER_MEDIA_MAX ) {
		return new WP_Error( 'starter_image_size', 'Изображение превышает допустимый размер 12 МБ.' );
	}

	return $ca_bundle ?: true;
}

/**
 * Reuse or sideload an approved source image.
 */
function vityaz_starter_sideload_image( array $image, int $parent_post_id, array &$report ): int|WP_Error {
	$url = trim( (string) ( $image['url'] ?? '' ) );

	if ( '' === $url ) {
		return new WP_Error( 'starter_image_empty', 'Для изображения не указан URL.' );
	}

	$validation = vityaz_starter_validate_image_url( $url );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$source_id    = 'official-site:media:' . hash( 'sha256', $url );
	$attachment_id = vityaz_starter_find_source_post( $source_id, 'attachment' );

	if ( $attachment_id ) {
		if ( 'trash' === get_post_status( $attachment_id ) ) {
			return new WP_Error( 'starter_image_trashed', 'Ранее импортированное изображение находится в корзине медиатеки; восстановите его или удалите окончательно.' );
		}

		$existing_mime = (string) get_post_mime_type( $attachment_id );
		$existing_file = get_attached_file( $attachment_id );
		$existing_size = $existing_file && is_readable( $existing_file ) ? filesize( $existing_file ) : false;

		if (
			! wp_attachment_is_image( $attachment_id )
			|| ! $existing_file
			|| ! is_readable( $existing_file )
			|| ! in_array( $existing_mime, array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ), true )
			|| ( false !== $existing_size && $existing_size > VITYAZ_STARTER_MEDIA_MAX )
		) {
			return new WP_Error( 'starter_image_broken', 'Ранее импортированное изображение отсутствует или повреждено; удалите вложение окончательно и повторите импорт.' );
		}

		++$report['media']['existing'];
		return $attachment_id;
	}

	$preflight = vityaz_starter_preflight_image( $url );

	if ( is_wp_error( $preflight ) ) {
		return $preflight;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$ca_bundle = is_string( $preflight ) ? $preflight : '';

	$http_filter = static function ( array $args, string $request_url ) use ( $url, $ca_bundle ): array {
		if ( $url === $request_url ) {
			$args['timeout']             = 20;
			$args['redirection']         = 0;
			$args['limit_response_size'] = VITYAZ_STARTER_MEDIA_MAX;
			$args['sslverify']           = true;

			if ( $ca_bundle ) {
				$args['sslcertificates'] = $ca_bundle;
			}
		}

		return $args;
	};

	add_filter( 'http_request_args', $http_filter, 10, 2 );

	if ( $ca_bundle ) {
		$ssl_filter = static function ( mixed $verify, string $request_url ) use ( $url, $ca_bundle ): mixed {
			return $url === $request_url ? $ca_bundle : $verify;
		};

		add_filter( 'https_ssl_verify', $ssl_filter, PHP_INT_MAX, 2 );
	}

	try {
		$attachment_id = media_sideload_image(
			$url,
			$parent_post_id,
			(string) ( $image['caption'] ?: $image['alt'] ),
			'id'
		);
	} finally {
		remove_filter( 'http_request_args', $http_filter, 10 );

		if ( isset( $ssl_filter ) ) {
			remove_filter( 'https_ssl_verify', $ssl_filter, PHP_INT_MAX );
		}
	}

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	$attachment_id = (int) $attachment_id;
	$mime          = (string) get_post_mime_type( $attachment_id );
	$file          = get_attached_file( $attachment_id );
	$file_size     = $file && is_readable( $file ) ? filesize( $file ) : false;

	if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ), true ) || ( false !== $file_size && $file_size > VITYAZ_STARTER_MEDIA_MAX ) ) {
		wp_delete_attachment( $attachment_id, true );
		return new WP_Error( 'starter_image_download_invalid', 'Загруженный файл не прошёл проверку MIME-типа или размера.' );
	}

	update_post_meta( $attachment_id, VITYAZ_STARTER_SOURCE_META, $source_id );
	update_post_meta( $attachment_id, '_vityaz_source_url', esc_url_raw( $url ) );

	if ( '' !== (string) ( $image['alt'] ?? '' ) ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $image['alt'] ) );
	}

	if ( '' !== (string) ( $image['caption'] ?? '' ) ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $attachment_id,
					'post_excerpt' => sanitize_textarea_field( (string) $image['caption'] ),
				)
			)
		);
	}

	++$report['media']['created'];

	return $attachment_id;
}

/**
 * Normalize starter achievements to the relevant ACF repeater shape.
 */
function vityaz_starter_achievements( mixed $value, bool $student ): array {
	$items = is_string( $value ) ? vityaz_lines( $value ) : vityaz_starter_list( is_array( $value ) ? $value : array() );
	$rows  = array();

	foreach ( $items as $item ) {
		if ( is_string( $item ) ) {
			$text   = trim( $item );
			$year   = '';
			$result = '';
		} elseif ( is_array( $item ) ) {
			$text   = trim( (string) ( $item['achievement'] ?? $item['title'] ?? $item['text'] ?? '' ) );
			$year   = sanitize_text_field( (string) ( $item['year'] ?? '' ) );
			$result = sanitize_text_field( (string) ( $item['result'] ?? $item['place'] ?? '' ) );
		} else {
			continue;
		}

		if ( '' === $text ) {
			continue;
		}

		$row = array( 'achievement' => sanitize_text_field( $text ), 'year' => $year );

		if ( $student ) {
			$row['result'] = $result;
		}

		$rows[] = $row;
	}

	return $rows;
}

/**
 * Normalize a list of plain values from JSON.
 */
function vityaz_starter_scalar_list( mixed $value ): array {
	if ( is_string( $value ) ) {
		return vityaz_lines( $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map(
				static function ( mixed $item ): string {
					if ( is_array( $item ) ) {
						$item = $item['id'] ?? $item['source_id'] ?? $item['name'] ?? $item['title'] ?? '';
					}

					return is_scalar( $item ) ? trim( (string) $item ) : '';
				},
				$value
			)
		)
	);
}

/**
 * Normalize a content row before preview or import.
 */
function vityaz_starter_normalize_record( string $collection, mixed $raw, int $index ): array|WP_Error {
	if ( ! is_array( $raw ) ) {
		return new WP_Error( 'starter_record_shape', sprintf( 'Строка %d раздела %s должна быть объектом.', $index + 1, $collection ) );
	}

	$source_id = vityaz_starter_source_id( $collection, $raw );
	$title     = vityaz_starter_title( $raw );

	if ( '' === $source_id ) {
		return new WP_Error( 'starter_record_id', sprintf( 'Строка %d раздела %s не содержит id/source_id/source_url.', $index + 1, $collection ) );
	}

	if ( '' === $title ) {
		return new WP_Error( 'starter_record_title', sprintf( 'Строка %d раздела %s не содержит заголовок.', $index + 1, $collection ) );
	}

	$post_type = match ( $collection ) {
		'trainers' => 'vityaz_trainer',
		'students' => 'vityaz_student',
		default    => 'page',
	};
	$content   = vityaz_starter_record_content( $raw );
	$excerpt   = sanitize_textarea_field( (string) ( $raw['excerpt'] ?? $raw['summary'] ?? $raw['subtitle'] ?? $raw['position'] ?? '' ) );

	return array(
		'collection'   => $collection,
		'source_id'    => $source_id,
		'raw_id'       => trim( (string) ( $raw['source_id'] ?? $raw['id'] ?? $raw['slug'] ?? '' ) ),
		'source_url'   => esc_url_raw( (string) ( $raw['source_url'] ?? $raw['url'] ?? '' ) ),
		'post_type'    => $post_type,
		'title'        => $title,
		'content'      => $content,
		'excerpt'      => $excerpt,
		'image'        => vityaz_starter_record_image( $raw ),
		'menu_order'   => isset( $raw['menu_order'] ) ? (int) $raw['menu_order'] : $index,
		'home'         => ! array_key_exists( 'home', $raw ) || (bool) $raw['home'],
		'kind'         => sanitize_key( (string) ( $raw['kind'] ?? 'page' ) ),
		'directions'   => vityaz_starter_scalar_list( $raw['directions'] ?? $raw['disciplines'] ?? array() ),
		'position'     => sanitize_textarea_field( (string) ( $raw['position'] ?? $raw['subtitle'] ?? '' ) ),
		'experience'   => sanitize_text_field( (string) ( $raw['experience'] ?? '' ) ),
		'qualification' => sanitize_textarea_field( (string) ( $raw['qualification'] ?? '' ) ),
		'achievements' => $raw['achievements'] ?? array(),
		'hall_ids'     => vityaz_starter_scalar_list( $raw['hall_ids'] ?? $raw['trainer_hall_ids'] ?? array() ),
		'halls'        => vityaz_starter_scalar_list( $raw['halls'] ?? array() ),
		'trainer_refs' => vityaz_starter_scalar_list( $raw['trainer_ids'] ?? $raw['trainer_refs'] ?? $raw['trainers'] ?? $raw['trainer'] ?? array() ),
		'aliases'      => vityaz_starter_scalar_list( $raw['aliases'] ?? array() ),
	);
}

/**
 * Return normalized rows and their validation errors.
 */
function vityaz_starter_normalized_collection( array $seed, string $collection ): array {
	$records = array();
	$errors  = array();
	$seen    = array();

	foreach ( vityaz_starter_list( $seed[ $collection ] ?? array() ) as $index => $raw ) {
		$record = vityaz_starter_normalize_record( $collection, $raw, $index );

		if ( is_wp_error( $record ) ) {
			$errors[] = $record->get_error_message();
		} elseif ( isset( $seen[ $record['source_id'] ] ) ) {
			$errors[] = sprintf( 'Раздел %s содержит повторяющийся source ID «%s».', $collection, $record['source_id'] );
		} else {
			$seen[ $record['source_id'] ] = true;
			$records[] = $record;
		}
	}

	return array( 'records' => $records, 'errors' => $errors );
}

/**
 * Check whether a source ID is already attached to another supported type.
 */
function vityaz_starter_find_wrong_type_source( string $source_id, string $expected_type ): int {
	$types = array_values( array_diff( array( 'vityaz_trainer', 'vityaz_student', 'page' ), array( $expected_type ) ) );

	return vityaz_starter_find_source_post( $source_id, $types );
}

/**
 * Build a read-only preview of the starter import.
 */
function vityaz_starter_preview(): array {
	$preview = array(
		'ready'         => false,
		'acf_ready'     => vityaz_has_acf_pro() && function_exists( 'update_field' ),
		'front_page_id' => (int) get_option( 'page_on_front' ),
		'version'       => '',
		'collections'   => array(),
		'gallery'       => array( 'total' => 0, 'field_set' => false, 'field_has_value' => false, 'new' => 0, 'existing' => 0, 'invalid' => 0 ),
		'contacts'      => array( 'new' => 0, 'existing' => 0 ),
		'warnings'      => array(),
		'errors'        => array(),
	);
	$seed    = vityaz_starter_load_seed();

	if ( is_wp_error( $seed ) ) {
		$preview['errors'][] = $seed->get_error_message();
		return $preview;
	}

	$preview['version'] = (string) $seed['version'];

	if ( ! empty( $seed['known_conflicts'] ) ) {
		$preview['warnings'][] = sprintf(
			'Snapshot содержит известных противоречий исходного сайта: %d. Перед публикацией проверьте раздел known_source_conflicts в JSON.',
			count( $seed['known_conflicts'] )
		);
	}

	if ( ! $preview['acf_ready'] ) {
		$preview['warnings'][] = 'Для запуска импорта требуется активный ACF Pro.';
	}

	if ( ! $preview['front_page_id'] || 'page' !== get_post_type( $preview['front_page_id'] ) ) {
		$preview['warnings'][] = 'Статическая главная страница не назначена: материалы будут созданы, но выборки главной и галерея не заполнятся.';
	}

	foreach ( array( 'trainers', 'students', 'pages' ) as $collection ) {
		$normalized = vityaz_starter_normalized_collection( $seed, $collection );
		$stats      = array(
			'total'    => count( vityaz_starter_list( $seed[ $collection ] ?? array() ) ),
			'new'      => 0,
			'existing' => 0,
			'conflict' => 0,
			'invalid'  => count( $normalized['errors'] ),
		);

		foreach ( $normalized['errors'] as $error ) {
			$preview['warnings'][] = $error;
		}

		foreach ( $normalized['records'] as $record ) {
			if ( vityaz_starter_find_source_post( $record['source_id'], $record['post_type'] ) ) {
				++$stats['existing'];
			} elseif ( vityaz_starter_find_wrong_type_source( $record['source_id'], $record['post_type'] ) || vityaz_starter_find_title_conflict( $record['title'], $record['post_type'] ) ) {
				++$stats['conflict'];
			} else {
				++$stats['new'];
			}
		}

		$preview['collections'][ $collection ] = $stats;
	}

	$front_page_id                                  = $preview['front_page_id'];
	$preview['gallery']['total']                    = count( vityaz_starter_list( $seed['gallery'] ) );
	$preview['gallery']['field_set']                = $front_page_id ? vityaz_starter_post_field_is_set( $front_page_id, 'gallery' ) : false;
	$preview['gallery']['field_has_value'] = $front_page_id && function_exists( 'get_field' )
		? ! empty( get_field( 'gallery', $front_page_id, false ) )
		: false;

	foreach ( vityaz_starter_list( $seed['gallery'] ) as $item ) {
		$image = vityaz_starter_image( $item );

		if ( '' === $image['url'] || is_wp_error( vityaz_starter_validate_image_url( $image['url'] ) ) ) {
			++$preview['gallery']['invalid'];
			continue;
		}

		$source_id = 'official-site:media:' . hash( 'sha256', $image['url'] );
		$attachment_id = vityaz_starter_find_source_post( $source_id, 'attachment' );

		if ( $attachment_id && 'trash' === get_post_status( $attachment_id ) ) {
			++$preview['gallery']['invalid'];
		} elseif ( $attachment_id ) {
			++$preview['gallery']['existing'];
		} else {
			++$preview['gallery']['new'];
		}
	}

	foreach ( vityaz_starter_contact_values( $seed['contacts'] ) as $field_name => $value ) {
		if ( '' === $value ) {
			continue;
		}

		if ( vityaz_starter_option_is_set( $field_name ) ) {
			++$preview['contacts']['existing'];
		} else {
			++$preview['contacts']['new'];
		}
	}

	$preview['ready'] = $preview['acf_ready'] && ! $preview['errors'];

	return $preview;
}

/**
 * Normalize the allowlisted global contact fields.
 */
function vityaz_starter_contact_values( array $contacts ): array {
	$vk_url = (string) ( $contacts['vk_url'] ?? $contacts['vk'] ?? $contacts['social_url'] ?? '' );

	return array(
		'phone'   => sanitize_text_field( (string) ( $contacts['phone'] ?? $contacts['phone_display'] ?? '' ) ),
		'email'   => sanitize_email( (string) ( $contacts['email'] ?? '' ) ),
		'address' => sanitize_text_field( (string) ( $contacts['address'] ?? '' ) ),
		'vk_url'  => esc_url_raw( $vk_url ),
	);
}

/**
 * Initialize the stable report shape used by the admin UI.
 */
function vityaz_starter_empty_report( string $version ): array {
	return array(
		'version'     => $version,
		'collections' => array(
			'trainers' => array( 'created' => 0, 'resumed' => 0, 'existing' => 0, 'conflicts' => 0, 'invalid' => 0 ),
			'students' => array( 'created' => 0, 'resumed' => 0, 'existing' => 0, 'conflicts' => 0, 'invalid' => 0 ),
			'pages'    => array( 'created' => 0, 'resumed' => 0, 'existing' => 0, 'conflicts' => 0, 'invalid' => 0 ),
		),
		'media'       => array( 'created' => 0, 'existing' => 0, 'failed' => 0 ),
		'contacts'    => array( 'updated' => 0, 'skipped' => 0 ),
		'gallery'     => array( 'updated' => false, 'count' => 0, 'skipped' => false ),
		'home'        => array( 'home_trainers' => 0, 'home_students' => 0 ),
		'warnings'    => array(),
		'errors'      => array(),
	);
}

/**
 * Acquire an atomic, expiring lock for the mutating import pass.
 */
function vityaz_starter_acquire_lock(): string|WP_Error {
	$now            = time();
	$existing       = (string) get_option( VITYAZ_STARTER_LOCK_OPTION, '' );
	$existing_stamp = (int) $existing;

	if ( $existing_stamp && $now - $existing_stamp < VITYAZ_STARTER_LOCK_TTL ) {
		return new WP_Error( 'starter_import_locked', 'Начальное наполнение уже выполняется в другом запросе. Дождитесь его завершения.' );
	}

	if ( $existing ) {
		delete_option( VITYAZ_STARTER_LOCK_OPTION );
	}

	$token = $now . '|' . wp_generate_uuid4();

	if ( ! add_option( VITYAZ_STARTER_LOCK_OPTION, $token, '', 'no' ) ) {
		return new WP_Error( 'starter_import_locked', 'Не удалось получить блокировку импорта. Повторите попытку позже.' );
	}

	return $token;
}

/**
 * Release the starter-content import lock.
 */
function vityaz_starter_release_lock( string $token ): void {
	$current = (string) get_option( VITYAZ_STARTER_LOCK_OPTION, '' );

	if ( $current && hash_equals( $current, $token ) ) {
		delete_option( VITYAZ_STARTER_LOCK_OPTION );
	}
}

/**
 * Store provenance immediately so an interrupted import remains idempotent.
 */
function vityaz_starter_mark_source( int $post_id, array $record, string $version ): void {
	update_post_meta( $post_id, VITYAZ_STARTER_SOURCE_META, $record['source_id'] );
	update_post_meta( $post_id, '_vityaz_source_url', $record['source_url'] );
	update_post_meta( $post_id, '_vityaz_starter_snapshot', $version );
	update_post_meta( $post_id, '_vityaz_starter_kind', $record['kind'] );
}

/**
 * Add source ACF values without touching values saved by an editor.
 */
function vityaz_starter_apply_record_fields( int $post_id, array $record ): bool {
	if ( 'trainers' === $record['collection'] ) {
		$fields = array(
			'trainer_position'      => $record['position'],
			'trainer_experience'    => $record['experience'],
			'trainer_qualification' => $record['qualification'],
			'trainer_hall_ids'      => array_values( array_unique( array_map( 'sanitize_title', $record['hall_ids'] ) ) ),
			'trainer_halls'         => array_map( static fn( string $hall ): array => array( 'hall' => sanitize_text_field( $hall ) ), $record['halls'] ),
			'trainer_achievements'  => vityaz_starter_achievements( $record['achievements'], false ),
		);
		$context = 'trainer_post';
	} else {
		$fields = array(
			'student_subtitle'      => $record['position'],
			'student_qualification' => $record['qualification'],
			'student_achievements'  => vityaz_starter_achievements( $record['achievements'], true ),
		);
		$context = 'student_post';
	}

	$success = true;

	foreach ( $fields as $field_name => $value ) {
		if ( '' === $value || array() === $value ) {
			continue;
		}

		if ( ! vityaz_starter_post_field_is_set( $post_id, $field_name ) && ! vityaz_starter_update_post_field_if_unset( $context, $field_name, $value, $post_id ) ) {
			$success = false;
		}
	}

	return $success;
}

/**
 * Add inferred or explicit directions only when a record has none.
 */
function vityaz_starter_apply_directions( int $post_id, array $record ): bool {
	if ( ! taxonomy_exists( 'vityaz_direction' ) ) {
		return false;
	}

	$current = wp_get_object_terms( $post_id, 'vityaz_direction', array( 'fields' => 'ids' ) );

	if ( is_wp_error( $current ) ) {
		return false;
	}

	if ( $current ) {
		return true;
	}

	$directions = array();

	foreach ( $record['directions'] as $direction ) {
		$normalized = function_exists( 'mb_strtolower' ) ? mb_strtolower( $direction, 'UTF-8' ) : strtolower( $direction );

		if ( str_contains( $normalized, 'карат' ) && ! in_array( 'Каратэ', $directions, true ) ) {
			$directions[] = 'Каратэ';
		}

		if ( str_contains( $normalized, 'кудо' ) && ! in_array( 'Кудо', $directions, true ) ) {
			$directions[] = 'Кудо';
		}
	}

	$haystack   = function_exists( 'mb_strtolower' )
		? mb_strtolower( implode( ' ', array( $record['position'], $record['qualification'], wp_strip_all_tags( $record['content'] ) ) ), 'UTF-8' )
		: strtolower( implode( ' ', array( $record['position'], $record['qualification'], wp_strip_all_tags( $record['content'] ) ) ) );

	if ( str_contains( $haystack, 'карат' ) && ! in_array( 'Каратэ', $directions, true ) ) {
		$directions[] = 'Каратэ';
	}

	if ( str_contains( $haystack, 'кудо' ) && ! in_array( 'Кудо', $directions, true ) ) {
		$directions[] = 'Кудо';
	}

	$directions = array_values( array_filter( array_map( 'sanitize_text_field', $directions ) ) );

	if ( $directions ) {
		return ! is_wp_error( wp_set_object_terms( $post_id, $directions, 'vityaz_direction', false ) );
	}

	return true;
}

/**
 * Import or resume one source record without title-based adoption.
 */
function vityaz_starter_import_record( array $record, string $status, string $version, array &$report ): int {
	$collection = $record['collection'];
	$post_id    = vityaz_starter_find_source_post( $record['source_id'], $record['post_type'] );
	$is_new     = false;

	if ( $post_id ) {
		if ( 'trash' === get_post_status( $post_id ) ) {
			++$report['collections'][ $collection ]['existing'];
			$report['warnings'][] = sprintf( '«%s» уже импортирован, но находится в корзине; запись оставлена без изменений.', $record['title'] );
			return 0;
		}

		if ( get_post_meta( $post_id, VITYAZ_STARTER_COMPLETE_META, true ) ) {
			++$report['collections'][ $collection ]['existing'];
			return $post_id;
		}

		++$report['collections'][ $collection ]['resumed'];
	} else {
		$wrong_type = vityaz_starter_find_wrong_type_source( $record['source_id'], $record['post_type'] );
		$title_post = vityaz_starter_find_title_conflict( $record['title'], $record['post_type'] );

		if ( $wrong_type || $title_post ) {
			++$report['collections'][ $collection ]['conflicts'];
			$report['warnings'][] = sprintf(
				'«%s» пропущен: найдена существующая запись без подходящего source ID%s.',
				$record['title'],
				$title_post ? ' (ID ' . $title_post . ')' : ''
			);
			return 0;
		}

		$post_data = array(
			'post_type'    => $record['post_type'],
			'post_status'  => 'draft',
			'post_title'   => $record['title'],
			'post_content' => $record['content'],
			'post_excerpt' => $record['excerpt'],
			'menu_order'   => in_array( $collection, array( 'trainers', 'students' ), true ) ? $record['menu_order'] : 0,
		);
		$post_id   = wp_insert_post( wp_slash( $post_data ), true );

		if ( is_wp_error( $post_id ) ) {
			$report['errors'][] = sprintf( 'Не удалось создать «%s»: %s', $record['title'], $post_id->get_error_message() );
			return 0;
		}

		$post_id = (int) $post_id;
		$is_new  = true;
		vityaz_starter_mark_source( $post_id, $record, $version );
		++$report['collections'][ $collection ]['created'];
	}

	$record_errors_before = count( $report['errors'] );

	if ( in_array( $collection, array( 'trainers', 'students' ), true ) ) {
		if ( ! vityaz_starter_apply_record_fields( $post_id, $record ) ) {
			$report['errors'][] = sprintf( 'Не удалось сохранить ACF-поля для «%s».', $record['title'] );
		}
		if ( ! vityaz_starter_apply_directions( $post_id, $record ) ) {
			$report['errors'][] = sprintf( 'Не удалось сохранить направление для «%s».', $record['title'] );
		}
	}

	if ( ! has_post_thumbnail( $post_id ) && '' !== $record['image']['url'] ) {
		$image = $record['image'];

		if ( '' === $image['alt'] ) {
			$image['alt'] = $record['title'];
		}

		$attachment_id = vityaz_starter_sideload_image( $image, $post_id, $report );

		if ( is_wp_error( $attachment_id ) ) {
			++$report['media']['failed'];
			$report['errors'][] = sprintf( 'Изображение для «%s»: %s', $record['title'], $attachment_id->get_error_message() );
		} elseif ( ! set_post_thumbnail( $post_id, $attachment_id ) ) {
			$report['errors'][] = sprintf( 'Не удалось назначить изображение для «%s».', $record['title'] );
		}
	}

	if ( 'students' !== $collection && count( $report['errors'] ) === $record_errors_before ) {
		if ( 'trainers' === $collection && 'publish' === $status && 'publish' !== get_post_status( $post_id ) ) {
			$published = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );

			if ( is_wp_error( $published ) ) {
				$report['errors'][] = sprintf( 'Не удалось опубликовать «%s»: %s', $record['title'], $published->get_error_message() );
			}
		}

		if ( count( $report['errors'] ) === $record_errors_before ) {
			update_post_meta( $post_id, VITYAZ_STARTER_COMPLETE_META, '1' );
		}
	}

	if ( count( $report['errors'] ) === $record_errors_before ) {
		delete_post_meta( $post_id, VITYAZ_STARTER_FAILED_META );
	} else {
		update_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, '1' );
	}

	if ( ! $is_new ) {
		vityaz_starter_mark_source( $post_id, $record, $version );
	}

	return $post_id;
}

/**
 * Normalize a manifest alias for internal relationship matching.
 */
function vityaz_starter_alias_key( string $value ): string {
	$value = remove_accents( wp_strip_all_tags( $value ) );
	$value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
	$value = str_replace( 'ё', 'е', $value );

	return trim( (string) preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $value ) );
}

/**
 * Produce a surname/initial signature for manifest-only name matching.
 */
function vityaz_starter_name_signature( string $name ): array {
	$parts = preg_split( '/\s+/u', vityaz_starter_alias_key( $name ), -1, PREG_SPLIT_NO_EMPTY ) ?: array();

	if ( ! $parts ) {
		return array( 'surname' => '', 'initials' => '' );
	}

	$surname  = array_shift( $parts );
	$initials = '';

	foreach ( $parts as $part ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $part, 0, 1, 'UTF-8' ) : substr( $part, 0, 1 );
	}

	return array( 'surname' => $surname, 'initials' => $initials );
}

/**
 * Build a manifest registry of successfully imported trainers.
 */
function vityaz_starter_trainer_registry( array $records, array $post_ids ): array {
	$registry = array();

	foreach ( $records as $record ) {
		$post_id = (int) ( $post_ids[ $record['source_id'] ] ?? 0 );

		if (
			! $post_id
			|| 'trash' === get_post_status( $post_id )
			|| ! get_post_meta( $post_id, VITYAZ_STARTER_COMPLETE_META, true )
			|| get_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, true )
		) {
			continue;
		}

		$aliases = array_values(
			array_unique(
				array_filter(
					array_merge(
						array( $record['source_id'], $record['raw_id'], $record['source_url'], $record['title'] ),
						$record['aliases']
					)
				)
			)
		);

		$registry[] = array(
			'post_id'    => $post_id,
			'source_id'  => $record['source_id'],
			'title'      => $record['title'],
			'aliases'    => $aliases,
			'alias_keys' => array_values( array_unique( array_map( 'vityaz_starter_alias_key', $aliases ) ) ),
			'signature'  => vityaz_starter_name_signature( $record['title'] ),
		);
	}

	return $registry;
}

/**
 * Resolve a student trainer reference only against this snapshot registry.
 */
function vityaz_starter_match_trainer_ref( string $reference, array $registry ): int {
	$key = vityaz_starter_alias_key( $reference );

	foreach ( $registry as $trainer ) {
		if ( in_array( $key, $trainer['alias_keys'], true ) ) {
			return (int) $trainer['post_id'];
		}
	}

	$signature = vityaz_starter_name_signature( $reference );
	$matches   = array();

	foreach ( $registry as $trainer ) {
		if ( ! $signature['surname'] || $signature['surname'] !== $trainer['signature']['surname'] ) {
			continue;
		}

		if ( $signature['initials'] && ! str_starts_with( $trainer['signature']['initials'], $signature['initials'] ) ) {
			continue;
		}

		$matches[] = (int) $trainer['post_id'];
	}

	return 1 === count( array_unique( $matches ) ) ? $matches[0] : 0;
}

/**
 * Fill student/trainer relationships in a second pass without overwrites.
 */
function vityaz_starter_link_students( array $records, array $student_ids, array $registry, string $status, array &$report ): void {
	foreach ( $records as $record ) {
		$post_id = (int) ( $student_ids[ $record['source_id'] ] ?? 0 );

		if (
			! $post_id
			|| 'trash' === get_post_status( $post_id )
			|| get_post_meta( $post_id, VITYAZ_STARTER_COMPLETE_META, true )
			|| get_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, true )
		) {
			continue;
		}

		$unresolved  = array();
		$trainer_ids = array();

		if ( $record['trainer_refs'] && ! vityaz_starter_post_field_is_set( $post_id, 'student_trainers' ) ) {
			foreach ( $record['trainer_refs'] as $reference ) {
				$trainer_id = vityaz_starter_match_trainer_ref( $reference, $registry );

				if ( $trainer_id ) {
					$trainer_ids[] = $trainer_id;
				} else {
					$unresolved[] = $reference;
				}
			}

			if (
				$trainer_ids
				&& ! $unresolved
				&& ! vityaz_starter_update_post_field_if_unset(
					'student_post',
					'student_trainers',
					array_values( array_unique( $trainer_ids ) ),
					$post_id
				)
			) {
				$report['errors'][] = sprintf( 'Не удалось сохранить тренеров для воспитанника «%s».', $record['title'] );
				update_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, '1' );
				continue;
			}
		}

		if ( $unresolved ) {
			$report['errors'][] = sprintf(
				'Воспитанник «%s»: не сопоставлены тренеры из snapshot: %s.',
				$record['title'],
				implode( ', ', array_map( 'sanitize_text_field', $unresolved ) )
			);
			update_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, '1' );
			continue;
		}

		if ( '' !== $record['image']['url'] && ! has_post_thumbnail( $post_id ) ) {
			update_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, '1' );
			continue;
		}

		if ( 'publish' === $status && 'publish' !== get_post_status( $post_id ) ) {
			$published = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );

			if ( is_wp_error( $published ) ) {
				$report['errors'][] = sprintf( 'Не удалось опубликовать «%s»: %s', $record['title'], $published->get_error_message() );
				update_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, '1' );
				continue;
			}
		}

		delete_post_meta( $post_id, VITYAZ_STARTER_FAILED_META );
		update_post_meta( $post_id, VITYAZ_STARTER_COMPLETE_META, '1' );
	}
}

/**
 * Resolve an ordered subset requested by the seed home configuration.
 */
function vityaz_starter_home_ids( array $seed, string $collection, array $records, array $post_ids, int $limit ): array {
	$home_key = 'trainers' === $collection ? 'home_trainers' : 'home_students';
	$refs     = vityaz_starter_scalar_list( $seed['home'][ $collection ] ?? $seed['home'][ $home_key ] ?? array() );
	$selected = array();

	if ( ! $refs && 'trainers' === $collection ) {
		$refs = array(
			'emelyanov-dmitrij-olegovich',
			'smetanin-vladimir-valerevich',
			'aspidov-igor-olegovich',
			'velichkin-nikita-valerevich',
		);
	}

	if ( $refs ) {
		foreach ( $refs as $reference ) {
			$reference_key = vityaz_starter_alias_key( $reference );

			foreach ( $records as $record ) {
				$aliases = array_filter( array_merge( array( $record['source_id'], $record['raw_id'], $record['source_url'] ), $record['aliases'] ) );

				if ( ! in_array( $reference_key, array_map( 'vityaz_starter_alias_key', $aliases ), true ) ) {
					continue;
				}

				$post_id = (int) ( $post_ids[ $record['source_id'] ] ?? 0 );

				if (
					$post_id
					&& 'publish' === get_post_status( $post_id )
					&& get_post_meta( $post_id, VITYAZ_STARTER_COMPLETE_META, true )
					&& ! get_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, true )
					&& ! in_array( $post_id, $selected, true )
				) {
					$selected[] = $post_id;
				}
				break;
			}
		}
	} else {
		foreach ( $records as $record ) {
			$post_id = (int) ( $post_ids[ $record['source_id'] ] ?? 0 );

			if (
				$record['home']
				&& $post_id
				&& 'publish' === get_post_status( $post_id )
				&& get_post_meta( $post_id, VITYAZ_STARTER_COMPLETE_META, true )
				&& ! get_post_meta( $post_id, VITYAZ_STARTER_FAILED_META, true )
			) {
				$selected[] = $post_id;
			}
		}
	}

	return array_slice( array_values( array_unique( $selected ) ), 0, $limit );
}

/**
 * Fill one home relationship only when an editor has never saved it.
 */
function vityaz_starter_append_home_relationship( int $front_page_id, string $field_name, array $imported_ids, int $limit, array &$report ): int {
	$existing = function_exists( 'get_field' ) ? get_field( $field_name, $front_page_id, false ) : array();
	$existing = is_array( $existing ) ? $existing : array();
	$existing_ids = array();

	foreach ( $existing as $item ) {
		$post_id = $item instanceof WP_Post ? $item->ID : (int) $item;

		if ( $post_id ) {
			$existing_ids[] = $post_id;
		}
	}

	if ( vityaz_starter_post_field_is_set( $front_page_id, $field_name ) ) {
		return count( array_values( array_unique( $existing_ids ) ) );
	}

	$ids = array();

	foreach ( $imported_ids as $item ) {
		$post_id = $item instanceof WP_Post ? $item->ID : (int) $item;

		if ( $post_id && ! in_array( $post_id, $ids, true ) ) {
			$ids[] = $post_id;
		}
	}

	$ids = array_slice( $ids, 0, $limit );

	if ( $ids && ! update_field( vityaz_acf_key( 'home_relation', $field_name ), $ids, $front_page_id ) ) {
		$report['errors'][] = sprintf( 'Не удалось заполнить выборку главной «%s».', $field_name );
		return 0;
	}

	return count( $ids );
}

/**
 * Import allowlisted contacts only when an option has never been stored.
 */
function vityaz_starter_import_contacts( array $contacts, array &$report ): void {
	foreach ( vityaz_starter_contact_values( $contacts ) as $field_name => $value ) {
		if ( '' === $value || vityaz_starter_option_is_set( $field_name ) ) {
			++$report['contacts']['skipped'];
			continue;
		}

		if ( update_field( vityaz_acf_key( 'options', $field_name ), $value, 'option' ) ) {
			++$report['contacts']['updated'];
		} else {
			$report['errors'][] = sprintf( 'Не удалось сохранить контактное поле «%s».', $field_name );
		}
	}
}

/**
 * Fill the home gallery without overwriting an existing non-empty selection.
 */
function vityaz_starter_import_gallery( array $items, int $front_page_id, array &$report, bool $fill_saved_empty = false ): void {
	if ( ! $front_page_id || 'page' !== get_post_type( $front_page_id ) ) {
		$report['gallery']['skipped'] = true;
		return;
	}

	$field_is_set  = vityaz_starter_post_field_is_set( $front_page_id, 'gallery' );
	$current_value = $field_is_set && function_exists( 'get_field' ) ? get_field( 'gallery', $front_page_id, false ) : false;

	if ( $field_is_set && ( ! $fill_saved_empty || ! empty( $current_value ) ) ) {
		$report['gallery']['skipped'] = true;

		if ( empty( $current_value ) && ! $fill_saved_empty ) {
			$report['warnings'][] = 'Галерея главной сохранена пустой и оставлена без изменений по настройке запуска.';
		}

		return;
	}

	$attachment_ids = array();
	$valid_images   = array();
	$source_items   = array_slice( vityaz_starter_list( $items ), 0, 12 );
	$expected_count = count( $source_items );

	foreach ( $source_items as $index => $item ) {
		$image = vityaz_starter_image( $item );

		if ( '' === $image['alt'] ) {
			$image['alt'] = sprintf( 'Фотогалерея Ассоциации Витязей, фото %d', $index + 1 );
		}

		$validation = '' === $image['url'] ? new WP_Error( 'starter_image_empty', 'Для изображения не указан URL.' ) : vityaz_starter_validate_image_url( $image['url'] );

		if ( is_wp_error( $validation ) ) {
			++$report['media']['failed'];
			$report['errors'][] = sprintf( 'Фотогалерея, изображение %d: %s', $index + 1, $validation->get_error_message() );
			continue;
		}

		$valid_images[] = array( 'index' => $index, 'image' => $image );
	}

	foreach ( $valid_images as $item ) {
		$index = (int) $item['index'];
		$image = $item['image'];

		$attachment_id = vityaz_starter_sideload_image( $image, $front_page_id, $report );

		if ( is_wp_error( $attachment_id ) ) {
			++$report['media']['failed'];
			$report['errors'][] = sprintf( 'Фотогалерея, изображение %d: %s', $index + 1, $attachment_id->get_error_message() );
			continue;
		}

		$attachment_ids[] = (int) $attachment_id;
	}

	if ( $expected_count && count( $valid_images ) === $expected_count && count( $attachment_ids ) === $expected_count ) {
		$gallery_ids = array_values( array_unique( $attachment_ids ) );

		if ( update_field( vityaz_acf_key( 'home', 'gallery' ), $gallery_ids, $front_page_id ) ) {
			$report['gallery']['updated'] = true;
			$report['gallery']['count']   = count( $gallery_ids );
		} else {
			$report['errors'][] = 'Не удалось сохранить галерею главной страницы.';
		}
	}
}

/**
 * Run the explicit starter import.
 */
function vityaz_run_starter_import( bool $publish_people = true, bool $fill_saved_empty_gallery = false ): array|WP_Error {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'starter_forbidden', 'Недостаточно прав для начального наполнения.' );
	}

	if ( ! vityaz_has_acf_pro() || ! function_exists( 'update_field' ) ) {
		return new WP_Error( 'starter_acf_missing', 'Для начального наполнения требуется активный ACF Pro.' );
	}

	$seed = vityaz_starter_load_seed();

	if ( is_wp_error( $seed ) ) {
		return $seed;
	}

	$lock_token = vityaz_starter_acquire_lock();

	if ( is_wp_error( $lock_token ) ) {
		return $lock_token;
	}

	try {
		$report       = vityaz_starter_empty_report( (string) $seed['version'] );
		$status       = $publish_people ? 'publish' : 'draft';
		$records      = array();
		$imported_ids = array( 'trainers' => array(), 'students' => array(), 'pages' => array() );

		foreach ( array( 'trainers', 'students', 'pages' ) as $collection ) {
			$normalized             = vityaz_starter_normalized_collection( $seed, $collection );
			$records[ $collection ] = $normalized['records'];
			$report['collections'][ $collection ]['invalid'] += count( $normalized['errors'] );
			$report['warnings'] = array_merge( $report['warnings'], $normalized['errors'] );

			foreach ( $normalized['records'] as $record ) {
				$post_id = vityaz_starter_import_record( $record, $status, (string) $seed['version'], $report );

				if ( $post_id ) {
					$imported_ids[ $collection ][ $record['source_id'] ] = $post_id;
				}
			}
		}

		$registry = vityaz_starter_trainer_registry( $records['trainers'], $imported_ids['trainers'] );
		vityaz_starter_link_students( $records['students'], $imported_ids['students'], $registry, $status, $report );
		vityaz_starter_import_contacts( $seed['contacts'], $report );

		$front_page_id = (int) get_option( 'page_on_front' );

		if ( $front_page_id && 'page' === get_post_type( $front_page_id ) ) {
			$trainer_home_ids = vityaz_starter_home_ids( $seed, 'trainers', $records['trainers'], $imported_ids['trainers'], 4 );
			$student_home_ids = vityaz_starter_home_ids( $seed, 'students', $records['students'], $imported_ids['students'], 8 );

			$report['home']['home_trainers'] = vityaz_starter_append_home_relationship( $front_page_id, 'home_trainers', $trainer_home_ids, 4, $report );
			$report['home']['home_students'] = vityaz_starter_append_home_relationship( $front_page_id, 'home_students', $student_home_ids, 8, $report );
		} else {
			$report['warnings'][] = 'Главная страница не назначена: relationship-поля не обновлены.';
		}

		vityaz_starter_import_gallery( $seed['gallery'], $front_page_id, $report, $fill_saved_empty_gallery );
		update_option( 'vityaz_starter_content_last_run', current_time( 'mysql' ), false );

		return $report;
	} finally {
		vityaz_starter_release_lock( $lock_token );
	}
}

/**
 * Register the protected starter-content screen.
 */
function vityaz_register_starter_content_page(): void {
	add_management_page(
		'Начальное наполнение «Витязь»',
		'Начальное наполнение «Витязь»',
		'manage_options',
		'vityaz-starter-content',
		'vityaz_render_starter_content_page'
	);
}
add_action( 'admin_menu', 'vityaz_register_starter_content_page' );

/**
 * Keep one import result across the POST/redirect/GET cycle.
 */
function vityaz_starter_store_result( array $result ): void {
	set_transient( 'vityaz_starter_result_' . get_current_user_id(), $result, 10 * MINUTE_IN_SECONDS );
}

/**
 * Return and remove the current administrator's import result.
 */
function vityaz_starter_take_result(): array {
	$key    = 'vityaz_starter_result_' . get_current_user_id();
	$result = get_transient( $key );

	delete_transient( $key );

	return is_array( $result ) ? $result : array();
}

/**
 * Process only an explicit confirmed request from the starter-content screen.
 */
function vityaz_handle_starter_content_request(): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		return;
	}

	$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$action = isset( $_POST['vityaz_starter_action'] ) ? sanitize_key( wp_unslash( $_POST['vityaz_starter_action'] ) ) : '';

	if ( 'vityaz-starter-content' !== $page || 'import' !== $action ) {
		return;
	}

	check_admin_referer( 'vityaz_run_starter_content', 'vityaz_starter_nonce' );

	if ( empty( $_POST['vityaz_confirm_starter'] ) ) {
		vityaz_starter_store_result(
			array(
				'type'    => 'error',
				'message' => 'Импорт не запущен: подтвердите создание материалов.',
			)
		);
	} else {
		$result = vityaz_run_starter_import(
			! empty( $_POST['vityaz_publish_people'] ),
			! empty( $_POST['vityaz_fill_empty_gallery'] )
		);
		$notice = is_wp_error( $result )
			? array( 'type' => 'error', 'message' => $result->get_error_message() )
			: array(
				'type'    => ! empty( $result['errors'] ) ? 'warning' : 'success',
				'message' => ! empty( $result['errors'] ) ? 'Импорт завершён частично. Проверьте ошибки ниже и повторите запуск.' : 'Начальное наполнение завершено.',
				'report'  => $result,
			);

		vityaz_starter_store_result( $notice );
	}

	wp_safe_redirect( admin_url( 'tools.php?page=vityaz-starter-content&starter-result=1' ) );
	exit;
}
add_action( 'admin_init', 'vityaz_handle_starter_content_request' );

/**
 * Render an import report in a compact, escaped form.
 */
function vityaz_render_starter_report( array $report ): void {
	$labels = array( 'trainers' => 'Тренеры', 'students' => 'Воспитанники', 'pages' => 'Черновики страниц' );
	?>
	<h2>Результат импорта</h2>
	<table class="widefat striped" style="max-width: 900px">
		<thead><tr><th>Раздел</th><th>Создано</th><th>Продолжено</th><th>Уже было</th><th>Конфликты</th><th>Некорректно</th></tr></thead>
		<tbody>
		<?php foreach ( $labels as $key => $label ) : ?>
			<?php $stats = $report['collections'][ $key ] ?? array(); ?>
			<tr>
				<td><?php echo esc_html( $label ); ?></td>
				<td><?php echo esc_html( (string) ( $stats['created'] ?? 0 ) ); ?></td>
				<td><?php echo esc_html( (string) ( $stats['resumed'] ?? 0 ) ); ?></td>
				<td><?php echo esc_html( (string) ( $stats['existing'] ?? 0 ) ); ?></td>
				<td><?php echo esc_html( (string) ( $stats['conflicts'] ?? 0 ) ); ?></td>
				<td><?php echo esc_html( (string) ( $stats['invalid'] ?? 0 ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p>
		<?php
		echo esc_html(
			sprintf(
				'Медиа: создано %1$d, использовано повторно %2$d, ошибок %3$d. Контакты: заполнено %4$d, сохранено без изменений %5$d. Галерея: %6$s (%7$d).',
				(int) ( $report['media']['created'] ?? 0 ),
				(int) ( $report['media']['existing'] ?? 0 ),
				(int) ( $report['media']['failed'] ?? 0 ),
				(int) ( $report['contacts']['updated'] ?? 0 ),
				(int) ( $report['contacts']['skipped'] ?? 0 ),
				! empty( $report['gallery']['updated'] ) ? 'заполнена' : 'не изменена',
				(int) ( $report['gallery']['count'] ?? 0 )
			)
		);
		?>
	</p>
	<?php foreach ( array( 'warnings' => 'Предупреждения', 'errors' => 'Ошибки' ) as $key => $heading ) : ?>
		<?php if ( ! empty( $report[ $key ] ) ) : ?>
			<h3><?php echo esc_html( $heading ); ?></h3>
			<ul class="ul-disc">
				<?php foreach ( $report[ $key ] as $message ) : ?>
					<li><?php echo esc_html( (string) $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php
}

/**
 * Render the preview and explicit import controls.
 */
function vityaz_render_starter_content_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостаточно прав для просмотра этой страницы.', 'vityaz' ) );
	}

	$preview = vityaz_starter_preview();
	$result  = vityaz_starter_take_result();
	$labels  = array( 'trainers' => 'Тренеры', 'students' => 'Воспитанники', 'pages' => 'Проекты, направления и история' );
	$notice_type = in_array( $result['type'] ?? '', array( 'success', 'warning', 'error' ), true ) ? $result['type'] : 'success';
	$gallery_field_state = ! empty( $preview['gallery']['field_has_value'] )
		? 'уже заполнено'
		: ( ! empty( $preview['gallery']['field_set'] ) ? 'сохранено пустым' : 'не задано' );
	?>
	<div class="wrap">
		<h1>Начальное наполнение «Витязь»</h1>
		<p>Инструмент читает локальный снимок официального сайта и запускается только вручную. Он не удаляет материалы, не заменяет сохранённые ACF-поля и не считает совпадение заголовка основанием для изменения записи.</p>
		<p><strong>Перед запуском сделайте резервную копию базы и подтвердите право на повторное использование фотографий, особенно изображений несовершеннолетних.</strong></p>

		<?php if ( $result ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible"><p><?php echo esc_html( (string) ( $result['message'] ?? '' ) ); ?></p></div>
			<?php if ( ! empty( $result['report'] ) && is_array( $result['report'] ) ) : ?>
				<?php vityaz_render_starter_report( $result['report'] ); ?>
			<?php endif; ?>
		<?php endif; ?>

		<h2>Предварительный просмотр</h2>
		<p>Версия snapshot: <code><?php echo esc_html( $preview['version'] ?: 'не определена' ); ?></code>. Просмотр не обращается к удалённым изображениям и не меняет базу.</p>
		<table class="widefat striped" style="max-width: 900px">
			<thead><tr><th>Раздел</th><th>Всего</th><th>Будет создано</th><th>Уже импортировано</th><th>Конфликты</th><th>Некорректно</th></tr></thead>
			<tbody>
			<?php foreach ( $labels as $key => $label ) : ?>
				<?php $stats = $preview['collections'][ $key ] ?? array(); ?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td><?php echo esc_html( (string) ( $stats['total'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( (string) ( $stats['new'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( (string) ( $stats['existing'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( (string) ( $stats['conflict'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( (string) ( $stats['invalid'] ?? 0 ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<?php
			echo esc_html(
				sprintf(
					'Галерея: %1$d изображений (%2$d новых, %3$d уже загружено, %4$d некорректно), поле %5$s. Контакты: %6$d будут заполнены, %7$d уже сохранены.',
					(int) $preview['gallery']['total'],
					(int) $preview['gallery']['new'],
					(int) $preview['gallery']['existing'],
					(int) $preview['gallery']['invalid'],
					$gallery_field_state,
					(int) $preview['contacts']['new'],
					(int) $preview['contacts']['existing']
				)
			);
			?>
		</p>

		<?php foreach ( array( 'warnings' => 'Предупреждения', 'errors' => 'Ошибки snapshot' ) as $key => $heading ) : ?>
			<?php if ( ! empty( $preview[ $key ] ) ) : ?>
				<h3><?php echo esc_html( $heading ); ?></h3>
				<ul class="ul-disc">
					<?php foreach ( $preview[ $key ] as $message ) : ?>
						<li><?php echo esc_html( (string) $message ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endforeach; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=vityaz-starter-content' ) ); ?>">
			<?php wp_nonce_field( 'vityaz_run_starter_content', 'vityaz_starter_nonce' ); ?>
			<input type="hidden" name="vityaz_starter_action" value="import">
			<p><label><input type="checkbox" name="vityaz_publish_people" value="1" checked> Сразу публиковать тренеров и воспитанников. Проекты, направления и история всегда создаются черновиками.</label></p>
			<p><label><input type="checkbox" name="vityaz_fill_empty_gallery" value="1" checked> Заполнить галерею из snapshot, если её поле уже было сохранено пустым. Непустая галерея никогда не заменяется.</label></p>
			<p><label><input type="checkbox" name="vityaz_confirm_starter" value="1" required> Подтверждаю создание отсутствующих записей и загрузку изображений с официального сайта.</label></p>
			<?php submit_button( 'Запустить начальное наполнение', 'primary', 'submit', true, $preview['ready'] ? array() : array( 'disabled' => 'disabled' ) ); ?>
		</form>
	</div>
	<?php
}
