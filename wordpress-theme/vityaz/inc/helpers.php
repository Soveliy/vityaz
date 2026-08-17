<?php
/**
 * Template helpers.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the active ACF installation includes Pro-only fields used by theme.
 */
function vityaz_has_acf_pro(): bool {
	return defined( 'ACF_PRO' ) && ACF_PRO && function_exists( 'acf_add_options_page' );
}

function vityaz_asset_uri( string $path = '' ): string {
	return trailingslashit( VITYAZ_THEME_URI . '/assets' ) . ltrim( $path, '/' );
}

function vityaz_get_field( string $name, mixed $default = null, mixed $post_id = false ): mixed {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, $post_id );

	if ( function_exists( 'vityaz_resolve_linked_field_value' ) ) {
		$value = vityaz_resolve_linked_field_value( $name, $value, $post_id );
	}

	if ( false === $value || null === $value || '' === $value || array() === $value ) {
		return $default;
	}

	return $value;
}

function vityaz_get_option( string $name, mixed $default = null ): mixed {
	return vityaz_get_field( $name, $default, 'option' );
}

/**
 * Read an ACF true/false field without replacing an explicitly saved false
 * value with the default used by vityaz_get_field().
 */
function vityaz_field_is_enabled( string $name, bool $default = true, mixed $post_id = false ): bool {
	if ( ! function_exists( 'get_field_object' ) ) {
		return $default;
	}

	$field = get_field_object( $name, $post_id );

	if ( ! is_array( $field ) || ! array_key_exists( 'value', $field ) ) {
		return $default;
	}

	return (bool) $field['value'];
}

function vityaz_image_url( mixed $image, string $fallback = '' ): string {
	if ( is_array( $image ) && ! empty( $image['url'] ) ) {
		return (string) $image['url'];
	}

	if ( is_numeric( $image ) ) {
		$url = wp_get_attachment_image_url( (int) $image, 'full' );

		if ( $url ) {
			return $url;
		}
	}

	if ( is_string( $image ) && '' !== $image ) {
		if ( preg_match( '#^(?:https?:)?//#', $image ) || str_starts_with( $image, '/' ) ) {
			return $image;
		}

		return vityaz_asset_uri( $image );
	}

	return $fallback ? vityaz_asset_uri( $fallback ) : '';
}

function vityaz_image_alt( mixed $image, string $fallback = '' ): string {
	if ( is_array( $image ) && ! empty( $image['alt'] ) ) {
		return (string) $image['alt'];
	}

	if ( is_numeric( $image ) ) {
		$alt = get_post_meta( (int) $image, '_wp_attachment_image_alt', true );

		if ( $alt ) {
			return (string) $alt;
		}
	}

	return $fallback;
}

function vityaz_phone_href( string $phone ): string {
	$digits = preg_replace( '/\D+/', '', $phone );

	if ( str_starts_with( $digits, '8' ) && 11 === strlen( $digits ) ) {
		$digits = '7' . substr( $digits, 1 );
	}

	return $digits ? '+' . $digits : '';
}

function vityaz_lines( mixed $value ): array {
	if ( is_array( $value ) ) {
		return array_values( array_filter( array_map( 'trim', $value ) ) );
	}

	return array_values(
		array_filter(
			array_map( 'trim', preg_split( '/\R/u', (string) $value ) ?: array() )
		)
	);
}

/**
 * Stable groups shared by the home-page hall buttons and map locations.
 */
function vityaz_location_group_choices(): array {
	return array(
		'center'    => 'Центральный округ',
		'northwest' => 'Северо-Западный район',
		'north'     => 'СХА, Победа, Дериглазова',
		'railway'   => 'Железнодорожный округ',
		'seym'      => 'Сеймский округ',
		'volokno'   => 'Волокно',
		'region'    => 'Курская область',
	);
}

/**
 * Read the bundled location dictionary shared with the frontend build.
 */
function vityaz_default_map_locations(): array {
	static $locations;

	if ( is_array( $locations ) ) {
		return $locations;
	}

	$file = VITYAZ_THEME_DIR . '/data/map-locations.json';

	if ( ! is_readable( $file ) ) {
		$locations = array();
		return $locations;
	}

	$decoded   = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$locations = is_array( $decoded ) ? $decoded : array();

	return $locations;
}

/**
 * Normalize an address for non-destructive dictionary matching.
 */
function vityaz_normalize_location_address( string $address ): string {
	$address = remove_accents( wp_strip_all_tags( $address ) );
	$address = function_exists( 'mb_strtolower' ) ? mb_strtolower( $address, 'UTF-8' ) : strtolower( $address );
	$address = str_replace( 'ё', 'е', $address );

	return trim( (string) preg_replace( '/[^\p{L}\p{N}]+/u', '', $address ) );
}

/**
 * Match differently formatted versions of the same postal address.
 */
function vityaz_location_addresses_match( string $first, string $second ): bool {
	$first_normalized  = vityaz_normalize_location_address( $first );
	$second_normalized = vityaz_normalize_location_address( $second );

	if ( ! $first_normalized || ! $second_normalized ) {
		return false;
	}

	if ( str_contains( $first_normalized, $second_normalized ) || str_contains( $second_normalized, $first_normalized ) ) {
		return true;
	}

	$tokenize_words = static function ( string $value ): array {
		$value  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
		$value  = str_replace( 'ё', 'е', $value );
		$tokens = preg_split( '/[^\p{L}]+/u', $value, -1, PREG_SPLIT_NO_EMPTY ) ?: array();
		$ignore = array( 'г', 'город', 'курск', 'курская', 'область', 'район', 'ул', 'улица', 'проспект', 'проезд', 'пр', 'кт', 'дом', 'пос', 'поселок' );

		return array_values(
			array_filter(
				$tokens,
				static fn( string $token ): bool => ! in_array( $token, $ignore, true ) && ( function_exists( 'mb_strlen' ) ? mb_strlen( $token, 'UTF-8' ) : strlen( $token ) ) >= 3
			)
		);
	};
	$tokenize_numbers = static function ( string $value ): array {
		$value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
		$value = (string) preg_replace( '/\s+/u', '', $value );
		preg_match_all( '/\d+\p{L}?/u', $value, $matches );

		return array_values( array_unique( $matches[0] ?? array() ) );
	};

	$first_words   = $tokenize_words( $first );
	$second_words  = $tokenize_words( $second );
	$first_numbers = $tokenize_numbers( $first );
	$second_numbers = $tokenize_numbers( $second );
	$required_words = min( count( $first_words ), count( $second_words ) );

	return $required_words > 0
		&& count( array_intersect( $first_words, $second_words ) ) >= max( 1, $required_words - 1 )
		&& ( ! $first_numbers || ! $second_numbers || ! array_diff( $first_numbers, $second_numbers ) || ! array_diff( $second_numbers, $first_numbers ) );
}

/**
 * Derive a stable group for legacy rows that predate location_group.
 */
function vityaz_location_group( array $row ): string {
	$group   = sanitize_key( (string) ( $row['location_group'] ?? '' ) );
	$choices = vityaz_location_group_choices();

	if ( isset( $choices[ $group ] ) ) {
		return $group;
	}

	if ( 'region' === ( $row['scope'] ?? '' ) ) {
		return 'region';
	}

	$district = trim( (string) ( $row['district'] ?? '' ) );
	$patterns = array(
		'center'    => '/центр/iu',
		'northwest' => '/северо[\s-]*запад/iu',
		'north'     => '/сха|побед|дериглаз/iu',
		'railway'   => '/железнодорож/iu',
		'seym'      => '/сейм/iu',
		'volokno'   => '/волокно/iu',
	);

	foreach ( $patterns as $key => $pattern ) {
		if ( preg_match( $pattern, $district ) ) {
			return $key;
		}
	}

	return '';
}

function vityaz_get_map_locations(): array {
	$rows = function_exists( 'get_field' ) ? get_field( 'map_locations', 'option' ) : false;

	if ( false === $rows || null === $rows ) {
		$rows = get_option( 'vityaz_map_locations_seed_version' ) ? array() : vityaz_default_map_locations();
	}

	if ( ! is_array( $rows ) ) {
		return array();
	}

	$locations = array();

	foreach ( $rows as $index => $row ) {
		$coordinates = isset( $row['coordinates'] ) && is_array( $row['coordinates'] ) ? $row['coordinates'] : array();
		$longitude   = isset( $row['longitude'] ) ? (float) $row['longitude'] : (float) ( $coordinates[0] ?? 0.0 );
		$latitude    = isset( $row['latitude'] ) ? (float) $row['latitude'] : (float) ( $coordinates[1] ?? 0.0 );
		$location_id = trim( (string) ( $row['location_id'] ?? '' ) ) ?: trim( (string) ( $row['id'] ?? '' ) );
		$location_id = $location_id ?: 'location-' . $index;

		if ( ! $longitude || ! $latitude || empty( $row['address'] ) ) {
			continue;
		}

		$locations[] = array(
			'id'          => sanitize_title( $location_id ),
			'name'        => (string) ( $row['name'] ?? '' ),
			'district'    => (string) ( $row['district'] ?? '' ),
			'group'       => vityaz_location_group( $row ),
			'address'     => (string) $row['address'],
			'coordinates' => array( $longitude, $latitude ),
			'disciplines' => array_values( (array) ( $row['disciplines'] ?? array( 'Каратэ' ) ) ),
			'scope'       => in_array( $row['scope'] ?? '', array( 'city', 'region' ), true ) ? $row['scope'] : 'city',
		);
	}

	return $locations;
}

/**
 * Find one normalized location by its stable ACF ID.
 */
function vityaz_get_map_location( string $location_id ): ?array {
	$location_id = sanitize_title( $location_id );

	if ( '' === $location_id ) {
		return null;
	}

	foreach ( vityaz_get_map_locations() as $location ) {
		if ( $location_id === $location['id'] ) {
			return $location;
		}
	}

	return null;
}

/**
 * Resolve a stored ordered list of location IDs to current dictionary rows.
 */
function vityaz_get_map_locations_by_id( mixed $location_ids ): array {
	$location_ids = is_array( $location_ids ) ? $location_ids : array( $location_ids );
	$locations     = array();
	$seen          = array();

	foreach ( $location_ids as $location_id ) {
		$location_id = sanitize_title( (string) $location_id );

		if ( '' === $location_id || isset( $seen[ $location_id ] ) ) {
			continue;
		}

		$location = vityaz_get_map_location( $location_id );

		if ( $location ) {
			$locations[]         = $location;
			$seen[ $location_id ] = true;
		}
	}

	return $locations;
}

/**
 * Build a readable label for selects and trainer cards.
 */
function vityaz_map_location_label( array $location ): string {
	$name    = trim( (string) ( $location['name'] ?? '' ) );
	$address = trim( (string) ( $location['address'] ?? '' ) );

	if ( $name && $address ) {
		return $name . ' — ' . $address;
	}

	return $address ?: $name;
}

/**
 * Return choices for ACF fields that reference the shared location dictionary.
 */
function vityaz_map_location_choices(): array {
	$choices = array();

	foreach ( vityaz_get_map_locations() as $location ) {
		$choices[ $location['id'] ] = vityaz_map_location_label( $location );
	}

	return $choices;
}
