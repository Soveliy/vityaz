<?php
/**
 * Lightweight metadata fallback when no SEO plugin is active.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detect common SEO plugins to avoid duplicate metadata.
 */
function vityaz_has_seo_plugin(): bool {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' );
}

/**
 * Build the current page description.
 */
function vityaz_meta_description(): string {
	if ( is_front_page() ) {
		return (string) vityaz_get_field(
			'hero_text',
			'Каратэ и кудо для детей в Курске и Курской области. Бесплатная пробная тренировка в Ассоциации Витязей.'
		);
	}

	if ( is_post_type_archive() ) {
		$post_type = (string) get_query_var( 'post_type' );
		$config    = vityaz_archive_config( $post_type );

		return (string) ( $config['intro'] ?? '' );
	}

	if ( is_singular() ) {
		$post_id   = get_queried_object_id();
		$post_type = get_post_type( $post_id );
		$lead      = match ( $post_type ) {
			'vityaz_news'  => 'news_lead',
			'vityaz_event' => 'event_lead',
			default         => '',
		};

		return vityaz_post_excerpt( $post_id, $lead );
	}

	return '';
}

/**
 * Output description and Open Graph fallback tags.
 */
function vityaz_output_meta_tags(): void {
	if ( vityaz_has_seo_plugin() ) {
		return;
	}

	$description = wp_strip_all_tags( vityaz_meta_description() );

	if ( ! $description ) {
		return;
	}

	$title = wp_get_document_title();
	$url   = is_singular() ? get_permalink() : ( is_post_type_archive() ? get_post_type_archive_link( get_query_var( 'post_type' ) ) : home_url( '/' ) );
	$image = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'full' ) : vityaz_image_url( vityaz_get_option( 'header_logo' ), 'img/logo.png' );

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( is_singular() ? 'article' : 'website' ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( (string) $url ) . '">' . "\n";

	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'vityaz_output_meta_tags', 2 );

/**
 * Build organization data reused by all schema entities.
 */
function vityaz_organization_schema(): array {
	$logo    = vityaz_image_url( vityaz_get_option( 'header_logo' ), 'img/logo.png' );
	$vk_url  = (string) vityaz_get_option( 'vk_url', '' );
	$address = (string) vityaz_get_option( 'address', 'г. Курск' );
	$schema  = array(
		'@type'     => 'SportsOrganization',
		'@id'       => home_url( '/#organization' ),
		'name'      => get_bloginfo( 'name' ),
		'url'       => home_url( '/' ),
		'logo'      => $logo,
		'telephone' => (string) vityaz_get_option( 'phone', '' ),
		'email'     => (string) vityaz_get_option( 'email', '' ),
		'address'   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
			'addressLocality' => 'Курск',
			'addressCountry'  => 'RU',
		),
	);

	if ( $vk_url ) {
		$schema['sameAs'] = array( $vk_url );
	}

	return array_filter( $schema );
}

/**
 * Output JSON-LD for the organization and public content entries.
 */
function vityaz_output_schema(): void {
	if ( vityaz_has_seo_plugin() || ( ! is_front_page() && ! is_singular( array( 'vityaz_news', 'vityaz_event', 'vityaz_student', 'vityaz_trainer' ) ) ) ) {
		return;
	}

	$organization = vityaz_organization_schema();
	$graph        = array( $organization );

	if ( is_singular( array( 'vityaz_news', 'vityaz_event', 'vityaz_student', 'vityaz_trainer' ) ) ) {
		$post_id   = get_queried_object_id();
		$post_type = get_post_type( $post_id );
		$image     = get_the_post_thumbnail_url( $post_id, 'full' );
		$entity    = array(
			'@id'  => get_permalink( $post_id ) . '#primary',
			'url'  => get_permalink( $post_id ),
			'name' => get_the_title( $post_id ),
		);

		if ( 'vityaz_news' === $post_type ) {
			$entity += array(
				'@type'         => 'NewsArticle',
				'headline'      => get_the_title( $post_id ),
				'description'   => vityaz_post_excerpt( $post_id, 'news_lead' ),
				'datePublished' => get_the_date( DATE_W3C, $post_id ),
				'dateModified'  => get_the_modified_date( DATE_W3C, $post_id ),
				'publisher'     => array( '@id' => $organization['@id'] ),
			);
		} elseif ( 'vityaz_event' === $post_type ) {
			$location_name = (string) vityaz_get_field( 'event_location_name', '', $post_id );
			$address       = (string) vityaz_get_field( 'event_address', '', $post_id );
			$entity       += array(
				'@type'       => 'SportsEvent',
				'description' => vityaz_post_excerpt( $post_id, 'event_lead' ),
				'startDate'   => vityaz_format_date( vityaz_get_field( 'event_start', '', $post_id ), 'c' ),
				'endDate'     => vityaz_format_date( vityaz_get_field( 'event_end', '', $post_id ), 'c' ),
				'eventStatus' => 'https://schema.org/EventScheduled',
				'organizer'   => array( '@id' => $organization['@id'] ),
				'location'    => array(
					'@type'   => 'Place',
					'name'    => $location_name,
					'address' => $address,
				),
			);
		} else {
			$is_student = 'vityaz_student' === $post_type;
			$entity    += array(
				'@type'       => 'Person',
				'description' => vityaz_post_excerpt( $post_id ),
				'jobTitle'    => $is_student ? 'Спортсмен' : (string) vityaz_get_field( 'trainer_position', 'Тренер', $post_id ),
				'memberOf'    => array( '@id' => $organization['@id'] ),
			);
		}

		if ( $image ) {
			$entity['image'] = $image;
		}

		$graph[] = array_filter( $entity );
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>' . "\n";
}
add_action( 'wp_head', 'vityaz_output_schema', 3 );
