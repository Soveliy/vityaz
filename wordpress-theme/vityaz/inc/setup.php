<?php
/**
 * WordPress theme setup and assets.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

function vityaz_setup(): void {
	load_theme_textdomain( 'vityaz', VITYAZ_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_image_size( 'vityaz-card', 720, 450, true );
	add_image_size( 'vityaz-person', 720, 1005, true );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'header' => __( 'Меню в шапке', 'vityaz' ),
			'footer' => __( 'Меню в подвале', 'vityaz' ),
		)
	);
}
add_action( 'after_setup_theme', 'vityaz_setup' );

function vityaz_enqueue_assets(): void {
	$css_path = VITYAZ_THEME_DIR . '/assets/css/index.css';
	$js_path  = VITYAZ_THEME_DIR . '/assets/js/index.js';

	wp_enqueue_style(
		'vityaz-app',
		vityaz_asset_uri( 'css/index.css' ),
		array(),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : VITYAZ_THEME_VERSION
	);

	wp_enqueue_script(
		'vityaz-app',
		vityaz_asset_uri( 'js/index.js' ),
		array(),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : VITYAZ_THEME_VERSION,
		true
	);

	$api_key = defined( 'VITYAZ_YANDEX_MAPS_API_KEY' )
		? (string) VITYAZ_YANDEX_MAPS_API_KEY
		: (string) vityaz_get_option( 'yandex_maps_api_key', '' );
	$locations = vityaz_get_map_locations();
	$config    = array(
		'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
		'assetsUrl'         => trailingslashit( vityaz_asset_uri() ),
		'contactFormId'     => function_exists( 'vityaz_cf7_form_id' ) ? vityaz_cf7_form_id() : 0,
		'requestNonce'      => wp_create_nonce( 'vityaz_request' ),
		'yandexMapsApiKey'  => $api_key,
	);

	if ( $locations ) {
		$config['mapLocations'] = $locations;
	}

	wp_add_inline_script(
		'vityaz-app',
		'window.vityazTheme = ' . wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'vityaz_enqueue_assets' );

function vityaz_module_script_tag( string $tag, string $handle ): string {
	if ( 'vityaz-app' !== $handle ) {
		return $tag;
	}

	$tag = (string) preg_replace( '/\s+type=(["\']).*?\1/i', '', $tag );

	return str_replace( '<script ', '<script type="module" ', $tag );
}
add_filter( 'script_loader_tag', 'vityaz_module_script_tag', 10, 2 );

function vityaz_body_classes( array $classes ): array {
	$classes[] = 'page';

	return $classes;
}
add_filter( 'body_class', 'vityaz_body_classes' );

function vityaz_acf_admin_notice(): void {
	if ( vityaz_has_acf_pro() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Для темы Vityaz необходимо установить и активировать ACF Pro.', 'vityaz' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'vityaz_acf_admin_notice' );
