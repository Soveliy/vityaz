<?php
/**
 * Opt-in HTTP mode for temporary development installations.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether the explicitly enabled insecure HTTP mode is active.
 */
function vityaz_insecure_http_enabled(): bool {
	return defined( 'VITYAZ_ALLOW_INSECURE_HTTP' ) && true === VITYAZ_ALLOW_INSECURE_HTTP;
}

/**
 * Replace HTTPS with HTTP without changing the host, port, path or query.
 *
 * @param mixed $url URL-like value received from a WordPress filter.
 * @return mixed
 */
function vityaz_use_http_scheme( $url ) {
	if ( ! vityaz_insecure_http_enabled() || ! is_string( $url ) ) {
		return $url;
	}

	return (string) preg_replace( '#^https://#i', 'http://', $url );
}

/**
 * Check whether a redirect points back to the current WordPress installation.
 */
function vityaz_is_local_redirect( string $location ): bool {
	$target_host = wp_parse_url( $location, PHP_URL_HOST );

	if ( ! is_string( $target_host ) || '' === $target_host ) {
		return true;
	}

	$known_hosts = array();
	$home_host   = wp_parse_url( (string) get_option( 'home' ), PHP_URL_HOST );

	if ( is_string( $home_host ) && '' !== $home_host ) {
		$known_hosts[] = strtolower( $home_host );
	}

	$request_authority = isset( $_SERVER['HTTP_HOST'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) )
		: '';
	$request_host      = $request_authority
		? wp_parse_url( 'http://' . $request_authority, PHP_URL_HOST )
		: '';

	if ( is_string( $request_host ) && '' !== $request_host ) {
		$known_hosts[] = strtolower( $request_host );
	}

	return in_array( strtolower( $target_host ), array_unique( $known_hosts ), true );
}

/**
 * Keep local WordPress redirects on HTTP while development mode is enabled.
 *
 * @param mixed $location Redirect URL or false from redirect_canonical.
 * @return mixed
 */
function vityaz_use_http_for_local_redirect( $location ) {
	if (
		! vityaz_insecure_http_enabled()
		|| ! is_string( $location )
		|| ! str_starts_with( strtolower( $location ), 'https://' )
		|| ! vityaz_is_local_redirect( $location )
	) {
		return $location;
	}

	return vityaz_use_http_scheme( $location );
}

/**
 * Warn administrators so the insecure mode is not left enabled in production.
 */
function vityaz_insecure_http_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Vityaz: включён временный HTTP-режим. Перед запуском сайта удалите VITYAZ_ALLOW_INSECURE_HTTP из wp-config.php и включите HTTPS.', 'vityaz' );
	echo '</p></div>';
}

/**
 * Register the development-only overrides after wp-config.php has been loaded.
 */
function vityaz_register_insecure_http_mode(): void {
	if ( ! vityaz_insecure_http_enabled() ) {
		return;
	}

	// FORCE_SSL_ADMIN may be enabled in an old config or by a plugin.
	force_ssl_admin( false );

	add_filter( 'pre_option_home', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'pre_option_siteurl', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'option_home', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'option_siteurl', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'home_url', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'site_url', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'network_home_url', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'network_site_url', 'vityaz_use_http_scheme', PHP_INT_MAX );
	add_filter( 'redirect_canonical', 'vityaz_use_http_for_local_redirect', PHP_INT_MAX );
	add_filter( 'wp_redirect', 'vityaz_use_http_for_local_redirect', PHP_INT_MAX );
	add_filter( 'secure_auth_cookie', '__return_false', PHP_INT_MAX );
	add_filter( 'secure_logged_in_cookie', '__return_false', PHP_INT_MAX );
	add_action( 'admin_notices', 'vityaz_insecure_http_notice' );
}

vityaz_register_insecure_http_mode();
